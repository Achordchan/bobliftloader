<?php
/** An enquiry sent from a Spanish page: the form says so, the plugin's messages and thank-you page follow, DingTalk names it. */
declare(strict_types=1);

$GLOBALS['filters'] = [];
// What inc/wp-inquiry.php takes from WordPress.
function add_action($name, $callback, $priority = 10, $args = 1) { $GLOBALS['filters'][$name][$priority][] = $callback; }
function add_filter($name, $callback, $priority = 10, $args = 1) { add_action($name, $callback, $priority, $args); }
function home_url($path = '') { return 'https://bobliftloader.com' . $path; }
function current_theme_supports($feature) { return $feature === 'achord-inquiry'; }
function sanitize_key($key) { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string) $key)); }
function wp_unslash($value) { return $value; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function achord_inquiry_name($field) { return 'achord_inquiry_' . $field; }
function bl_brand_logo() { return '/assets/images/logo.png'; }
require __DIR__ . '/../inc/helpers.php';
require __DIR__ . '/../inc/i18n.php';
require __DIR__ . '/../inc/wp-inquiry.php';

$run = static function (string $name, $value, ...$args) {
    $hooks = $GLOBALS['filters'][$name] ?? [];
    ksort($hooks);
    foreach ($hooks as $callbacks) foreach ($callbacks as $callback) $value = $callback($value, ...$args);
    return $value;
};
$check = static function (bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
};

// English first: nothing changes.
$check($run('achord_inquiry_page_language', 'en') === 'en', 'an English page sends en');
$config = $run('achord_inquiry_config', ['success_url' => '', 'fallback_url' => '']);
$check($config['success_url'] === 'https://bobliftloader.com/thank-you/', 'English thank-you page');

// The plugin reads the posted language before it answers.
$_POST['achord_inquiry_lang'] = 'es';
foreach ($GLOBALS['filters']['achord_inquiry_before_handle'] ?? [] as $callbacks) foreach ($callbacks as $callback) $callback();
$check(bl_lang() === 'es', 'the posted language is the reply language');
$config = $run('achord_inquiry_config', ['success_url' => '', 'fallback_url' => '']);
$check($config['success_url'] === 'https://bobliftloader.com/es/thank-you/', 'Spanish thank-you page');
$check($config['fallback_url'] === 'https://bobliftloader.com/es/contact/', 'Spanish contact page');
$check($run('achord_inquiry_message', 'Please complete the verification and try again.', 'verificationRequired') === 'Complete la verificación e inténtelo de nuevo.', 'plugin message in Spanish');
$rows = $run('achord_inquiry_dingtalk_rows', [['label' => '姓名', 'value' => 'Ana', 'long' => false]], ['language' => 'es']);
$check(($rows[0]['label'] ?? '') === '语言' && ($rows[0]['value'] ?? '') === '西班牙语（ES）', 'DingTalk names the language first');
$rows = $run('achord_inquiry_dingtalk_rows', [['label' => '姓名', 'value' => 'Ana', 'long' => false]], ['language' => 'en']);
$check(!in_array('语言', array_column($rows, 'label'), true), 'no language row for English');
// A language the site does not have is ignored.
$GLOBALS['bl_lang'] = 'en';
$_POST['achord_inquiry_lang'] = 'xx';
foreach ($GLOBALS['filters']['achord_inquiry_before_handle'] ?? [] as $callbacks) foreach ($callbacks as $callback) $callback();
$check(bl_lang() === 'en', 'an unknown posted language is ignored');
echo "i18n enquiry ok\n";
