<?php
/**
 * php tests/test_dealers_page_wp.php — WordPress gets a published page at /dealers/ once. A draft or private page an editor
 * already keeps there is never published by the theme: it is left alone, the dashboard says so, and nothing is marked done.
 */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('HOUR_IN_SECONDS', 3600);
define('OBJECT', 'OBJECT');
$GLOBALS['options'] = $GLOBALS['transients'] = $GLOBALS['posts'] = $GLOBALS['hooks'] = [];
function add_action($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$hook][] = $callback; }
function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$hook][] = $callback; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value) { $GLOBALS['options'][$key] = $value; return true; }
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function set_transient($key, $value, $ttl = 0) { $GLOBALS['transients'][$key] = $value; return true; }
function delete_transient($key) { unset($GLOBALS['transients'][$key]); return true; }
function bl_lock_acquire($name, $tries = 50) { return 'token'; }
function bl_lock_release($name, $token) {}
// As WordPress does: a single type also searches attachments; a list of types searches only those.
function get_page_by_path($path, $output = 'OBJECT', $type = 'page') { $types = is_array($type) ? $type : [$type, 'attachment']; foreach ($GLOBALS['posts'] as $post) if ($post->post_name === $path && in_array($post->post_type ?? 'page', $types, true)) return $post; return null; }
function get_post_type($id) { return isset($GLOBALS['posts'][$id]) ? ($GLOBALS['posts'][$id]->post_type ?? 'page') : false; }
function wp_insert_post($args, $error = false) { $id = 100 + count($GLOBALS['posts']); $GLOBALS['posts'][$id] = (object) ['ID' => $id, 'post_name' => $args['post_name'], 'post_status' => $args['post_status']]; return $id; }
function get_post_field($field, $id) { return $GLOBALS['posts'][$id]->$field ?? ''; }
function get_post_status($id) { return $GLOBALS['posts'][$id]->post_status ?? false; }
function wp_delete_post($id, $force = false) { unset($GLOBALS['posts'][$id]); }
function is_wp_error($thing) { return false; }
function current_user_can($cap) { return true; }
function get_edit_post_link($id) { return 'https://example.test/wp-admin/post.php?post=' . $id . '&action=edit'; }
function esc_url($v) { return (string) $v; }
function esc_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
require dirname(__DIR__) . '/inc/bootstrap.php';
require dirname(__DIR__) . '/inc/wp-inquiry.php';
$fail = static function (string $what): void { fwrite(STDERR, $what . "\n"); exit(1); };
$notice = static function (): string { ob_start(); foreach ($GLOBALS['hooks']['admin_notices'] ?? [] as $callback) $callback(); return (string) ob_get_clean(); };

// Nothing at the address: a published page is made and remembered.
bl_wp_dealers_page();
$id = (int) get_option('bl_dealers_page');
if ($id <= 0 || get_post_status($id) !== 'publish' || get_post_field('post_name', $id) !== 'dealers') $fail('page not created');

// A draft already there: it stays a draft, provisioning is not marked done, and the editor is told.
$GLOBALS['options'] = $GLOBALS['transients'] = [];
$GLOBALS['posts'] = [7 => (object) ['ID' => 7, 'post_name' => 'dealers', 'post_status' => 'draft']];
bl_wp_dealers_page();
if (get_option('bl_dealers_page') || $GLOBALS['posts'][7]->post_status !== 'draft' || count($GLOBALS['posts']) !== 1) $fail('draft page published or marked done');
if (get_transient('bl_dealers_page_conflict') !== 7 || !str_contains($notice(), 'post=7')) $fail('editor not told about the draft');
bl_wp_dealers_page();
if (count($GLOBALS['posts']) !== 1) $fail('check repeated within the hour');

// Once the editor publishes it, the notice clears and that page is used.
$GLOBALS['posts'][7]->post_status = 'publish';
if ($notice() !== '' || get_transient('bl_dealers_page_conflict')) $fail('notice stays after publishing');
bl_wp_dealers_page();
if ((int) get_option('bl_dealers_page') !== 7) $fail('published page not used');

// If the editor deletes it instead, the theme makes its own.
$GLOBALS['options'] = $GLOBALS['transients'] = [];
$GLOBALS['posts'] = [7 => (object) ['ID' => 7, 'post_name' => 'dealers', 'post_status' => 'private']];
bl_wp_dealers_page();
unset($GLOBALS['posts'][7]);
$notice();
bl_wp_dealers_page();
if (($id = (int) get_option('bl_dealers_page')) <= 0 || $id === 7 || get_post_status($id) !== 'publish') $fail('page not made after the private one was deleted');

// A media file whose slug is "dealers": not treated as a draft page, not touched, and the editor is told to rename it.
$GLOBALS['options'] = $GLOBALS['transients'] = [];
$GLOBALS['posts'] = [9 => (object) ['ID' => 9, 'post_name' => 'dealers', 'post_status' => 'inherit', 'post_type' => 'attachment']];
bl_wp_dealers_page();
if (get_option('bl_dealers_page') || count($GLOBALS['posts']) !== 1 || get_transient('bl_dealers_page_conflict') !== 9) $fail('media file collision not held');
if (!str_contains($notice(), '媒体库') || !str_contains($notice(), 'post=9')) $fail('media file notice');
$GLOBALS['posts'][9]->post_name = 'dealers-photo';
$notice();
bl_wp_dealers_page();
if (($id = (int) get_option('bl_dealers_page')) <= 0 || get_post_field('post_name', $id) !== 'dealers' || get_post_status($id) !== 'publish') $fail('page not made after the media file was renamed');
echo "dealers page: ok\n";
