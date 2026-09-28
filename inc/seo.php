<?php
declare(strict_types=1);

function bl_structured_data(array $route): array
{
    $base = bl_public_base();
    $organization = [
        '@type' => 'Organization', '@id' => 'https://www.boblift.com/#organization',
        'name' => 'Xuzhou BOB-LIFT Construction Machinery Co., Ltd.', 'alternateName' => 'BOB-LIFT',
        'url' => 'https://www.boblift.com/', 'email' => 'info@boblift.com', 'telephone' => '+86-15852499415',
        'sameAs' => ['https://www.bobliftcrane.com/'],
        'logo' => bl_absolute_asset(bl_asset('images/logo.png')),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => 'No. 79, Zone B, Jingluotuo Industrial Park, Yunlong District', 'addressLocality' => 'Xuzhou', 'addressRegion' => 'Jiangsu', 'addressCountry' => 'CN'],
    ];
    $graph = [$organization,
        ['@type' => 'WebSite', '@id' => $base . '/#website', 'url' => $base . '/', 'name' => 'BOB-LIFT Compact Equipment', 'description' => 'The compact equipment business website of Xuzhou BOB-LIFT Construction Machinery Co., Ltd.', 'publisher' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
        ['@type' => $route['type'] === 'about' ? 'AboutPage' : ($route['type'] === 'contact' ? 'ContactPage' : 'WebPage'), '@id' => $base . $route['path'] . '#webpage', 'url' => $base . $route['path'], 'name' => $route['title'], 'description' => $route['description'], 'isPartOf' => ['@id' => $base . '/#website'], 'about' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
    ];
    if ($route['type'] === 'product' && $route['record']['model'] !== '') {
        $p = $route['record'];
        $graph[] = array_filter(['@type' => 'Product', 'name' => $p['name'], 'model' => $p['model'], 'image' => $p['image'] ? bl_absolute_asset($p['image']) : null, 'description' => $p['excerpt'], 'url' => $base . $route['path'], 'brand' => ['@type' => 'Brand', 'name' => 'BOB-LIFT'], 'manufacturer' => ['@id' => $organization['@id']], 'additionalProperty' => array_map(static fn($name, $value) => ['@type' => 'PropertyValue', 'name' => $name, 'value' => $value], array_keys($p['specs']), array_values($p['specs']))], static fn($value) => $value !== null); // models awaiting photography have no image
    }
    if ($route['type'] === 'article') {
        $a = $route['record'];
        $graph[] = ['@type' => 'Article', 'headline' => $a['title'], 'description' => $a['excerpt'], 'image' => bl_absolute_asset($a['image']), 'datePublished' => $a['date'], 'author' => ['@id' => $organization['@id']], 'publisher' => ['@id' => $organization['@id']], 'mainEntityOfPage' => ['@id' => $base . $route['path'] . '#webpage'], 'citation' => $a['source'] ?: null];
        $graph[count($graph) - 1] = array_filter($graph[count($graph) - 1], static fn($value) => $value !== null);
        if (!empty($a['event']['start'])) {
            $e = $a['event'];
            $graph[] = ['@type' => 'Event', 'name' => 'BOB-LIFT at the ' . $e['name'] . ', booth ' . $e['booth'], 'startDate' => $e['start'], 'endDate' => $e['end'] ?: $e['start'], 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode', 'eventStatus' => 'https://schema.org/EventScheduled', 'location' => ['@type' => 'Place', 'name' => $e['venue'], 'address' => $e['address']], 'organizer' => ['@id' => $organization['@id']], 'image' => bl_absolute_asset($a['image']), 'description' => $a['excerpt'], 'url' => $base . $route['path']];
        }
    }
    if ($route['path'] !== '/' && $route['status'] === 200) {
        $items = [['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => $base . '/']];
        $parent = $route['type'] === 'product' ? ($route['record']['category'] === 'mini-dumpers' ? ['Mini dumpers', '/products/mini-dumpers/'] : ['Skid steer loaders', '/products/skid-steer-loaders/']) : ($route['type'] === 'article' ? ['News', '/news/'] : ($route['type'] === 'application' ? ['Applications', '/applications/'] : null));
        if ($parent) $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $parent[0], 'item' => $base . $parent[1]];
        $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $route['title'], 'item' => $base . $route['path']];
        $graph[] = ['@type' => 'BreadcrumbList', 'itemListElement' => $items];
    }
    if ($route['type'] === 'support') {
        $questions = [];
        foreach (bl_support_faq() as $group) foreach ($group['items'] as $item) $questions[] = ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]];
        $graph[] = ['@type' => 'FAQPage', 'mainEntity' => $questions, 'isPartOf' => ['@id' => $base . '/#website']];
    }
    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

function bl_absolute_asset(string $url): string
{
    return str_starts_with($url, '/') ? bl_public_base() . $url : $url;
}

function bl_render_seo(array $route): void
{
    $title = $route['title'] . ' | BOB-LIFT';
    $canonical = bl_public_base() . $route['path'];
    $filtered = (bool) array_filter(['q', 'load', 'gear', 'operator', 'opening', 'payload', 'category'], static fn($key) => bl_query($key) !== '');
    $robots = $route['status'] === 200 && bl_indexable() && !$filtered ? 'index, follow, max-image-preview:large' : 'noindex, follow';
    $image = bl_absolute_asset(!empty($route['record']['image']) ? $route['record']['image'] : bl_asset('images/logo.png'));
    echo '<title>' . bl_escape($title) . '</title>';
    echo '<meta name="description" content="' . bl_escape($route['description']) . '">';
    echo '<meta name="robots" content="' . $robots . '">';
    if ($route['status'] === 200) echo '<link rel="canonical" href="' . bl_escape($canonical) . '">';
    foreach (['og:type' => $route['type'] === 'article' ? 'article' : 'website', 'og:title' => $title, 'og:description' => $route['description'], 'og:url' => $canonical, 'og:image' => $image, 'og:site_name' => 'BOB-LIFT Compact Equipment', 'og:locale' => 'en_US'] as $key => $value) echo '<meta property="' . $key . '" content="' . bl_escape($value) . '">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    if ($route['status'] === 200) echo '<script type="application/ld+json">' . json_encode(bl_structured_data($route), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}
