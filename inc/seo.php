<?php
declare(strict_types=1);

function bl_structured_data(array $route): array
{
    $base = bl_public_base();
    $organization = [
        '@type' => 'Organization', '@id' => bl_company('website') . '#organization',
        'name' => bl_company('name'), 'alternateName' => 'BOB-LIFT',
        'url' => bl_company('website'), 'email' => bl_company('email'), 'telephone' => bl_company('phone_seo'),
        'sameAs' => ['https://www.bobliftcrane.com/'],
        'logo' => bl_absolute_asset(bl_brand_logo()),
        'address' => ['@type' => 'PostalAddress', 'streetAddress' => bl_company('street'), 'addressLocality' => bl_company('city'), 'addressRegion' => bl_company('region'), 'addressCountry' => bl_company('country_code')],
    ];
    // The registered address stays the organisation's address; the factory and the office are places of the same organisation.
    $places = [];
    foreach (['Main factory' => 'factory_street', 'Office' => 'office_street'] as $placeName => $placeKey) {
        if (bl_company($placeKey) === '') continue;
        $places[] = ['@type' => 'Place', 'name' => $placeName, 'address' => ['@type' => 'PostalAddress', 'streetAddress' => bl_company($placeKey), 'addressLocality' => bl_company('city'), 'addressRegion' => bl_company('region'), 'addressCountry' => bl_company('country_code')]];
    }
    if ($places) $organization['location'] = $places;
    $graph = [$organization,
        ['@type' => 'WebSite', '@id' => $base . '/#website', 'url' => $base . '/', 'name' => 'BOB-LIFT Loader', 'description' => 'The loader equipment business website of ' . bl_company('name'), 'publisher' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
        ['@type' => $route['type'] === 'about' ? 'AboutPage' : ($route['type'] === 'contact' ? 'ContactPage' : 'WebPage'), '@id' => $base . $route['path'] . '#webpage', 'url' => $base . $route['path'], 'name' => $route['title'], 'description' => $route['description'], 'isPartOf' => ['@id' => $base . '/#website'], 'about' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
    ];
    if ($route['type'] === 'product' && $route['record']['model'] !== '') {
        $p = $route['record'];
        // Every photograph of the machine (gallery first, then the product's own photographs, drawings aside), once each.
        $images = array_values(array_unique(array_map('bl_absolute_asset', array_filter([$p['image'], ...array_column(bl_product_gallery($p), 'src'), ...array_column(bl_product_photos($p, ['at-work', 'detail', 'delivery']), 'src')]))));
        $graph[] = array_filter(['@type' => 'Product', 'name' => $p['name'], 'model' => $p['model'], 'sku' => $p['model'], 'mpn' => $p['model'], 'category' => $p['category'] === 'mini-dumpers' ? 'Mini dumpers' : 'Skid steer loaders', 'image' => $images ?: null, 'description' => bl_product_description($p), 'url' => $base . $route['path'], 'brand' => ['@type' => 'Brand', 'name' => 'BOB-LIFT'], 'manufacturer' => ['@id' => $organization['@id']], 'additionalProperty' => array_map(static fn($name, $value) => ['@type' => 'PropertyValue', 'name' => $name, 'value' => $value], array_keys($p['specs']), array_values($p['specs']))], static fn($value) => $value !== null); // models awaiting photography have no image
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
        $parent = $route['type'] === 'product' ? [bl_family_label($route['record']['category']), '/products/' . ($route['record']['category'] === 'mini-dumpers' ? 'mini-dumpers' : 'skid-steer-loaders') . '/'] : ($route['type'] === 'article' ? ['News', '/news/'] : (in_array($route['type'], ['application', 'application_case'], true) ? ['Applications', '/applications/'] : null));
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
    $page = bl_seo_content_page($route);
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
    $robots = $route['status'] === 200 && bl_indexable() && !$filtered && empty($route['preview']) && $route['type'] !== 'thanks' ? 'index, follow, max-image-preview:large' : 'noindex, follow';
    $image = bl_absolute_asset(bl_share_image($route));
    echo '<title>' . bl_escape($title) . '</title>';
    echo '<meta name="description" content="' . bl_escape($route['description']) . '">';
    echo '<meta name="robots" content="' . $robots . '">';
    if ($route['status'] === 200 && empty($route['preview'])) echo '<link rel="canonical" href="' . bl_escape($canonical) . '">';
    foreach (['og:type' => in_array($route['type'], ['article', 'application_case'], true) ? 'article' : 'website', 'og:title' => $title, 'og:description' => $route['description'], 'og:url' => $canonical, 'og:image' => $image, 'og:site_name' => 'BOB-LIFT Loader', 'og:locale' => 'en_US'] as $key => $value) echo '<meta property="' . $key . '" content="' . bl_escape($value) . '">';
    echo '<meta name="twitter:card" content="summary_large_image">';
    if ($route['status'] === 200 && empty($route['preview'])) echo '<script type="application/ld+json">' . json_encode(bl_structured_data($route), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) . '</script>';
}

/**
 * robots.txt without the rules that shut named AI crawlers out. The file is read as groups (one or more User-agent lines and the
 * rules under them): a named crawler's User-agent line is removed from its group, and a group left with no agent goes with its rules
 * and the comments written for it. Groups that also name another agent, and every other group, keep what they have.
 */
function bl_allow_ai_crawlers(string $robots): string
{
    $bots = array_map('strtolower', ['GPTBot', 'ChatGPT-User', 'OAI-SearchBot', 'ClaudeBot', 'Claude-Web', 'Claude-SearchBot', 'anthropic-ai', 'Google-Extended', 'Applebot-Extended', 'PerplexityBot', 'Perplexity-User', 'meta-externalagent', 'Bytespider', 'CCBot', 'Amazonbot', 'cohere-ai', 'Diffbot', 'FacebookBot', 'ImagesiftBot', 'omgili', 'Omgilibot', 'SentiBot', 'YouBot']);
    $blocks = [];     // each: ['lines' => [...], 'agents' => n, 'kept' => n, 'rules' => bool]
    $global = [];     // Sitemap lines and the like belong to no group
    $pending = [];    // comments and blank lines after a group's rules: they lead the next group if one starts, else stay with this one
    $block = null;
    foreach (preg_split('/\R/', $robots) as $line) {
        $trim = trim($line);
        if (preg_match('/^user-agent[ \t]*:[ \t]*(.*)$/i', $trim, $m)) {
            if ($block === null || $block['rules']) {
                if ($block !== null) $blocks[] = $block;
                $block = ['lines' => $pending, 'agents' => 0, 'kept' => 0, 'rules' => false];
                $pending = [];
            } else {
                $block['lines'] = array_merge($block['lines'], $pending);
                $pending = [];
            }
            $block['agents']++;
            $ai = in_array(strtolower(trim(preg_replace('/\s*#.*$/', '', $m[1]))), $bots, true);
            if (!$ai) {
                $block['kept']++;
                $block['lines'][] = $line;
            }
        } elseif ($trim === '' || $trim[0] === '#') {
            if ($block !== null && $block['rules']) $pending[] = $line;
            elseif ($block !== null) $block['lines'][] = $line;
            else $global[] = $line;
        } elseif (preg_match('/^sitemap[ \t]*:/i', $trim)) {
            $global[] = $line;
        } else {
            if ($block === null) {
                $global[] = $line;
                continue;
            }
            $block['lines'] = array_merge($block['lines'], $pending);
            $pending = [];
            $block['rules'] = true;
            $block['lines'][] = $line;
        }
    }
    if ($block !== null) {
        $block['lines'] = array_merge($block['lines'], $pending);
        $blocks[] = $block;
    }
    $out = [];
    foreach ($blocks as $b) {
        if ($b['agents'] > 0 && $b['kept'] === 0) continue;
        $text = trim(implode("\n", $b['lines']), "\r\n");
        if ($text !== '') $out[] = $text;
    }
    $sitemaps = array_filter($global, static fn($l) => preg_match('/^\s*sitemap\s*:/i', $l));
    $others = array_filter($global, static fn($l) => !preg_match('/^\s*sitemap\s*:/i', $l) && trim($l) !== '');
    if ($others) array_unshift($out, implode("\n", $others));
    if ($sitemaps) $out[] = implode("\n", $sitemaps);
    return implode("\n\n", $out) . "\n";
}
