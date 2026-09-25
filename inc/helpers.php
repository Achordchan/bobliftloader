<?php
declare(strict_types=1);

function bl_is_wordpress(): bool
{
    return defined('ABSPATH') && function_exists('home_url');
}

function bl_asset(string $path): string
{
    $base = bl_is_wordpress() ? get_template_directory_uri() . '/assets/' : '/assets/';
    return $base . ltrim($path, '/');
}

function bl_url(string $path = '/'): string
{
    return bl_is_wordpress() ? home_url('/' . ltrim($path, '/')) : '/' . ltrim($path, '/');
}

function bl_escape(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
        $product['gallery'] = array_map('bl_asset', $product['gallery'] ?? []);
    }
    unset($product);
    return $products;
}

/** Up to three headline figures, in the order buyers compare them; short labels suit the catalogue cards. */
function bl_key_specs(array $product, bool $short = false): array
{
    $labels = ['Rated load' => 'Rated load', 'Drum capacity' => 'Drum', 'Bucket capacity' => 'Bucket', 'Engine power' => 'Power', 'Machine weight' => 'Weight'];
    $specs = [];
    foreach ($labels as $key => $shortLabel) {
        if (!empty($product['specs'][$key])) $specs[$short ? $shortLabel : $key] = $product['specs'][$key];
        if (count($specs) === 3) break;
    }
    return $specs;
}

/** "a 200 kg rated load, a 0.15 m³ bucket and a 7.6 kW engine", from whichever of those figures are published. */
function bl_spec_phrase(array $product): string
{
    $parts = [];
    foreach (['Rated load' => 'rated load', 'Drum capacity' => 'mixing drum', 'Bucket capacity' => 'bucket', 'Engine power' => 'engine'] as $key => $noun) {
        if (!empty($product['specs'][$key])) $parts[] = 'a ' . $product['specs'][$key] . ' ' . $noun;
    }
    if (count($parts) < 2) return implode('', $parts);
    return implode(', ', array_slice($parts, 0, -1)) . ' and ' . end($parts);
}

function bl_product_excerpt(array $product): string
{
    $phrase = bl_spec_phrase($product);
    if ($phrase !== '') return $product['name'] . ' with ' . $phrase . '. Explore published specifications and discuss your application with BOB-LIFT.';
    return 'Explore the BOB-LIFT ' . $product['model'] . ' ' . ($product['category'] === 'mini-dumpers' ? 'mini dumper' : 'mini skid steer loader') . '. View original product photography and discuss the configuration for your next project.';
}

/**
 * Loader names read BL-{running gear}{size class}-{operator position}, e.g. BL-T360-H: T tracked / S wheeled,
 * H hand (stand-on) / C cabin (seated), as set by BOB-LIFT on 2026-09-24. Other models return null.
 */
function bl_model_parts(string $model): ?array
{
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
    $gear = ['label' => 'Running gear', 'options' => ['tracked' => 'Tracked', 'wheeled' => 'Wheeled']];
    if ($category === 'skid-steer-loaders') return [
        'gear' => $gear,
        'operator' => ['label' => 'Operator', 'options' => ['stand-on' => 'Stand-on', 'seated' => 'Seated']],
        'load' => ['label' => 'Rated load', 'options' => ['light' => 'Up to 250 kg', 'mid' => '251–400 kg', 'heavy' => 'Over 400 kg']],
    ];
    return [
        'load' => ['label' => 'Rated load', 'options' => ['light' => 'Up to 1 t', 'mid' => '1–2 t', 'heavy' => 'Over 2 t']],
        'gear' => $gear,
    ];
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
        'gear' => str_contains($gear, 'track') ? 'tracked' : (str_contains($gear, 'tire') || str_contains($gear, 'wheel') || !$loader ? 'wheeled' : ''),
        'operator' => str_contains($station, 'stand') ? 'stand-on' : (str_contains($station, 'seat') ? 'seated' : ''),
        'load' => !$load ? '' : ($load <= $bands[0] ? 'light' : ($load <= $bands[1] ? 'mid' : 'heavy')),
    ];
}

/** Text the catalogue search looks through: name, model and the model without its T / S letter, as older literature writes it. */
function bl_product_search_text(array $product): string
{
    return strtolower($product['name'] . ' ' . $product['model'] . ' ' . preg_replace('/^BL-[TS]/', 'BL-', $product['model']));
}

