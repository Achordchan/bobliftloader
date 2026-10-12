<?php
declare(strict_types=1);

/** Resolve an allowlisted page template; request paths never become file paths. */
function bl_resolve_route(): array
{
    $rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $basePath = bl_is_wordpress() ? rtrim((string) parse_url(home_url('/'), PHP_URL_PATH), '/') : '';
    $path = $basePath && str_starts_with($rawPath, $basePath . '/') ? substr($rawPath, strlen($basePath)) : $rawPath;
    $path = '/' . trim(rawurldecode($path), '/') . '/';
    if ($path === '//') $path = '/';
    $pages = [
        '/' => ['home', bl_editable('home')['seo']['title'], bl_editable('home')['seo']['description']],
        '/about/' => ['about', bl_editable('about')['seo']['title'], bl_editable('about')['seo']['description']],
        '/products/' => ['products', 'Skid Steer Loaders & Mini Dumpers', 'Explore BOB-LIFT compact equipment. Compare skid steer loader and mini dumper specifications side by side, then discuss your configuration with our team.'],
        '/products/skid-steer-loaders/' => ['products', bl_editable('loaders')['seo']['title'], bl_editable('loaders')['seo']['description']],
        '/products/mini-dumpers/' => ['products', bl_editable('dumpers')['seo']['title'], bl_editable('dumpers')['seo']['description']],
        '/applications/' => ['applications', bl_editable('applications')['seo']['title'], bl_editable('applications')['seo']['description']],
        '/support/' => ['support', bl_editable('support')['seo']['title'], bl_editable('support')['seo']['description']],
        '/news/' => ['news', bl_editable('news')['seo']['title'], bl_editable('news')['seo']['description']],
        '/contact/' => ['contact', bl_editable('contact')['seo']['title'], bl_editable('contact')['seo']['description']],
        '/thank-you/' => ['thanks', bl_editable('thanks')['seo']['title'], bl_editable('thanks')['seo']['description']],
        '/privacy/' => ['privacy', bl_editable('privacy')['seo']['title'], bl_editable('privacy')['seo']['description']],
    ];
    $route = ['type' => '404', 'title' => 'Page not found', 'description' => 'The requested page could not be found.', 'path' => $path, 'status' => 404, 'category' => '', 'record' => null];
    if (isset($pages[$path])) {
        [$type, $title, $description] = $pages[$path];
        $route = array_replace($route, compact('type', 'title', 'description'), ['status' => 200]);
        if ($type === 'products' && $path !== '/products/') $route['category'] = explode('/', trim($path, '/'))[1];
    } elseif (preg_match('~^/product/([a-z0-9-]+)/$~', $path, $matches)) {
        $product = bl_find_record(bl_products(), $matches[1]);
        if ($product) $route = array_replace($route, ['type' => 'product', 'title' => bl_product_page_title($product), 'description' => bl_product_description($product), 'record' => $product, 'status' => 200]);
    } elseif (preg_match('~^/news/([a-z0-9-]+)/$~', $path, $matches)) {
        $article = bl_find_record(bl_articles(), $matches[1]);
        if ($article) $route = array_replace($route, ['type' => 'article', 'title' => $article['title'], 'description' => $article['excerpt'], 'record' => $article, 'status' => 200]);
    } elseif (preg_match('~^/application/([^/]+)/$~', $path, $matches)) {
        // WordPress slugs can hold non-ASCII letters (stored percent-encoded) and underscores; the request path is already decoded.
        $case = null;
        foreach (bl_application_cases() as $candidate) if (rawurldecode($candidate['slug']) === $matches[1]) { $case = $candidate; break; }
        if ($case) {
            $case['content_html'] = bl_application_case_content($case);
            $route = array_replace($route, ['type' => 'application_case', 'title' => $case['title'], 'description' => $case['excerpt'], 'record' => $case, 'status' => 200]);
        }
    } elseif (preg_match('~^/applications/([a-z0-9-]+)/$~', $path, $matches)) {
        global $applications;
        foreach ($applications as $application) {
            if (bl_application_slug($application) === $matches[1]) $route = array_replace($route, ['type' => 'application', 'title' => $application['title'] . ' Equipment', 'description' => $application['description'], 'record' => $application, 'status' => 200]);
        }
    }
    // Scenario paths are owned by the option, including hidden records and virtual pages.
    if (preg_match('~^/applications/([a-z0-9-]+)/$~', $path, $matches) && isset(bl_editable('applications')['jobs'][$matches[1]])) {
        return $route;
    }
    // An editor's preview (draft, or unsaved changes to a published post) arrives at the site root with the post id.
    if (bl_is_wordpress() && ($preview = bl_wp_preview())) {
        $route = array_replace($route, ['type' => $preview['type'], 'title' => $preview['title'], 'description' => $preview['type'] === 'product' ? bl_product_description($preview['record']) : $preview['record']['excerpt'], 'record' => $preview['record'], 'status' => 200, 'preview' => true, 'path' => $preview['path']]);
    }
    if (bl_is_wordpress() && !is_404() && $route['type'] === '404' && is_singular(['page', 'post'])) {
        $post = get_queried_object();
        $route = array_replace($route, ['type' => 'page', 'title' => bl_plain_title(get_the_title($post)), 'description' => $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 35), 'status' => 200]);
    }
    if (bl_is_wordpress() && is_404()) $route['status'] = 404;
    if ($route['status'] === 404) $route['type'] = '404';
    return $route;
}

