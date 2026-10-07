<?php
/** Rename continuity: production readers and save handlers, in-memory WordPress boundaries only. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wordpress.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
function get_the_title($post) { return $post->post_title; }
function get_permalink($post) { return home_url('/product/' . $post->post_name . '/'); }
function get_the_post_thumbnail_url(...$args) { return ''; }
function wp_trim_words($text, $limit) { return $text; }
function selected($a, $b, $echo = true) { return $a === $b ? ' selected' : ''; }
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$capture = static function (callable $render): string { ob_start(); $render(); return ob_get_clean(); };
// A subclass supplies the fields read by the real production mapper without changing older fixtures.
class AliasTestPost extends WP_Post { public string $post_excerpt = 'Written summary'; public string $post_content = ''; }
$GLOBALS['posts'] = [1 => new AliasTestPost(1, 'bl_product', 'new-machine', 'Renamed machine'), 2 => new AliasTestPost(2, 'bl_product', 'draft-machine', 'Draft', 'draft'), 3 => new WP_Post(3, 'bl_news', 'news'), 4 => new WP_Post(4, 'bl_page_content', 'support')];
$GLOBALS['meta'][1] = ['_bl_record' => ['model' => 'OLD', 'category' => 'skid-steer-loaders'], '_wp_old_slug' => ['old-machine', 'older-machine']];
$GLOBALS['meta'][2] = ['_bl_record' => ['model' => 'DRAFT']];
$GLOBALS['meta'][3] = ['_bl_record' => ['products' => ['old-machine']]];
$before = $GLOBALS['meta'];
$products = bl_products();
$check(bl_find_record($products, 'old-machine')['slug'] === 'new-machine', 'old slug unresolved');
$check(bl_find_record($products, 'older-machine')['slug'] === 'new-machine', 'multiple historical slugs unresolved');
$check(bl_find_record($products, 'draft-machine') === null && bl_find_record($products, 'unknown') === null, 'unpublished or missing product exposed');
$check($GLOBALS['meta'] === $before, 'read wrote records');
$check(bl_find_record([['slug' => 'story']], 'old-machine') === null, 'product aliases leaked into non-product records');
$GLOBALS['posts'][5] = new AliasTestPost(5, 'bl_product', 'old-machine', 'Reused slug');
$GLOBALS['meta'][5]['_bl_record'] = ['model' => 'REUSED'];
$check(bl_find_record(bl_products(), 'old-machine')['model'] === 'REUSED', 'current slug did not win');
$check(bl_loading_aliases(['old-machine' => [2, 4]], bl_products()) === ['old-machine' => [2, 4]], 'reused slug incorrectly assigned loading');
unset($GLOBALS['posts'][5], $GLOBALS['meta'][5]);
$check(bl_find_record(bl_products(), 'old-machine')['model'] === 'OLD', 'request index did not refresh for changed records');
$check(bl_loading_aliases(['old-machine' => [2, 4], 'new-machine' => []], bl_products())['new-machine'] === [], 'explicit empty loading overridden');

// REST and classic renames append within the existing write, never touching related records/options.
$options = $GLOBALS['options'];
$request = new WP_REST_Request(['id' => 1, 'meta' => ['_bl_record' => ['model' => 'NEW', 'model_aliases' => ['injected']]]]);
$check(!is_wp_error(run_filter('rest_pre_insert_bl_product', (object) [], $request)), 'REST rename rejected');
$record = $request->get_param('meta')['_bl_record'];
$check($record['model_aliases'] === ['OLD'] && $GLOBALS['meta'] === $before, 'REST aliases not prepared atomically');
update_post_meta(1, '_bl_record', $record);
$_POST = ['bl_record_nonce' => 'bl_save_record', 'bl_product_complete' => '1', 'bl_record' => ['model' => 'LATEST', 'category' => 'skid-steer-loaders']];
run_hook('save_post', 1);
$check(get_post_meta(1, '_bl_record', true)['model_aliases'] === ['OLD', 'NEW'], 'classic rename lost model history');
run_hook('save_post', 1);
$check(get_post_meta(1, '_bl_record', true)['model_aliases'] === ['OLD', 'NEW'], 'unchanged model duplicated history');
$check($GLOBALS['meta'][3] === $before[3] && $GLOBALS['options'] === $options, 'rename rewrote references or options');
$check(bl_application_models(['models' => ['OLD', 'NEW']])[0]['model'] === 'LATEST', 'application model aliases unresolved');
$check(count(bl_application_models(['models' => ['OLD', 'NEW']])) === 1, 'model aliases duplicated recommendation');
$index = bl_named_record_index([['model' => 'LATEST', 'model_aliases' => ['OLD']], ['model' => 'OLD']], 'model', 'model_aliases');
$check($index['old']['model'] === 'OLD', 'current model did not win');
$html = bl_content_selector('models', 'models', ['OLD', 'missing']);
$check(str_contains($html, 'value="LATEST" name="models[]" checked') && !str_contains($html, '未找到：OLD') && str_contains($html, '未找到：missing'), 'model selector lost current checked name');
$html = $capture(static fn() => bl_admin_product_checklist($GLOBALS['posts'][3], 'Products'));
$check(str_contains($html, 'value="new-machine" checked="checked"') && !str_contains($html, '未发布'), 'news selector lost alias selection');

// Simulate the actual snapshot restore action before the first cached support read.
$GLOBALS['options']['bl_content_support'] = ['containers' => ['loading' => ['new-machine' => [8, 16]]]];
$GLOBALS['options']['bl_content_snapshots_support'] = [['content' => ['containers' => ['loading' => ['old-machine' => [3, 7]]]]]];
$_POST = ['bl_page' => 'support', 'bl_content_revision' => bl_content_revision($GLOBALS['options']['bl_content_support'], 'support')];
try { bl_content_handle_submission('restore'); } catch (TestRedirect $redirect) {}
$check(isset($GLOBALS['options']['bl_content_support']['containers']['loading']['old-machine']) && !isset($GLOBALS['options']['bl_content_support']['containers']['loading']['new-machine']), 'restore rewrote old loading key');
$check(bl_product_loading(bl_products()[0]) === [3, 7], 'restored loading not visible on renamed product');

$html = $capture(static fn() => bl_content_field(['key' => 'containers.loading', 'type' => 'loading'], ['old-machine' => [3, 7]], false));
$check(str_contains($html, 'name="bl_content[containers][loading][new-machine][0]" value="3"'), 'loading editor lost historical key value');

// Only a reliable row rename creates history; reorders retain it without inventing aliases.
$defaults = bl_content_defaults('loaders');
$rows = $defaults['attachments']['items'];
$oldName = $rows[0]['name'];
$rows[0]['name'] = 'Renamed bucket';
$saved = bl_content_prepare('loaders', [], ['attachments' => ['items' => $rows]]);
$check($saved['attachments']['items'][0]['aliases'] === [$oldName], 'attachment rename history missing');
$rows = $saved['attachments']['items'];
$rows[0]['name'] = 'Current bucket';
$saved = bl_content_prepare('loaders', $saved, ['attachments' => ['items' => $rows]]);
$check($saved['attachments']['items'][0]['aliases'] === [$oldName, 'Renamed bucket'], 'second attachment rename lost history');
$unchanged = bl_content_prepare('loaders', $saved, ['hero' => ['text' => 'Updated']]);
$check($unchanged['attachments']['items'][0]['aliases'] === [$oldName, 'Renamed bucket'], 'unrelated save removed history');
$reordered = array_reverse($saved['attachments']['items']);
$reorderedSave = bl_content_prepare('loaders', $saved, ['attachments' => ['items' => $reordered]]);
$check(end($reorderedSave['attachments']['items'])['aliases'] === [$oldName, 'Renamed bucket'], 'reorder lost history');
$uncertain = $defaults['attachments']['items'];
$uncertain[0]['name'] = 'Different'; $uncertain[0]['image'] = '';
$check(!isset(bl_attachment_row_aliases($uncertain, $defaults['attachments']['items'])[0]['aliases']), 'uncertain row generated alias');
$duplicate = $defaults['attachments']['items'];
$duplicate[1]['image'] = $duplicate[0]['image'];
$renamedDuplicate = $duplicate;
$renamedDuplicate[0]['name'] = 'Ambiguous rename';
$check(!isset(bl_attachment_row_aliases($renamedDuplicate, $duplicate)[0]['aliases']), 'duplicate image treated as reliable identity');
$injected = $defaults['attachments']['items'];
$injected[0]['aliases'] = ['Forged alias'];
$prepared = bl_content_prepare('loaders', [], ['attachments' => ['items' => $injected]]);
$check(!isset($prepared['attachments']['items'][0]['aliases']), 'submitted aliases trusted');
$swapped = $defaults['attachments']['items'];
[$swapped[0], $swapped[1]] = [$swapped[1], $swapped[0]];
$check(bl_attachment_row_aliases($swapped, $defaults['attachments']['items']) === $swapped, 'reorder invented rename history');
$GLOBALS['options']['bl_content_loaders'] = $saved;
$GLOBALS['posts'][6] = new WP_Post(6, 'bl_page_content', 'loaders');
$beforeMeta = $GLOBALS['meta'];
$beforeOptions = $GLOBALS['options'];
$request = new WP_REST_Request(['id' => 6, 'meta' => ['_bl_content' => ['hero' => ['text' => 'Saved via REST']]]]);
$check(!is_wp_error(run_filter('rest_pre_insert_bl_page_content', (object) [], $request)), 'loader REST save failed');
$check($GLOBALS['options']['bl_content_loaders']['attachments']['items'][0]['aliases'] === [$oldName, 'Renamed bucket'], 'option write lost attachment aliases');
$afterOptions = $GLOBALS['options'];
unset($afterOptions['bl_content_loaders'], $beforeOptions['bl_content_loaders'], $afterOptions['bl_content_snapshots_loaders'], $beforeOptions['bl_content_snapshots_loaders']); // the page's own history is expected
$check($GLOBALS['meta'] === $beforeMeta && $afterOptions === $beforeOptions, 'loader save wrote another record');
$product = ['category' => 'skid-steer-loaders', 'attachments' => [strtoupper($oldName)]];
$check(bl_product_attachments($product)[0]['name'] === 'Current bucket', 'product attachment alias unresolved');
$check(bl_product_attachments(['category' => 'skid-steer-loaders'])[0]['name'] === 'Current bucket', 'default CSV alias unresolved');
$html = bl_content_selector('attachments', 'attachments', [strtoupper($oldName), 'missing']);
$check(str_contains($html, 'value="Current bucket" name="attachments[]" checked') && !str_contains($html, '未找到：' . strtoupper($oldName)) && str_contains($html, '未找到：missing'), 'attachment selector lost current checked name');
$check(bl_named_record_index([['name' => 'Current', 'aliases' => ['Old']], ['name' => 'Old']], 'name', 'aliases')['old']['name'] === 'Old', 'current attachment name did not win');
echo "PASS: $checks rename alias checks\n";
