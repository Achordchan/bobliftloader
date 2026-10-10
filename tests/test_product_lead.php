<?php
/** A product's own lead time: REST field, product terms, the product page and the edit screen. Run: php tests/test_product_lead.php */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-product-page.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
function selected($a, $b, $echo = true) { return $a === $b ? ' selected' : ''; }
function bl_wp_application_cases() { return []; }
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_product', 'machine')];

// REST: the lead time is an editable field, cleaned to a short value and unit; no value drops it.
$check(in_array('lead', BL_REST_FIELDS['bl_product'], true), 'lead is not an editable REST field');
$check(bl_rest_field('bl_product', 'lead', ['value' => ' 10 ', 'unit' => 'working days']) === ['value' => '10', 'unit' => 'working days'], 'lead value or unit not kept');
$check(bl_rest_field('bl_product', 'lead', ['value' => '<b>30</b>', 'unit' => '']) === ['value' => '30', 'unit' => ''], 'lead markup not stripped');
$check(bl_rest_field('bl_product', 'lead', ['value' => '', 'unit' => 'working days']) === [], 'a lead without a value was kept');
$check(bl_rest_field('bl_product', 'lead', 'ten days') === [], 'a lead that is not an object was kept');
$check(mb_strlen(bl_rest_field('bl_product', 'lead', ['value' => str_repeat('9', 80)])['value']) === 24, 'lead value not limited');
$GLOBALS['meta'][1]['_bl_record'] = ['model' => 'Kept', 'lead' => ['value' => '10', 'unit' => 'working days']];
$request = new WP_REST_Request(['id' => 1, 'meta' => ['_bl_record' => ['seo_description' => 'Only this changes']]]);
run_filter('rest_pre_insert_bl_product', (object) [], $request);
$check(($request->get_param('meta')['_bl_record']['lead'] ?? null) === ['value' => '10', 'unit' => 'working days'], 'a write that did not send lead changed it');
$request = new WP_REST_Request(['id' => 1, 'meta' => ['_bl_record' => ['lead' => ['value' => '30', 'unit' => 'working days']]]]);
run_filter('rest_pre_insert_bl_product', (object) [], $request);
$check(($request->get_param('meta')['_bl_record']['lead'] ?? null) === ['value' => '30', 'unit' => 'working days'], 'REST did not save lead');
$request = new WP_REST_Request(['id' => 1, 'meta' => ['_bl_record' => ['lead' => ['value' => '']]]]);
run_filter('rest_pre_insert_bl_product', (object) [], $request);
$check(($request->get_param('meta')['_bl_record']['lead'] ?? null) === [], 'an empty lead did not clear the product lead time');

// Terms: the product's lead time over the site-wide one; a missing unit keeps the site-wide unit.
$site = bl_support_terms();
$check(bl_product_terms([])['lead'] === $site['lead'], 'a product without lead changed the site-wide lead time');
$check(bl_product_terms(['lead' => []])['lead'] === $site['lead'], 'an empty lead changed the site-wide lead time');
$ten = bl_product_terms(['lead' => ['value' => '10', 'unit' => 'working days']]);
$check($ten['lead']['value'] === '10' && $ten['lead']['unit'] === 'working days' && $ten['lead']['label'] === $site['lead']['label'], 'product lead time not applied');
$check(bl_product_terms(['lead' => ['value' => '10', 'unit' => '']])['lead']['unit'] === $site['lead']['unit'], 'a missing unit lost the site-wide unit');
$check(bl_product_terms(['lead' => ['value' => '10']])['warranty'] === $site['warranty'], 'the other terms changed');

// The product page: hero and Shipping read the product's own lead time; another product keeps the site-wide one.
$render = static function (array $product): string {
    $product['excerpt'] = bl_product_excerpt($product);
    ob_start(); (static function ($route) { require __DIR__ . '/../templates/pages/product.php'; })(['record' => $product]); return ob_get_clean();
};
$base = bl_read_data('products')[0] + ['category' => 'skid-steer-loaders'];
$html = $render($base);
$check(str_contains($html, '<b>' . $site['lead']['value'] . '</b> ' . $site['lead']['unit']) && str_contains($html, 'Ships in ' . $site['lead']['value'] . ' ' . $site['lead']['unit'] . ' from order'), 'a product without lead lost the site-wide lead time');
$html = $render($base + ['lead' => ['value' => '10', 'unit' => 'working days']]);
$check(str_contains($html, '<b>10</b> working days'), 'hero did not show the product lead time');
$check(str_contains($html, 'Ships in 10 working days from order'), 'Shipping did not show the product lead time');
$check(!str_contains($html, $site['lead']['value'] . ' ' . $site['lead']['unit']) || $site['lead']['value'] === '10', 'the site-wide lead time still shows on a product with its own');

// A saved loading note that uses the lead-time variables reads the product's own lead time, like the rest of the Shipping block.
$GLOBALS['options']['bl_content_dumpers'] = ['product' => ['copy' => ['shipping_loading' => 'Loading follows a {lead_time_words} {lead_unit} build']]];
$dumper = array_values(array_filter(bl_read_data('products'), static fn(array $p): bool => $p['slug'] === '500kg-tracked-mini-dumper'))[0];
$html = $render($dumper + ['lead' => ['value' => '10', 'unit' => 'working days']]);
$check(str_contains($html, 'Ships in 10 working days from order. Loading follows a 10 working days build'), 'the loading note kept the site-wide lead time on a product with its own');

// The edit screen offers it, with what is saved.
$GLOBALS['meta'][1]['_bl_record'] = ['lead' => ['value' => '30', 'unit' => 'working days']];
ob_start(); bl_product_page_meta_box(new WP_Post(1, 'bl_product', 'machine')); $box = ob_get_clean();
$check(str_contains($box, 'name="bl_page[lead][value]"') && str_contains($box, 'value="30"') && str_contains($box, 'name="bl_page[lead][unit]"') && str_contains($box, 'value="working days"'), 'edit screen lacks the lead time fields');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "product lead time ok\n";
