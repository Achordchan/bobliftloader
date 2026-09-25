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
        'width' => ['label' => 'Machine width', 'options' => ['narrow' => 'Under 1 m']],
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
    $width = preg_match('/^([\d,]+)\s*mm$/', $product['specs']['Machine width'] ?? '', $m) ? (int) str_replace(',', '', $m[1]) : 0;
    $bands = $category === 'skid-steer-loaders' ? [250, 400] : [1000, 2000];
    return [
        // Every tracked model says so; the wheeled site dumpers publish no running-gear row at all.
        'gear' => str_contains($gear, 'track') ? 'tracked' : (str_contains($gear, 'tire') || str_contains($gear, 'wheel') || !$loader ? 'wheeled' : ''),
        'operator' => str_contains($station, 'stand') ? 'stand-on' : (str_contains($station, 'seat') ? 'seated' : ''),
        'load' => !$load ? '' : ($load <= $bands[0] ? 'light' : ($load <= $bands[1] ? 'mid' : 'heavy')),
        'width' => $width && $width < 1000 ? 'narrow' : '',
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
    }
    unset($article);
    return $articles;
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

function bl_contact_url(?array $product = null, string $application = ''): string
{
    $query = [];
    if ($product) $query['product'] = $product['slug'];
    if ($application !== '') $query['application'] = $application;
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
