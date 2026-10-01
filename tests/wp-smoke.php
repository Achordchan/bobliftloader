<?php
declare(strict_types=1);
defined('BL_TEST_CONTEXT') || exit;

// Remove only the stock content from this fresh, isolated verification instance.
foreach ([['hello-world', 'post', 'Hello world!'], ['sample-page', 'page', 'Sample Page']] as [$slug, $type, $title]) {
    $sample = get_page_by_path($slug, OBJECT, $type);
    if ($sample && $sample->post_title === $title) wp_delete_post($sample->ID, true);
}

function bl_test_assert(bool $condition, string $message): void
{
    if (!$condition) throw new RuntimeException($message);
}

$firstImport = bl_import_initial_content();
$secondImport = bl_import_initial_content();
bl_test_assert($secondImport === ['products' => 0, 'news' => 0, 'pages' => 0], 'Import is not idempotent');
bl_test_assert(count(bl_wp_products()) === count(bl_read_data('products')), 'Expected every product record to be published');
$newsCount = count(bl_read_data('news'));
$productCount = count(bl_read_data('products'));
bl_test_assert(count(bl_wp_articles()) === $newsCount, 'Expected every news record to be published');
$post = get_page_by_path('bl-t1000-h-mini-skid-steer-loader', OBJECT, 'bl_product');
$original = get_post_meta($post->ID, '_bl_record', true);
try {
    wp_set_current_user(1);
    // The edit screen posts spec and video rows as parallel lists and the gallery as one field per image.
    $specs = ['key' => array_keys($original['specs']), 'value' => array_values($original['specs'])];
    $_POST = ['bl_record_nonce' => wp_create_nonce('bl_save_record'), 'bl_record' => ['model' => 'QA-PERSISTENCE', 'category' => 'skid-steer-loaders', 'source' => $original['source'], 'image' => $original['image'], 'gallery' => $original['gallery'], 'specs' => $specs],
        'bl_videos_nonce' => wp_create_nonce('bl_save_videos'), 'bl_videos' => ['src' => ['videos/bucket.mp4', ''], 'poster' => ['videos/bucket.webp', ''], 'caption' => ['QA clip', '']]];
    wp_update_post(['ID' => $post->ID, 'post_title' => $post->post_title]);
    $saved = get_post_meta($post->ID, '_bl_record', true);
    bl_test_assert($saved['model'] === 'QA-PERSISTENCE', 'Valid admin save failed');
    bl_test_assert($saved['specs'] === $original['specs'] && $saved['gallery'] === $original['gallery'] && $saved['load'] === $original['load'], 'Spec rows or gallery did not round-trip');
    bl_test_assert($saved['videos'] === [['src' => 'videos/bucket.mp4', 'poster' => 'videos/bucket.webp', 'caption' => 'QA clip']], 'Video rows were not saved');
    unset($_POST['bl_videos_nonce'], $_POST['bl_videos']);
    bl_test_assert(bl_find_record(bl_wp_products(), $post->post_name)['model'] === 'QA-PERSISTENCE', 'Frontend did not read saved data');
    $_POST['bl_record_nonce'] = 'invalid';
    $_POST['bl_record']['model'] = 'QA-NONCE-REJECT';
    wp_update_post(['ID' => $post->ID, 'post_title' => $post->post_title]);
    bl_test_assert(get_post_meta($post->ID, '_bl_record', true)['model'] === 'QA-PERSISTENCE', 'Invalid nonce was accepted');
    wp_set_current_user(0);
    $_POST['bl_record_nonce'] = wp_create_nonce('bl_save_record');
    wp_update_post(['ID' => $post->ID, 'post_title' => $post->post_title]);
    bl_test_assert(get_post_meta($post->ID, '_bl_record', true)['model'] === 'QA-PERSISTENCE', 'Anonymous save was accepted');
    $_POST = [];
    wp_set_current_user(1);
    wp_update_post(['ID' => $post->ID, 'post_status' => 'draft']);
    bl_test_assert(count(bl_wp_products()) === $productCount - 1, 'Draft product leaked into frontend');
} finally {
    $_POST = [];
    update_post_meta($post->ID, '_bl_record', $original);
    wp_update_post(['ID' => $post->ID, 'post_status' => 'publish']);
    wp_set_current_user(0);
}
bl_test_assert(count(bl_wp_products()) === $productCount, 'Product restore failed');
// The record is open to the REST API (the WordPress.com connector writes through it): editors read and write it, a
// REST write is cleaned and the derived load follows the specs, and anonymous visitors can neither read nor write it.
// rest_do_request() points the global $post at whatever it last returned, so the product is kept apart.
$product = $post;
$original = get_post_meta($product->ID, '_bl_record', true);
try {
    wp_set_current_user(1);
    $read = new WP_REST_Request('GET', '/wp/v2/bl_product/' . $product->ID);
    $read->set_param('context', 'edit');
    $read = rest_do_request($read);
    bl_test_assert($read->get_status() === 200 && ($read->get_data()['meta']['_bl_record']['specs'] ?? null) === $original['specs'], 'REST did not return the product record');
    $record = $original;
    $record['model'] = '<b>QA-REST</b>';
    $record['specs']['Rated load'] = '1,234 kg';
    $record['specs']['Hydraulic flow'] = '40 L/min & 20 MPa';
    $record['gallery'] = ['javascript:alert(1)', $original['gallery'][0] ?? 'images/a.webp'];
    $write = new WP_REST_Request('POST', '/wp/v2/bl_product/' . $product->ID);
    $write->set_body_params(['meta' => ['_bl_record' => $record]]);
    bl_test_assert(rest_do_request($write)->get_status() === 200, 'REST write was refused');
    $saved = get_post_meta($product->ID, '_bl_record', true);
    bl_test_assert($saved['model'] === 'QA-REST' && $saved['load'] === 1234 && $saved['specs']['Rated load'] === '1,234 kg', 'REST write was not cleaned or derived');
    bl_test_assert($saved['specs']['Hydraulic flow'] === '40 L/min & 20 MPa' && !in_array('javascript:alert(1)', $saved['gallery'], true), 'REST write mangled text or kept a script link');
    $partial = new WP_REST_Request('POST', '/wp/v2/bl_product/' . $product->ID);
    $partial->set_body_params(['meta' => ['_bl_record' => ['specs' => ['Rated load' => '900 kg']]]]);
    bl_test_assert(rest_do_request($partial)->get_status() === 200, 'REST partial write was refused');
    $partialSaved = get_post_meta($product->ID, '_bl_record', true);
    bl_test_assert($partialSaved['model'] === 'QA-REST' && $partialSaved['gallery'] === $saved['gallery'] && $partialSaved['load'] === 900, 'REST partial write lost the fields it did not send');
    $remove = new WP_REST_Request('POST', '/wp/v2/bl_product/' . $product->ID);
    $remove->set_body_params(['meta' => ['_bl_record' => null]]);
    bl_test_assert(rest_do_request($remove)->get_status() >= 400 && get_post_meta($product->ID, '_bl_record', true)['model'] === 'QA-REST', 'REST removed the product record');
    $story = get_page_by_path('customer-visits-boblift-factory', OBJECT, 'bl_news');
    $newsOriginal = get_post_meta($story->ID, '_bl_record', true);
    $newsWrite = new WP_REST_Request('POST', '/wp/v2/bl_news/' . $story->ID);
    $newsWrite->set_body_params(['meta' => ['_bl_record' => array_merge(array_diff_key((array) $newsOriginal, ['line' => 1]), ['products' => 'not-a-list', 'poster' => ['image' => 'images/a.webp', 'alt' => ['x']], 'event' => ['name' => 'QA', 'start' => '2026-10-15', 'source' => 'javascript:alert(1)']])]]);
    bl_test_assert(rest_do_request($newsWrite)->get_status() === 200, 'REST news write was refused');
    $newsSaved = get_post_meta($story->ID, '_bl_record', true);
    update_post_meta($story->ID, '_bl_record', $newsOriginal);
    bl_test_assert($newsSaved['products'] === [] && $newsSaved['event']['source'] === '' && $newsSaved['event']['start'] === '2026-10-15', 'REST news write kept a bad list or link');
    bl_test_assert(($newsSaved['poster'] ?? null) === ($newsOriginal['poster'] ?? null) && ($newsSaved['line'] ?? null) === ($newsOriginal['line'] ?? null), 'REST news write changed a field the edit screen does not offer');
    wp_set_current_user(0);
    $anonymous = rest_do_request(new WP_REST_Request('GET', '/wp/v2/bl_product/' . $product->ID));
    bl_test_assert(!isset($anonymous->get_data()['meta']['_bl_record']), 'Anonymous REST read exposed the record');
    $write->set_body_params(['meta' => ['_bl_record' => ['model' => 'QA-ANON']]]);
    rest_do_request($write);
    bl_test_assert(get_post_meta($product->ID, '_bl_record', true)['model'] === 'QA-REST', 'Anonymous REST write was accepted');
} finally {
    update_post_meta($product->ID, '_bl_record', $original);
    wp_set_current_user(0);
}
$staleProduct = get_page_by_path('1-ton-mini-dumper', OBJECT, 'bl_product');
$staleExcerpt = $staleProduct->post_excerpt;
$staleRecord = get_post_meta($staleProduct->ID, '_bl_record', true);
try {
    // A summary generated under the original name, on a product since renamed and given a description.
    wp_update_post(['ID' => $staleProduct->ID, 'post_title' => 'QA Renamed Dumper', 'post_content' => '<p>QA-DESCRIPTION-NOT-A-SUMMARY</p>', 'post_excerpt' => $staleRecord['name'] . ' with a 9 kW engine. Explore published specifications and discuss your application with BOB-LIFT.']);
    update_post_meta($staleProduct->ID, '_bl_record', array_replace_recursive($staleRecord, ['specs' => ['Engine power' => '13.2 kW']]));
    $fresh = bl_find_record(bl_wp_products(), '1-ton-mini-dumper');
    bl_test_assert($fresh['excerpt'] === bl_product_excerpt($fresh) && str_contains($fresh['excerpt'], '13.2 kW'), 'Generated product summary kept old figures');
    foreach (['QA-HANDWRITTEN-SUMMARY', 'Built for farm tracks. Explore published specifications and discuss your application with BOB-LIFT.'] as $handwritten) {
        wp_update_post(['ID' => $staleProduct->ID, 'post_excerpt' => $handwritten]);
        bl_test_assert(bl_find_record(bl_wp_products(), '1-ton-mini-dumper')['excerpt'] === $handwritten, 'Handwritten product summary was replaced');
    }
} finally {
    wp_update_post(['ID' => $staleProduct->ID, 'post_title' => $staleProduct->post_title, 'post_content' => $staleProduct->post_content, 'post_excerpt' => $staleExcerpt]);
    update_post_meta($staleProduct->ID, '_bl_record', $staleRecord);
}
$news = get_page_by_path('customer-visits-boblift-factory', OBJECT, 'bl_news');
$originalContent = $news->post_content;
try {
    wp_update_post(['ID' => $news->ID, 'post_content' => '<p>QA-NEWS-EDITOR-CHECK</p>']);
    $updatedNews = bl_find_record(bl_wp_articles(), $news->post_name);
    bl_test_assert(str_contains($updatedNews['content_html'], 'QA-NEWS-EDITOR-CHECK'), 'News editor changes were not read');
} finally {
    wp_update_post(['ID' => $news->ID, 'post_content' => $originalContent]);
}
$report = ['wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'theme' => get_stylesheet(), 'import' => $firstImport, 'idempotent_import' => true, 'admin_save_and_frontend_read' => true, 'news_editor_read' => true, 'product_summary_follows_specs' => true, 'nonce_rejection' => true, 'anonymous_rejection' => true, 'draft_visibility' => true, 'rest_record' => true, 'restored' => true, 'published_products' => $productCount, 'published_news' => $newsCount];
file_put_contents('/wordpress/wp-content/bl-verification.json', wp_json_encode($report));
echo wp_json_encode($report);
