<?php
/**
 * Product and range pages list the stories about them, at most three, never a group story or a story about the other
 * family. On a product page, guides whose text links to the model come first, the most specific first; then stories
 * naming it (fair reports among them), the rest of its family and company news, newest first. A range page lists its
 * family's stories, newest first.
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
        $story('round-up', '2026-10-12', ['loader-a', 'loader-b'], ['content_html' => '<p><a href="' . bl_url('/product/loader-a/') . '">A</a> <a href=\'/product/loader-b\'>B</a> <a href="/news/x/">x</a></p>']),
        $story('a-specific', '2026-10-11', ['loader-a'], ['content_html' => '<a href="/product/loader-a/#specs">A</a> and <a href="/product/loader-a/">again</a>']),
        $story('a-fair', '2026-10-10', ['loader-a'], ['content_html' => '<a href="/product/loader-a/">A</a>', 'event' => ['start' => '2026-10-15']]),
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
$check($slugs(bl_related_articles(['loader-a'], 'skid-steer-loaders')) === 'a-specific,round-up,a-fair', 'loader-a page: ' . $slugs(bl_related_articles(['loader-a'], 'skid-steer-loaders')));
$check($slugs(bl_related_articles(['loader-a'], 'skid-steer-loaders', 10)) === 'a-specific,round-up,a-fair,a-guide,a-older,b-newest,both-older,company', 'loader-a page in full: ' . $slugs(bl_related_articles(['loader-a'], 'skid-steer-loaders', 10)));
$check($slugs(bl_related_articles(['dumper-a'], 'mini-dumpers', 10)) === 'dumper,both-older,company', 'dumper page: ' . $slugs(bl_related_articles(['dumper-a'], 'mini-dumpers', 10)));
$check(bl_article_product_links(['content_html' => '<a href="/product/loader-b/">B</a><a href="/product/nope/">?</a><a href="https://another-site.example/product/loader-a/">x</a><a href="//another-site.example/product/loader-a/">x</a>']) === ['loader-b' => 1], 'links to unknown pages or other sites counted');
$check(bl_article_product_links(['content_html' => '<a href="' . bl_url('/product/loader-a/') . '">A</a>']) === ['loader-a' => 1], 'an absolute link to this site was not counted');
$check($slugs(bl_related_articles([], 'skid-steer-loaders')) === 'round-up,a-specific,a-fair', 'loader range: ' . $slugs(bl_related_articles([], 'skid-steer-loaders')));
$check($slugs(bl_related_articles([], 'mini-dumpers', 5)) === 'dumper,both-older', 'dumper range: ' . $slugs(bl_related_articles([], 'mini-dumpers', 5)));
ob_start(); $guides = bl_related_articles([], 'mini-dumpers'); $guideCopy = static fn(string $key): string => $key; require __DIR__ . '/../templates/partials/related-guides.php'; $html = (string) ob_get_clean();
$check(substr_count($html, 'class="bl-news-card"') === 2 && str_contains($html, '<h3><a href') && !str_contains($html, '<h2><a href'), 'section markup');
ob_start(); $guides = []; require __DIR__ . '/../templates/partials/related-guides.php'; $check(trim((string) ob_get_clean()) === '', 'empty section still printed');
if ($failures) exit(1);
echo "related guides: ok\n";
