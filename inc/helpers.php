<?php
declare(strict_types=1);
require_once __DIR__ . '/record-aliases.php';

function bl_is_wordpress(): bool
{
    return defined('ABSPATH') && function_exists('home_url');
}

/** WordPress hands titles back as HTML (4x4 becomes 4&#215;4, quotes turn curly); the theme escapes them itself, so take plain text. */
function bl_plain_title(string $title): string
{
    return html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function bl_asset(string $path): string
{
    $path = ltrim($path, '/');
    $base = bl_is_wordpress() ? get_template_directory_uri() . '/assets/' : '/assets/';
    return $base . $path . (str_starts_with($path, 'images/') && !str_starts_with($path, 'images/icons/') ? bl_image_version($path) : '');
}

/* Theme images are served with a one-year cache under fixed names, so a redrawn image kept showing the old picture to returning visitors. Their URLs carry a short hash of the file's content: it changes only when the image does, not on every deploy. Site icons keep their plain addresses. */
function bl_image_version(string $path): string
{
    static $versions = [];
    if (isset($versions[$path])) return $versions[$path];
    $file = __DIR__ . '/../assets/' . $path;
    if (!is_file($file)) return $versions[$path] = '';
    $key = md5($path . '|' . filemtime($file) . '|' . filesize($file));
    $hash = function_exists('wp_cache_get') ? wp_cache_get($key, 'bl_image_version') : false;
    if (!is_string($hash)) {
        $hash = (string) hash_file('crc32b', $file);
        if (function_exists('wp_cache_set')) wp_cache_set($key, $hash, 'bl_image_version', 86400);
    }
    return $versions[$path] = '?ver=' . $hash;
}

/* Stylesheets and scripts are served with a one-year cache, so their URLs carry the file's modification time to make browsers fetch each deployed change. */
function bl_asset_versioned(string $path): string
{
    $file = __DIR__ . '/../assets/' . ltrim($path, '/');
    return bl_asset($path) . (is_file($file) ? '?ver=' . filemtime($file) : '');
}

/*
 * A site picture (a theme image or a media-library upload) resized to $width pixels wide by WordPress.com's image CDN,
 * which also serves it as WebP or AVIF where the browser takes them. A card or thumbnail then downloads a picture its own
 * size rather than the full photograph. The address is returned unchanged off WordPress, on a site served on its own
 * port (the CDN cannot reach it), for a picture that is not a
 * file on this site, one already no wider than $width, and one that carries Content Credentials (C2PA): the CDN signs
 * every copy of those again, and the signature makes a 300-pixel copy weigh a megabyte.
 */
function bl_image_resized(string $url, int $width): string
{
    $facts = bl_image_facts($url);
    if (!$facts || $facts['c2pa'] || $facts['width'] <= $width) return $url;
    $parts = parse_url($url);
    // ssl=1 has the CDN fetch the original over HTTPS; a picture served over plain HTTP (or a site address that is) is fetched that way.
    $https = ($parts['scheme'] ?? parse_url(home_url(), PHP_URL_SCHEME)) === 'https';
    return 'https://i0.wp.com/' . bl_image_cdn_host(home_url()) . $parts['path'] . '?' . (isset($parts['query']) ? $parts['query'] . '&' : '') . 'w=' . $width . ($https ? '&ssl=1' : '');
}

/*
 * The site's host as the CDN addresses it; empty where the CDN cannot fetch from it: a site on its own port (the CDN
 * addresses a host alone), and one on a local or private name (localhost, an IP address, .local, .test and the like).
 * The bl_image_cdn filter turns the CDN off for a site that is not public, or on for a public host this misjudges.
 */
function bl_image_cdn_host(string $home): string
{
    $host = strtolower((string) parse_url($home, PHP_URL_HOST));
    if ($host === '' || parse_url($home, PHP_URL_PORT)) return '';
    $public = str_contains($host, '.') && !filter_var(trim($host, '[]'), FILTER_VALIDATE_IP)
        && !preg_match('/\.(local|localhost|localdomain|test|example|invalid|internal|lan|home\.arpa)$/', $host);
    if (function_exists('apply_filters')) $public = (bool) apply_filters('bl_image_cdn', $public, $host);
    return $public ? $host : '';
}

/* srcset for a site picture at the given widths, its full size last; empty when it cannot be resized (see bl_image_resized). */
function bl_image_srcset(string $url, array $widths): string
{
    $facts = bl_image_facts($url);
    if (!$facts || $facts['c2pa']) return '';
    $candidates = [];
    foreach ($widths as $width) if ($width < $facts['width']) $candidates[] = bl_image_resized($url, $width) . ' ' . $width . 'w';
    return $candidates ? implode(', ', [...$candidates, $url . ' ' . $facts['width'] . 'w']) : '';
}

/* The width and Content Credentials of a picture on this site, read from its file once and remembered while it is unchanged; null for anything else. */
function bl_image_facts(string $url): ?array
{
    static $known = [];
    if (!bl_is_wordpress() || $url === '' || !function_exists('wp_upload_dir') || !function_exists('get_transient')) return null;
    if (bl_image_cdn_host(home_url()) === '') return null;
    if (array_key_exists($url, $known)) return $known[$url];
    $path = (string) parse_url($url, PHP_URL_PATH);
    $host = parse_url($url, PHP_URL_HOST);
    // Only the site's own host, as the CDN addresses it: no other host, port or credentials, which a CDN address would drop.
    if (($host && strtolower($host) !== bl_image_cdn_host(home_url())) || parse_url($url, PHP_URL_PORT) || parse_url($url, PHP_URL_USER)) return $known[$url] = null;
    $uploads = wp_upload_dir();
    $roots = [(string) parse_url($uploads['baseurl'], PHP_URL_PATH) => $uploads['basedir'], (string) parse_url(get_template_directory_uri(), PHP_URL_PATH) => get_template_directory()];
    $file = '';
    foreach ($roots as $prefix => $dir) if ($prefix !== '' && str_starts_with($path, $prefix . '/')) $file = $dir . substr(rawurldecode($path), strlen($prefix));
    if ($file === '' || str_contains($file, '..') || !is_file($file)) return $known[$url] = null;
    $key = md5($file . '|' . filemtime($file) . '|' . filesize($file));
    $facts = get_transient('bl_image_' . $key);
    if (!is_array($facts)) {
        $size = @getimagesize($file);
        $facts = ['width' => (int) ($size[0] ?? 0), 'c2pa' => bl_image_has_c2pa($file)];
        set_transient('bl_image_' . $key, $facts, MONTH_IN_SECONDS);
    }
    return $known[$url] = $facts['width'] ? $facts : null;
}

/* Whether a picture file carries Content Credentials: a C2PA chunk in a WebP, a JUMBF "c2pa" box elsewhere (JPEG, PNG). */
function bl_image_has_c2pa(string $file): bool
{
    $bytes = (string) file_get_contents($file);
    if (str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP') {
        for ($at = 12; $at + 8 <= strlen($bytes);) {
            if (substr($bytes, $at, 4) === 'C2PA') return true;
            $length = unpack('V', substr($bytes, $at + 4, 4))[1];
            $at += 8 + $length + ($length & 1);
        }
        return false;
    }
    return str_contains($bytes, 'c2pa');
}

/* Maps every module URL to its versioned URL, so modules main.js imports by relative path are refreshed with it. */
function bl_script_import_map(): string
{
    $imports = [];
    foreach (glob(__DIR__ . '/../assets/js/*.js') ?: [] as $file) {
        $imports[bl_asset('js/' . basename($file))] = bl_asset_versioned('js/' . basename($file));
    }
    return (string) json_encode(['imports' => $imports], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
}

function bl_url(string $path = '/'): string
{
    return bl_is_wordpress() ? home_url('/' . ltrim($path, '/')) : '/' . ltrim($path, '/');
}

function bl_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * A title as element text: escaped, and a one-letter word (A, a, I) held to the word after it with a no-break space,
 * so a line never ends on it ("A closer look. A / stronger connection."). Display only: the stored title is unchanged,
 * and attributes (alt, title) and structured data keep using bl_escape() or their own encoding.
 */
function bl_title_text(string $text): string
{
    return (string) preg_replace('/(?<![\p{L}\p{N}\p{M}\'’&#-])([AaI]) (?=\S)/u', '$1&nbsp;', bl_escape($text));
}

function bl_icon(string $name, string $class = ''): string
{
    return '<img class="bl-icon ' . bl_escape($class) . '" src="' . bl_escape(bl_asset('icons/' . $name . '.svg')) . '" alt="" width="24" height="24" aria-hidden="true">';
}

function bl_query(string $key, string $default = ''): string
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? trim(substr($_GET[$key], 0, 200)) : $default;
}

function bl_read_data(string $file): array
{
    $data = json_decode((string) file_get_contents(__DIR__ . '/data/' . $file . '.json'), true);
    return is_array($data) ? $data : [];
}

function bl_products(): array
{
    if (bl_is_wordpress()) {
        return bl_wp_products();
    }
    $products = bl_read_data('products');
    foreach ($products as &$product) {
        $product['image'] = $product['image'] ? bl_asset($product['image']) : '';
        $product['excerpt'] = bl_product_excerpt($product);
        $product['gallery_paths'] = $product['gallery'] ?? [];
        $product['gallery'] = array_map('bl_product_media', $product['gallery'] ?? []);
    }
    unset($product);
    return $products;
}

/** A dumper's capacity figure: its hopper, or the older "Bucket capacity" name where a record still uses that. Empty values do not count. */
function bl_dumper_capacity(array $specs): string
{
    foreach (['Hopper capacity', 'Bucket capacity'] as $key) {
        if (trim((string) ($specs[$key] ?? '')) !== '') return trim((string) $specs[$key]);
    }
    return '';
}

/**
 * A dumper's single capacity is its hopper, whichever name the record gives it: the older "Bucket capacity" moves to
 * "Hopper capacity" (same position) unless the record also lists a hopper, in which case it is a front bucket and stays.
 */
function bl_normalize_dumper_capacity(array $specs): array
{
    if (trim((string) ($specs['Bucket capacity'] ?? '')) === '' || trim((string) ($specs['Hopper capacity'] ?? '')) !== '') return $specs;
    $normalized = [];
    foreach ($specs as $key => $value) {
        if ($key === 'Hopper capacity') continue; // an empty hopper row gives way to the figure that moves in
        $normalized[$key === 'Bucket capacity' ? 'Hopper capacity' : $key] = $value;
    }
    return $normalized;
}

/** Up to three headline figures, in the order buyers compare them; short labels suit the catalogue cards. */
function bl_key_specs(array $product, bool $short = false): array
{
    $labels = ['Rated load' => 'Rated load', 'Drum capacity' => 'Drum', 'Bucket capacity' => 'Bucket', 'Hopper capacity' => 'Hopper', 'Engine power' => 'Power', 'Machine weight' => 'Weight'];
    $specs = [];
    foreach ($labels as $key => $shortLabel) {
        if (!empty($product['specs'][$key])) $specs[$short ? $shortLabel : $key] = $product['specs'][$key];
        if (count($specs) === 3) break;
    }
    return $specs;
}

/** The retired "electric version" engine option, on its own; an engine described with an electric starter is not it. */
function bl_is_retired_electric_option(string $option): bool
{
    return (bool) preg_match('/^electric(\s+versions?)?$/i', trim($option));
}

/** Engine options without the retired "electric version" choice, which products imported from the old seed data still store. */
function bl_without_retired_engine_options(array $specs): array
{
    if (!isset($specs['Engine options']) || stripos((string) $specs['Engine options'], 'electric') === false) return $specs;
    $options = array_filter(array_map('trim', explode(' / ', (string) $specs['Engine options'])), static fn($option) => $option !== '' && !bl_is_retired_electric_option($option));
    $specs['Engine options'] = implode(' / ', $options);
    return $specs;
}

/**
 * Engine brands across products, in order of first mention: "Kubota D1105" and "Kubota" count once, as do "Briggs & Stratton
 * Vanguard" and "Briggs & Stratton" (model codes, the words holding a digit, are dropped; a name that extends an earlier
 * one is that brand's product line). A legacy "electric version" option, still stored on sites seeded before it was dropped, is skipped.
 */
function bl_engine_brands(array $products): array
{
    $names = [];
    foreach ($products as $p) foreach (array_filter(array_map('trim', explode(' / ', $p['specs']['Engine options'] ?? ''))) as $option) {
        if (bl_is_retired_electric_option($option)) continue;
        $name = trim((string) preg_replace('/\s*\S*\d\S*/u', '', $option));
        if ($name !== '') $names[] = $name;
    }
    $brands = [];
    foreach ($names as $name) $brands[$name] = true;
    $brands = array_keys($brands);
    $shortest = $brands;
    usort($shortest, static fn($a, $b) => strlen($a) <=> strlen($b));
    $canonical = [];
    foreach ($shortest as $name) {
        $base = $name;
        foreach ($canonical as $known) if (stripos($name, $known . ' ') === 0) { $base = $known; break; }
        $canonical[$name] = $base;
    }
    return array_values(array_unique(array_map(static fn($name) => $canonical[$name], $brands)));
}

/** An indefinite article for a figure as it is read aloud: "an 18.6 kW", "an 8 kW", "an 11 kW", "a 7.6 kW". */
function bl_indefinite_article(string $figure): string
{
    $digits = preg_replace('/[^\d.]/', '', $figure);
    $whole = (string) (int) strtok($digits === '' ? '0' : $digits, '.');
    if ($digits === '' || $digits[0] === '.') return 'a';
    if ($whole[0] === '8') return 'an';
    // 11 and 18 ("eleven", "eighteen") and 11,000 / 18,000…; any group of 1-2 digits leading the number in thousands.
    $lead = strlen($whole) % 3 === 0 ? substr($whole, 0, 3) : substr($whole, 0, strlen($whole) % 3);
    return in_array($lead, ['11', '18'], true) ? 'an' : 'a';
}

/** "a 200 kg rated load, a 0.15 m³ bucket and a 7.6 kW engine", from whichever of those figures are published. */
function bl_spec_phrase(array $product): string
{
    $parts = [];
    // A dumper's single capacity is its hopper (the FJZ30's front bucket is not what it counts); a record that gives both keeps its bucket a bucket.
    $capacity = ($product['category'] ?? '') === 'mini-dumpers' && empty($product['specs']['Hopper capacity']) ? 'hopper' : 'bucket';
    foreach (['Rated load' => 'rated load', 'Drum capacity' => 'mixing drum', 'Bucket capacity' => $capacity, 'Hopper capacity' => 'hopper', 'Engine power' => 'engine'] as $key => $noun) {
        if (!empty($product['specs'][$key])) $parts[] = bl_indefinite_article($product['specs'][$key]) . ' ' . $product['specs'][$key] . ' ' . $noun;
    }
    if (count($parts) < 2) return implode('', $parts);
    return implode(', ', array_slice($parts, 0, -1)) . ' and ' . end($parts);
}

/**
 * Summary built from the key figures. The import stores one in each product's excerpt, so an excerpt that is still
 * exactly that wording is treated as generated and rebuilt from the current specs instead of showing old numbers.
 */
function bl_product_excerpt(array $product): string
{
    $phrase = bl_spec_phrase($product);
    if ($phrase !== '') return $product['name'] . ' with ' . $phrase . '. Explore published specifications and discuss your application with BOB-LIFT.';
    return 'Explore the BOB-LIFT ' . $product['model'] . ' ' . ($product['category'] === 'mini-dumpers' ? 'mini dumper' : 'mini skid steer loader') . '. View original product photography and discuss the configuration for your next project.';
}

/** Only the exact generated wording counts: one of the product's names, figures such as "a 1,500 kg rated load", then the stock close. */
function bl_is_generated_product_excerpt(string $excerpt, array $names): bool
{
    $names = implode('|', array_map(static fn($name) => preg_quote($name, '/'), array_filter(array_unique($names), 'strlen')));
    $figure = 'an? [\\d.,]+ ?[^\\s,]* (?:rated load|mixing drum|bucket|hopper|engine)';
    return ($names !== '' && preg_match('/^(?:' . $names . ') with ' . $figure . '(?:, ' . $figure . ')*(?: and ' . $figure . ')?\\. Explore published specifications and discuss your application with BOB-LIFT\\.$/u', $excerpt))
        || (bool) preg_match('/^Explore the BOB-LIFT [^.]+ (?:mini dumper|mini skid steer loader)\\. View original product photography and discuss the configuration for your next project\\.$/', $excerpt);
}

/**
 * Loader names read BL-{running gear}{size class}-{operator position}, e.g. BL-T360-H: T tracked / S wheeled,
 * H hand (stand-on) / C cabin (seated), as set by BOB-LIFT on 2026-09-24. Other models return null.
 */
/** Page title for a product: its name, plus the model when the name does not already carry it, so a search result
 * reads "FY30 3 Ton Wheeled Mini Dumper | BOB-LIFT" rather than repeating "| FY30" before the brand. */
function bl_product_page_title(array $product): string
{
    $name = (string) $product['name'];
    $model = (string) ($product['model'] ?? '');
    return $model === '' || stripos($name, $model) !== false ? $name : $name . ' | ' . $model;
}

function bl_model_parts(string $model): ?array
{
    // Mini dumpers: FC / FY / FJZ then the rated load in hundreds of kilograms (and -2 / -4 on the FY20), or the two tracked names.
    if (preg_match('/^(FC|FY|FJZ)-?(\d+)(?:-([24]))?$/', $model, $m)) {
        $parts = [[$m[1], ['FC' => 'Front-tip', 'FY' => 'Hydraulic tipping', 'FJZ' => 'Self-loading'][$m[1]]], [$m[2], number_format((int) $m[2] * 100) . ' kg load']];
        if (($m[3] ?? '') !== '') $parts[] = ['-' . $m[3], $m[3] === '2' ? 'Two-wheel drive' : 'Four-wheel drive'];
        return $parts;
    }
    if (preg_match('/^BL-T(\d+)-(HD|HJ)$/', $model, $m)) return [['T', 'Tracked'], [$m[1], $m[2] === 'HD' ? 'Rated load, kg' : 'Drum, litres'], [$m[2], $m[2] === 'HD' ? 'Dumping' : 'Jiao · mixer']];
    if (!preg_match('/^BL-([TS])(\d+)-([HC])$/', $model, $m)) return null;
    return [
        [$m[1], $m[1] === 'T' ? 'Tracked' : 'Wheeled'],
        [$m[2], 'Size class'],
        [$m[3], $m[3] === 'H' ? 'Hand · stand-on' : 'Cabin · seated'],
    ];
}

/**
 * Catalogue filters for a family page, or for the whole range when $category is '': group key => label and options.
 * The keys are the query parameters; each product's own values come from bl_product_facets() with the same scope.
 */
function bl_catalog_filters(string $category): array
{
    return bl_editable($category === 'skid-steer-loaders' ? 'loaders' : 'dumpers')['catalog_filters'];
}

/** A product's value for each catalogue filter. Load bands follow the page: kilograms for loaders, tonnes elsewhere. */
function bl_product_facets(array $product, string $category): array
{
    $loader = $product['category'] === 'skid-steer-loaders';
    $gear = strtolower($product['specs']['Running gear'] ?? '');
    $station = strtolower($product['specs']['Operator station'] ?? '');
    $load = (int) $product['load'];
    $bands = $category === 'skid-steer-loaders' ? [250, 400] : [1000, 2000];
    return [
        // Every tracked model says so; the wheeled site dumpers publish no running-gear row at all.
        'gear' => str_contains($gear, 'track') ? 'tracked' : (str_contains($gear, 'tyre') || str_contains($gear, 'tire') || str_contains($gear, 'wheel') || !$loader ? 'wheeled' : ''),
        'operator' => str_contains($station, 'stand') ? 'stand-on' : (str_contains($station, 'seat') ? 'seated' : ''),
        // Wheeled dumpers publish "4×2" or "4×4"; the tracked models and the loaders publish none.
        'drive' => preg_match('/\b4\s*[×x]\s*([24])\b/iu', (string) ($product['specs']['Drive'] ?? ''), $drive) ? '4x' . $drive[1] : '',
        'load' => !$load ? '' : ($load <= $bands[0] ? 'light' : ($load <= $bands[1] ? 'mid' : 'heavy')),
    ];
}

/** Text the catalogue search looks through: name, model, the model without its T / S letter as older literature writes it, and any former code. */
function bl_product_search_text(array $product): string
{
    return strtolower(implode(' ', [$product['name'], $product['model'], preg_replace('/^BL-[TS]/', 'BL-', $product['model']), ...(BL_FORMER_MODELS[$product['model']] ?? [])]));
}

/** Specification rows grouped for the detail table; labels outside the known groups fall under "Details". */
function bl_spec_groups(array $specs): array
{
    $specs = array_slice($specs, 0, BL_CONTENT_LIMITS['specs'], true);
    $groups = [
        'Performance' => ['Rated load', 'Lifting capacity', 'Breakout force', 'Bucket capacity', 'Hopper capacity', 'Drum capacity', 'Travel speed', 'Max travel speed', 'Hydraulic pressure', 'Hydraulic pumps', 'Gradeability', 'Tipping', 'Tipping angle', 'Machine weight'],
        'Engine' => ['Engine options', 'Engine model', 'Engine power', 'Engine type', 'Fuel', 'Fuel tank', 'Starting', 'Emission standard', 'Operating voltage'],
        'Dimensions' => ['Working height', 'Max dumping height', 'Height with arms raised', 'Length with bucket', 'Length without bucket', 'Machine width', 'Bucket width', 'Overall height', 'Standard dimensions', 'Dimensions with footplate', 'Dimensions with roof / cab', 'Hopper size', 'Drum length', 'Drum opening diameter', 'Loading bucket length', 'Loading bucket opening width', 'Wheelbase', 'Minimum ground clearance', 'Ground clearance'],
        'Build' => ['Running gear', 'Drive', 'Brakes', 'Operator station', 'Controls', 'Travel control', 'Body material', 'Bucket material'],
    ];
    $result = [];
    foreach ($groups as $group => $labels) {
        foreach ($labels as $label) if (isset($specs[$label])) $result[$group][$label] = $specs[$label];
    }
    foreach ($specs as $label => $value) {
        if (!array_filter($result, static fn($rows) => array_key_exists($label, $rows))) $result['Details'][$label] = $value;
    }
    return $result;
}

/**
 * Splits the spec groups into side-by-side columns whose heights come out as even as possible, so a short group does
 * not leave a gap under it. Heights are counted in rows: a heading is 0.8 of a row and the space between two groups
 * 0.9, and a row whose label and value will not fit on one line at that column width counts twice. Every way of
 * sharing the groups out is tried, keeping reading order inside a column; the most columns win among layouts that
 * end within a row of each other, otherwise the most even layout does.
 *
 * @param array<string, array<string, string>> $groups
 * @return list<list<string>> group names per column, the first group in the first column
 */
function bl_spec_columns(array $groups, int $max = 4): array
{
    $names = array_keys($groups);
    $max = max(1, min($max, count($names)));
    $best = null;
    for ($count = $max; $count >= min(2, $max); $count--) { // one column only when there is one group
        $fits = [4 => 34, 3 => 44][$count] ?? 70; // characters of label and value that fit on one line
        $heights = array_map(static fn($rows) => 0.8 + array_sum(array_map(static fn($label, $value) => strlen($label . $value) > $fits ? 2 : 1, array_keys($rows), $rows)), array_values($groups));
        $layout = null;
        for ($code = 0, $total = $count ** count($names); $code < $total; $code++) {
            $columns = array_fill(0, $count, []);
            $height = array_fill(0, $count, -0.9);
            for ($i = 0, $rest = $code; $i < count($names); $i++, $rest = intdiv($rest, $count)) {
                $columns[$rest % $count][] = $names[$i];
                $height[$rest % $count] += $heights[$i] + 0.9;
            }
            if (in_array([], $columns, true) || $columns[0][0] !== $names[0]) continue;
            $spread = max($height) - min($height);
            if ($layout === null || $spread < $layout[0] - 0.01) $layout = [$spread, $columns];
        }
        if ($layout === null) continue;
        if ($best === null || $layout[0] < $best[0] - 0.01) $best = $layout;
        if ($layout[0] <= 1) break;
    }
    usort($best[1], static fn($a, $b) => array_search($a[0], $names, true) <=> array_search($b[0], $names, true));
    return $best[1];
}

function bl_articles(): array
{
    if (bl_is_wordpress()) {
        return bl_wp_articles();
    }
    $articles = bl_read_data('news');
    foreach ($articles as &$article) {
        $article['image'] = bl_asset($article['image']);
        if (!empty($article['poster']['image'])) $article['poster']['image'] = bl_asset($article['poster']['image']);
    }
    unset($article);
    return $articles;
}

/**
 * A news picture's width over height, from its WordPress attachment or, for pictures shipped with the theme, from the
 * file; null when it cannot be read (the list then shows the picture whole, never cropped through its lettering).
 */
function bl_news_image_ratio(array $article): ?float
{
    $size = null;
    $id = (int) ($article['image_id'] ?? 0);
    // A featured image is shown at 'large' (which a site may hard-crop), so that rendition is measured.
    if ($id && function_exists('wp_get_attachment_image_src') && ($src = wp_get_attachment_image_src($id, 'large'))) {
        if (!empty($src[1]) && !empty($src[2])) $size = [(int) $src[1], (int) $src[2]];
    }
    // Any other upload is measured as the very file its URL names: the original or one of its sizes.
    if (!$id && function_exists('attachment_url_to_postid') && str_contains((string) $article['image'], '/wp-content/uploads/')
        && ($upload = (int) attachment_url_to_postid($article['image'])) && ($meta = wp_get_attachment_metadata($upload))) {
        $name = basename((string) parse_url((string) $article['image'], PHP_URL_PATH));
        foreach (array_merge([['file' => basename((string) ($meta['file'] ?? '')), 'width' => $meta['width'] ?? 0, 'height' => $meta['height'] ?? 0]], $meta['sizes'] ?? []) as $rendition) {
            if (($rendition['file'] ?? '') === $name) { $size = [(int) $rendition['width'], (int) $rendition['height']]; break; }
        }
    }
    $file = $size ? false : strstr((string) parse_url((string) $article['image'], PHP_URL_PATH), '/assets/');
    if ($file && ($info = @getimagesize(dirname(__DIR__) . $file))) $size = $info;
    return $size && !empty($size[0]) && !empty($size[1]) ? $size[0] / $size[1] : null;
}

/** "15–19 October 2026", or "30 October – 2 November 2026" across months. */
function bl_event_dates(array $event): string
{
    [$start, $end] = [strtotime($event['start']), strtotime($event['end'] ?: $event['start'])];
    if ($start === $end) return date('j F Y', $start);
    if (date('Y-m', $start) === date('Y-m', $end)) return date('j', $start) . '–' . date('j F Y', $end);
    return date('j F', $start) . ' – ' . date('j F Y', $end);
}

/** Where an event stands today: 'upcoming' with the days to go, 'live', or 'past'. Dates are the event's local days. */
/** A real calendar date written YYYY-MM-DD (2026-13-15 and 2026-02-30 are not). */
function bl_is_date(string $value): bool
{
    return (bool) preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m) && checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
}

