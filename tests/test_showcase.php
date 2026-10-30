<?php
declare(strict_types=1);
// php tests/test_showcase.php — the BL-T360-H / BL-T460-H 3D showcase at /bl-t360-t460/: route, sitemap, the parts the theme
// writes from the product records, the built markup in English, and the way in from the two product pages.
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$route = bl_resolve_route('/bl-t360-t460/');
$check($route['type'] === 'showcase' && $route['status'] === 200, 'route: ' . $route['type'] . ' ' . $route['status']);
$check(in_array('/bl-t360-t460/', bl_sitemap_paths(), true), 'the showcase is not in the sitemap');
ob_start(); require __DIR__ . '/../templates/pages/sitemap.php'; $sitemapHtml = (string) ob_get_clean();
$check(str_contains($sitemapHtml, 'BL-T360-H &amp; BL-T460-H in 3D') || str_contains($sitemapHtml, 'BL-T360-H & BL-T460-H in 3D'), 'the showcase is not in the HTML sitemap');
$check(str_ends_with(bl_share_image($route), 'bl360/assets/details/share.jpg'), 'share image: ' . bl_share_image($route));

$products = bl_showcase_products();
$check(array_column($products, 'model') === ['BL-T360-H', 'BL-T460-H'], 'showcase products: ' . json_encode(array_column($products, 'model')));

// The table follows the records: a changed specification shows on the page, equal values span both columns.
$changed = $products;
$changed[1]['specs']['Machine width'] = '1,234 mm';
$specs = bl_showcase_specs_html($changed);
$check(str_contains($specs, '<td>1,234 mm</td>'), 'the table does not follow the product record');
$check(str_contains($specs, '<td colspan="2">' . bl_escape($products[0]['specs']['Bucket capacity']) . '</td>'), 'equal values do not span both columns');
$check(str_contains($specs, 'href="/product/bl-t360-h-mini-skid-steer-loader/"') && str_contains($specs, 'href="/product/bl-t460-h-mini-skid-steer-loader/"'), 'the table heads do not link the product pages');
$contact = bl_showcase_contact_html($products);
$check(str_contains($contact, '/contact/?product=bl-t360-h-mini-skid-steer-loader#enquiry') && str_contains($contact, bl_company('email')), 'contact section');

// The built markup: English, every asset from the theme folder, no language menu, the two theme sections filled in.
$body = bl_showcase_markup('body.html', $products);
$head = bl_showcase_markup('head.html');
$check(!preg_match('/[一-鿿]/u', (string) preg_replace('~<script\b[^>]*>.*?</script>~s', '', $body)), 'Chinese text in the page');
$check(!str_contains($body . $head, '{{'), 'a placeholder is left in the markup');
$check(!str_contains($body, 'id="lang"'), 'the language menu is still there');
$check(!preg_match('~(src|href|srcset)="assets/~', $body . $head), 'an asset address is not from the theme folder');
$check(str_contains($body, 'class="exp-footer-links"') && str_contains($body, '/privacy/') && (bl_consent_categories() ? str_contains($body, 'data-bl-consent-open') : true), 'footer links (privacy, cookie choices)');
$check(str_contains($body, 'id="specs"') && str_contains($body, 'id="contact"'), 'specs or contact section missing');
$check(str_contains($head, '"three":"/assets/bl360/assets/vendor/three/three.module.js"'), 'import map: three.js');
$check(substr_count($body . $head, '<script type="importmap">') === 1, 'more than one import map');
foreach (['assets/model/loader.glb.gz', 'assets/env/sky.hdr.gz', 'app.js', 'explore.js', 'i18n/i18n.js', 'site.css'] as $file) $check(is_file(__DIR__ . '/../assets/bl360/' . $file), 'missing assets/bl360/' . $file);
$check(!is_dir(__DIR__ . '/../assets/bl360/assets/real-motion'), 'the real-machine clips are copied again (the page uses assets/videos/)');

// The two product pages lead to the showcase; other products do not.
$render = static function (array $product): string {
    $route = ['type' => 'product', 'record' => $product, 'path' => '/product/' . $product['slug'] . '/', 'status' => 200, 'title' => $product['name'], 'description' => '', 'category' => ''];
    ob_start();
    try { require __DIR__ . '/../templates/pages/product.php'; } finally { $html = ob_get_clean(); }
    return (string) $html;
};
foreach (bl_products() as $product) {
    $count = substr_count($render($product), 'href="/bl-t360-t460/"');
    $check(bl_is_showcase_product($product) ? $count === 2 : $count === 0, $product['slug'] . ': ' . $count . ' showcase links');
}

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "showcase: ok\n";
