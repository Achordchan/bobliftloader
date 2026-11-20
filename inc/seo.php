<?php
declare(strict_types=1);

/**
 * The company's crane-line profiles, the same as the crane site lists: its YouTube and Facebook and its B2B directory
 * listings (each names Xuzhou BOB-LIFT, checked 2026-10-23).
 */
const BL_CRANE_LINE_PROFILES = [
    'https://www.youtube.com/@bobliftcrane',
    'https://www.facebook.com/bobliftconstruction',
    'https://bobliftcrane.en.made-in-china.com/',
    'https://boblift.en.made-in-china.com/',
    'https://www.directindustry.com/prod/bob-lift-241919.html',
    'https://www.exporthub.com/boblift/profile.html',
];

function bl_structured_data(array $route): array
{
    $base = bl_public_base();
    // 全站设置 → 品牌标志 is shared with the structured data when one is saved; otherwise the square theme logo (600 × 600 on
    // white) stands in, since Google needs at least 112 px on each side and the header logo is 91 px tall.
    $savedLogo = trim(bl_content_value('global', 'brand.logo'));
    $logo = bl_absolute_asset($savedLogo !== '' ? $savedLogo : bl_asset('images/logo-square-600.png'));
    $organization = [
        // One company, one entity: the crane site and this site describe the same Organization (same @id, name, official
        // website, logo and profile list), so search engines can tie both sites and the group site to one brand.
        '@type' => 'Organization', '@id' => bl_company('website') . '#organization',
        'name' => 'BOB-LIFT', 'legalName' => bl_company('name'),
        // The Chinese registered name lets search engines and AI assistants match Chinese-language queries to the same company.
        'alternateName' => array_values(array_filter([bl_company('name'), bl_company('name_zh'), 'BOBLIFT'])),
        'description' => 'BOB-LIFT (' . bl_company('name') . ') is a Chinese manufacturer of ' . (bl_sister_site() ? 'truck mounted cranes, marine cranes and loader equipment' : 'skid steer loaders and mini dumpers') . ' in Xuzhou, Jiangsu.',
        'url' => bl_company('website'), 'email' => bl_company('email'), 'telephone' => bl_company('phone_seo'),
        'logo' => ['@type' => 'ImageObject', '@id' => bl_company('website') . '#logo', 'url' => $logo, 'contentUrl' => $logo, 'caption' => 'BOB-LIFT'] + ($savedLogo !== '' ? [] : ['width' => 600, 'height' => 600]),
        'image' => ['@id' => bl_company('website') . '#logo'],
        // Unified social credit code from the national enterprise credit register (business licence, checked 2026-11-12).
        'identifier' => ['@type' => 'PropertyValue', 'propertyID' => 'Unified Social Credit Code', 'value' => '91320300078204097G'],
        'foundingDate' => '2003',
        'contactPoint' => ['@type' => 'ContactPoint', 'contactType' => 'sales', 'email' => bl_company('email'), 'telephone' => bl_company('phone_seo'), 'availableLanguage' => ['English', 'Chinese']],
        // The crane website and the crane line's profiles are listed only while 全站设置 → 起重机站推荐 has an address; clearing it takes cranes off this website everywhere.
        'sameAs' => array_values(array_unique(array_merge(['https://bobliftloader.com/'], bl_sister_site() ? array_merge([bl_sister_site()['url']], BL_CRANE_LINE_PROFILES) : [], array_column(bl_social_links(), 'url')))),
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
        ['@type' => 'WebSite', '@id' => $base . '/#website', 'url' => $base . '/', 'name' => 'BOB-LIFT Loader', 'alternateName' => 'BOB-LIFT', 'description' => 'The loader equipment business website of ' . bl_company('name'), 'publisher' => ['@id' => $organization['@id']], 'inLanguage' => 'en'],
        ['@type' => $route['type'] === 'about' ? 'AboutPage' : ($route['type'] === 'contact' ? 'ContactPage' : 'WebPage'), '@id' => $base . bl_lang_path($route['path'], bl_lang()) . '#webpage', 'url' => $base . bl_lang_path($route['path'], bl_lang()), 'name' => $route['title'], 'description' => $route['description'], 'isPartOf' => ['@id' => $base . '/#website'], 'about' => ['@id' => $organization['@id']], 'inLanguage' => BL_I18N_LANGUAGES[bl_lang()]['hreflang']],
    ];
    // No Product node: Google's product snippets need a price, a review or a rating, and the site states none of them.
    if ($route['type'] === 'application_case') {
        $c = $route['record'];
        $graph[] = array_filter(['@type' => 'Article', 'headline' => $c['title'], 'description' => $c['excerpt'], 'image' => bl_absolute_asset($c['image']), 'datePublished' => $c['date'], 'dateModified' => $c['updated'] ?? null, 'author' => ['@id' => $organization['@id']], 'publisher' => ['@id' => $organization['@id']], 'mainEntityOfPage' => ['@id' => $base . $route['path'] . '#webpage']], static fn($value) => $value !== null);
    }
    if ($route['type'] === 'article') {
        $a = $route['record'];
        $graph[] = ['@type' => 'Article', 'headline' => $a['title'], 'description' => $a['excerpt'], 'image' => bl_absolute_asset($a['image']), 'datePublished' => $a['date'], 'dateModified' => $a['updated'] ?? null, 'author' => bl_article_author($a, $organization['@id']), 'publisher' => ['@id' => $organization['@id']], 'mainEntityOfPage' => ['@id' => $base . bl_lang_path($route['path'], bl_lang()) . '#webpage'], 'citation' => $a['source'] ?: null, 'inLanguage' => BL_I18N_LANGUAGES[bl_lang()]['hreflang']];
        $graph[count($graph) - 1] = array_filter($graph[count($graph) - 1], static fn($value) => $value !== null);
        foreach (bl_article_videos((string) ($a['content_html'] ?? '')) as $video) $graph[] = $video;
        if (!empty($a['event']['start'])) {
            // BOB-LIFT is the exhibitor, so it is also the performer. No offers: booths have no ticket price to state.
            $e = $a['event'];
            $graph[] = ['@type' => 'Event', 'name' => 'BOB-LIFT at the ' . $e['name'] . ', booth ' . $e['booth'], 'startDate' => $e['start'], 'endDate' => $e['end'] ?: $e['start'], 'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode', 'eventStatus' => 'https://schema.org/EventScheduled', 'location' => ['@type' => 'Place', 'name' => $e['venue'], 'address' => $e['address']], 'organizer' => ['@id' => $organization['@id']], 'performer' => ['@id' => $organization['@id']], 'image' => bl_absolute_asset($a['image']), 'description' => $a['excerpt'], 'url' => $base . $route['path']];
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
    if ($route['type'] === 'dealers' && ($dealerFaq = bl_dealer_faq())) {
        $graph[] = ['@type' => 'FAQPage', 'mainEntity' => array_map(static fn($item) => ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]], $dealerFaq), 'isPartOf' => ['@id' => $base . '/#website']];
    }
    if ($route['type'] === 'support') {
        $questions = [];
        foreach (bl_support_faq() as $group) foreach ($group['items'] as $item) $questions[] = ['@type' => 'Question', 'name' => $item['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']]];
        $graph[] = ['@type' => 'FAQPage', 'mainEntity' => $questions, 'isPartOf' => ['@id' => $base . '/#website']];
    }
    return ['@context' => 'https://schema.org', '@graph' => $graph];
}