function bl_event_status(array $event, ?string $today = null): array
{
    $today = new DateTimeImmutable($today ?? 'today', new DateTimeZone('Asia/Shanghai'));
    $today = $today->setTime(0, 0);
    $start = new DateTimeImmutable($event['start'], new DateTimeZone('Asia/Shanghai'));
    $end = new DateTimeImmutable($event['end'] ?: $event['start'], new DateTimeZone('Asia/Shanghai'));
    if ($today < $start) {
        $days = (int) $today->diff($start)->days;
        return ['state' => 'upcoming', 'label' => $days === 1 ? 'Opens tomorrow' : 'Opens in ' . $days . ' days'];
    }
    if ($today <= $end) return ['state' => 'live', 'label' => 'On now'];
    return ['state' => 'past', 'label' => 'Held ' . bl_event_dates($event)];
}

/** Codes the dumpers carried before the 2026 brochure and technical sheet; search still finds a model by them. */
const BL_FORMER_MODELS = ['FC10' => ['FC10-L'], 'FY15' => ['FY15-L'], 'FY20-2' => ['FY20-2-L'], 'FY20-4' => ['FY-20'], 'FY30' => ['FY-30'], 'FY40' => ['FY-40'], 'FY50' => ['FY-50'], 'FJZ30' => ['FJZ-30'], 'BL-T500-HD' => ['500 Tracked'], 'BL-T200-HJ' => ['200 Mixer']];

