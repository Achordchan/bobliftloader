<?php
/** Native editing boundaries and legacy fallbacks; no WordPress database is bootstrapped. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
if (($argv[1] ?? '') === 'legacy') $GLOBALS['options']['bl_content_privacy'] = ['body' => ['intro' => '<h2>Stored introduction</h2>', 'ending' => '<p>Stored ending</p>']];
$GLOBALS['options']['bl_content_global'] = ['brand' => ['icon' => 'images/legacy.png']];
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-rest.php';
function add_meta_box(...$args) {}
function remove_post_type_support($type, $feature) { $GLOBALS['removed_support'][] = [$type, $feature]; }
function remove_meta_box($id, $type, $context) { $GLOBALS['removed_boxes'][] = [$id, $type, $context]; }
function get_queried_object() { return $GLOBALS['queried'] ?? null; }
function bl_page_editor_content() { return run_filter('the_content', get_queried_object()->post_content); }
class WP_Admin_Bar {
    public array $removed = [];
    function remove_node($id) { $this->removed[] = $id; }
}
function is_admin() { return true; }
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$capture = static function (callable $fn): string { ob_start(); $fn(); return ob_get_clean(); };
class NativeTestPost extends WP_Post { public int $post_parent = 0; }
$page = static function (int $id, string $slug, int $parent = 0): WP_Post {
    $post = new NativeTestPost($id, 'page', $slug);
    $post->post_parent = $parent;
    $post->post_content = '<p>Keep existing body</p>';
    return $post;
};
$GLOBALS['posts'] = [1 => $page(1, 'home'), 2 => $page(2, 'support'), 3 => $page(3, 'applications'), 4 => $page(4, 'landscaping', 3), 5 => $page(5, 'contact'), 6 => $page(6, 'thank-you'), 7 => $page(7, 'about'), 8 => $page(8, 'privacy'), 9 => $page(9, 'ordinary'), 10 => $page(10, 'privacy', 9)];
$GLOBALS['options']['show_on_front'] = 'page';
$GLOBALS['options']['page_on_front'] = 1;
$before = serialize($GLOBALS['posts']);
foreach (range(1, 6) as $id) {
    $post = get_post($id);
    $check(run_filter('use_block_editor_for_post', true, $post) === false, "structured page $id still uses blocks");
    $GLOBALS['removed_support'] = [];
    $_GET['post'] = $id;
    run_hook('load-post.php');
    $check($GLOBALS['removed_support'] === [['page', 'editor']], 'body editor not removed');
    $html = $capture(static fn() => run_hook('edit_form_after_title', $post));
    $check(str_contains($html, 'bl-panel') && str_contains($html, 'admin.php?page=bl-content-') && !str_contains($html, '<textarea'), 'missing read-only card or content control emitted');
}
foreach ([7, 8, 9, 10] as $id) {
    $check(run_filter('use_block_editor_for_post', true, get_post($id)) === ($id !== 8), 'native editor selection incorrect');
    $GLOBALS['removed_support'] = [];
    $_GET['post'] = $id;
    run_hook('load-post.php');
    $check($GLOBALS['removed_support'] === [], 'native editor support removed');
}
// An ordinary child page under Applications (not a scenario) keeps its editor; a scenario page does not.
$apps = array_values(array_filter($GLOBALS['posts'], static fn($p) => $p->post_type === 'page' && $p->post_name === 'applications'))[0] ?? null;
if ($apps) {
    $guide = new WP_Post(901, 'page', 'installation-guide', 'Installation guide'); $guide->post_parent = $apps->ID; $GLOBALS['posts'][901] = $guide;
    $scenario = new WP_Post(902, 'page', 'landscaping', 'Landscaping'); $scenario->post_parent = $apps->ID; $GLOBALS['posts'][902] = $scenario;
    $check(!bl_page_body_is_structured($guide) && bl_page_body_is_structured($scenario), 'non-scenario child page lost its editor');
    $card = $capture(static fn() => run_hook('edit_form_after_title', $guide));
    $check($card === '', 'an ordinary child page was told its body does not show');
    unset($GLOBALS['posts'][901], $GLOBALS['posts'][902]);
}
$GLOBALS['options']['show_on_front'] = 'posts';
$check(!bl_page_body_is_structured(get_post(1)), 'inactive static front page locked');
$check($before === serialize($GLOBALS['posts']), 'editor setup changed stored bodies');
// A password-protected privacy page shows the password form, never its body.
$GLOBALS['posts'][8]->post_content = '<p>Secret policy</p>';
$GLOBALS['posts'][8]->post_password = 'pw';
$GLOBALS['queried'] = get_post(8);
$check(str_contains(bl_privacy_content(), 'post-password-form') && !str_contains(bl_privacy_content(), 'Secret policy'), 'protected privacy body exposed');
$GLOBALS['posts'][8]->post_password = '';
$GLOBALS['posts'][8]->post_content = '';
$GLOBALS['queried'] = get_post(8);
foreach ([0, 9] as $mode) {
    $GLOBALS['inquiry_form_id'] = $mode;
    $expected = bl_privacy_body('intro') . bl_privacy_body($mode ? 'submitted' : 'draft') . bl_privacy_body('ending');
    $check(bl_privacy_content() === $expected, 'default privacy bytes changed');
}
$legacy = ['body' => ['intro' => '<h2>Stored introduction</h2>', 'ending' => '<p>Stored ending</p>']];
$check(bl_privacy_body('intro') === (($argv[1] ?? '') === 'legacy' ? $legacy['body']['intro'] : bl_privacy_body('intro')), 'legacy introduction ignored');
$check(bl_privacy_content() === bl_privacy_body('intro') . bl_privacy_body('submitted') . bl_privacy_body('ending'), 'saved legacy fallback changed');
$html = $capture('bl_privacy_migration_card');
$check(str_contains($html, 'data-bl-copy-policy') && str_contains($html, esc_textarea(bl_privacy_body('intro') . bl_privacy_body('ending'))), 'migration copy payload missing');
$GLOBALS['posts'][8]->post_content = '<p>Native privacy body</p>';
add_filter('the_content', static fn($body) => $body . '<p>Filtered</p>');
$check(bl_privacy_content() === '<p>Native privacy body</p><p>Filtered</p>' . bl_privacy_body('submitted'), 'native content filter or mode missing');
$check($capture('bl_privacy_migration_card') === '', 'migration warning remains after native body populated');
$GLOBALS['posts'][8]->post_content = '   ';
$check(bl_privacy_content() === bl_privacy_body('intro') . bl_privacy_body('submitted') . bl_privacy_body('ending'), 'whitespace body does not fall back');
foreach (['privacy' => $legacy, 'global' => ['brand' => ['icon' => 'images/legacy.png']]] as $key => $saved) {
    $prepared = bl_content_prepare($key, $saved, bl_content_defaults($key), true);
    foreach ($saved as $group => $values) foreach ($values as $field => $value) $check($prepared[$group][$field] === $value, 'hidden legacy value lost on save');
    $fields = array_merge(...array_column(bl_content_schema($key), 'fields'));
    $keys = array_column($fields, 'key');
    foreach ($key === 'privacy' ? ['body.intro', 'body.ending'] : ['brand.icon'] as $field) $check(!in_array($field, $keys, true), 'legacy field remains editable');
}
$GLOBALS['options']['bl_content_global'] = ['brand' => ['icon' => 'images/legacy.png']];
$check(bl_brand_icon('images/icons/icon-32.png', 32) === 'images/legacy.png', 'saved icon fallback lost');
$GLOBALS['options']['site_icon'] = 1;
$check(bl_brand_icon('images/icons/icon-32.png', 32) === 'https://example.test/native-icon.png', 'native site icon not preferred');
$check(str_contains($capture('bl_native_site_icon_note'), 'options-general.php'), 'site icon link does not point to General');
// Invoke this module's menu callback only; other registered callbacks require unrelated admin APIs.
$GLOBALS['submenu']['themes.php'] = [['Customize', 'manage_options', 'customize.php?return=x'], ['Theme editor', 'edit_themes', 'theme-editor.php'], ['Themes', 'switch_themes', 'themes.php']];
$GLOBALS['submenu']['plugins.php'] = [['Plugin editor', 'edit_plugins', 'plugin-editor.php']];
$callbacks = $GLOBALS['hooks']['admin_menu'][999];
end($callbacks)();
$check(array_column($GLOBALS['submenu']['themes.php'], 2) === ['themes.php'], 'misleading theme menu remains');
$check($GLOBALS['submenu']['plugins.php'][0][2] === 'plugin-editor.php', 'plugin editor changed');
$bar = new WP_Admin_Bar();
run_hook('admin_bar_menu', $bar);
$check(in_array('customize', $bar->removed, true), 'toolbar customizer remains');
try { run_hook('load-theme-editor.php'); $check(false, 'direct theme editor access allowed'); }
catch (RuntimeException $e) { $check(str_contains($e->getMessage(), '主题由 GitHub 部署'), 'wrong theme editor rejection'); }
foreach (['bl_product', 'bl_news', 'bl_application'] as $type) run_hook('add_meta_boxes_' . $type);
$check(count($GLOBALS['removed_boxes']) === 3, 'custom fields not hidden for all three record types');
echo "native admin and privacy compatibility ok\n";

if (!isset($argv[1])) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' legacy', $status);
    $check($status === 0, 'legacy stored policy regression');
}
