<?php
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-support.php';
require __DIR__ . '/../inc/wp-product-page.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$GLOBALS['posts'] = [
    1 => new WP_Post(1, 'bl_page_content', 'support'),
    2 => new WP_Post(2, 'bl_page_content', 'home'),
    3 => new WP_Post(3, 'bl_product', 'published', 'Published'),
    4 => new WP_Post(4, 'bl_product', 'draft-model', 'Draft model', 'draft'),
    5 => new WP_Post(5, 'bl_news', 'news'),
    6 => new WP_Post(6, 'bl_application', 'case'),
];
$restSave = static function (int $id, array $input, ?string $revision = null) {
    $request = new WP_REST_Request(['id' => $id, 'meta' => ['_bl_content' => $input]] + ($revision === null ? [] : ['bl_content_revision' => $revision]));
    return run_filter('rest_pre_insert_bl_page_content', (object) [], $request);
};
// B1: execute the registered REST read and write callbacks, not only the merge helper.
$GLOBALS['options']['bl_content_support'] = ['containers' => ['loading' => ['new-machine' => [3, 7]]]];
$read = run_filter('get_post_metadata', null, 1, '_bl_content', true)[0];
$check($read['containers']['loading']['new-machine'] === [3, 7], 'REST read lost dynamic model');
$check(!is_wp_error($restSave(1, ['hero' => ['eyebrow' => 'Updated']])) && bl_page_content_current('support')['containers']['loading']['new-machine'] === [3, 7], 'unrelated REST save lost dynamic model');
$restSave(1, ['containers' => ['loading' => ['second-machine' => [2, 8]]]]);
$check(bl_page_content_current('support')['containers']['loading']['new-machine'] === [3, 7], 'dynamic map update replaced other models');
$check(bl_page_content_current('support')['containers']['loading']['second-machine'] === [2, 8], 'new REST model not saved');
$form = bl_page_content_current('support'); unset($form['containers']['loading']['new-machine']);
$next = bl_content_prepare('support', get_option('bl_content_support'), $form, true);
$check(bl_content_resolve('support', $next)['containers']['loading']['new-machine'] === [3, 7], 'form lost unpublished model');
// B2: migration is identical for REST and frontend; a later explicit change to default must not resurrect old facts.
$facts = [['value' => '40,000', 'unit' => 'm²', 'label' => 'Own factory']];
$GLOBALS['options']['bl_content_home'] = ['intro' => ['facts' => $facts], 'historical' => ['preserved' => true]];
$check(run_filter('get_post_metadata', null, 2, '_bl_content', true)[0]['approach']['facts'] === $facts, 'REST did not migrate old facts');
$restSave(2, ['hero' => ['eyebrow' => 'Changed']]);
$check(bl_page_content_current('home')['approach']['facts'] === $facts, 'unrelated REST save lost migrated facts');
$check(get_option('bl_content_home')['historical']['preserved'], 'save removed historical unknown data');
$restSave(2, ['approach' => ['facts' => bl_home_defaults()['approach']['facts']]]);
$check(bl_page_content_current('home')['approach']['facts'] === bl_home_defaults()['approach']['facts'], 'old facts resurrected after explicit default');
// A17: old absent/empty values retain their original rendering; only a new edit makes empty authoritative.
$base = $GLOBALS['bl_application_base']; $slug = bl_application_slug($base[0]);
$defaults = bl_content_resolve('applications', []);
$legacy = ['jobs' => [$slug => ['subtitle' => '', 'description' => '', 'checks' => []]]];
$check(bl_apply_application_content($base, bl_content_resolve('applications', $legacy)) === bl_apply_application_content($base, $defaults), 'legacy empty values changed rendering');
$empty = bl_content_prepare('applications', [], $legacy);
$rendered = bl_apply_application_content($base, bl_content_resolve('applications', $empty))[0];
$check($rendered['subtitle'] === '' && $rendered['description'] === '' && $rendered['checks'] === [], 'explicit empty values fell back');
$unrelated = bl_content_prepare('applications', [], ['hero' => ['line1' => 'Changed']]);
$check(bl_content_resolve('applications', $unrelated)['jobs'][$slug] === $defaults['jobs'][$slug], 'unset application fields changed');
$form = $defaults; unset($form['jobs'][$slug]['checks']);
$check(bl_content_resolve('applications', bl_content_prepare('applications', [], $form, true))['jobs'][$slug]['checks'] === [], 'HTML removal of all checks was ignored');
foreach (['https://outside.test/path?q=1' => 'https://outside.test/path?q=1', '/support/#containers' => 'https://example.test/support/#containers', '#machines' => '#machines'] as $input => $expected) {
    $check(bl_escape(bl_application_link(bl_content_clean_url($input))) === bl_escape($expected), 'incorrect link output: ' . $input);
}
// E4: stale saves/resets do not write; reset snapshots precede mutation and are bounded, undo uses the same lock.
$before = get_option('bl_content_support'); $revision = bl_content_revision($before);
$restSave(1, ['hero' => ['eyebrow' => 'Latest']], $revision);
$current = get_option('bl_content_support');
$error = $restSave(1, ['hero' => ['eyebrow' => 'Stale']], $revision);
$check(is_wp_error($error) && $error->code === 'bl_content_conflict' && get_option('bl_content_support') === $current, 'stale REST snapshot overwrote content');
$historyBeforeConflict = get_option('bl_content_snapshots_support');
$error = bl_content_locked('support', fn() => [], $revision);
$check(is_wp_error($error) && get_option('bl_content_snapshots_support') === $historyBeforeConflict, 'stale reset wrote snapshot/content');
$GLOBALS['fail_option'] = 'bl_content_snapshots_support';
$error = bl_content_locked('support', fn() => [], bl_content_revision($current));
$check(is_wp_error($error) && get_option('bl_content_support') === $current, 'reset proceeded after snapshot failure');
unset($GLOBALS['fail_option']);
$check(bl_content_locked('support', fn() => [], bl_content_revision($current)) === true, 'reset failed');
$check(get_option('bl_content_snapshots_support')[0]['content'] === $current && bl_page_content_current('support') === bl_support_defaults(), 'reset did not back up original or restore defaults');
$check(bl_content_revision(get_option('bl_content_support')) !== bl_content_revision([]), 'reset lost revision');
$_POST = ['bl_page' => 'support', 'bl_content_revision' => bl_content_revision(get_option('bl_content_support'))];
try { bl_content_handle_submission('restore'); } catch (TestRedirect $redirect) {}
$expectedRestore = bl_content_resolve('support', $current);
$check(bl_content_resolve('support', get_option('bl_content_support')) === $expectedRestore, 'undo did not restore the complete snapshot');
$beforeFailure = get_option('bl_content_support');
$GLOBALS['fail_option'] = 'bl_content_support';
$error = bl_content_locked('support', fn() => [], bl_content_revision($beforeFailure));
$check(is_wp_error($error) && get_option('bl_content_support') === $beforeFailure, 'write failure reported success or changed content');
unset($GLOBALS['fail_option']);
$error = bl_content_locked('support', static function () { unset($GLOBALS['options']['bl_lock_content_support']); return []; });
$check(is_wp_error($error) && get_option('bl_content_support') === $beforeFailure, 'expired lock allowed a write');
for ($i = 0; $i < 12; $i++) bl_content_locked('support', fn() => [], null);
$check(count(get_option('bl_content_snapshots_support')) === 10, 'snapshot history unbounded');
$_POST = ['bl_page' => 'home', 'bl_content_complete' => '1', 'bl_content_revision' => 'stale', 'bl_content' => ['hero' => ['eyebrow' => 'Keep my input']]];
try { bl_content_handle_submission('save'); $check(false, 'stale form was accepted'); } catch (TestRedirect $redirect) {
    parse_str(parse_url($redirect->getMessage(), PHP_URL_QUERY), $_GET);
    $recovery = bl_content_recovery('home');
    $check($recovery['input'] === $_POST['bl_content'] && $recovery['revision'] === 'stale', 'conflict lost input or silently rebased it');
    $GLOBALS['user_id'] = 8; $check(bl_content_recovery('home') === [], 'another user could read recovery'); unset($GLOBALS['user_id']);
}
// Content and revision in a REST response must remain paired across a concurrent writer.
run_hook('rest_api_init');
$snapshot = bl_rest_content_snapshot('home', true);
$payload = run_filter('get_post_metadata', null, 2, '_bl_content', true)[0];
$GLOBALS['options']['bl_content_home']['_bl_revision'] = 'concurrent-writer';
$getRevision = $GLOBALS['rest_fields']['bl_page_content']['bl_content_revision']['get_callback'];
$check($getRevision(['id' => 2]) === bl_content_revision($snapshot) && $payload === bl_content_resolve('home', $snapshot), 'REST content and revision described different snapshots');
bl_rest_content_snapshot('home', true);
// E5: invoke all save hooks with missing marker and with a missing section nonce.
$GLOBALS['meta'][3]['_bl_record'] = ['model' => 'Original', 'faq' => [['q' => 'Keep']], 'videos' => [['src' => 'keep.mp4']]];
$original = $GLOBALS['meta'][3];
$_POST = ['action' => 'editpost', 'post_ID' => 3, 'bl_record_nonce' => 'bl_save_record', 'bl_page_nonce' => 'bl_save_product_page', 'bl_videos_nonce' => 'bl_save_videos', 'bl_record' => ['model' => 'Wrong'], 'bl_page' => []];
run_hook('save_post_bl_product', 3); run_hook('save_post', 3);
$check($GLOBALS['meta'][3] === $original, 'truncated form wrote product meta');
try { run_hook('load-post.php'); $check(false, 'truncated form reached core write'); } catch (RuntimeException $e) { $check(str_contains($e->getMessage(), '没有保存任何字段'), 'missing truncation message'); }
$_POST['bl_product_complete'] = '1'; $check(bl_product_submission_complete(), 'complete form rejected');
run_hook('load-post.php'); run_hook('save_post_bl_product', 3); run_hook('save_post', 3);
$check($GLOBALS['meta'][3]['_bl_record']['model'] === 'Wrong' && $GLOBALS['meta'][3]['_bl_record']['faq'] === [] && $GLOBALS['meta'][3]['_bl_record']['videos'] === [], 'complete product submission failed to save explicit edits');
ob_start(); run_hook('dbx_post_sidebar', get_post(3)); $marker = ob_get_clean();
$check(str_contains($marker, 'name="bl_product_complete"'), 'form marker not emitted');
// E6: both admin and REST keep draft associations but allow explicit removal; frontend remains published-only.
foreach ([5, 6] as $id) {
    $GLOBALS['meta'][$id]['_bl_record'] = ['products' => ['draft-model']];
    $_POST = ['bl_record_nonce' => 'bl_save_record', 'bl_record' => ['products' => ['draft-model']]];
    run_hook('save_post', $id);
    $check($GLOBALS['meta'][$id]['_bl_record']['products'] === ['draft-model'], 'admin lost draft association');
    $request = new WP_REST_Request(['id' => $id, 'meta' => ['_bl_record' => ['products' => ['draft-model', 'unknown']]]]);
    run_filter('rest_pre_insert_' . get_post_type($id), (object) [], $request);
    $check($request->get_param('meta')['_bl_record']['products'] === ['draft-model'], 'REST lost draft or admitted unknown association');
    ob_start(); bl_admin_product_checklist(get_post($id), ''); $html = ob_get_clean();
    $check(str_contains($html, 'value="draft-model" checked="checked"') && str_contains($html, '未发布'), 'draft checkbox missing or unmarked');
    $_POST['bl_record']['products'] = []; run_hook('save_post', $id);
    $check($GLOBALS['meta'][$id]['_bl_record']['products'] === [], 'explicit removal ignored');
}
$check(bl_find_record(bl_products(), 'draft-model') === null, 'frontend exposed draft product');
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "backend P0 regression ok\n";