function bl_indexable(): bool
{
    if (bl_is_wordpress()) return (bool) get_option('blog_public');
    return getenv('BL_ENV') === 'production';
}

function bl_public_base(): string
{
    return bl_is_wordpress() ? rtrim(home_url('/'), '/') : rtrim(getenv('BL_SITE_URL') ?: 'https://bobliftloader.com', '/');
}

function bl_sitemap_paths(): array
{
    $paths = ['/', '/about/', '/products/skid-steer-loaders/', '/products/mini-dumpers/', '/applications/', '/support/', '/news/', '/contact/', '/privacy/'];
    // Resolved here rather than read from the template's global: WordPress answers /sitemap.xml before any template runs.
    foreach (bl_apply_application_content($GLOBALS['bl_application_base'] ?? []) as $app) $paths[] = '/applications/' . bl_application_slug($app) . '/';
    foreach (bl_products() as $product) $paths[] = '/product/' . $product['slug'] . '/';
    foreach (bl_application_cases() as $case) $paths[] = '/application/' . $case['slug'] . '/';
    foreach (bl_articles() as $article) $paths[] = '/news/' . $article['slug'] . '/';
    return $paths;
}

function bl_serve_discovery(): void
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    bl_serve_llms_txt((string) $path);
    if ($path === '/robots.txt') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n" . (bl_indexable() ? "Allow: /\nSitemap: " . bl_public_base() . "/sitemap.xml\n" : "Disallow: /\n");
        exit;
    }
    bl_serve_sitemap((string) $path);
}

/** The XML sitemap of every public page; the same list in local runs and in WordPress. */
function bl_sitemap_xml(): string
{
    $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach (bl_sitemap_paths() as $page) $xml .= '<url><loc>' . bl_escape(bl_public_base() . $page) . '</loc></url>';
    return $xml . '</urlset>';
}

/**
 * Answers /sitemap.xml and stops; any other path, or a site that is not public yet, is left to the normal routing. The platform's
 * own sitemap (wp-sitemap.xml) only knows WordPress posts, not the theme's product, range and application addresses, so it is
 * switched off in functions.php and its address points here.
 */
function bl_serve_sitemap(string $path): void
{
    if ($path !== '/sitemap.xml' || !bl_indexable()) return;
    if (bl_is_wordpress()) status_header(200);
    header('Content-Type: application/xml; charset=utf-8');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=3600');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo bl_sitemap_xml();
    exit;
}

/** Resolve virtual scenario URLs before canonical redirects; no post or rewrite-rule writes are needed. */
function bl_wp_application_route(): void
{
    $path = '/' . trim((string) ($GLOBALS['wp']->request ?? ''), '/') . '/';
    if (!preg_match('~^/applications/[a-z0-9-]+/$~', $path)) return;
    if (!isset(bl_editable('applications')['jobs'][basename($path)])) return;
    remove_action('template_redirect', 'redirect_canonical');
    remove_action('template_redirect', 'wp_old_slug_redirect');
    $visible = bl_apply_application_content($GLOBALS['bl_application_base']);
    $slug = basename($path);
    $found = false;
    foreach ($visible as $app) if (bl_application_slug($app) === $slug) $found = true;
    if ($found) {
        $GLOBALS['wp_query']->is_404 = false;
        status_header(200);
    } else {
        $GLOBALS['wp_query']->set_404();
        status_header(404);
    }
}