/**
 * Who wrote a story, for its Article node: the WordPress author shown in the byline, as a Person working for BOB-LIFT,
 * or the organisation itself for stories imported without one (the byline then reads BOB-LIFT too).
 */
function bl_article_author(array $article, string $organizationId): array
{
    $author = $article['author'] ?? [];
    if (empty($author['name'])) return ['@id' => $organizationId];
    return array_filter(['@type' => 'Person', 'name' => $author['name'], 'jobTitle' => ($author['title'] ?? '') ?: null, 'image' => !empty($author['avatar']) ? bl_absolute_asset($author['avatar']) : null, 'worksFor' => ['@id' => $organizationId]], static fn($value) => $value !== null);
}

/**
 * Films set into a story's text, as VideoObject nodes. A film counts when written as
 * <figure class="bl-article-video" data-upload-date="…" data-duration="PT57S"><video title="…" poster="…" src="…"></video><figcaption>…</figcaption></figure>;
 * Google needs a name, a thumbnail and the upload date, so a film missing any of them is left out rather than marked up half.
 */
function bl_article_videos(string $html): array
{
    if (!str_contains($html, 'bl-article-video') || !class_exists('DOMDocument')) return [];
    $document = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $document->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    $nodes = [];
    foreach ((new DOMXPath($document))->query('//figure[contains(concat(" ", normalize-space(@class), " "), " bl-article-video ")]') as $figure) {
        $video = $figure->getElementsByTagName('video')->item(0);
        if (!$video instanceof DOMElement) continue;
        $caption = $figure->getElementsByTagName('figcaption')->item(0);
        $name = trim($video->getAttribute('title'));
        $description = $caption ? trim(preg_replace('/\s+/', ' ', $caption->textContent)) : '';
        $node = array_filter([
            '@type' => 'VideoObject', 'name' => $name, 'description' => $description !== '' ? $description : $name,
            'thumbnailUrl' => $video->getAttribute('poster') !== '' ? bl_absolute_asset($video->getAttribute('poster')) : '',
            'contentUrl' => $video->getAttribute('src') !== '' ? bl_absolute_asset($video->getAttribute('src')) : '',
            'uploadDate' => trim($figure->getAttribute('data-upload-date')), 'duration' => trim($figure->getAttribute('data-duration')),
        ], static fn($value) => $value !== '');
        if (isset($node['name'], $node['thumbnailUrl'], $node['uploadDate'], $node['contentUrl'])) $nodes[] = $node;
    }
    return $nodes;
}