/** Specification rows grouped for the detail table; labels outside the known groups fall under "Details". */
function bl_spec_groups(array $specs): array
{
    $groups = [
        'Performance' => ['Rated load', 'Lifting capacity', 'Bucket capacity', 'Drum capacity', 'Travel speed', 'Hydraulic pressure', 'Gradeability', 'Tipping', 'Tipping angle', 'Machine weight'],
        'Engine' => ['Engine options', 'Engine power', 'Engine type', 'Fuel', 'Starting', 'Emission standard', 'Operating voltage'],
        'Dimensions' => ['Working height', 'Length with bucket', 'Length without bucket', 'Machine width', 'Bucket width', 'Overall height', 'Standard dimensions', 'Dimensions with roof / cab', 'Hopper size', 'Drum length', 'Drum opening diameter', 'Loading bucket length', 'Loading bucket opening width', 'Minimum ground clearance'],
        'Build' => ['Running gear', 'Operator station', 'Controls', 'Body material', 'Bucket material'],
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

/** "15–19 October 2026", or "30 October – 2 November 2026" across months. */
function bl_event_dates(array $event): string
{
    [$start, $end] = [strtotime($event['start']), strtotime($event['end'] ?: $event['start'])];
    if ($start === $end) return date('j F Y', $start);
    if (date('Y-m', $start) === date('Y-m', $end)) return date('j', $start) . '–' . date('j F Y', $end);
    return date('j F', $start) . ' – ' . date('j F Y', $end);
}

/** Where an event stands today: 'upcoming' with the days to go, 'live', or 'past'. Dates are the event's local days. */
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

/** News sections of this site, keyed by the value stored with each story. Stories from the wider group carry none. */
const BL_NEWS_CATEGORIES = ['product-news' => 'Product news', 'customer-stories' => 'Customer stories', 'industry-news' => 'Industry news'];

/** A story's section key, or '' for a group story or any older value (Company news, Exhibitions) no longer used. */
function bl_news_category(array $article): string
{
    return isset(BL_NEWS_CATEGORIES[$article['category'] ?? '']) ? $article['category'] : '';
}

/** What the list and the story show above the title: the section, or "BOB-LIFT group" for the parent company's stories. */
function bl_news_label(array $article): string
{
    $category = bl_news_category($article);
    if ($category) return BL_NEWS_CATEGORIES[$category];
    return ($article['line'] ?? '') === 'group' ? 'BOB-LIFT group' : 'News';
}

function bl_find_record(array $records, string $slug): ?array
{
    foreach ($records as $record) {
        if ($record['slug'] === $slug) {
            return $record;
        }
    }
    return null;
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

function bl_product_card(array $product, string $attributes = ''): void
{
    require __DIR__ . '/../templates/partials/product-card.php';
}

function bl_news_card(array $article): void
{
    require __DIR__ . '/../templates/partials/news-card.php';
}

function bl_application_slug(array $application): string
{
    return strtolower(str_replace(' ', '-', $application['title']));
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
        'gear' => $tracked === count($models) ? 'Tracks' : ($tracked ? ($family === 'loader' ? 'Tracks / tires' : 'Wheels / tracks') : ($family === 'loader' ? 'Tires' : 'Wheels')),
    ];
}

/** "200–500 kg", or tonnes once both ends reach a tonne ("1–5 t"); a mixed range reads "0.5–5 t". */
function bl_load_range(array $range): string
{
    [$low, $high] = $range;
    if ($high < 1000) return number_format($low) . '–' . number_format($high) . ' kg';
    $t = static fn(int $kg): string => rtrim(rtrim(number_format($kg / 1000, 1), '0'), '.');
    return $t($low) . '–' . $t($high) . ' t';
}

/** The one published figure that decides an application ($application['focus']), as value, unit and what it measures. */
function bl_application_focus(array $application): array
{
    $ranges = bl_family_ranges($application['product']);
    return match ($application['focus']) {
        'width' => ['value' => number_format($ranges['width'][0]), 'unit' => 'mm', 'label' => 'Narrowest loader, bucket fitted'],
        'load' => ['value' => str_replace(' kg', '', bl_load_range($ranges['load'])), 'unit' => 'kg', 'label' => 'Rated load across ' . $ranges['count'] . ' loaders'],
        default => ['value' => str_replace(' t', '', bl_load_range($ranges['load'])), 'unit' => 't', 'label' => 'Rated load across ' . $ranges['loaded'] . ' dumpers'],
    };
}
