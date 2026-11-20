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
function sanitize_key($v) { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $v)); }
function wp_unslash($v) { return $v; }
function is_email($v) { return (bool) filter_var($v, FILTER_VALIDATE_EMAIL); }
function get_post_meta($id, $key, $single = false) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function current_theme_supports($name) { return $name === 'achord-inquiry'; }
// The plugin's side, as far as the theme calls it.
function bl_wp_products() { $GLOBALS['catalogue_loads'] = ($GLOBALS['catalogue_loads'] ?? 0) + 1; return [['model' => 'BL-T360', 'name' => 'BL-T360 Mini Skid Steer', 'slug' => 'bl-t360', 'url' => 'https://example.test/product/bl-t360/', 'image' => '', 'category' => 'skid-steer-loaders']]; }
function achord_inquiry_active() { return $GLOBALS['plugin_on'] ?? true; }
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
if (array_keys($fields) !== ['product', 'model', 'name', 'email', 'phone', 'company', 'country', 'message', 'business', 'market', 'website', 'promo']) $fail(1, 'fields');
if ($fields['phone']['label'] !== 'WhatsApp' || $fields['product']['mail'] !== false || $fields['country']['mail'] !== false) $fail(2, 'field labels');

// The submission: the family in Chinese, a model only if the site sells it, the country from the IP.
$model = (string) bl_products()[0]['model'];
$clean = apply('achord_inquiry_submission_fields', ['product' => 'Skid steer loader', 'model' => strtolower($model), 'country' => 'Narnia', 'language' => '', 'message' => 'Two units for a farm.']);
if ($clean['product'] !== '滑移装载机' || $clean['model'] !== $model || $clean['country'] !== '加拿大' || $clean['language'] !== 'en' || $clean['message'] !== 'Two units for a farm.') $fail(3, 'submission ' . json_encode($clean, JSON_UNESCAPED_UNICODE));
// A model is kept only under its own equipment: the loader's model sent with 迷你翻斗车 is dropped.
$clean = apply('achord_inquiry_submission_fields', ['product' => 'Mini dumper', 'model' => $model]);
if ($clean['product'] !== '迷你翻斗车' || $clean['model'] !== '') $fail(70, 'model kept under another equipment ' . json_encode($clean, JSON_UNESCAPED_UNICODE));
// The parts & service prompt lines alone are an empty message; anything written after them is kept.
$prompt = "Model:\nSerial number:\nWhat I need (part, fault or question):\n";
if (apply('achord_inquiry_submission_fields', ['product' => 'Parts & service', 'message' => $prompt])['message'] !== '') $fail(71, 'bare service prompt counted as a message');
if (apply('achord_inquiry_submission_fields', ['product' => 'Parts & service', 'message' => "Model: T360\nSerial number:\n"])['message'] === '') $fail(72, 'filled service prompt dropped');
// An enquiry must carry a message; the dealer application keeps its own required fields.
$required = apply('achord_inquiry_config', ['required' => ['name', 'email']])['required'];
if ($required !== ['name', 'email', 'message']) $fail(73, 'message not required on an enquiry ' . json_encode($required));
$_POST['bl_lead'] = 'dealer';
$required = apply('achord_inquiry_config', ['required' => ['name', 'email']])['required'];
unset($_POST['bl_lead']);
if ($required !== ['name', 'email']) $fail(74, 'message required on a dealer application ' . json_encode($required));
// The year-end offer mark: only the offers page's own value, only while the offers run (inc/promo.php), never on a dealer application.
$clean = apply('achord_inquiry_submission_fields', ['product' => 'Skid steer loader', 'promo' => BL_PROMO_CODE]);
if ($clean['promo'] !== (bl_promo_live() ? BL_PROMO_LABEL : '')) $fail(75, 'offer mark ' . json_encode($clean, JSON_UNESCAPED_UNICODE));
if (apply('achord_inquiry_submission_fields', ['product' => 'Skid steer loader', 'promo' => 'free-machine'])['promo'] !== '') $fail(76, 'unknown offer mark kept');
$clean = apply('achord_inquiry_submission_fields', ['product' => '<script>', 'model' => 'NOT-A-MODEL']);
if ($clean['product'] !== '' || $clean['model'] !== '') $fail(4, 'unknown family or model kept');

