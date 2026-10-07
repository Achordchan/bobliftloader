<?php
/** Downloads exercise real content writers and templates using in-memory WordPress boundaries. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
function bl_wp_products() { return array_map(static fn($p) => ['slug' => $p->post_name] + get_post_meta($p->ID, '_bl_record', true), get_posts(['post_type' => 'bl_product', 'post_status' => 'publish'])); }
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-support.php';
function attachment_url_to_postid($url) { return $url === 'https://example.test/manual.pdf' ? 123 : 0; }
function get_attached_file($id) { return $id === 123 ? __FILE__ : ''; }
function size_format($size, $precision = 0) { return number_format($size / 1024, $precision) . ' KB'; }
$GLOBALS['posts'] = [1 => new WP_Post(1, 'bl_product', 'loader'), 2 => new WP_Post(2, 'bl_product', 'dumper')];
$GLOBALS['meta'][1]['_bl_record'] = ['model' => 'NEW'];
$GLOBALS['meta'][2]['_bl_record'] = ['model' => 'DUMPER'];
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$save = static fn(array $items, ?string $revision = null) => bl_downloads_rest_save(new WP_REST_Request(['items' => $items, 'bl_content_revision' => $revision]));
$rows = static fn() => bl_content_resolve('downloads', bl_downloads_saved())['items'];
$capture = static function (callable $render): string { ob_start(); $render(); return ob_get_clean(); };
$check(count($rows()) === 1 && $rows()[0]['id'] === 'catalogue' && !bl_downloads_extended(), 'default list must contain the catalogue only');
$check(str_ends_with(bl_catalogue()['url'], '/assets/downloads/boblift-compact-equipment-catalogue.pdf'), 'bundled catalogue missing');
$GLOBALS['options']['bl_catalogue_url'] = 'https://example.test/legacy.pdf';
$GLOBALS['options']['bl_catalogue_size'] = 2097152;
$before = $GLOBALS['options'];
$check(bl_catalogue() === ['url' => 'https://example.test/legacy.pdf', 'size' => '2.0 MB', 'format' => 'PDF'], 'legacy front-end result changed');
$check(bl_catalogue_rest_read() === ['bl_catalogue_url' => 'https://example.test/legacy.pdf', 'bl_catalogue_size' => 2097152], 'legacy REST result changed');
$check($GLOBALS['options'] === $before, 'read migrated options');
$initial = $rows();
$check(!is_wp_error($save($initial)) && !bl_downloads_extended(), 'saving inherited row enabled extended output');
$check(bl_catalogue()['size'] === '2.0 MB', 'saving inherited row changed legacy size');
$history = bl_content_history('downloads');
$check(count($history) === 1 && $history[0]['content'] === [], 'initial before-image missing');
$primary = $initial[0];
$manual = ['id' => 'manual', 'url' => 'https://example.test/manual.pdf', 'title' => 'User manual', 'description' => 'Maintenance instructions', 'type' => 'manual', 'scope' => 'skid-steer-loaders', 'models' => ''];
$parts = ['id' => 'parts', 'url' => 'https://outside.test/parts.zip', 'title' => 'Parts', 'description' => '', 'type' => 'parts', 'scope' => 'models', 'models' => 'NEW'];
$check(!is_wp_error($save([$parts, $primary, $manual])) && bl_downloads_extended(), 'list save failed');
$check(array_column(bl_downloads_for(), 'id') === ['parts', 'catalogue', 'manual'], 'list order not preserved');
$check(bl_downloads_for()[2]['size'] === filesize(__FILE__), 'media size not read from file');
$check(bl_downloads_for()[0]['size'] === 0, 'external size fabricated');
$check(bl_catalogue()['url'] === $primary['url'], 'primary catalogue depends on display order');
$check(array_column(bl_downloads_for(['slug' => 'loader', 'model' => 'NEW', 'category' => 'skid-steer-loaders']), 'id') === ['parts', 'catalogue', 'manual'], 'product filters lost matching rows');
$check(array_column(bl_downloads_for(['slug' => 'dumper', 'model' => 'DUMPER', 'category' => 'mini-dumpers']), 'id') === ['catalogue'], 'unrelated documents leaked');
// Renames remain within the product; download references resolve against its read-time aliases.
$GLOBALS['meta'][1]['_bl_record'] = ['model' => 'LATEST', 'model_aliases' => ['NEW']];
$aliasState = $GLOBALS['options'];
$check(in_array('parts', array_column(bl_downloads_for(['slug' => 'loader', 'model' => 'LATEST', 'category' => 'skid-steer-loaders']), 'id'), true), 'renamed model lost downloads');
$check($GLOBALS['options'] === $aliasState, 'alias read rewrote references');
$check(array_diff_key($GLOBALS['options'], ['bl_downloads' => true]) === $before, 'save changed another option or left a lock');
$check(!isset($GLOBALS['options']['bl_content_snapshots_downloads'], $GLOBALS['options']['bl_content_downloads']), 'separate download persistence introduced');
$state = $GLOBALS['options'];
foreach ([[$manual], [$primary, $primary], [$primary, array_replace($manual, ['url' => 'javascript:alert(1)'])], [$primary, array_replace($manual, ['size' => 99])], [$primary, array_replace($manual, ['type' => 'invalid'])], [$primary, array_replace($parts, ['models' => ''])], [$primary, array_replace($manual, ['title' => []])], array_fill(0, 51, $primary)] as $invalid) {
    $check(is_wp_error($save($invalid)), 'invalid list accepted');
    $check($GLOBALS['options'] === $state, 'invalid list changed content or history');
}
$check(is_wp_error($save($rows(), 'stale')) && $GLOBALS['options'] === $state, 'stale list wrote content/history');
$GLOBALS['fail_option'] = 'bl_downloads';
$check(is_wp_error($save([$primary])) && $GLOBALS['options'] === $state, 'failed write partially committed history');
unset($GLOBALS['fail_option']);
$beforeRows = $rows();
$check(!is_wp_error(bl_catalogue_rest_save(new WP_REST_Request(['bl_catalogue_url' => 'https://example.test/replaced.pdf']))), 'legacy write failed');
$check($rows()[0] === $beforeRows[0] && $rows()[2] === $beforeRows[2], 'legacy endpoint overwrote other list rows');
$check(get_option('bl_catalogue_url') === $primary['url'] && get_option('bl_catalogue_size') === 2097152, 'legacy endpoint rewrote legacy options');
$check(bl_catalogue()['size'] === '', 'new URL reused unrelated legacy size');
$history = bl_content_history('downloads');
$_POST = ['bl_page' => 'downloads', 'bl_snapshot' => $history[0]['id'], 'bl_content_revision' => bl_content_revision(bl_downloads_saved())];
try { bl_content_handle_submission('restore'); } catch (TestRedirect $redirect) { $check(str_contains($redirect->getMessage(), 'page=bl-downloads&bl_saved=1'), 'restore changed admin slug'); }
$check($rows() === $beforeRows, 'restore did not restore complete list/order');
$check(bl_content_history('downloads')[0]['content']['items'][1]['url'] === 'https://example.test/replaced.pdf', 'restore failed to snapshot current list');
for ($i = 0; $i < 12; $i++) $save([$primary]);
$history = bl_content_history('downloads');
$check(count($history) === 10, 'history unbounded');
foreach ($history as $entry) $check(!isset($entry['content']['_bl_history']), 'history recursively nested');
$html = $capture(static fn() => bl_downloads_screen());
$check(str_contains($html, '历史版本') && str_contains($html, 'data-bl-download-pick') && str_contains($html, 'data-bl-selector'), 'admin/history controls missing');
$check(str_contains($html, 'name="bl_content_complete"') && str_contains($html, 'name="bl_content_revision"'), 'form guards missing');
$check(!str_contains($html, 'name="bl_content[items][0][size]"') && !str_contains($html, 'border-left'), 'size editable or prohibited styling');
$check(substr_count($html, 'bl-ce-remove') === 1, 'primary row removable');
run_hook('rest_api_init');
foreach (['catalogue', 'downloads'] as $route) {
    $endpoint = $GLOBALS['rest_routes']['boblift/v1/' . $route];
    $GLOBALS['caps']['edit_pages'] = false;
    $check(!$endpoint[0]['permission_callback']() && !$endpoint[1]['permission_callback'](), 'route permits non-editor');
    $GLOBALS['caps'] = ['edit_pages' => true, 'manage_options' => false];
    $check($endpoint[0]['permission_callback']() && $endpoint[1]['permission_callback'](), 'route requires administrator');
}
$GLOBALS['caps']['edit_pages'] = false;
$state = $GLOBALS['options'];
$check(is_wp_error($save([$primary])) && is_wp_error(bl_catalogue_rest_save(new WP_REST_Request(['bl_catalogue_url' => '']))), 'direct write permission missing');
$check($GLOBALS['options'] === $state, 'unauthorized save changed options');
$GLOBALS['caps'] = [];
// Exercise the actual form save and incomplete-submission boundary.
$_POST = ['bl_page' => 'downloads', 'bl_content_revision' => bl_content_revision(bl_downloads_saved()), 'bl_content' => ['items' => [$primary, $manual]], 'bl_content_complete' => '1'];
try { bl_content_handle_submission('save'); } catch (TestRedirect $redirect) {}
$check(count($rows()) === 2, 'form save failed');
$state = $GLOBALS['options'];
unset($_POST['bl_content_complete']);
try { bl_content_handle_submission('save'); } catch (RuntimeException $error) { $check(str_contains($error->getMessage(), '不完整'), 'incomplete form error missing'); }
$check($GLOBALS['options'] === $state, 'incomplete form changed data');
// Unicode media paths remain usable both when selected now and inherited from the old option.
$GLOBALS['options'] = ['bl_catalogue_url' => 'https://example.test/uploads/产品画册.pdf'];
$unicodePrimary = $rows()[0];
$unicodeManual = array_replace($manual, ['url' => 'https://example.test/uploads/用户手册.pdf']);
$check(!is_wp_error($save([$unicodePrimary, $unicodeManual])), 'Unicode media URLs blocked list save');
$check($rows()[0]['url'] === $unicodePrimary['url'] && $rows()[1]['url'] === $unicodeManual['url'], 'Unicode URL bytes changed');
$unicodeManual['title'] = 'Updated manual';
$check(!is_wp_error($save([$unicodePrimary, $unicodeManual])), 'unchanged Unicode URLs blocked metadata edit');
$encoded = array_replace($unicodeManual, ['url' => 'https://example.test/uploads/%E7%94%A8%E6%88%B7.pdf?download=1#page=2']);
$check(!is_wp_error($save([$unicodePrimary, $encoded])) && $rows()[1]['url'] === $encoded['url'], 'encoded path was double encoded');
foreach (['https:///manual.pdf', 'https://bad_host.test/用户手册.pdf', 'ftp://example.test/manual.pdf', 'https://example.test:99999/file.pdf'] as $url) {
    $state = $GLOBALS['options'];
    $check(is_wp_error($save([$unicodePrimary, array_replace($manual, ['url' => $url])])) && $GLOBALS['options'] === $state, 'invalid scheme/host/port accepted');
}
$GLOBALS['options'] = ['bl_catalogue_url' => 'https://legacy_host.test/旧画册.pdf'];
$oldPrimary = $rows()[0];
$check(!is_wp_error($save([$oldPrimary, $manual])) && $rows()[0]['url'] === $oldPrimary['url'], 'unchanged historical URL blocked saving other rows');
echo "P6 downloads: $checks checks passed\n";
