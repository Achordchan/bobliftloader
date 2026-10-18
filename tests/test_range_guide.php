<?php
/** A family page's buying guide: edited in WordPress, cleaned when saved, left out while empty, and linked from the section nav once filled. */
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
    $route = ['category' => $category, 'path' => '/products/' . $category . '/', 'type' => 'products'];
    ob_start(); require __DIR__ . '/../templates/pages/products.php'; return ob_get_clean();
};
// Saved before anything reads the page (bl_editable() caches it); the dumpers stay unsaved, so empty.
$GLOBALS['options']['bl_content_loaders'] = ['buying' => [
    'intro' => 'Start with the job.',
    'points' => [['title' => 'Match the size', 'text' => 'Read <a href="/support/#containers">container loading</a>.'], ['title' => '', 'text' => ''], ['title' => 'Pick the engine', 'text' => 'Petrol or diesel.']],
    'faq' => [['q' => 'What is the smallest?', 'a' => 'The BL-T360-H.', 'link_label' => 'See it', 'link_url' => '/product/bl-t360-h/'], ['q' => 'Ship?', 'a' => 'Yes.', 'link_label' => '', 'link_url' => '']],
]];
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

foreach (['loaders', 'dumpers'] as $page) {
    $keys = [];
    foreach (bl_content_schema($page, bl_content_defaults($page)) as $section) foreach ($section['fields'] as $field) $keys[$field['key']] = $field;
    $check(($keys['buying.title']['type'] ?? '') === 'heading' && ($keys['buying.points']['type'] ?? '') === 'list' && ($keys['buying.faq']['type'] ?? '') === 'list', "$page: the buying guide is not editable");
    $check(($keys['buying.points']['fields']['text']['type'] ?? '') === 'rich' && isset($keys['buying.faq']['fields']['link_url']), "$page: points take no links, or questions no link");
}

// Saving keeps links and bold in a point (wp_kses filters the rest in WordPress); a question's answer stays plain text.
$clean = bl_content_clean_all('loaders', ['buying' => ['points' => [['title' => 'Size', 'text' => 'See <a href="/support/#containers">loading</a> <strong>now</strong>']], 'faq' => [['q' => 'Q?', 'a' => 'A <b>b</b>', 'link_label' => '', 'link_url' => '']]]])['buying'];
$check(str_contains($clean['points'][0]['text'], '<a href="/support/#containers">loading</a> <strong>now</strong>'), 'a point loses its link or bold');
$check(!str_contains($clean['faq'][0]['a'], '<b>'), 'an answer keeps markup');

// Empty: no section and no nav link (the dumpers are left unsaved).
$html = $render('mini-dumpers');
$check(!str_contains($html, 'id="buying-guide"') && !str_contains($html, 'href="#buying-guide"'), 'an empty guide still shows');

// Filled: points numbered with their links, questions with an optional link, a blank row left out, the nav pointing at it,
// and the section placed after the model names and before the closing call to action (the related guides sit between, when there are any).
$html = $render('skid-steer-loaders');
$check(str_contains($html, 'id="buying-guide"') && str_contains($html, '<span class="bl-blue-text">YOUR MINI SKID STEER.</span>'), 'a filled guide does not show');
$check(str_contains($html, '<span>01</span><div><h4>Match the size</h4><p>Read <a href="/support/#containers">container loading</a>.</p>') && str_contains($html, '<span>02</span><div><h4>Pick the engine</h4>') && !str_contains($html, '<span>03</span><div><h4>'), 'points are not numbered, lose their link, or keep a blank row');
$check(substr_count($html, 'class="bl-sup-faq-item"') === 2 && str_contains($html, '/product/bl-t360-h/">See it'), 'questions or their link are missing');
$check(str_contains($html, '<p>Start with the job.</p>') && str_contains($html, '/contact/?equipment=loader#enquiry">Ask which model suits your job'), 'the intro or the enquiry link is missing');
$check(str_contains($html, 'href="#buying-guide"'), 'the section nav does not link the guide');
$check(strpos($html, 'id="model-names"') < strpos($html, 'id="buying-guide"') && strpos($html, 'id="buying-guide"') < strpos($html, 'class="bl-page-cta'), 'the guide is out of place');
echo "Range guide: $checks checks passed\n";
