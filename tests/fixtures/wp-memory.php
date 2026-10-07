<?php
/** Minimal WordPress boundary doubles for P0 tests; no database, HTTP calls, or production options are used. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
define('OBJECT', 'OBJECT');
define('DAY_IN_SECONDS', 86400);
$GLOBALS['hooks'] = $GLOBALS['options'] = $GLOBALS['meta'] = $GLOBALS['transients'] = [];
function add_action($name, $callback, $priority = 10, $args = 1) { $GLOBALS['hooks'][$name][$priority][] = $callback; }
function add_filter($name, $callback, $priority = 10, $args = 1) { add_action($name, $callback, $priority, $args); }
function run_hook($name, ...$args) {
    $hooks = $GLOBALS['hooks'][$name] ?? []; ksort($hooks);
    foreach ($hooks as $callbacks) foreach ($callbacks as $callback) $callback(...$args);
}
function run_filter($name, $value, ...$args) {
    $hooks = $GLOBALS['hooks'][$name] ?? []; ksort($hooks);
    foreach ($hooks as $callbacks) foreach ($callbacks as $callback) $value = $callback($value, ...$args);
    return $value;
}
function register_rest_field($type, $name, $args) { $GLOBALS['rest_fields'][$type][$name] = $args; }
function home_url($path = '') { return 'https://example.test' . $path; }
function wp_parse_url($url) { return parse_url($url); }
function get_template_directory_uri() { return 'https://example.test/theme'; }
function wp_cache_get(...$args) { return false; }
function wp_cache_set(...$args) {}
function wp_cache_delete(...$args) {}
function is_email($value) { return filter_var($value, FILTER_VALIDATE_EMAIL); }
function sanitize_email($value) { $value = trim((string) $value); return filter_var($value, FILTER_VALIDATE_EMAIL) ? $value : ''; }
function wp_date($format, $time = null) { return gmdate($format, $time ?? time()); }
function sanitize_text_field($value) { return trim(strip_tags($value)); }
function sanitize_textarea_field($value) { return trim(strip_tags($value)); }
function sanitize_title($value) { return strtolower(preg_replace('/[^a-zA-Z0-9-]/', '', $value)); }
function sanitize_key($value) { return sanitize_title($value); }
function esc_url($value) { return htmlspecialchars($value, ENT_QUOTES); }
function esc_url_raw($value, $protocols = []) { return $value; }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES); }
function esc_attr($value) { return esc_html($value); }
function esc_textarea($value) { return esc_html($value); }
function wp_unslash($value) { return $value; }
function wp_kses($value, $allowed) { return $value; }
// Sanitizer internals are not under test; the real theme's field selection/merge/save code is executed unchanged.
class WP_HTML_Tag_Processor {
    function __construct(private string $html) {}
    function next_tag($tag) { return false; }
    function get_updated_html() { return $this->html; }
}
class WP_Error {
    function __construct(public string $code, public string $message, public array $data = []) {}
    function get_error_message() { return $this->message; }
    function get_error_code() { return $this->code; }
}
function is_wp_error($value) { return $value instanceof WP_Error; }
class WP_REST_Request {
    function __construct(public array $params) {}
    function get_param($key) { return $this->params[$key] ?? null; }
    function set_param($key, $value) { $this->params[$key] = $value; }
}
class WP_Post {
    public string $post_content = '';
    public string $post_content_filtered = '';
    public string $post_excerpt = '';
    function __construct(public int $ID, public string $post_type, public string $post_name, public string $post_title = '', public string $post_status = 'publish') {}
}
function get_post($id) { return $GLOBALS['posts'][$id] ?? null; }
function get_post_type($id) { return get_post($id)->post_type; }
function get_posts($query) { return array_values(array_filter($GLOBALS['posts'], fn($p) => $p->post_type === $query['post_type'] && ($query['post_status'] === 'any' || in_array($p->post_status, (array) $query['post_status'], true)))); }
function get_page_by_path($slug, $output, $type) { foreach ($GLOBALS['posts'] as $post) if ($post->post_type === $type && $post->post_name === $slug) return $post; return null; }
function get_post_meta($id, $key, $single) { return $GLOBALS['meta'][$id][$key] ?? []; }
function update_post_meta($id, $key, $value) {
    $check = run_filter('update_post_metadata', null, $id, $key, $value, '');
    if ($check !== null) return $check;
    if (($GLOBALS['fail_meta'] ?? 0) === $id || ($GLOBALS['fail_meta_key'] ?? '') === $key) return false;
    if (($GLOBALS['meta'][$id][$key] ?? null) === $value) return false;
    $GLOBALS['meta'][$id][$key] = $value; return true;
}
function wp_slash($value) { return $value; }
function get_userdata($id) { return (object) ['display_name' => 'Editor ' . $id]; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function update_option($key, $value, $autoload = null) {
    if (($GLOBALS['fail_option'] ?? '') === $key) return false;
    if (get_option($key) === $value) return false;
    $GLOBALS['options'][$key] = $value; return true;
}
function delete_option($key) { unset($GLOBALS['options'][$key]); return true; }
function set_transient($key, $value, $ttl) { $GLOBALS['transients'][$key] = $value; return true; }
function get_transient($key) { return $GLOBALS['transients'][$key] ?? false; }
function maybe_unserialize($value) { return $value === '' ? false : unserialize($value); }
function wp_generate_password(...$args) { return bin2hex(random_bytes(10)); }
function wp_generate_uuid4() { return bin2hex(random_bytes(16)); }
function current_user_can(...$args) { $GLOBALS['cap_checks'][] = $args; return $GLOBALS['caps'][$args[0]] ?? true; }
function get_current_user_id() { return $GLOBALS['user_id'] ?? 7; }
function wp_verify_nonce($nonce, $action) { return $nonce === $action; }
function check_admin_referer($action) {}
function wp_is_post_revision($id) { return false; }
function wp_is_post_autosave($id) { return false; }
function checked($a, $b, $echo = true) { return $a === $b ? ' checked="checked"' : ''; }
function admin_url($path) { return home_url('/wp-admin/' . $path); }
class TestRedirect extends RuntimeException {}
function wp_safe_redirect($url) { throw new TestRedirect($url); }
function wp_die($message, $title = '', $args = []) { throw new RuntimeException($message); }
function bl_wp_products() { return array_map(fn($p) => ['slug' => $p->post_name, 'model' => (string) (get_post_meta($p->ID, '_bl_record', true)['model'] ?? $p->post_name)], get_posts(['post_type' => 'bl_product', 'post_status' => 'publish'])); }
class MemoryWpdb {
    public string $options = 'options';
    function prepare($sql, ...$args) { return [$sql, $args]; }
    function get_var($query) { return isset($GLOBALS['options'][$query[1][0]]) ? (str_starts_with($query[1][0], 'bl_lock_') ? $GLOBALS['options'][$query[1][0]] : serialize($GLOBALS['options'][$query[1][0]])) : null; }
    function query($query) {
        [$sql, $args] = $query;
        if (str_starts_with($sql, 'INSERT IGNORE')) {
            if (isset($GLOBALS['options'][$args[0]])) return 0;
            $GLOBALS['options'][$args[0]] = $args[1]; return 1;
        }
        if (str_starts_with($sql, 'UPDATE') && ($GLOBALS['options'][$args[1]] ?? '') < $args[2]) { $GLOBALS['options'][$args[1]] = $args[0]; return 1; }
        return 0;
    }
    function delete($table, $where) { if (($GLOBALS['options'][$where['option_name']] ?? null) === $where['option_value']) unset($GLOBALS['options'][$where['option_name']]); }
}
$GLOBALS['wpdb'] = new MemoryWpdb();

function register_setting($group, $name, $args) { $GLOBALS['settings'][$name] = $args; }
function register_rest_route($namespace, $route, $args) { $GLOBALS['rest_routes'][$namespace . $route] = $args; }
function register_meta($type, $key, $args) { $GLOBALS['registered_meta'][$type][$key] = $args; }
function register_post_meta($type, $key, $args) { $GLOBALS['post_meta_schema'][$type][$key] = $args; }
function register_post_type($type, $args) { $GLOBALS['post_types'][$type] = $args; }
function wp_kses_post($value) { return $value; }

function wp_nonce_field($action, $name = '_wpnonce') { echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($action) . '">'; }
function submit_button($text, ...$args) { echo '<button type="submit">' . esc_html($text) . '</button>'; }
function wp_strip_all_tags($value) { return strip_tags($value); }
function wp_html_excerpt($value, $count, $more = '') { return mb_substr($value, 0, $count) . (mb_strlen($value) > $count ? $more : ''); }
function wp_basename($value) { return basename($value); }
function bl_wp_inquiry_form_id() { return $GLOBALS['inquiry_form_id'] ?? 0; }
function get_site_icon_url($size = 512) { return get_option('site_icon') ? 'https://example.test/native-icon.png' : ''; }