/** News sections of this site, keyed by the value stored with each story. Stories from the wider group carry none. */
const BL_NEWS_CATEGORIES = ['product-news' => 'Product news', 'customer-stories' => 'Customer stories', 'industry-news' => 'Guides'];

/** The tabs on the news list: the stored sections plus Company news, where every story without a section (the parent company's, or one saved without a choice) is listed, so the tabs add up to All. */
const BL_NEWS_SECTIONS = BL_NEWS_CATEGORIES + ['company' => 'Company news'];

/** The tab a story is listed under: its section, or 'company'. */
function bl_news_section(array $article): string
{
    return bl_news_category($article) ?: 'company';
}

/** A story's section key, or '' for a group story or any older value (Company news, Exhibitions) no longer used. */
function bl_news_category(array $article): string
{
    if (($article['line'] ?? '') === 'group') return ''; // group stories sit under All only, whatever category they still carry
    return isset(BL_NEWS_CATEGORIES[$article['category'] ?? '']) ? $article['category'] : '';
}

/**
 * Whose story it is: 'group' or 'compact'. Stories imported before news had a line keep their original record (the
 * importer never rewrites it), so the bundled data answers for those until an editor saves the story with a choice.
 */
function bl_news_line(array $record, string $slug): string
{
    $line = $record['line'] ?? (bl_find_record(bl_read_data('news'), $slug)['line'] ?? 'compact');
    return $line === 'group' ? 'group' : 'compact';
}

