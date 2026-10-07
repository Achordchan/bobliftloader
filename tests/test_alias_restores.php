<?php
/** Rename and restore regressions run production readers and locked writes with in-memory storage. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wordpress.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
function get_the_title($post) { return $post->post_title; }
function get_permalink($post) { return home_url('/product/' . $post->post_name . '/'); }
function get_the_post_thumbnail_url(...$args) { return ''; }
function wp_trim_words($text, $limit) { return $text; }
class RestoreAliasPost extends WP_Post { public string $post_excerpt = 'Summary'; public string $post_content = ''; }
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$GLOBALS['posts'] = [1 => new RestoreAliasPost(1, 'bl_product', 'machine', 'Machine')];

// Saved history participates in legacy expansion, while an exact current name still wins.
$GLOBALS['meta'][1]['_bl_record'] = ['model' => 'CUSTOM', 'model_aliases' => ['FY30'], 'category' => 'mini-dumpers'];
$check(bl_application_models(['models' => ['FY-30']])[0]['model'] === 'CUSTOM', 'legacy alias did not expand saved model history');
$products = bl_products();
$products[] = ['model' => 'FY-30', 'category' => 'mini-dumpers'];
$check(bl_dumper_model_index($products)['fy-30']['model'] === 'FY-30', 'legacy expansion replaced a current name');

// A picker-canonicalized B selection survives restoring A, including history from both versions.
$snapshot = ['model' => 'A', 'model_aliases' => ['EARLY', 'A'], 'category' => 'mini-dumpers'];
$current = ['model' => 'B', 'model_aliases' => ['A', 'INTERMEDIATE', 'EARLY'], 'category' => 'mini-dumpers'];
$GLOBALS['meta'][1]['_bl_record'] = $current;
$GLOBALS['meta'][1]['_bl_record_history'] = [['id' => 'model-before', 'record' => $snapshot]];
$GLOBALS['meta'][2]['_bl_record'] = ['models' => ['B']];
$references = $GLOBALS['meta'][2];
$token = bl_lock_acquire('record_1');
$check(is_wp_error(bl_record_history_restore(1, 'model-before')) && $GLOBALS['meta'][1]['_bl_record'] === $current, 'restore bypassed record lock');
bl_lock_release('record_1', $token);
$check(bl_record_history_restore(1, 'model-before') === true, 'model restore failed');
$restored = get_post_meta(1, '_bl_record', true);
$check($restored['model_aliases'] === ['EARLY', 'INTERMEDIATE', 'B'], 'restore lost accumulated model history or retained its own name');
$check(bl_application_models($references['_bl_record'])[0]['model'] === 'A', 'canonical selection broke after model restore');
$check($GLOBALS['meta'][2] === $references && get_post_meta(1, '_bl_record_history', true)[0]['record'] === $current, 'restore rewrote reference or omitted backup');

// Restoring the loaders page matches rows as a save does (same name, or same place with the same picture, group and
// use); the restored row keeps every name it has had, and a row that matches nothing stays as it was in the snapshot.
$row = bl_content_defaults('loaders')['attachments']['items'][0];
$snapshotRows = [array_replace($row, ['name' => 'Old bucket', 'image' => 'images/qa-bucket.webp', 'aliases' => ['Earliest bucket']]), array_replace($row, ['name' => 'Forks', 'image' => 'images/qa-forks.webp'])];
$currentRows = [array_replace($row, ['name' => 'New bucket', 'image' => 'images/qa-bucket.webp', 'aliases' => ['Old bucket', 'Middle bucket', 'Earliest bucket']]), array_replace($row, ['name' => 'Forks', 'image' => 'images/qa-forks.webp'])];
$snapshot = ['attachments' => ['items' => $snapshotRows]];
$current = ['attachments' => ['items' => $currentRows]];
$GLOBALS['options']['bl_content_loaders'] = $current;
$GLOBALS['options']['bl_content_snapshots_loaders'] = [['id' => 'attachment-before', 'content' => $snapshot]];
$GLOBALS['meta'][1]['_bl_record']['attachments'] = ['New bucket'];
$beforeMeta = $GLOBALS['meta'];
$_POST = ['bl_page' => 'loaders', 'bl_snapshot' => 'attachment-before', 'bl_content_revision' => bl_content_revision($current)];
try { bl_content_handle_submission('restore'); } catch (TestRedirect $redirect) {}
$rows = get_option('bl_content_loaders')['attachments']['items'];
$check($rows[0]['name'] === 'Old bucket' && $rows[0]['aliases'] === ['Earliest bucket', 'Middle bucket', 'New bucket'], 'attachment restore lost history or retained its own name');
$check(!isset($rows[1]['aliases']), 'an unchanged row picked up aliases');
$check(bl_product_attachments(['category' => 'skid-steer-loaders', 'attachments' => ['New bucket']])[0]['name'] === 'Old bucket', 'canonical attachment selection broke after restore');
$check($GLOBALS['meta'] === $beforeMeta && get_option('bl_content_snapshots_loaders')[0]['content'] === $current, 'attachment restore rewrote product or omitted backup');
$other = array_replace($row, ['name' => 'Unrelated', 'image' => 'images/qa-other.webp', 'group' => 'Other']);
$check(bl_attachment_restore_aliases([$snapshotRows[0]], [$other]) === [$snapshotRows[0]], 'a different row transferred aliases');
// Loading figures: the newest old address with figures wins after a further rename; a current key always wins.
$fits = bl_loading_aliases(['a' => [6, 14], 'b' => [8, 16]], [['slug' => 'c', '_wp_old_slug' => ['a', 'b']]]);
$check($fits['c'] === [8, 16], 'a further rename fell back to older loading figures');
$check(bl_loading_aliases(['a' => [6, 14], 'c' => []], [['slug' => 'c', '_wp_old_slug' => ['a']]])['c'] === [], 'an explicit current key lost to an old one');
// A snapshot taken before any attachment edit has no rows of its own; restoring it still keeps the renamed row's history.
$defaults = bl_content_defaults('loaders')['attachments']['items'];
$renamed = $defaults; $renamed[0]['name'] = 'Renamed default'; $renamed[0]['aliases'] = [$defaults[0]['name']];
$restoredDefaults = bl_content_restore_values('loaders', [], ['attachments' => ['items' => $renamed]]);
$check(($restoredDefaults['attachments']['items'][0]['aliases'] ?? null) === ['Renamed default'] && $restoredDefaults['attachments']['items'][0]['name'] === $defaults[0]['name'], 'restoring a snapshot without rows dropped alias history');
// Two quick renames over REST: the second save merges the history it finds under the lock.
$check(bl_product_model_aliases(['model' => 'C', 'model_aliases' => ['A']], ['model' => 'B', 'model_aliases' => ['A']])['model_aliases'] === ['A', 'B'], 'a second rename lost the first new name');
// Renamed A to B and moved it: restoring the A snapshot finds B through B's own alias list, wherever it now sits.
$x = array_replace($row, ['name' => 'Spare', 'image' => 'images/qa-spare.webp']);
$restoredMoved = bl_attachment_restore_aliases([array_replace($row, ['name' => 'A tool', 'image' => 'images/qa-a.webp'])], [$x, array_replace($row, ['name' => 'B tool', 'image' => 'images/qa-b.webp', 'aliases' => ['A tool', 'Older tool']])]);
$check($restoredMoved[0]['aliases'] === ['Older tool', 'B tool'], 'a moved, renamed row lost its history on restore');
echo "PASS: $checks alias restore checks\n";
