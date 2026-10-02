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
    $_POST = ['bl_record_nonce' => wp_create_nonce('bl_save_record'), 'bl_record' => ['model' => 'QA-PERSISTENCE', 'category' => 'skid-steer-loaders', 'source' => $original['source'], 'spec_source' => 'QA spec sheet', 'image' => $original['image'], 'gallery' => $original['gallery'], 'specs' => $specs],
        'bl_videos_nonce' => wp_create_nonce('bl_save_videos'), 'bl_videos' => ['src' => ['videos/bucket.mp4', ''], 'poster' => ['videos/bucket.webp', ''], 'caption' => ['QA clip', '']]];
    wp_update_post(['ID' => $post->ID, 'post_title' => $post->post_title]);
    $saved = get_post_meta($post->ID, '_bl_record', true);
    bl_test_assert($saved['model'] === 'QA-PERSISTENCE' && $saved['spec_source'] === 'QA spec sheet', 'Valid admin save failed');
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
    $partial->set_body_params(['meta' => ['_bl_record' => ['specs' => ['Rated load' => '900 kg'], 'spec_source' => '<i>QA brochure</i>']]]);
    bl_test_assert(rest_do_request($partial)->get_status() === 200, 'REST partial write was refused');
    $partialSaved = get_post_meta($product->ID, '_bl_record', true);
    bl_test_assert($partialSaved['model'] === 'QA-REST' && $partialSaved['gallery'] === $saved['gallery'] && $partialSaved['load'] === 900, 'REST partial write lost the fields it did not send');
    bl_test_assert($partialSaved['spec_source'] === 'QA brochure', 'REST write did not set the specification source');
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
    bl_test_assert(bl_is_generated_product_excerpt($staleExcerpt, [$staleProduct->post_title]), 'Import did not store the generated summary');
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
// The 网站内容 screens through REST: one bl_page_content item per screen; a write merges into the current content,
// is cleaned like the screen, saves only what differs, and anonymous visitors can neither read nor write it.
// Jobs added after a site was initialised get their pages on a later request; the import itself records the full list.
bl_test_assert(get_option('bl_application_pages') === bl_application_page_paths(), 'The import did not record the application pages');
foreach (bl_application_page_paths() as $path) bl_test_assert(get_page_by_path($path) instanceof WP_Post, "Missing page $path");
wp_delete_post(get_page_by_path('applications/farms-and-orchards')->ID, true);
update_option('bl_application_pages', array_slice(bl_application_page_paths(), 0, 3));
// While another request holds the lock this one leaves the pages alone; once it is free the page is made exactly once.
$heldBy = bl_lock_acquire('application_pages', 1);
bl_application_make_pages();
bl_test_assert(!get_page_by_path('applications/farms-and-orchards'), 'Pages were made while another request held the lock');
bl_lock_release('application_pages', $heldBy);
bl_application_make_pages();
bl_application_make_pages();
bl_test_assert(get_page_by_path('applications/farms-and-orchards') instanceof WP_Post && get_option('bl_application_pages') === bl_application_page_paths(), 'A new application page was not made on an initialised site');
bl_test_assert(count(get_posts(['post_type' => 'page', 'name' => 'farms-and-orchards', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids'])) === 1, 'An application page was made twice');
// A page an editor trashed for a job that was already there is not brought back.
$landscapingPage = get_page_by_path('applications/landscaping')->ID;
wp_trash_post($landscapingPage);
update_option('bl_application_pages', array_slice(bl_application_page_paths(), 0, 5));
bl_application_make_pages();
bl_test_assert(count(get_posts(['post_type' => 'page', 'name' => 'landscaping', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids'])) === 0, 'A trashed application page was published again');
wp_untrash_post($landscapingPage);
wp_publish_post($landscapingPage);
// A new job whose slug something else already holds is left alone, once, with no suffixed copies.
$applicationsPage = get_page_by_path('applications');
wp_delete_post(get_page_by_path('applications/building-sites')->ID, true);
$blocker = wp_insert_attachment(['post_title' => 'building-sites', 'post_name' => 'building-sites', 'post_parent' => $applicationsPage->ID, 'post_mime_type' => 'image/webp', 'post_status' => 'inherit']);
update_option('bl_application_pages', array_slice(bl_application_page_paths(), 0, 5));
bl_application_make_pages();
bl_application_make_pages();
bl_test_assert(get_option('bl_application_pages') === bl_application_page_paths() && !get_posts(['post_type' => 'page', 'name' => 'building-sites-2', 'post_status' => 'any', 'fields' => 'ids']), 'A slug conflict left suffixed pages or kept retrying');
wp_delete_post($blocker, true);
update_option('bl_application_pages', array_slice(bl_application_page_paths(), 0, 5));
bl_application_make_pages();
bl_test_assert(get_page_by_path('applications/building-sites') instanceof WP_Post, 'The page was not made once its slug was free');
// A trashed page for a new job (renamed to <slug>__trashed) also counts as the editor's choice.
$farmsPage = get_page_by_path('applications/farms-and-orchards')->ID;
wp_trash_post($farmsPage);
update_option('bl_application_pages', array_slice(bl_application_page_paths(), 0, 3));
bl_application_make_pages();
bl_test_assert(!get_posts(['post_type' => 'page', 'name' => 'farms-and-orchards', 'post_status' => 'any', 'fields' => 'ids']) && get_post_status($farmsPage) === 'trash', 'A trashed new application page was replaced');
wp_untrash_post($farmsPage);
wp_publish_post($farmsPage);
$contentItem = get_page_by_path('loaders', OBJECT, 'bl_page_content');
bl_test_assert($contentItem instanceof WP_Post && count(get_posts(['post_type' => 'bl_page_content', 'post_status' => 'any', 'numberposts' => -1])) === count(BL_CONTENT_PAGES), 'Content items were not made one per screen');
// A missing item is made again on a later request, as long as the marker was not set; a failed insert leaves it unset.
delete_option('bl_page_content_items');
wp_delete_post(get_page_by_path('home', OBJECT, 'bl_page_content')->ID, true);
add_filter('wp_insert_post_empty_content', $emptyHome = static fn($empty, $data) => $data['post_type'] === 'bl_page_content' ? true : $empty, 10, 2);
bl_page_content_make_items();
remove_filter('wp_insert_post_empty_content', $emptyHome, 10);
bl_test_assert(get_option('bl_page_content_items') === false && !get_page_by_path('home', OBJECT, 'bl_page_content'), 'A failed item insert was marked complete');
bl_page_content_make_items();
bl_test_assert(get_option('bl_page_content_items') === array_keys(BL_CONTENT_PAGES) && get_page_by_path('home', OBJECT, 'bl_page_content'), 'A missing item was not made again');
$supportItem = get_page_by_path('support', OBJECT, 'bl_page_content');
$savedOptions = ['loaders' => get_option('bl_content_loaders'), 'support' => get_option('bl_content_support')];
try {
    wp_set_current_user(1);
    $read = new WP_REST_Request('GET', '/wp/v2/bl_page_content/' . $contentItem->ID);
    $read->set_param('context', 'edit');
    $read = rest_do_request($read);
    $content = $read->get_data()['meta']['_bl_content'] ?? null;
    bl_test_assert($read->get_status() === 200 && ($content['jobs']['items'][0]['title'] ?? '') === 'Renovation and indoor work' && count($content['attachments']['items']) === 24, 'REST did not return the loader page content');
    $write = new WP_REST_Request('POST', '/wp/v2/bl_page_content/' . $contentItem->ID);
    $write->set_body_params(['meta' => ['_bl_content' => ['jobs' => ['items' => [['title' => '<b>QA job</b>', 'text' => 'QA text', 'tools' => 'Auger, Sweeper']]], 'attachments' => ['note' => 'QA note']]]]);
    bl_test_assert(rest_do_request($write)->get_status() === 200, 'REST content write was refused');
    $stored = get_option('bl_content_loaders');
    bl_test_assert($stored['jobs']['items'] === [['title' => 'QA job', 'text' => 'QA text', 'tools' => 'Auger, Sweeper']] && $stored['attachments'] === ['note' => 'QA note'], 'REST content write was not cleaned or saved as a difference');
    bl_test_assert(!isset($stored['jobs']['eyebrow']) && get_post_meta($contentItem->ID, '_bl_content', true)['attachments']['title'] === "ONE LOADER.\n*MANY JOBS.*", 'Unsent content did not keep its defaults');
    bl_test_assert($GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->postmeta} WHERE post_id = %d AND meta_key = '_bl_content'", $contentItem->ID)) === '0', 'Content was stored on the item');
    // While another save holds the page's lock, a write waits, then gives up without saving; the lock is always released.
    $held = sprintf('%010d|qa', time());
    $GLOBALS['wpdb']->insert($GLOBALS['wpdb']->options, ['option_name' => 'bl_lock_content_loaders', 'option_value' => $held, 'autoload' => 'no']);
    $busy = new WP_REST_Request('POST', '/wp/v2/bl_page_content/' . $contentItem->ID);
    $busy->set_body_params(['meta' => ['_bl_content' => ['attachments' => ['note' => 'QA-BUSY']]]]);
    bl_test_assert(rest_do_request($busy)->get_status() === 409 && get_option('bl_content_loaders')['attachments']['note'] === 'QA note', 'A write went through while the page was locked');
    // Releasing needs the owner's token: another request's release leaves this lock in place.
    bl_lock_release('content_loaders', 'not-the-owner');
    bl_test_assert(bl_lock_owned('content_loaders', $held), 'A lock was released by a request that does not own it');
    // A lock older than five minutes is taken over.
    $GLOBALS['wpdb']->update($GLOBALS['wpdb']->options, ['option_value' => sprintf('%010d|qa', time() - 301)], ['option_name' => 'bl_lock_content_loaders']);
    bl_test_assert(rest_do_request($busy)->get_status() === 200 && get_option('bl_content_loaders')['attachments']['note'] === 'QA-BUSY' && $GLOBALS['wpdb']->get_var("SELECT COUNT(*) FROM {$GLOBALS['wpdb']->options} WHERE option_name IN ('bl_lock_content_loaders', 'bl_lock_page_content_items')") === '0', 'The lock was not released after a save');
    $rename = new WP_REST_Request('POST', '/wp/v2/bl_page_content/' . $contentItem->ID);
    $rename->set_body_params(['slug' => 'loader-content', 'status' => 'publish']);
    rest_do_request($rename);
    $renamed = get_post($contentItem->ID);
    bl_test_assert($renamed->post_name === 'loaders' && $renamed->post_status === 'private', 'A content item lost its slug or became public');
    $faq = new WP_REST_Request('POST', '/wp/v2/bl_page_content/' . $supportItem->ID);
    $faq->set_body_params(['meta' => ['_bl_content' => ['faq' => ['intro' => 'QA intro']]]]);
    rest_do_request($faq);
    bl_test_assert(['intro' => 'QA intro'] + (is_array($savedOptions['support']) ? (array) ($savedOptions['support']['faq'] ?? []) : []) == get_option('bl_content_support')['faq'], 'Support FAQ write did not merge');
    wp_set_current_user(0);
    $anonymous = rest_do_request(new WP_REST_Request('GET', '/wp/v2/bl_page_content/' . $contentItem->ID));
    bl_test_assert(!isset($anonymous->get_data()['meta']['_bl_content']) && rest_do_request(new WP_REST_Request('GET', '/wp/v2/bl_page_content'))->get_data() === [], 'Anonymous REST read exposed page content');
    $write->set_body_params(['meta' => ['_bl_content' => ['attachments' => ['note' => 'QA-ANON']]]]);
    rest_do_request($write);
    bl_test_assert(get_option('bl_content_loaders')['attachments']['note'] === 'QA-BUSY', 'Anonymous REST content write was accepted');
} finally {
    foreach ($savedOptions as $page => $value) is_array($value) ? update_option('bl_content_' . $page, $value) : delete_option('bl_content_' . $page);
    wp_set_current_user(0);
}
$report = ['wordpress' => get_bloginfo('version'), 'php' => PHP_VERSION, 'theme' => get_stylesheet(), 'import' => $firstImport, 'idempotent_import' => true, 'admin_save_and_frontend_read' => true, 'news_editor_read' => true, 'product_summary_follows_specs' => true, 'nonce_rejection' => true, 'anonymous_rejection' => true, 'draft_visibility' => true, 'rest_record' => true, 'rest_page_content' => true, 'restored' => true, 'published_products' => $productCount, 'published_news' => $newsCount];
file_put_contents('/wordpress/wp-content/bl-verification.json', wp_json_encode($report));
echo wp_json_encode($report);