/** What the list and the story show above the title: the tab the story is listed under. */
function bl_news_label(array $article): string
{
    return BL_NEWS_SECTIONS[bl_news_section($article)];
}

/** The machine filters on the news list: a story is listed under the family of each product it names; one that names none is listed under All only. */
const BL_NEWS_PRODUCT_FILTERS = ['skid-steer-loaders' => 'Skid steer loaders', 'mini-dumpers' => 'Mini dumpers'];

/** Where a story is filed in the machine filters: one entry per product family it names, none when it names no product. */
function bl_news_families(array $article): array
{
    $index = bl_record_slug_index(bl_products());
    $families = [];
    foreach ((array) ($article['products'] ?? []) as $slug) {
        $family = (string) ($index[(string) $slug]['category'] ?? '');
        if (isset(BL_NEWS_PRODUCT_FILTERS[$family])) $families[$family] = true;
    }
    return array_keys($families);
}

/** Pinned stories first, then the rest, each group keeping the order it came in (newest first). */
function bl_news_order(array $articles): array
{
    $pinned = array_values(array_filter($articles, static fn($article) => !empty($article['pinned'])));
    $rest = array_values(array_filter($articles, static fn($article) => empty($article['pinned'])));
    return array_merge($pinned, $rest);
}

function bl_find_record(array $records, string $slug): ?array
{
    return bl_record_slug_index($records)[$slug] ?? null;
}

