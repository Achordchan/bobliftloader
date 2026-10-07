<?php
/** P4 history/body/poster regressions execute production save boundaries against in-memory WordPress doubles. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-support.php';
require __DIR__ . '/../inc/wp-product-page.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
require __DIR__ . '/../inc/wp-tracking.php';
function bl_wp_image($path) { return str_starts_with($path, 'images/') ? bl_asset($path) : $path; }
function bl_wp_articles() { return bl_read_data('news'); }
function bl_wp_inquiry_ready() { return false; }
function selected($a, $b, $echo = true) { return $a === $b ? ' selected' : ''; }

$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$capture = static function (callable $render): string { ob_start(); $render(); return ob_get_clean(); };
$GLOBALS['posts'] = [
    1 => new WP_Post(1, 'bl_product', 'old-product', 'Product'),
    2 => new WP_Post(2, 'bl_news', 'news'),
    3 => new WP_Post(3, 'bl_application', 'case', '', 'draft'),
    4 => new WP_Post(4, 'bl_page_content', 'loaders'),
];
$GLOBALS['meta'][1]['_bl_record'] = ['model' => 'OLD', 'attachments' => ['Pallet forks'], 'legacy' => 'Keep'];
$GLOBALS['meta'][2]['_bl_record'] = ['sections' => [['heading' => 'Imported heading', 'text' => 'Imported body']], 'poster' => ['image' => 'images/poster.webp', 'alt' => 'Poster', 'caption' => 'Caption', 'after' => 2]];

$types = json_decode((string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__DIR__ . '/fixtures/register-backend-p1-types.php') . ' all'), true);
foreach (['bl_product', 'bl_news', 'bl_application'] as $type) $check(in_array('revisions', $types[$type], true) === ($type === 'bl_news'), "$type incorrect revision support");

// Every ordinary save snapshots the raw prior value; stale requests are rejected before writing history.
foreach (array_keys(BL_CONTENT_PAGES) as $page) {
    $before = bl_content_saved_now((string) $page);
    $check(bl_content_locked((string) $page, static fn($old) => bl_content_prepare((string) $page, $old, []), bl_content_revision($before)) === true, "$page save failed");
    $history = get_option('bl_content_snapshots_' . $page);
    $check($history[0]['content'] === $before && $history[0]['user_id'] === 7 && $history[0]['changes'] === 0 && strtotime($history[0]['saved_at']) !== false, "$page snapshot incomplete");
}
$saveHome = static fn($label) => bl_content_locked('home', static fn($old) => bl_content_prepare('home', $old, ['hero' => ['eyebrow' => $label]]));
$saveHome('Version A'); $saveHome('Version B'); $saveHome('Version C');
$history = get_option('bl_content_snapshots_home');
$restoreId = $history[1]['id'];
$beforeRestore = get_option('bl_content_home');
$_POST = ['bl_page' => 'home', 'bl_content_revision' => bl_content_revision($beforeRestore), 'bl_snapshot' => $restoreId];
try { bl_content_handle_submission('restore'); } catch (TestRedirect $redirect) {}
$check(bl_page_content_current('home')['hero']['eyebrow'] === 'Version A', 'restore ignored selected snapshot ID');
$check(get_option('bl_content_snapshots_home')[0]['content'] === $beforeRestore, 'restore did not snapshot current content');
$check(get_option('bl_content_snapshots_home')[0]['changes'] === 1, 'leaf change count incorrect');
$staleHistory = get_option('bl_content_snapshots_home');
try { bl_content_handle_submission('restore'); } catch (RuntimeException $error) { $check(str_contains($error->getMessage(), '版本已过期'), 'stale restore wrong error'); }
$check(get_option('bl_content_snapshots_home') === $staleHistory, 'stale restore changed history');
$_POST['bl_content_revision'] = bl_content_revision(get_option('bl_content_home'));
$_POST['bl_snapshot'] = 'missing';
try { bl_content_handle_submission('restore'); } catch (RuntimeException $error) { $check(str_contains($error->getMessage(), '没有可恢复'), 'missing history wrong error'); }
for ($i = 0; $i < 13; $i++) $saveHome('Version ' . $i);
$check(count(get_option('bl_content_snapshots_home')) === 10, 'history limit is not ten');
$historyHtml = $capture(static fn() => bl_content_history_screen('home', bl_content_revision(get_option('bl_content_home'))));
$check(str_contains($historyHtml, '<details') && str_contains($historyHtml, '历史版本') && str_contains($historyHtml, 'Editor 7') && str_contains($historyHtml, '<td>1 处</td>') && substr_count($historyHtml, 'name="bl_snapshot"') === 10, 'history UI missing operator/count/individual restore controls');
$before = get_option('bl_content_home'); $history = get_option('bl_content_snapshots_home');
$GLOBALS['fail_option'] = 'bl_content_home';
$check(is_wp_error($saveHome('Fail')) && get_option('bl_content_home') === $before && get_option('bl_content_snapshots_home')[0]['content'] === $before, 'failed write changed content or omitted its before-image');
unset($GLOBALS['fail_option']);
$_POST = [];

// REST changes to an empty body take ownership; metadata-only requests do not. Clients cannot unset ownership.
$rest = static function (array $params): WP_REST_Request {
    $request = new WP_REST_Request(['id' => 2] + $params);
    // Like core: only a string (or content.raw) becomes post_content on the prepared post.
    $content = $params['content'] ?? null;
    $body = is_string($content) ? $content : (is_array($content) && is_string($content['raw'] ?? null) ? $content['raw'] : null);
    $result = run_filter('rest_pre_insert_bl_news', (object) ($body === null ? [] : ['post_content' => $body]), $request);
    if (is_wp_error($result)) throw new RuntimeException($result->get_error_message());
    $data = run_filter('wp_insert_post_data', ['post_type' => 'bl_news', 'post_content' => $body ?? get_post(2)->post_content], (array) $result);
    foreach ($data as $key => $value) $GLOBALS['posts'][2]->$key = $value;
    if (isset($request->params['meta']['_bl_record'])) update_post_meta(2, '_bl_record', $request->params['meta']['_bl_record']);
    return $request;
};
$oldNews = get_post_meta(2, '_bl_record', true);
$rest(['meta' => ['_bl_record' => ['caption' => 'Changed']]]);
$check(empty(get_post_meta(2, '_bl_record', true)['_bl_body_managed']), 'metadata edit claimed news body');
$rest(['content' => ['rendered' => 'ignored']]);
$check(!bl_news_body_managed(get_post(2), get_post_meta(2, '_bl_record', true)), 'a content object without raw claimed the news body');
$rest(['content' => '']);
$owned = get_post_meta(2, '_bl_record', true);
$check(bl_news_body_managed(get_post(2), $owned) && $owned['sections'] === $oldNews['sections'], 'empty REST body did not take over or erased legacy sections');
$rest(['meta' => ['_bl_record' => ['_bl_body_managed' => false, 'poster' => ['image' => '']]]]);
$record = get_post_meta(2, '_bl_record', true);
$check(bl_news_body_managed(get_post(2), $record) && $record['poster']['image'] === '' && $record['poster']['after'] === 2, 'poster removal or read-only ownership failed');
$rest(['meta' => ['_bl_record' => ['poster' => ['image' => 'https://example.test/new.jpg', 'alt' => 'New', 'caption' => 'Caption']]]]);
$check(get_post_meta(2, '_bl_record', true)['poster']['image'] === 'https://example.test/new.jpg', 'REST poster replacement failed');
$posterHtml = $capture(static fn() => bl_news_image_box(get_post(2)));
$check(str_contains($posterHtml, '正文海报') && str_contains($posterHtml, '<img src="https://example.test/new.jpg"') && str_contains($posterHtml, 'bl_record[poster][image]') && str_contains($posterHtml, '移除图片'), 'poster preview/media/removal controls missing');
$GLOBALS['meta'][2]['_bl_record'] = $oldNews;
$_POST = ['bl_record_nonce' => 'bl_save_record', 'content' => '', 'bl_record' => ['poster' => ['image' => '']]];
run_hook('save_post', 2);
$check(bl_news_body_managed(get_post(2), get_post_meta(2, '_bl_record', true)) && get_post_meta(2, '_bl_record', true)['poster']['image'] === '', 'classic news save failed ownership/removal');
$_POST = [];

// Exercise the real template: only the new ownership marker disables imported text.
$renderArticle = static function (bool $managed) use ($oldNews): string {
    $article = array_replace(bl_read_data('news')[0], $oldNews, ['content_html' => '', '_bl_body_managed' => $managed]);
    $route = ['record' => $article];
    ob_start(); require __DIR__ . '/../templates/pages/article.php'; return ob_get_clean();
};
$check(str_contains($renderArticle(false), 'Imported body'), 'untouched import no longer renders legacy body');
$check(!str_contains($renderArticle(true), 'Imported body') && str_contains($renderArticle(true), 'images/poster.webp'), 'owned empty body fell back or lost retained poster');

// Structured edits retain their own before-images, without creating WordPress revisions.
foreach ([1, 2, 3] as $id) {
    unset($GLOBALS['bl_record_history_entry']); // each save below is its own request
    $original = (array) get_post_meta($id, '_bl_record', true);
    $next = array_replace($original, ['source' => 'https://example.test/history']);
    $check(bl_record_locked($id, static fn() => $next) === true, 'record save failed');
    $history = get_post_meta($id, '_bl_record_history', true);
    $check($history[0]['record'] === $original && $history[0]['user_id'] === 7 && $history[0]['changes'] === 1 && strtotime($history[0]['saved_at']) !== false, 'record history incomplete');
    $check(bl_record_history_restore($id, $history[0]['id']) === true, 'record restore failed');
    $check(get_post_meta($id, '_bl_record', true) === $original, 'record restore changed editorial values');
    $check(get_post_meta($id, '_bl_record_history', true)[0]['record'] === $next, 'restore did not back up current record');
}
// One product form saves through several boxes in one request: one history entry, holding the record before the first.
unset($GLOBALS['bl_record_history_entry']);
$_POST['bl_product_complete'] = '1';
$before = (array) get_post_meta(1, '_bl_record', true);
$count = count((array) get_post_meta(1, '_bl_record_history', true));
bl_record_locked(1, static fn(array $r) => array_replace($r, ['source' => 'https://example.test/one']));
bl_record_locked(1, static fn(array $r) => array_replace($r, ['spec_source' => 'Box two']));
bl_record_locked(1, static fn(array $r) => array_replace($r, ['caption' => 'Box three']));
$history = get_post_meta(1, '_bl_record_history', true);
$check(count($history) === min(10, $count + 1) && $history[0]['record'] === $before && $history[0]['changes'] >= 2, 'one submission took several history entries or lost its before-image');
unset($GLOBALS['bl_record_history_entry'], $_POST['bl_product_complete']);
echo "backend P4 history ok ($checks checks)\n";
