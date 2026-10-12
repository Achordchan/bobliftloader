<?php
/**
 * News list: pinned stories first, the product filter files each story under the families of the products it names,
 * the section tabs and the product chips keep each other's choice, and the layout switch is in the page.
 * Run: php tests/test_news_list.php
 */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
function bl_wp_products() {
    $make = static fn(string $slug, string $category) => ['slug' => $slug, 'model' => strtoupper($slug), 'name' => $slug, 'category' => $category, 'image' => '', 'gallery' => [], 'excerpt' => '', 'load' => 0, 'specs' => []];
    return [$make('loader-a', 'skid-steer-loaders'), $make('dumper-a', 'mini-dumpers')];
}
function bl_wp_articles() {
    $story = static fn(string $slug, string $date, string $category, array $products, array $extra = []) => $extra + ['slug' => $slug, 'title' => 'Story ' . $slug, 'url' => '/news/' . $slug . '/', 'date' => $date, 'excerpt' => 'Excerpt ' . $slug, 'image' => '/img/' . $slug . '.jpg', 'category' => $category, 'line' => 'compact', 'products' => $products, 'event' => []];
    $stories = [
        $story('loader-new', '2026-10-10', 'product-news', ['loader-a']),
        $story('dumper-new', '2026-10-09', 'customer-stories', ['dumper-a']),
        $story('both', '2026-10-08', 'product-news', ['loader-a', 'dumper-a']),
        $story('company-old', '2026-10-01', '', [], ['line' => 'group']),
        $story('pinned-old', '2026-09-01', 'industry-news', [], ['pinned' => true]),
    ];
    // With no dumper story left, only the loaders chip could narrow the list.
    return empty($GLOBALS['bl_test_no_dumpers']) ? $stories : array_values(array_filter($stories, static fn($a) => !in_array('dumper-a', $a['products'], true)));
}
function get_permalink($post) { return '/'; }

$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$render = static function (array $query): string {
    $_GET = $query;
    ob_start();
    require __DIR__ . '/../templates/pages/news.php';
    return (string) ob_get_clean();
};
$slugs = static fn(string $html): array => array_map(static fn($m) => $m, preg_match_all('~href="/news/([a-z-]+)/"~', $html, $found) ? array_values(array_unique($found[1])) : []);

$check(bl_news_families(['products' => ['loader-a']]) === ['skid-steer-loaders'], 'loader story not filed under loaders');
$check(bl_news_families(['products' => ['loader-a', 'dumper-a']]) === ['skid-steer-loaders', 'mini-dumpers'], 'story naming both families not filed under both');
$check(bl_news_families(['products' => []]) === [] && bl_news_families(['products' => ['unknown']]) === [], 'story with no known product is filed under a machine');
$check(array_column(bl_news_order(bl_wp_articles()), 'slug') === ['pinned-old', 'loader-new', 'dumper-new', 'both', 'company-old'], 'pinned story not first, or the rest lost their order');

// Exhibition news is a stored section of its own, ahead of Customer stories and Guides in the tabs.
$check(bl_news_section(['category' => 'exhibitions']) === 'exhibitions' && bl_news_label(['category' => 'exhibitions']) === 'Exhibition news', 'exhibitions section missing');
$check(array_keys(BL_NEWS_SECTIONS) === ['product-news', 'exhibitions', 'customer-stories', 'industry-news', 'company'], 'section order');

$all = $render([]);
$check($slugs($all) === ['pinned-old', 'loader-new', 'dumper-new', 'both', 'company-old'], 'All list order: ' . implode(',', $slugs($all)));
$check(substr_count($all, 'class="bl-news-pin"') === 1, 'pinned mark missing or repeated');
$check(str_contains($all, 'data-bl-news-view') && str_contains($all, 'data-view="list"') && str_contains($all, 'data-view="grid"'), 'layout switch missing');
$check(str_contains($all, '<ol class="bl-news-list" data-view="grid">'), 'news list should open as a grid');
$check(str_contains($all, 'aria-label="Filter by machine"'), 'product filter missing');

$loaders = $render(['product' => 'skid-steer-loaders']);
$check($slugs($loaders) === ['loader-new', 'both'], 'loaders filter: ' . implode(',', $slugs($loaders)));
$dumpers = $render(['product' => 'mini-dumpers']);
$check($slugs($dumpers) === ['dumper-new', 'both'], 'dumpers filter: ' . implode(',', $slugs($dumpers)));
// Company news is a tab (every story without a section), not a machine; the old product=company link falls back to everything.
$company = $render(['category' => 'company']);
$check($slugs($company) === ['company-old'], 'company tab: ' . implode(',', $slugs($company)));
$check($slugs($render(['product' => 'company'])) === $slugs($all), 'product=company is not ignored');
$tabCounts = array_sum(array_map('intval', preg_match_all('~class="bl-category-nav bl-news-tabs".*?</nav>~s', $all, $nav) ? (preg_match_all('~<a [^>]*>(?!All)[^<]*<small>(\d+)</small>~', $nav[0][0], $n) ? $n[1] : []) : []));
$check($tabCounts === count($slugs($all)), 'section tabs add up to ' . count($slugs($all)) . ', got ' . $tabCounts);

// The two filters combine, and each link keeps the other's choice.
$both = $render(['product' => 'skid-steer-loaders', 'category' => 'product-news']);
$check($slugs($both) === ['loader-new', 'both'], 'combined filter: ' . implode(',', $slugs($both)));
$check(str_contains($loaders, 'category=product-news&amp;product=skid-steer-loaders'), 'section tab drops the product choice');
$check(str_contains($both, 'href="/news/?category=product-news"') || str_contains($both, 'news/?category=product-news"'), 'product All chip drops the section choice');

// A stray value falls back to everything; a product chip with no story under the chosen section is not shown.
$check($slugs($render(['product' => 'cranes'])) === $slugs($all), 'unknown product value is not ignored');
$stories = $render(['category' => 'customer-stories']);
$check(!str_contains($stories, 'product=skid-steer-loaders'), 'chip with no story shown');

// A bookmarked section + product pair with no story left keeps both choices and shows the empty list, not every section.
$none = $render(['product' => 'mini-dumpers', 'category' => 'industry-news']);
$check($slugs($none) === [], 'empty combination listed stories: ' . implode(',', $slugs($none)));
$check(str_contains($none, 'aria-current="page">Guides'), 'section dropped when no story matches the product');
$check(str_contains($none, 'category=industry-news&amp;product=mini-dumpers') && str_contains($none, '?product=mini-dumpers'), 'a choice dropped from the links');
$check(str_contains($none, 'bl-empty'), 'empty combination shows no empty message');

// One machine chip still shows when it narrows the list (the loaders story beside stories naming no machine).
$GLOBALS['bl_test_no_dumpers'] = true;
$single = $render([]);
$check(str_contains($single, 'aria-label="Filter by machine"') && !str_contains($single, 'product=mini-dumpers'), 'single machine chip hidden although it narrows the list');
$GLOBALS['bl_test_no_dumpers'] = false;

if ($failures) { fwrite(STDERR, "$failures failure(s)\n"); exit(1); }
echo "news list ok\n";
