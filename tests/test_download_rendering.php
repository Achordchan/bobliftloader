<?php
/** Render real templates with seed products and an in-memory WordPress option store. */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
$GLOBALS['download_test_products'] = bl_products();
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
function bl_wp_products() { return $GLOBALS['download_test_products']; }
function bl_wp_articles() { return bl_read_data('news'); }
function bl_wp_image($path) { return str_starts_with($path, 'images/') ? bl_asset($path) : $path; }
function bl_wp_inquiry_ready() { return false; }
function bl_wp_application_cases() { return []; }
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$render = static function (string $page, ?array $product = null): string {
    $route = ['record' => $product];
    ob_start(); require __DIR__ . '/../templates/pages/' . $page . '.php'; return ob_get_clean();
};
$products = bl_products();
$loader = array_values(array_filter($products, static fn($p) => $p['category'] === 'skid-steer-loaders'))[0];
$dumper = array_values(array_filter($products, static fn($p) => $p['category'] === 'mini-dumpers'))[0];
$GLOBALS['options']['bl_catalogue_url'] = 'https://example.test/original.pdf';
$GLOBALS['options']['bl_catalogue_size'] = 3145728;
$defaultSupport = $render('support');
$defaultProduct = $render('product', $loader);
$defaultThanks = $render('thanks');
$primary = bl_content_resolve('downloads', [])['items'][0];
$GLOBALS['options']['bl_downloads'] = ['items' => [$primary]];
$check($render('support') === $defaultSupport && $render('product', $loader) === $defaultProduct && $render('thanks') === $defaultThanks, 'saving inherited catalogue changed template bytes');
$check(str_contains($defaultProduct, '3.0 MB') && str_contains($defaultProduct, $primary['url']), 'legacy product catalogue output missing');
$manual = ['id' => 'manual', 'url' => 'https://example.test/instructions.zip', 'title' => 'Instructions <unsafe>', 'description' => 'Service & care', 'type' => 'manual', 'scope' => 'skid-steer-loaders', 'models' => ''];
$GLOBALS['options']['bl_downloads']['items'][] = $manual;
$support = $render('support');
$product = $render('product', $loader);
$check(str_contains($support, 'Instructions &lt;unsafe&gt;') && str_contains($support, 'Service &amp; care') && str_contains($support, $manual['url']), 'Support did not render escaped document card');
$check(str_contains($product, $manual['url']) && str_contains($product, 'Instructions &lt;unsafe&gt;') && str_contains($product, '<small>ZIP</small>'), 'product did not reuse download link');
$check(!str_contains($render('product', $dumper), $manual['url']), 'loader-only download appeared on dumper');
$check($render('thanks') === $defaultThanks, 'extra documents changed catalogue-only thanks page');
$GLOBALS['options']['bl_downloads']['items'] = [$manual, $primary];
$support = $render('support');
$check(strpos($support, $manual['url']) < strpos($support, $primary['url']), 'Support ignored document order');
$GLOBALS['options']['bl_downloads']['items'][0]['scope'] = 'models';
$GLOBALS['options']['bl_downloads']['items'][0]['models'] = $dumper['model'];
$check(str_contains($render('product', $dumper), $manual['url']) && !str_contains($render('product', $loader), $manual['url']), 'model scope did not select the specified product');
$GLOBALS['options']['bl_downloads']['items'] = [$primary];
$check($render('support') === $defaultSupport && $render('product', $loader) === $defaultProduct, 'removing extra documents failed to restore original bytes');
echo "Download templates: $checks checks passed\n";
