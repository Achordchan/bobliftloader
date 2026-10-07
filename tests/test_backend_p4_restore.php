<?php
/** Whole-snapshot restores and failure ordering execute the production save boundaries. */
declare(strict_types=1);
require __DIR__ . '/test_backend_p4_history.php';
$checks = 0;
foreach (['support', 'applications', 'loaders'] as $page) {
    $snapshot = ['_bl_schema_version' => 1, 'hero' => ['eyebrow' => 'Historical'], 'containers' => ['loading' => ['old' => [1, 2]]], 'jobs' => ['landscaping' => ['models' => 'OLD']], 'product' => ['attachments' => 'Old tool'], 'attachments' => ['items' => [['name' => 'Old tool', '_bl_id' => 'legacy-row']]]];
    $current = ['hero' => ['eyebrow' => 'Current'], 'containers' => ['loading' => ['new' => [3, 4]]], 'jobs' => ['landscaping' => ['models' => 'NEW']]];
    $GLOBALS['options']['bl_content_' . $page] = $current;
    $GLOBALS['options']['bl_content_snapshots_' . $page] = [['id' => 'selected', 'content' => $snapshot]];
    $beforeMeta = $GLOBALS['meta'];
    $_POST = ['bl_page' => $page, 'bl_snapshot' => 'selected', 'bl_content_revision' => bl_content_revision($current)];
    try { bl_content_handle_submission('restore'); } catch (TestRedirect $redirect) {}
    $restored = get_option('bl_content_' . $page);
    unset($restored['_bl_revision']);
    $check($restored === bl_content_migrate($page, $snapshot), "$page did not restore complete migrated snapshot");
    $check(get_option('bl_content_snapshots_' . $page)[0]['content'] === $current && $GLOBALS['meta'] === $beforeMeta, "$page restore omitted backup or wrote another record");
}
$_POST = [];
$legacyRows = bl_content_defaults('loaders')['attachments']['items'];
$legacyRows[0]['_bl_id'] = ['obsolete' => 'ignored'];
$clean = bl_content_prepare('loaders', [], ['attachments' => ['items' => $legacyRows]]);
$check(!is_wp_error($clean) && !isset($clean['attachments']['items'][0]['_bl_id']), 'obsolete row ID was not ignored');
foreach ([1, 2, 3] as $id) {
    $original = ['model' => 'OLD', 'attachments' => ['Old tool'], 'products' => ['old'], '_bl_body_managed' => true];
    $current = ['model' => 'NEW', 'attachments' => [], 'products' => ['new']];
    $GLOBALS['meta'][$id]['_bl_record'] = $original;
    $check(bl_record_locked($id, static fn() => $current) === true, 'record save failed');
    $history = get_post_meta($id, '_bl_record_history', true);
    if ($id === 1) $original['model_aliases'] = ['NEW'];
    $check(bl_record_history_restore($id, $history[0]['id']) === true && get_post_meta($id, '_bl_record', true) === $original, 'restore did not replace whole record');
    $check(get_post_meta($id, '_bl_record_history', true)[0]['record'] === $current, 'restore omitted current record backup');
    $beforeHistory = get_post_meta($id, '_bl_record_history', true);
    $check(bl_record_locked($id, static fn($old) => $old) === true && get_post_meta($id, '_bl_record_history', true) === $beforeHistory, 'unchanged save created history');
    $sameId = get_post_meta($id, '_bl_record_history', true)[1]['id'];
    $check(bl_record_history_restore($id, $sameId) === true && get_post_meta($id, '_bl_record_history', true)[0]['record'] === $original && get_post_meta($id, '_bl_record_history', true)[0]['id'] !== $beforeHistory[0]['id'], 'identical explicit restore omitted backup');
}
foreach (['_bl_record_history', '_bl_record'] as $key) {
    $before = $GLOBALS['meta'];
    $GLOBALS['fail_meta_key'] = $key;
    $result = bl_record_locked(2, static fn($old) => array_replace($old, ['caption' => 'Failed write']));
    $check(is_wp_error($result) && get_post_meta(2, '_bl_record', true) === $before[2]['_bl_record'], 'failed write changed record');
    if ($key === '_bl_record_history') $check($GLOBALS['meta'] === $before, 'history failure wrote data');
    else $check(get_post_meta(2, '_bl_record_history', true)[0]['record'] === $before[2]['_bl_record'], 'record failure removed completed backup');
    unset($GLOBALS['fail_meta_key']);
}
// A record lock blocks only that record; body ownership remains part of the core write.
$token = bl_lock_acquire('record_2', 1);
$before = $GLOBALS['meta'][2];
$check(is_wp_error(bl_record_locked(2, static fn() => ['caption' => 'Busy'])) && $GLOBALS['meta'][2] === $before, 'same-record lock was bypassed');
$check(bl_record_locked(3, static fn($old) => array_replace($old, ['caption' => 'Independent'])) === true, 'unrelated record was blocked');
$request = new WP_REST_Request(['id' => 2, 'content' => '']);
$prepared = run_filter('rest_pre_insert_bl_news', (object) ['post_content' => ''], $request);
$data = run_filter('wp_insert_post_data', ['post_type' => 'bl_news', 'post_content' => ''], (array) $prepared);
$check($data['post_content_filtered'] === 'bl-body-managed' && $request->get_param('meta') === null, 'body ownership required a metadata write');
bl_lock_release('record_2', $token);
for ($i = 0; $i < 13; $i++) { unset($GLOBALS['bl_record_history_entry']); bl_record_locked(2, static fn($old) => array_replace($old, ['caption' => "Version $i"])); } // one request per save
$history = get_post_meta(2, '_bl_record_history', true);
$check(count($history) === 10 && $history[0]['record']['caption'] === 'Version 11' && $history[9]['record']['caption'] === 'Version 2', 'record history cap/order incorrect');
$before = $GLOBALS['meta'];
$check(is_wp_error(bl_record_history_restore(2, 'missing')) && $GLOBALS['meta'] === $before, 'missing snapshot wrote data');
$html = $capture(static fn() => bl_record_history_screen(get_post(2)));
$forms = $capture(static fn() => run_hook('admin_footer'));
$check(!str_contains($html, '<form') && substr_count($html, 'form="bl-record-restore-') === 10 && str_contains($html, 'Editor 7'), 'history UI metadata or form targets missing');
$check(substr_count($forms, 'name="bl_snapshot"') === 10 && str_contains($forms, '当前内容会先存进历史'), 'history restore forms incomplete');
$_POST = ['bl_post_id' => 2, 'bl_snapshot' => $history[0]['id']];
$GLOBALS['caps']['edit_post'] = false;
try { run_hook('admin_post_bl_restore_record'); $check(false, 'unauthorized restore succeeded'); }
catch (RuntimeException $error) { $check(str_contains($error->getMessage(), '没有权限'), 'missing permission error'); }
$check($GLOBALS['meta'] === $before, 'unauthorized restore wrote data');
unset($GLOBALS['caps']['edit_post']);
try { run_hook('admin_post_bl_restore_record'); } catch (TestRedirect $redirect) {}
$check(get_post_meta(2, '_bl_record', true)['caption'] === 'Version 11', 'admin restore ignored snapshot');
$_POST = [];
$check(bl_news_body_managed(new WP_Post(9, 'bl_news', 'legacy'), ['_bl_body_managed' => true]), 'legacy record body marker ignored');
$GLOBALS['meta'][9]['_bl_body_managed'] = true;
$check(bl_news_body_managed(new WP_Post(9, 'bl_news', 'legacy'), []), 'legacy standalone body marker ignored');
$check(!array_filter(array_keys($GLOBALS['options']), static fn($key) => str_starts_with($key, 'bl_lock_')), 'record/page lock leaked');
echo "backend P4 whole snapshot restore ok ($checks checks)\n";