function bl_absolute_asset(string $url): string
{
    return str_starts_with($url, '/') ? bl_public_base() . $url : $url;
}

/** The picture a shared link shows: the record's own, else the page's chosen one, else the one chosen for the whole site (Home), else the theme's. */
function bl_share_image(array $route): string
{
    if (!empty($route['record']['image'])) return $route['record']['image'];
    if ($route['type'] === 'showcase') return bl_asset('bl360/assets/details/share.jpg');
    $page = bl_seo_content_page($route);
    foreach (array_unique([$page, 'home']) as $key) {
        $custom = (string) (bl_editable($key)['seo']['image'] ?? '');
        if ($custom !== '') return $custom;
    }
    return bl_asset('images/share-default.jpg');
}

/**
 * The page's <title>, also used for og:title and twitter:title. A news story or case may carry its own search title
 * (_bl_seo_title, as record['seo_title']) so the result line can differ from the H1; empty, the H1 is used. A search title
 * that already ends in the brand ("| BOB-LIFT", "– BOB-LIFT Loader"…) is used as written, so the brand is never doubled.
 * The home page carries the site name Google shows above results (BOB-LIFT Loader, as in the WebSite node and og:site_name);
 * other pages keep the shorter brand.
 */
function bl_seo_title(array $route): string
{
    $brand = $route['type'] === 'home' ? 'BOB-LIFT Loader' : 'BOB-LIFT';
    $custom = in_array($route['type'], ['article', 'application_case'], true) ? trim((string) ($route['record']['seo_title'] ?? '')) : '';
    if ($custom === '') return $route['title'] . ' | ' . $brand;
    return preg_match('/[|\-–—:]\s*BOB-LIFT(?:\s+Loader)?\s*$/iu', $custom) ? $custom : $custom . ' | ' . $brand;
}

function bl_render_seo(array $route): void
{
    $title = bl_seo_title($route);
    $lang = bl_lang();
    $canonical = bl_public_base() . bl_lang_path($route['path'], $lang);
    $filtered = (bool) array_filter(['q', 's', 'load', 'gear', 'operator', 'opening', 'payload', 'category', 'product'], static fn($key) => bl_query($key) !== '');
    $robots = $route['status'] === 200 && bl_indexable() && !$filtered && empty($route['preview']) && $route['type'] !== 'thanks' ? 'index, follow, max-image-preview:large' : 'noindex, follow';
    $image = bl_absolute_asset(bl_share_image($route));
    echo '<title>' . bl_escape($title) . '</title>';
    echo '<meta name="description" content="' . bl_escape($route['description']) . '">';
    echo '<meta name="robots" content="' . $robots . '">';
    if ($route['status'] === 200 && empty($route['preview'])) echo '<link rel="canonical" href="' . bl_escape($canonical) . '">';
    foreach (['og:type' => in_array($route['type'], ['article', 'application_case'], true) ? 'article' : 'website', 'og:title' => $title, 'og:description' => $route['description'], 'og:url' => $canonical, 'og:image' => $image, 'og:site_name' => 'BOB-LIFT Loader', 'og:locale' => BL_I18N_LANGUAGES[$lang]['locale']] as $key => $value) echo '<meta property="' . $key . '" content="' . bl_escape($value) . '">';
    // The page in every language it has, both ways, English as the default (only for pages translated at least once).
    $alternates = array_filter(bl_i18n_alternates($route['path']));
    if ($route['status'] === 200 && empty($route['preview']) && count($alternates) > 1) {
        foreach ($alternates as $code => $path) echo '<link rel="alternate" hreflang="' . BL_I18N_LANGUAGES[$code]['hreflang'] . '" href="' . bl_escape(bl_public_base() . $path) . '">';
        echo '<link rel="alternate" hreflang="x-default" href="' . bl_escape(bl_public_base() . $alternates['en']) . '">';
        foreach ($alternates as $code => $path) if ($code !== $lang) echo '<meta property="og:locale:alternate" content="' . BL_I18N_LANGUAGES[$code]['locale'] . '">';
    }
    echo '<meta name="twitter:card" content="summary_large_image">';
    echo '<meta name="twitter:title" content="' . bl_escape($title) . '">';
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