// The dealer application: its own lead type, the range and business in Chinese, no model; an enquiry drops the dealer fields.
$_POST['bl_lead'] = 'dealer';
$clean = apply('achord_inquiry_submission_fields', ['product' => 'Mini dumpers', 'business' => 'Rental company', 'model' => strtolower($model), 'market' => 'Australia']);
if (($clean['lead_type'] ?? '') !== 'dealer' || $clean['product'] !== '迷你翻斗车' || $clean['business'] !== '设备租赁' || $clean['model'] !== '' || $clean['market'] !== 'Australia' || $clean['country'] !== '加拿大') $fail(41, 'dealer ' . json_encode($clean, JSON_UNESCAPED_UNICODE));
unset($_POST['bl_lead']);
$clean = apply('achord_inquiry_submission_fields', ['product' => 'Mini dumper', 'business' => 'Rental company', 'market' => 'Australia', 'website' => 'https://x.test']);
if (isset($clean['lead_type']) || $clean['business'] !== '' || $clean['market'] !== '' || $clean['website'] !== '') $fail(42, 'dealer fields kept on an enquiry');
$types = apply('achord_inquiry_lead_types', []);
if (($types['dealer']['dingtalk_title'] ?? '') === '' || ($types['dealer']['kind'] ?? '') !== '经销商申请') $fail(43, 'dealer lead type');

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

