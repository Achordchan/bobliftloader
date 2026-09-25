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
        '/' => ['home', 'Small size. Serious capability.', 'Explore BOB-LIFT skid steer loaders and mini dumpers. A dedicated compact equipment business website of Xuzhou BOB-LIFT Construction Machinery Co., Ltd.'],
        '/about/' => ['about', 'About BOB-LIFT Compact Equipment', 'BOB-LIFT Compact Equipment is the mini skid steer loader and mini dumper line of Xuzhou BOB-LIFT Construction Machinery Co., Ltd., built to order at its factory in Xuzhou.'],
        '/products/' => ['products', 'Skid Steer Loaders & Mini Dumpers', 'Explore BOB-LIFT compact equipment. Compare skid steer loader and mini dumper specifications side by side, then discuss your configuration with our team.'],
        '/products/skid-steer-loaders/' => ['products', 'Skid Steer Loaders', 'Compare BOB-LIFT mini skid steer loaders rated from 200 to 500 kg: stand-on, tracked and wheeled seated models with published specifications.'],
        '/products/mini-dumpers/' => ['products', 'Mini Dumpers | 0.5–5 Ton Range', 'Browse BOB-LIFT mini dumpers rated from 0.5 to 5 tonnes, including tracked and mixer models. View model specifications, bucket capacities and enquiry options.'],
        '/applications/' => ['applications', 'Compact Equipment Applications', 'Find BOB-LIFT equipment for landscaping, site preparation and material transport. Explore application requirements and relevant product families.'],
        '/support/' => ['support', 'Buying, Warranty & Support', 'How buying from BOB-LIFT works: one-unit orders, T/T payment, 8–30 working days to ship, a 12-month whole-machine warranty, spare parts, downloads and answers to common questions.'],
        '/news/' => ['news', 'BOB-LIFT News & Company Updates', 'Read company and exhibition updates from BOB-LIFT, with links to original announcements on the main BOB-LIFT website.'],
        '/contact/' => ['contact', 'Contact BOB-LIFT Compact Equipment', 'Contact BOB-LIFT for skid steer loaders, mini dumpers, specifications and quotations. Reach our Xuzhou team by email, phone or WhatsApp.'],
        '/privacy/' => ['privacy', 'Privacy & Enquiry Information', 'How this BOB-LIFT website handles enquiry information and email drafts.'],
    ];
    $route = ['type' => '404', 'title' => 'Page not found', 'description' => 'The requested page could not be found.', 'path' => $path, 'status' => 404, 'category' => '', 'record' => null];
    if (isset($pages[$path])) {
        [$type, $title, $description] = $pages[$path];
        $route = array_replace($route, compact('type', 'title', 'description'), ['status' => 200]);
        if ($type === 'products' && $path !== '/products/') $route['category'] = explode('/', trim($path, '/'))[1];
    } elseif (preg_match('~^/product/([a-z0-9-]+)/$~', $path, $matches)) {
        $product = bl_find_record(bl_products(), $matches[1]);
        if ($product) $route = array_replace($route, ['type' => 'product', 'title' => $product['name'] . ($product['model'] ? ' | ' . $product['model'] : ''), 'description' => $product['excerpt'], 'record' => $product, 'status' => 200]);
    } elseif (preg_match('~^/news/([a-z0-9-]+)/$~', $path, $matches)) {
        $article = bl_find_record(bl_articles(), $matches[1]);
        if ($article) $route = array_replace($route, ['type' => 'article', 'title' => $article['title'], 'description' => $article['excerpt'], 'record' => $article, 'status' => 200]);
    } elseif (preg_match('~^/applications/([a-z0-9-]+)/$~', $path, $matches)) {
        global $applications;
        foreach ($applications as $application) {
            if (bl_application_slug($application) === $matches[1]) $route = array_replace($route, ['type' => 'application', 'title' => $application['title'] . ' Equipment', 'description' => $application['description'], 'record' => $application, 'status' => 200]);
        }
    }
    if (bl_is_wordpress() && !is_404() && $route['type'] === '404' && is_singular(['page', 'post'])) {
        $post = get_queried_object();
        $route = array_replace($route, ['type' => 'page', 'title' => get_the_title($post), 'description' => $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 35), 'status' => 200]);
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
    $paths = ['/', '/about/', '/products/', '/products/skid-steer-loaders/', '/products/mini-dumpers/', '/applications/', '/support/', '/news/', '/contact/', '/privacy/'];
    global $applications;
    foreach ($applications as $app) $paths[] = '/applications/' . bl_application_slug($app) . '/';
    foreach (bl_products() as $product) $paths[] = '/product/' . $product['slug'] . '/';
    foreach (bl_articles() as $article) $paths[] = '/news/' . $article['slug'] . '/';
    return $paths;
}

function bl_serve_discovery(): void
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    if ($path === '/robots.txt') {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n" . (bl_indexable() ? "Allow: /\nSitemap: " . bl_public_base() . "/sitemap.xml\n" : "Disallow: /\n");
        exit;
    }
    if ($path === '/sitemap.xml') {
        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach (bl_sitemap_paths() as $page) echo '<url><loc>' . bl_escape(bl_public_base() . $page) . '</loc></url>';
        echo '</urlset>';
        exit;
    }
}