function bl_product_url(array $product): string
{
    return $product['url'] ?? bl_url('/product/' . $product['slug'] . '/');
}

function bl_article_url(array $article): string
{
    return $article['url'] ?? bl_url('/news/' . $article['slug'] . '/');
}

/** $fit carries a visitor's site numbers (width, load, ground) so the enquiry opens with them already written. */
function bl_contact_url(?array $product = null, string $application = '', array $fit = []): string
{
    $query = [];
    if ($product) $query['product'] = $product['slug'];
    if ($application !== '') $query['application'] = $application;
    if (!empty($fit['width'])) $query['width'] = $fit['width'];
    if (!empty($fit['load'])) $query['load'] = $fit['load'];
    if (($fit['ground'] ?? '') === 'soft') $query['ground'] = 'soft';
    if (($fit['equipment'] ?? '') === 'dumper') $query['equipment'] = 'dumper';
    return bl_url('/contact/') . ($query ? '?' . http_build_query($query) : '') . '#enquiry';
}

function bl_breadcrumbs(array $items): void
{
    echo '<nav class="bl-breadcrumbs" aria-label="Breadcrumb"><ol><li><a href="' . bl_escape(bl_url('/')) . '">Home</a></li>';
    foreach ($items as $item) {
        echo '<li>' . (isset($item['url']) ? '<a href="' . bl_escape($item['url']) . '">' . bl_escape($item['label']) . '</a>' : '<span aria-current="page">' . bl_escape($item['label']) . '</span>') . '</li>';
    }
    echo '</ol></nav>';
}