// Notifications go where the Contact Form 7 form sent them until a sales email is set in 全站设置, then there.
$GLOBALS['options']['bl_inquiry_form'] = 18;
$GLOBALS['meta'][18]['_mail'] = ['recipient' => 'inbox@example.test'];
if (apply('achord_inquiry_recipient', '') !== 'inbox@example.test') $fail(14, 'legacy recipient');
$GLOBALS['options']['admin_email'] = 'admin@example.test';
$GLOBALS['meta'][18]['_mail'] = ['recipient' => '[_site_admin_email]'];
if (apply('achord_inquiry_recipient', '') !== 'admin@example.test') $fail(15, 'admin email tag');
$GLOBALS['meta'][18]['_mail'] = ['recipient' => 'Sales <sales@example.test>, [_site_admin_email], [unknown], not-an-address'];
if (bl_wp_inquiry_legacy_recipients() !== ['sales@example.test', 'admin@example.test'] || apply('achord_inquiry_recipient', '') !== 'sales@example.test') $fail(16, 'recipient list');
// The notification goes to all of them; another mail, or one the admin sent elsewhere, is left as it is.
apply('achord_inquiry_before_mail', null);
if (apply('wp_mail', ['to' => 'sales@example.test'])['to'] !== ['sales@example.test', 'admin@example.test']) $fail(17, 'extra inboxes');
if (apply('wp_mail', ['to' => 'sales@example.test'])['to'] !== 'sales@example.test') $fail(18, 'extra inboxes added to another mail');
apply('achord_inquiry_before_mail', null);
if (apply('wp_mail', ['to' => 'boss@example.test'])['to'] !== 'boss@example.test') $fail(19, 'admin recipient overridden');
$GLOBALS['meta'][18]['_mail'] = ['recipient' => '[_site_admin_email]'];
$GLOBALS['options']['admin_email'] = 'broken';
if (apply('achord_inquiry_recipient', '') !== 'info@boblift.com') $fail(20, 'unusable legacy recipient');
$GLOBALS['meta'][18]['_mail'] = ['recipient' => 'inbox@example.test'];
// (bl_company() keeps what it read first in this process, so the address itself is not compared here.)
$GLOBALS['options']['bl_content_global'] = ['company' => ['email' => 'sales@example.test']];
if (apply('achord_inquiry_recipient', '') !== bl_company('email')) $fail(11, 'recipient');
$defaults = ['store_ip' => '0', 'turnstile_enabled' => '0', 'turnstile_site_key' => '', 'turnstile_secret_key' => ''];
if (apply('achord_inquiry_default_options', $defaults) !== ['store_ip' => '1'] + $defaults) $fail(12, 'defaults without cf7');
$GLOBALS['options']['wpcf7'] = ['turnstile' => ['site-k' => 'secret-k']];
$given = apply('achord_inquiry_default_options', $defaults);
if ($given['turnstile_site_key'] !== 'site-k' || $given['turnstile_secret_key'] !== 'secret-k' || $given['turnstile_enabled'] !== '1') $fail(13, 'cf7 turnstile keys');
// A CF7 form that copied the mail elsewhere or answered the customer waits for an administrator to confirm the switch.
$GLOBALS['meta'][18]['_mail'] = ['recipient' => 'a@example.test', 'additional_headers' => "Reply-To: [name] <[email]>"];
if (bl_wp_inquiry_legacy_extras() !== [] || !bl_wp_inquiry_switch_allowed()) $fail(21, 'plain form held back');
$GLOBALS['meta'][18]['_mail']['additional_headers'] .= "\nBcc: boss@example.test";
$GLOBALS['meta'][18]['_mail_2'] = ['active' => true];
if (count(bl_wp_inquiry_legacy_extras()) !== 2 || bl_wp_inquiry_switch_allowed()) $fail(22, 'cc or autoresponder not held back');
$GLOBALS['meta'][18]['_mail_2'] = ['active' => false];
$GLOBALS['meta'][18]['_mail']['additional_headers'] = '';
$GLOBALS['meta'][18]['_form'] = '<label>Order number [text* order]</label>';
if (count(bl_wp_inquiry_legacy_extras()) !== 1 || bl_wp_inquiry_switch_allowed()) $fail(24, 'edited form not held back');
// The theme's own form on a site under a subdirectory is not an edited one.
$themeForm = <<<'FORM'
<fieldset><legend>01 / YOUR EQUIPMENT</legend>
[radio product use_label_element default:shortcode_attr "Skid steer loader" "Mini dumper" "Help me choose" "Parts & service"]
[hidden model default:shortcode_attr]</fieldset>
<fieldset><legend>02 / A LITTLE ABOUT YOU</legend><div class="bl-form-grid">
<label>Your name <span>*</span>[text* name autocomplete:name maxlength:100 placeholder "Full name"]</label>
<label>Business email <span>*</span>[email* email autocomplete:email maxlength:200 placeholder "you@company.com"]</label>
<label>Company[text company autocomplete:organization maxlength:150 placeholder "Company name"]</label>
</div>
<label class="bl-message-label">Your project[textarea message rows:3 maxlength:2000 default:shortcode_attr placeholder "What work do you need to get done?"]</label>
<div class="bl-hp" aria-hidden="true"><label>Leave this field empty[text bl_website tabindex:-1 autocomplete:off]</label></div></fieldset>
<div class="bl-form-bottom"><p>Your details are used only to answer this enquiry. <br>See our <a href="/privacy/">privacy information</a>.</p>[submit class:bl-button class:bl-button-blue "Send enquiry"]</div>
FORM;
$GLOBALS['meta'][18]['_form'] = $themeForm;
if (bl_wp_inquiry_legacy_extras() !== []) $fail(26, 'theme form read as an edit');
$GLOBALS['meta'][18]['_form'] = str_replace('href="/privacy/"', 'href="/site/privacy/"', $themeForm);
if (bl_wp_inquiry_legacy_extras() !== []) $fail(25, 'subdirectory privacy link read as an edit');
$GLOBALS['meta'][18]['_form'] = '<label>Order number [text* order]</label>';
$GLOBALS['meta'][18]['_form'] = $themeForm;
$GLOBALS['meta'][18]['_mail'] = ['recipient' => 'sales@example.test, [email]'];
if (count(bl_wp_inquiry_legacy_extras()) !== 1 || !str_contains(bl_wp_inquiry_legacy_extras()[0], '[email]') || bl_wp_inquiry_switch_allowed()) $fail(47, 'form-tag recipient not held back');
$GLOBALS['meta'][18]['_mail'] = ['recipient' => 'Sales <sales@example.test>, [_site_admin_email]'];
if (bl_wp_inquiry_legacy_extras() !== []) $fail(48, 'plain recipients held back');
$GLOBALS['meta'][18]['_form'] = '<label>Order number [text* order]</label>';
$GLOBALS['options']['bl_inquiry_switch_confirmed'] = '1';
if (!bl_wp_inquiry_switch_allowed()) $fail(23, 'confirmed switch held back');
// A cached page still posting the old CF7 form is turned back with a reload request; other CF7 forms are left alone.
class WPCF7_Validation { public array $errors = []; public function invalidate($tag, $message) { $this->errors[] = [$tag->name, $message]; } }
$tag = static fn(string $name) => (object) ['name' => $name, 'basetype' => $name];
$form = static fn(int $id) => new class($id) { public function __construct(private int $id) {} public function id() { return $this->id; } };
$GLOBALS['cf7_form'] = $form(18);
$result = apply('wpcf7_validate', new WPCF7_Validation(), [$tag('product'), $tag('name'), $tag('email')]);
if (count($result->errors) !== 1 || $result->errors[0][0] !== 'email' || !str_contains($result->errors[0][1], 'reload')) $fail(30, 'legacy cf7 submission not turned back');
$GLOBALS['cf7_form'] = $form(99);
if (apply('wpcf7_validate', new WPCF7_Validation(), [$tag('email')])->errors !== []) $fail(31, 'another cf7 form turned back');
function get_posts($args) { return ['bl_product' => [101, 102], 'wpcf7_contact_form' => [18, 19]][$args['post_type'] ?? ''] ?? []; }
function get_post_type($id) { return in_array((int) $id, [18, 19], true) ? 'wpcf7_contact_form' : ''; }
$GLOBALS['meta'][19]['_hash'] = 'ffff000aaaa';
// A saved [contact-form-7] shortcode of the old form is recognised by number, hash or title (without CF7); another form's is not.
function get_post_field($field, $id) { return $id === 18 ? 'Old enquiry' : ''; }
$GLOBALS['meta'][18]['_hash'] = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0';
if (!bl_wp_inquiry_is_legacy_shortcode(['id' => '18']) || !bl_wp_inquiry_is_legacy_shortcode(['id' => 'a1b2c3d']) || !bl_wp_inquiry_is_legacy_shortcode(['title' => 'Old enquiry'])) $fail(32, 'legacy shortcode not recognised');
$GLOBALS['meta'][18]['_hash'] = '1234567abc';
if (!bl_wp_inquiry_is_legacy_shortcode(['id' => '1234567'])) $fail(40, 'all-digit hash not recognised');
$GLOBALS['meta'][18]['_hash'] = 'a1b2c3d4e5f6a7b8c9d0e1f2a3b4c5d6e7f8a9b0';
// The title stands in for a stale id, not for the id of another form that exists.
if (!bl_wp_inquiry_is_legacy_shortcode(['id' => '555', 'title' => 'Old enquiry']) || !bl_wp_inquiry_is_legacy_shortcode(['id' => 'deadbee', 'title' => 'Old enquiry'])) $fail(45, 'stale id with title not recognised');
if (bl_wp_inquiry_is_legacy_shortcode(['id' => '19', 'title' => 'Old enquiry']) || bl_wp_inquiry_is_legacy_shortcode(['id' => 'ffff000', 'title' => 'Old enquiry'])) $fail(46, 'another form taken by title');
if (bl_wp_inquiry_is_legacy_shortcode(['id' => 'a1b']) || bl_wp_inquiry_is_legacy_shortcode(['title' => 'Other'])) $fail(38, 'short hash or other title taken');
if (bl_wp_inquiry_is_legacy_shortcode(['id' => '99']) || bl_wp_inquiry_is_legacy_shortcode(['id' => 'zzz'])) $fail(33, 'other shortcode taken over');
if (apply('pre_do_shortcode_tag', false, 'gallery', ['id' => '18']) !== false || apply('pre_do_shortcode_tag', false, 'contact-form-7', ['id' => '99']) !== false) $fail(34, 'other shortcode replaced');
// The email-draft form addresses the same inbox the notifications would go to.
if (function_exists('bl_is_wordpress') && bl_is_wordpress()) {
    if (bl_inquiry_fallback_email() !== bl_company('email')) $fail(35, 'fallback email with sales email set');
    unset($GLOBALS['options']['bl_content_global']);
    $GLOBALS['meta'][18]['_mail'] = ['recipient' => 'Sales <legacy@example.test>'];
    if (bl_inquiry_fallback_email() !== 'legacy@example.test') $fail(36, 'fallback email ignores the cf7 inbox');
} else $fail(37, 'not running as WordPress');
// Without CF7 the theme registers the shortcode names: the old form's renders the theme form, another's renders nothing.
$GLOBALS['shortcodes'] = [];
function shortcode_exists($name) { return isset($GLOBALS['shortcodes'][$name]); }
function add_shortcode($name, $callback) { $GLOBALS['shortcodes'][$name] = $callback; }
foreach ($GLOBALS['filters']['init'] ?? [] as $callback) if ($callback instanceof Closure && str_ends_with((new ReflectionFunction($callback))->getFileName(), '/inc/wp-inquiry.php')) $callback();
if (array_keys($GLOBALS['shortcodes']) !== ['contact-form-7', 'contact-form'] || ($GLOBALS['shortcodes']['contact-form-7'])(['id' => '99']) !== '') $fail(39, 'fallback shortcodes');
// Without the plugin the old shortcode draws no second email-draft form, only a mail button.
// With the plugin, the saved shortcode's product, model and message carry over, read without loading the catalogue
// (the shortcode may sit in a product's own text, which loading the catalogue renders).
$GLOBALS['meta'][101]['_bl_record'] = ['model' => 'BL-T200'];
$GLOBALS['meta'][102]['_bl_record'] = ['model' => $model];
$GLOBALS['catalogue_loads'] = 0;
$saved = bl_wp_inquiry_legacy_shortcode_form(['product' => 'Mini dumper', 'model' => strtolower($model), 'message' => 'Price <for 2>']);
if (!str_contains($saved, 'value="Mini dumper" checked') || !str_contains($saved, 'data-model="' . $model . '"') || !str_contains($saved, 'data-model-family="dumper"') || !str_contains($saved, 'Price &lt;for 2&gt;</textarea>')) $fail(42, 'shortcode prefill lost');
if (!str_contains(bl_wp_inquiry_legacy_shortcode_form(['product' => 'nonsense']), 'value="Skid steer loader" checked')) $fail(43, 'unknown family');
if ($GLOBALS['catalogue_loads'] !== 0 || bl_wp_inquiry_known_model('nope') !== '') $fail(44, 'shortcode loaded the catalogue or kept an unknown model');
$GLOBALS['plugin_on'] = false;
$button = bl_wp_inquiry_legacy_shortcode_form();
if (str_contains($button, '<form') || !str_contains($button, 'mailto:')) $fail(41, 'second draft form drawn');
echo "inquiry plugin integration ok\n";
