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
$news = get_page_by_path('customer-visits-boblift-factory', OBJECT, 'bl_news');
$originalContent = $news->post_content;
try {
    wp_update_post(['ID' => $news->ID, 'post_content' => '<p>QA-NEWS-EDITOR-CHECK</p>']);
    $updatedNews = bl_find_record(bl_wp_articles(), $news->post_name);
    bl_test_assert(str_contains($updatedNews['content_html'], 'QA-NEWS-EDITOR-CHECK'), 'News editor changes were not read');
} finally {
    wp_update_post(['ID' => $news->ID, 'post_content' => $originalContent]);
}
$report = ['wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'theme' => get_stylesheet(), 'import' => $firstImport, 'idempotent_import' => true, 'admin_save_and_frontend_read' => true, 'news_editor_read' => true, 'nonce_rejection' => true, 'anonymous_rejection' => true, 'draft_visibility' => true, 'restored' => true, 'published_products' => $productCount, 'published_news' => $newsCount];
file_put_contents('/wordpress/wp-content/bl-verification.json', wp_json_encode($report));
echo wp_json_encode($report);