/** A product card; $heading is the card title's level (h3 where the cards sit under a section's own h2). */
function bl_product_card(array $product, string $attributes = '', string $heading = 'h2'): void
{
    $heading = in_array($heading, ['h2', 'h3'], true) ? $heading : 'h2';
    require __DIR__ . '/../templates/partials/product-card.php';
}

function bl_case_card(array $case, string $heading = 'h2'): void
{
    $heading = in_array($heading, ['h2', 'h3'], true) ? $heading : 'h2';
    require __DIR__ . '/../templates/partials/case-card.php';
}

function bl_news_card(array $article): void
{
    require __DIR__ . '/../templates/partials/news-card.php';
}

/** The enquiry form's id when the site sends enquiries through Contact Form 7; 0 for the theme's own email-draft form. */
function bl_inquiry_form_id(): int
{
    return bl_is_wordpress() ? bl_wp_inquiry_form_id() : 0;
}

/** The application cases, newest first; only $hub's own when one is named. Empty where there is no database (standalone preview). */
function bl_application_cases(string $hub = ''): array
{
    $cases = bl_is_wordpress() ? bl_wp_application_cases() : [];
    return $hub === '' ? $cases : array_values(array_filter($cases, static fn($case): bool => $case['hub'] === $hub));
}

/** The rendered body of a case, only for the case page itself. */
function bl_application_case_content(array $case): string
{
    return bl_is_wordpress() ? bl_wp_application_case_content((int) ($case['id'] ?? 0)) : '';
}

