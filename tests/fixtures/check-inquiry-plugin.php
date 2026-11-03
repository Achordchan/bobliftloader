<?php
/** What the theme hands the inquiry plugin: its fields, the cleaned submission, the form, the recipient and the defaults. */
declare(strict_types=1);
define('ABSPATH', __DIR__ . '/');
$GLOBALS['filters'] = [];
function add_action($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['filters'][$hook][] = $callback; }
function add_filter($hook, $callback, $priority = 10, $args = 1) { $GLOBALS['filters'][$hook][] = $callback; }
function apply($hook, ...$args) { foreach ($GLOBALS['filters'][$hook] ?? [] as $callback) $args[0] = $callback(...$args); return $args[0]; }
function get_template_directory_uri() { return 'https://example.test/theme'; }
function home_url($path = '') { return 'https://example.test' . $path; }
function admin_url($path = '') { return 'https://example.test/wp-admin/' . $path; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
function esc_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_textarea($v) { return htmlspecialchars((string) $v, ENT_QUOTES); }
function esc_url($v) { return (string) $v; }
function wp_make_link_relative($v) { return (string) preg_replace('#^https?://[^/]+#', '', (string) $v); }
function current_theme_supports($name) { return $name === 'achord-inquiry'; }
// The plugin's side, as far as the theme calls it.
function bl_wp_products() { return [['model' => 'BL-T360', 'name' => 'BL-T360 Mini Skid Steer', 'slug' => 'bl-t360', 'url' => 'https://example.test/product/bl-t360/', 'image' => '', 'category' => 'skid-steer-loaders']]; }
function achord_inquiry_active() { return true; }
function achord_inquiry_hidden_fields(array $args = []) { return '<input type="hidden" name="action" value="bl_submit_inquiry"><input type="hidden" name="source_entry" value="' . esc_attr($args['source'] ?? '') . '">'; }
function achord_inquiry_action_url() { return 'https://example.test/?bl_inquiry_submit=1'; }
function achord_inquiry_turnstile_widget($class = '', $lazy = false) { return $GLOBALS['turnstile'] ? '<div class="achord-inquiry-turnstile ' . $class . '"><div data-turnstile-widget data-sitekey="k"></div></div>' : ''; }
function achord_inquiry_client_ip() { return '203.0.113.7'; }
function achord_inquiry_ip_country($ip) { return $ip === '203.0.113.7' ? '加拿大' : ''; }
require dirname(__DIR__, 2) . '/inc/bootstrap.php';
require dirname(__DIR__, 2) . '/inc/wp-inquiry.php';

$fail = static function (int $code, string $what): void { echo $what . "\n"; exit($code); };

// The fields: WhatsApp is asked, the country is not (it comes from the IP), the product is shown in the mail's card.
$fields = apply('achord_inquiry_fields', []);
if (array_keys($fields) !== ['product', 'model', 'name', 'email', 'phone', 'company', 'country', 'message']) $fail(1, 'fields');
if ($fields['phone']['label'] !== 'WhatsApp' || $fields['product']['mail'] !== false || $fields['country']['mail'] !== false) $fail(2, 'field labels');

// The submission: the family in Chinese, a model only if the site sells it, the country from the IP.
$model = (string) bl_products()[0]['model'];
$clean = apply('achord_inquiry_submission_fields', ['product' => 'Mini dumper', 'model' => strtolower($model), 'country' => 'Narnia', 'language' => '']);
if ($clean['product'] !== '迷你翻斗车' || $clean['model'] !== $model || $clean['country'] !== '加拿大' || $clean['language'] !== 'en') $fail(3, 'submission ' . json_encode($clean, JSON_UNESCAPED_UNICODE));
$clean = apply('achord_inquiry_submission_fields', ['product' => '<script>', 'model' => 'NOT-A-MODEL']);
if ($clean['product'] !== '' || $clean['model'] !== '') $fail(4, 'unknown family or model kept');

// The form: posts to the plugin, the page's choice checked, WhatsApp after the email, the source named, no Contact Form 7.
$GLOBALS['turnstile'] = false;
$html = bl_wp_inquiry_form(['product' => 'dumper', 'model' => $model, 'message' => 'Quote <please>', 'source' => 'Quick enquiry']);
if (!str_contains($html, 'action="https://example.test/?bl_inquiry_submit=1"') || !str_contains($html, 'class="bl-inquiry-form"')) $fail(5, 'form action');
if (!str_contains($html, 'value="Mini dumper" checked') || substr_count($html, ' checked') !== 1) $fail(6, 'chosen family');
if (strpos($html, 'name="email"') > strpos($html, 'name="phone"') || !str_contains($html, 'type="tel" name="phone"')) $fail(7, 'whatsapp field');
if (!str_contains($html, 'Quote &lt;please&gt;</textarea>') || !str_contains($html, 'value="Quick enquiry"') || str_contains($html, 'wpcf7')) $fail(8, 'prefill, source or cf7');
if (str_contains($html, 'data-turnstile-widget')) $fail(9, 'turnstile drawn while off');
$GLOBALS['turnstile'] = true;
if (!str_contains(bl_wp_inquiry_form([]), 'data-turnstile-widget') || !str_contains(bl_wp_inquiry_form([]), 'value="Skid steer loader" checked')) $fail(10, 'turnstile or default family');

// Notifications go to the site's sales email; the IP is kept by default and Contact Form 7's Turnstile keys carry over.
$GLOBALS['options']['bl_content_global'] = ['company' => ['email' => 'sales@example.test']];
if (apply('achord_inquiry_recipient', '') !== 'sales@example.test') $fail(11, 'recipient');
$defaults = ['store_ip' => '0', 'turnstile_enabled' => '0', 'turnstile_site_key' => '', 'turnstile_secret_key' => ''];
if (apply('achord_inquiry_default_options', $defaults) !== ['store_ip' => '1'] + $defaults) $fail(12, 'defaults without cf7');
$GLOBALS['options']['wpcf7'] = ['turnstile' => ['site-k' => 'secret-k']];
$given = apply('achord_inquiry_default_options', $defaults);
if ($given['turnstile_site_key'] !== 'site-k' || $given['turnstile_secret_key'] !== 'secret-k' || $given['turnstile_enabled'] !== '1') $fail(13, 'cf7 turnstile keys');
echo "inquiry plugin integration ok\n";
