<?php
/** Public consumers share the resolved collection; WordPress boundaries run in memory. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
$GLOBALS['options']['bl_content_applications'] = ['_bl_schema_version' => 1, 'jobs' => [
    'landscaping' => ['hidden' => '1'],
    'building-sites' => ['order' => '0', 'title' => 'Renamed Site'],
    'road-work' => ['title' => 'Road Work', 'subtitle' => 'ROAD WORK', 'description' => 'Road work description', 'product' => 'dumper', 'focus' => 'load', 'order' => '1', 'image' => 'https://example.test/road.webp', 'checks' => []],
]];
$GLOBALS['posts'] = [];
function bl_wp_articles() { return []; }
function bl_wp_application_cases() { return []; }
function bl_wp_preview() { return null; }
function is_404() { return $GLOBALS['wp_query']->is_404; }
function is_singular($types) { return $GLOBALS['native_page'] ?? false; }
function status_header($code) { $GLOBALS['status'] = $code; }
function remove_action(...$args) {}
function wp_json_encode($value, $flags = 0) { return json_encode($value, $flags); }
$GLOBALS['wp_query'] = new class { public $is_404 = true; public function set_404() { $this->is_404 = true; } };
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-admin.php';
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
foreach (['road-work' => 200, 'landscaping' => 404] as $slug => $status) {
    $_SERVER['REQUEST_URI'] = '/applications/' . $slug . '/';
    $GLOBALS['wp'] = (object) ['request' => 'applications/' . $slug];
    $GLOBALS['native_page'] = true;
    bl_wp_application_route();
    $route = bl_resolve_route();
    $check($route['status'] === $status && $GLOBALS['status'] === $status, 'native/virtual route status: ' . $slug);
}
$check(!in_array('/applications/landscaping/', bl_sitemap_paths(), true) && in_array('/applications/road-work/', bl_sitemap_paths(), true), 'sitemap visibility');
$sitemap = bl_sitemap_xml();
$check(str_starts_with($sitemap, '<?xml') && str_contains($sitemap, '<loc>https://example.test/applications/road-work/</loc>') && !str_contains($sitemap, '/applications/landscaping/'), 'sitemap xml');
$llms = bl_llms_txt();
$check(!str_contains($llms, '/applications/landscaping/') && str_contains($llms, '/applications/road-work/'), 'llms visibility');
foreach (['pages/applications.php', 'partials/site-search.php', 'home.php', 'pages/contact.php'] as $template) {
    ob_start();
    $allProducts = [];
    $route = ['type' => 'contact', 'record' => null];
    $_GET['application'] = 'Building sites';
    require __DIR__ . '/../templates/' . $template;
    $html = ob_get_clean();
    if ($template === 'pages/contact.php') {
        $check($applicationName === 'Renamed Site' && $selectedFamily === 'dumper', 'contact old name resolved by position');
    } else {
        $check(!str_contains($html, '/applications/landscaping/') && str_contains($html, '/applications/road-work/'), 'reader visibility: ' . $template);
        if ($template === 'home.php') $check(str_contains($html, 'bl-job-row') && !str_contains($html, 'bl-application-tile') && str_contains($html, 'bl-home-support') && str_contains($html, '/support/#after-delivery') && str_contains($html, 'Minimum order'), 'home: job list and the buying and support block');
    }
}
$_SERVER['REQUEST_URI'] = '/applications/road-work/';
$route = bl_resolve_route();
ob_start(); require __DIR__ . '/../templates/pages/application.php'; $html = ob_get_clean();
$check(str_contains($html, 'Road work description') && !str_contains($html, '/applications/landscaping/'), 'new detail rendering');
ob_start(); bl_content_screen('applications'); $admin = ob_get_clean();
$check(str_contains($admin, 'jobs][road-work][title]') && str_contains($admin, 'jobs][landscaping][hidden]') && str_contains($admin, 'new_jobs][0][slug]'), 'admin cannot manage hidden/new scenarios');
$check(bl_content_migrate('home', ['approach' => ['eyebrow' => '03 / PEOPLE BEHIND THE MACHINE']])['approach']['eyebrow'] === '04 / PEOPLE BEHIND THE MACHINE', 'home: saved stock people-section number follows the new order');
$check(bl_content_migrate('home', ['approach' => ['eyebrow' => '03 / OUR OWN WORDS']])['approach']['eyebrow'] === '03 / OUR OWN WORDS', 'home: custom people-section wording is kept');
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "application readers regression ok\n";