function bl_case_url(array $case): string
{
    return $case['url'] ?? bl_url('/application/' . $case['slug'] . '/');
}

/** One of the three application hubs by its URL slug. */
function bl_application_hub(string $slug): ?array
{
    foreach ($GLOBALS['applications'] ?? $GLOBALS['bl_application_base'] ?? [] as $application) if (bl_application_slug($application) === $slug) return $application;
    return null;
}

function bl_application_slug(array $application): string
{
    return $application['slug'] ?? strtolower(str_replace(' ', '-', $application['title']));
}

/**
 * Every model with a published width, with the dimensions the application pages quote. A loader's width is its widest
 * point with the bucket fitted (several buckets are wider than the body); a dumper's comes from its standard L × W × H.
 */
function bl_model_dimensions(): array
{
    $mm = static fn(string $value): int => preg_match('/([\d,]+)/', $value, $m) ? (int) str_replace(',', '', $m[1]) : 0;
    $models = [];
    foreach (bl_products() as $product) {
        $specs = $product['specs'];
        $loader = $product['category'] === 'skid-steer-loaders';
        if ($loader) {
            [$body, $bucket, $height] = [$mm($specs['Machine width'] ?? ''), $mm($specs['Bucket width'] ?? ''), $mm($specs['Overall height'] ?? '')];
        } else {
            $dimensions = array_map($mm, explode('×', $specs['Standard dimensions'] ?? ''));
            [$body, $bucket, $height] = [$dimensions[1] ?? 0, 0, $dimensions[2] ?? 0];
        }
        if (!$body) continue;
        $models[] = [
            'model' => $product['model'],
            'slug' => $product['slug'],
            'url' => bl_product_url($product),
            'image' => $product['image'],
            'line' => trim(preg_replace('/^' . preg_quote($product['model'], '/') . '\s*/', '', $product['name'])) ?: $product['name'],
            'family' => $loader ? 'loader' : 'dumper',
            'width' => max($body, $bucket),
            'body' => $body,
            'bucket' => $bucket,
            'height' => $height,
            'load' => (int) $product['load'],
            'extra' => !empty($specs['Drum capacity']) ? $specs['Drum capacity'] . ' drum' : '',
            'tracked' => bl_product_facets($product, '')['gear'] === 'tracked',
        ];
    }
    return $models;
}

