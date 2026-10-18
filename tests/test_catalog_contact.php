<?php
/** A range page's first screen offers a quote (the quick enquiry, or the contact form without the script) and WhatsApp with a family message; a search has neither. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wordpress.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
function get_the_title($post) { return $post->post_title; }
function get_permalink($post) { return home_url('/products/' . $post->post_name . '/'); }
function get_the_post_thumbnail_url(...$args) { return ''; }
function get_post_thumbnail_id(...$args) { return 0; }
function apply_filters($name, $value, ...$args) { return $value; }
function wp_trim_words($text, $count = 55) { return implode(' ', array_slice(preg_split('/\s+/', trim($text)), 0, $count)); }
$GLOBALS['posts'] = [];
foreach (array_values(bl_read_data('products')) as $i => $record) {
    $id = $i + 1;
    $GLOBALS['posts'][$id] = new WP_Post($id, 'bl_product', (string) $record['slug'], (string) ($record['name'] ?? $record['model']));
    $GLOBALS['meta'][$id]['_bl_record'] = $record;
}
$render = static function (string $category): string {
    $route = ['category' => $category, 'path' => $category ? '/products/' . $category . '/' : '/products/', 'type' => 'products'];
    ob_start(); require __DIR__ . '/../templates/pages/products.php'; return ob_get_clean();
};
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

foreach (['loaders' => 'skid-steer-loaders', 'dumpers' => 'mini-dumpers'] as $page => $category) {
    $keys = [];
    foreach (bl_content_schema($page, bl_content_defaults($page)) as $section) foreach ($section['fields'] as $field) $keys[$field['key']] = $field['type'];
    $check(($keys['catalog.hero_quote'] ?? '') === 'text' && ($keys['catalog.hero_whatsapp'] ?? '') === 'text', "$page: the quote and WhatsApp fields are not editable");
    $html = $render($category);
    $check(preg_match('~<span class="bl-catalog-contact"><a class="bl-button bl-button-blue" href="[^"]*/contact/\?equipment=' . ($page === 'loaders' ? 'loader' : 'dumper') . '#enquiry" data-quick-open>Get a quote ~', $html) === 1, "$category: no quote button opening the quick enquiry, or the contact form with this family");
    $message = $page === 'loaders' ? 'mini skid steer loaders' : 'mini dumpers';
    $check(str_contains($html, '<a class="bl-catalog-wa" href="' . bl_escape(bl_company('sales_whatsapp_url')) . '?text=' . rawurlencode("Hello BOB-LIFT, I am interested in your $message.") . '" target="_blank" rel="noopener"'), "$category: WhatsApp does not carry the family message");
    $check(strpos($html, 'bl-catalog-contact') < strpos($html, 'bl-catalog-actions'), "$category: the contact row is not before the film and catalogue");
}
$check(!str_contains($render(''), 'bl-catalog-contact'), 'a search shows the contact row');
echo "Catalog contact: $checks checks passed\n";
