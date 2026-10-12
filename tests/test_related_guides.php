<?php
/**
 * Product and range pages list the stories about them: stories naming the model first, then the rest of its family,
 * newest first, at most three, never a group story or a story about the other family.
 * Run: php tests/test_related_guides.php
 */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
function bl_wp_products() {
    $make = static fn(string $slug, string $category) => ['slug' => $slug, 'model' => strtoupper($slug), 'name' => $slug, 'category' => $category, 'image' => '', 'gallery' => [], 'excerpt' => '', 'load' => 0, 'specs' => []];
    return [$make('loader-a', 'skid-steer-loaders'), $make('loader-b', 'skid-steer-loaders'), $make('dumper-a', 'mini-dumpers')];
}
function bl_wp_articles() {
    $story = static fn(string $slug, string $date, array $products, array $extra = []) => $extra + ['slug' => $slug, 'title' => 'Story ' . $slug, 'url' => '/news/' . $slug . '/', 'date' => $date, 'excerpt' => '', 'image' => '/img/' . $slug . '.jpg', 'category' => 'industry-news', 'line' => 'compact', 'products' => $products, 'event' => []];
    return [
        $story('b-newest', '2026-10-10', ['loader-b']),
        $story('dumper', '2026-10-09', ['dumper-a']),
        $story('group', '2026-10-08', ['loader-a'], ['line' => 'group']),
        $story('a-guide', '2026-10-07', ['loader-a']),
        $story('company', '2026-10-06', []),
        $story('both-older', '2026-10-01', ['loader-b', 'dumper-a']),
        $story('a-older', '2026-09-01', ['loader-a']),
    ];
}
function get_permalink($post) { return '/'; }
$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$slugs = static fn(array $articles) => implode(',', array_column($articles, 'slug'));
$check($slugs(bl_related_articles(['loader-a'], 'skid-steer-loaders')) === 'a-guide,a-older,b-newest', 'loader-a page: ' . $slugs(bl_related_articles(['loader-a'], 'skid-steer-loaders')));
$check($slugs(bl_related_articles([], 'skid-steer-loaders')) === 'b-newest,a-guide,both-older', 'loader range: ' . $slugs(bl_related_articles([], 'skid-steer-loaders')));
$check($slugs(bl_related_articles([], 'mini-dumpers', 5)) === 'dumper,both-older', 'dumper range: ' . $slugs(bl_related_articles([], 'mini-dumpers', 5)));
ob_start(); $guides = bl_related_articles([], 'mini-dumpers'); $guideCopy = static fn(string $key): string => $key; require __DIR__ . '/../templates/partials/related-guides.php'; $html = (string) ob_get_clean();
$check(substr_count($html, 'class="bl-news-card"') === 2 && str_contains($html, '<h3><a href') && !str_contains($html, '<h2><a href'), 'section markup');
ob_start(); $guides = []; require __DIR__ . '/../templates/partials/related-guides.php'; $check(trim((string) ob_get_clean()) === '', 'empty section still printed');
if ($failures) exit(1);
echo "related guides: ok\n";