/** Published ranges for one family ('loader' or 'dumper'). */
function bl_family_ranges(string $family): array
{
    $models = array_values(array_filter(bl_model_dimensions(), static fn($m) => $m['family'] === $family));
    $loads = array_filter(array_column($models, 'load'));
    $widths = array_column($models, 'width');
    $tracked = count(array_filter($models, static fn($m) => $m['tracked']));
    return [
        'count' => count($models),
        'width' => $widths ? [min($widths), max($widths)] : [0, 0],
        'load' => $loads ? [min($loads), max($loads)] : [0, 0],
        'loaded' => count($loads),
        'gear' => $tracked === count($models) ? 'Tracks' : ($tracked ? 'Wheels / tracks' : 'Wheels'),
    ];
}

/** A family's name ('loader' / 'dumper' or its category slug) as its catalogue card is titled in WordPress; blank falls back to the default. */
function bl_family_label(string $family): string
{
    $page = in_array($family, ['dumper', 'mini-dumpers'], true) ? 'dumpers' : 'loaders';
    $label = trim(bl_content_value($page, 'catalog.card_label'));
    return $label !== '' ? $label : (string) bl_content_defaults($page)['catalog']['card_label'];
}

/** A family's studio picture ('loader' or 'dumper'): the one chosen on the home page's family card, else the theme's own. */
function bl_family_image(string $family): string
{
    return bl_editable('home')['families'][$family]['image'] ?: bl_asset('images/range-' . $family . '-studio.webp');
}

/** "200–500 kg", "500–5,000 kg": always kilograms, as the specifications and the home page write rated loads. */
function bl_load_range(array $range): string
{
    [$low, $high] = $range;
    return number_format($low) . '–' . number_format($high) . ' kg';
}

/** The machines an application names in 'models', in that order, matched by current or former model name; none named, none returned. */
function bl_application_models(array $application): array
{
    $all = bl_products();
    $index = bl_dumper_model_index($all);
    foreach ($all as $product) $index[strtolower($product['model'])] ??= $product;
    // Preserve the legacy catalogue resolution unless a saved rename history exists.
    if (array_filter($all, static fn($product) => !empty($product['model_aliases']))) $index = bl_named_record_index($all, 'model', 'model_aliases') + $index;
    $products = [];
    foreach ((array) ($application['models'] ?? []) as $model) {
        $product = $index[strtolower(trim((string) $model))] ?? null;
        if ($product && !in_array($product, $products, true)) $products[] = $product;
    }
    return $products;
}

/**
 * The one published figure that decides an application ($application['focus']), as value, unit and what it measures:
 * across the machines it names when it names them, otherwise across its whole family.
 */
function bl_application_focus(array $application): array
{
    // Only the named machines from the job's own family count, as on the job page; none left means the whole family.
    $family = $application['product'] === 'dumper' ? 'mini-dumpers' : 'skid-steer-loaders';
    $named = array_column(array_filter(bl_application_models($application), static fn($p) => $p['category'] === $family), 'slug');
    $models = array_values(array_filter(bl_model_dimensions(), static fn($m) => $m['family'] === $application['product'] && (!$named || in_array($m['slug'], $named, true))));
    $loads = array_filter(array_column($models, 'load'));
    $widths = array_column($models, 'width');
    $load = $loads ? [min($loads), max($loads)] : [0, 0];
    $noun = $application['product'] === 'dumper' ? 'dumpers' : 'loaders';
    return match ($application['focus']) {
        'width' => ['value' => number_format($widths ? min($widths) : 0), 'unit' => 'mm', 'label' => $application['product'] === 'dumper' ? 'Narrowest dumper' : 'Narrowest loader, measured with bucket fitted'],
        default => ['value' => str_replace(' kg', '', bl_load_range($load)), 'unit' => 'kg', 'label' => 'Rated load across ' . count($loads) . ' ' . $noun],
    };
}
