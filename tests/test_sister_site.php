<?php
/** The crane website at the foot of a family page: after the page's own content and before the closing call to action, opened through the leave-site notice, with a line under an empty result; an empty address leaves both out. */
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
// Run again with an empty address saved (bl_editable() caches each page, so that run is a process of its own).
$empty = ($argv[1] ?? '') === 'empty';
if ($empty) $GLOBALS['options']['bl_content_global'] = ['sister' => ['url' => '']];
$render = static function (string $category): string {
    $route = ['category' => $category, 'path' => '/products/' . $category . '/', 'type' => 'products'];
    ob_start(); require __DIR__ . '/../templates/pages/products.php'; return ob_get_clean();
};
$checks = 0;
if ($empty) {
    $html = $render('skid-steer-loaders');
    if (str_contains($html, 'bl-sister') || str_contains($html, 'bl-empty-sister')) throw new RuntimeException('an empty address still shows the crane card');
    exit(0);
}
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

$keys = [];
foreach (bl_content_schema('global', bl_content_defaults('global')) as $section) foreach ($section['fields'] as $field) $keys[$field['key']] = $field['type'];
$check(($keys['sister.url'] ?? '') === 'url' && isset($keys['sister.title'], $keys['sister.text'], $keys['sister.button'], $keys['sister.empty']), 'the crane card is not editable');

foreach (['skid-steer-loaders', 'mini-dumpers'] as $category) {
    $html = $render($category);
    $link = 'href="https://www.bobliftcrane.com/" data-leave-site data-name="Cranes" data-domain="bobliftcrane.com" data-mark="C"';
    $check(substr_count($html, 'class="bl-sister bl-shell"') === 1 && str_contains($html, '<a class="bl-button bl-sister-button" ' . $link . '>Visit bobliftcrane.com'), "$category: the crane card or its notice link is missing");
    $check(str_contains($html, '<a class="bl-sister-media" ' . $link . ' tabindex="-1" aria-hidden="true">') && str_contains($html, 'images/sister/truck-mounted-crane.webp') && str_contains($html, 'images/sister/marine-crane.webp'), "$category: the crane pictures are missing");
    $check(strpos($html, 'id="buying-guide"') < strpos($html, 'class="bl-sister bl-shell"') && strpos($html, 'class="bl-sister bl-shell"') < strpos($html, 'class="bl-page-cta'), "$category: the crane card is not last before the call to action");
    $check(str_contains($html, '<p class="bl-empty-sister">Looking for cranes? <a ' . $link . '>bobliftcrane.com</a></p>'), "$category: the empty result has no crane line");
}

// No address: neither the card nor the line.
exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' empty 2>&1', $output, $status);
$check($status === 0, 'an empty address still shows the crane card: ' . implode("\n", $output));
echo "Sister site: $checks checks passed\n";
