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
        ['@type' => 'WebSite', '@id' => $base . '/#website', 'url' => $base . '/', 'name' => 'BOB-LIFT Loader', 'description' => 'The loader equipment business website of Xuzhou BOB-LIFT Construction Machinery Co., Ltd.', 'publisher' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
        ['@type' => $route['type'] === 'about' ? 'AboutPage' : ($route['type'] === 'contact' ? 'ContactPage' : 'WebPage'), '@id' => $base . $route['path'] . '#webpage', 'url' => $base . $route['path'], 'name' => $route['title'], 'description' => $route['description'], 'isPartOf' => ['@id' => $base . '/#website'], 'about' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
    ];
    if ($route['type'] === 'product' && $route['record']['model'] !== '') {
        $p = $route['record'];
        $graph[] = array_filter(['@type' => 'Product', 'name' => $p['name'], 'model' => $p['model'], 'image' => $p['image'] ? bl_absolute_asset($p['image']) : null, 'description' => $p['excerpt'], 'url' => $base . $route['path'], 'brand' => ['@type' => 'Brand', 'name' => 'BOB-LIFT'], 'manufacturer' => ['@id' => $organization['@id']], 'additionalProperty' => array_map(static fn($name, $value) => ['@type' => 'PropertyValue', 'name' => $name, 'value' => $value], array_keys($p['specs']), array_values($p['specs']))], static fn($value) => $value !== null); // models awaiting photography have no image
    }
    if ($route['type'] === 'application_case') {
        $c = $route['record'];
        $graph[] = array_filter(['@type' => 'Article', 'headline' => $c['title'], 'description' => $c['excerpt'], 'image' => bl_absolute_asset($c['image']), 'datePublished' => $c['date'], 'dateModified' => $c['updated'] ?? null, 'author' => ['@id' => $organization['@id']], 'publisher' => ['@id' => $organization['@id']], 'mainEntityOfPage' => ['@id' => $base . $route['path'] . '#webpage']], static fn($value) => $value !== null);
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
        $parent = $route['type'] === 'product' ? ($route['record']['category'] === 'mini-dumpers' ? ['Mini dumpers', '/products/mini-dumpers/'] : ['Skid steer loaders', '/products/skid-steer-loaders/']) : ($route['type'] === 'article' ? ['News', '/news/'] : (in_array($route['type'], ['application', 'application_case'], true) ? ['Applications', '/applications/'] : null));
        if ($parent) $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $parent[0], 'item' => $base . $parent[1]];
        // A case sits under its job: Home > Applications > Landscaping > this case.
        $hub = $route['type'] === 'application_case' ? bl_application_hub((string) $route['record']['hub']) : null;
        if ($hub) $items[] = ['@type' => 'ListItem', 'position' => count($items) + 1, 'name' => $hub['title'], 'item' => $base . '/applications/' . bl_application_slug($hub) . '/'];
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

/** The picture a shared link shows: the record's own, else the page's chosen one, else the one chosen for the whole site (Home), else the theme's. */
function bl_share_image(array $route): string
{
    if (!empty($route['record']['image'])) return $route['record']['image'];
    $page = in_array($route['type'], ['home', 'support', 'applications'], true) ? $route['type'] : 'home';
    foreach (array_unique([$page, 'home']) as $key) {
        $custom = (string) (bl_editable($key)['seo']['image'] ?? '');
        if ($custom !== '') return $custom;
    }
    return bl_asset('images/share-default.jpg');
}

function bl_render_seo(array $route): void
{
    $title = $route['title'] . ' | BOB-LIFT';
    $canonical = bl_public_base() . $route['path'];
    $filtered = (bool) array_filter(['q', 'load', 'gear', 'operator', 'opening', 'payload', 'category'], static fn($key) => bl_query($key) !== '');
    $robots = $route['status'] === 200 && bl_indexable() && !$filtered && empty($route['preview']) ? 'index, follow, max-image-preview:large' : 'noindex, follow';
    $image = bl_absolute_asset(bl_share_image($route));
    echo '<title>' . bl_escape($title) . '</title>';
    echo '<meta name="description" content="' . bl_escape($route['description']) . '">';
    echo '<meta name="robots" content="' . $robots . '">';
    if ($route['status'] === 200 && empty($route['preview'])) echo '<link rel="canonical" href="' . bl_escape($canonical) . '">';
    foreach (['og:type' => in_array($route['type'], ['article', 'application_case'], true) ? 'article' : 'website', 'og:title' => $title, 'og:description' => $route['description'], 'og:url' => $canonical, 'og:image' => $image, 'og:site_name' => 'BOB-LIFT Loader', 'og:locale' => 'en_US'] as $key => $value) echo '<meta property="' . $key . '" content="' . bl_escape($value) . '">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    if ($route['status'] === 200 && empty($route['preview'])) echo '<script type="application/ld+json">' . json_encode(bl_structured_data($route), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

/** robots.txt without the blocks that shut named AI crawlers out; the rules for every other agent stay as they are. */
function bl_allow_ai_crawlers(string $robots): string
{
    $bots = 'GPTBot|ChatGPT-User|OAI-SearchBot|ClaudeBot|Claude-Web|Claude-SearchBot|anthropic-ai|Google-Extended|Applebot-Extended|PerplexityBot|Perplexity-User|meta-externalagent|Bytespider|CCBot|Amazonbot|cohere-ai|Diffbot|FacebookBot|ImagesiftBot|Omgilibot|YouBot';
    $robots = preg_replace('/^User-agent:[ \t]*(?:' . $bots . ')[ \t]*\R(?:(?:Disallow|Allow|Crawl-delay):[^\r\n]*\R?)*\R*/mi', '', $robots) ?? $robots;
    $robots = preg_replace('/^#[^\r\n]*AI crawlers[^\r\n]*\R*/mi', '', $robots) ?? $robots;
    return rtrim($robots) . "\n";
}
