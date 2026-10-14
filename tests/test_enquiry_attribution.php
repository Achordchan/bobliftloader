<?php
/** The advertisement behind an enquiry: hidden fields are cleaned server-side, kept with the enquiry, and shown in the notification card. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';

class WPCF7_ContactForm
{
    public static ?WPCF7_ContactForm $current = null;
    public function __construct(private int $id, private string $mailBody = '') {}
    public function id(): int { return $this->id; }
    public static function get_current(): ?WPCF7_ContactForm { return self::$current; }
    public function prop(string $name): array { return ['body' => $this->mailBody]; }
}
class WPCF7_Submission
{
    public static ?WPCF7_Submission $instance = null;
    public function __construct(private array $data) {}
    public static function get_instance(): ?WPCF7_Submission { return self::$instance; }
    public function get_posted_data(): array { return $this->data; }
    public function get_posted_string(string $name): string { return (string) ($this->data[$name] ?? ''); }
    public function get_meta(string $name) { return ['url' => 'https://example.test/product/t460/', 'remote_ip' => '', 'timestamp' => 1760412000][$name] ?? ''; }
}
class WPCF7_Mail
{
    public function __construct(private string $name) {}
    public function name(): string { return $this->name; }
}
const BL_ENQUIRY_CARD_MARKER = '<!-- bl-inquiry-card -->'; // as in inc/wp-inquiry.php
// What inc/wp-inquiry-templates.php takes from the rest of the theme.
function bl_wp_inquiry_card_mail(): bool { return true; }
function bl_wp_inquiry_mail(): array { return ['body' => BL_ENQUIRY_CARD_MARKER . "\n<table width=\"100%\"><tr><td>stock card</td></tr></table>"]; }
function bl_products(): array { return []; }
function bl_asset(string $path): string { return 'https://example.test/theme/assets/' . $path; }
function bl_wp_ip_country(string $ip): string { return '加拿大'; }
function bl_wp_inquiry_thumbnail(array $product): string { return ''; }
function bl_wp_inquiry_product_card(string $family, string $model): string { return ''; }
require __DIR__ . '/../inc/wp-enquiry-attribution.php';
require __DIR__ . '/../inc/wp-inquiry-templates.php';

$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

$GLOBALS['inquiry_form_id'] = 5;
$check(bl_wp_inquiry_form_id() === 5, 'the test form is not the theme form');

// A value is one printable line within its length; a click ID must look like one.
$check(bl_enquiry_attribution_clean('gclid', 'Cj0KCQ_abc-123') === 'Cj0KCQ_abc-123', 'a real click ID was altered');
$check(bl_enquiry_attribution_clean('gclid', "abc\n<script>") === '', 'a click ID with markup was kept');
$check(bl_enquiry_attribution_clean('gclid', str_repeat('a', 300)) === '', 'a 300-character click ID was cut instead of dropped');
$check(bl_enquiry_attribution_clean('utm_source', "goo\ngle <b>ads</b>") === 'goo gle b ads /b', 'tags or line breaks survived in a campaign tag');
$check(strlen(bl_enquiry_attribution_clean('utm_campaign', str_repeat('x', 500))) === 120, 'a campaign tag was not limited to 120');
$check(bl_enquiry_attribution_clean('anything_else', 'x') === '' && bl_enquiry_attribution_clean('gclid', ['a']) === '', 'an unknown name or an array was accepted');

// The posted data: only known fields, cleaned, and only for the theme's form.
$_POST = ['bl_attr_gclid' => 'EAIaIQ-1', 'bl_attr_landing' => 'https://example.test/product/t460/', 'bl_attr_utm_source' => 'google', 'bl_attr_evil' => 'x', 'bl_attr_gbraid' => "bad value<>", 'name' => 'Ann'];
WPCF7_ContactForm::$current = new WPCF7_ContactForm(5);
$data = run_filter('wpcf7_posted_data', ['name' => 'Ann', 'bl_attr_evil' => 'kept by Contact Form 7', 'bl_attr_utm_medium' => 'forged by a stale value']);
$check($data['name'] === 'Ann', 'the form fields were disturbed');
$check(($data['bl_attr_gclid'] ?? '') === 'EAIaIQ-1' && ($data['bl_attr_utm_source'] ?? '') === 'google' && ($data['bl_attr_landing'] ?? '') === 'https://example.test/product/t460/', 'the attribution fields were not added');
$check(!isset($data['bl_attr_evil']) && !isset($data['bl_attr_gbraid']) && !isset($data['bl_attr_utm_medium']), 'an unknown, malformed or stale attribution field survived');
WPCF7_ContactForm::$current = new WPCF7_ContactForm(6);
$other = run_filter('wpcf7_posted_data', ['name' => 'Ann', 'bl_attr_gclid' => 'x']);
$check($other === ['name' => 'Ann', 'bl_attr_gclid' => 'x'], 'another form was changed, or its own bl_attr_ field removed');
WPCF7_ContactForm::$current = null;
$check(run_filter('wpcf7_posted_data', ['name' => 'Ann', 'bl_attr_gclid' => 'x']) === ['name' => 'Ann', 'bl_attr_gclid' => 'x'], 'a submission of unknown form was changed');
WPCF7_ContactForm::$current = new WPCF7_ContactForm(5);
$_POST = ['name' => 'Ann'];
$check(run_filter('wpcf7_posted_data', ['name' => 'Ann']) === ['name' => 'Ann'], 'fields appeared although none were sent');

// What Flamingo keeps, read back without the prefix.
$attribution = bl_enquiry_attribution(['name' => 'Ann', 'bl_attr_gclid' => 'EAIaIQ-1', 'bl_attr_gad_campaignid' => '24354771502', 'bl_attr_gbraid' => "x<y"]);
$check($attribution === ['gclid' => 'EAIaIQ-1', 'gad_campaignid' => '24354771502'], 'the stored attribution was not read back: ' . json_encode($attribution));
$check(bl_enquiry_attribution(['name' => 'Ann']) === [], 'attribution found in an enquiry without it');

// The notification card: the block sits in front of the reply button, and only the theme's card gets it.
$button = '<tr><td align="center" style="padding:32px 36px 12px;">';
$card = '<table width="100%"><tr><td>body</td></tr>' . $button . 'reply</td></tr></table>';
$form = new WPCF7_ContactForm(5, BL_ENQUIRY_CARD_MARKER . "\n" . $card);
WPCF7_Submission::$instance = new WPCF7_Submission(['bl_attr_gclid' => 'EAIaIQ-1', 'bl_attr_utm_campaign' => 'loaders <1>']);
$out = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail'));
$at = strpos($out['body'], '广告来源');
$check($at !== false && $at < strpos($out['body'], $button), 'the advertisement block is missing or after the button');
$check(str_contains($out['body'], 'EAIaIQ-1') && str_contains($out['body'], 'Google Ads 点击') && !str_contains($out['body'], '<1>'), 'the block lacks the click ID, the label, or escapes nothing');
$autoreply = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail_2'));
$check($autoreply['body'] === $card, 'the autoresponder got the block');
$edited = run_filter('wpcf7_mail_components', ['body' => $card], new WPCF7_ContactForm(5, 'plain edited body'), new WPCF7_Mail('mail'));
$check($edited['body'] === $card, 'an edited mail got the block');
WPCF7_Submission::$instance = new WPCF7_Submission(['name' => 'Ann']);
$none = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail'));
$check($none['body'] === $card, 'the card changed for an enquiry without attribution');
WPCF7_Submission::$instance = new WPCF7_Submission(['bl_attr_utm_source' => 'newsletter']);
$tagsOnly = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail'));
$check(str_contains($tagsOnly['body'], '营销标记') && !str_contains($tagsOnly['body'], 'Google Ads 点击'), 'tags alone were labelled as a Google Ads click');

// The designs: the notification is drawn by wpcf7_mail_html_body (the default design c, and a, b, d), which replaces the whole body,
// so each design carries the lines itself. Run the whole pipeline: html body first, then the mail components.
$stock = bl_wp_inquiry_mail()['body'];
$stockForm = new WPCF7_ContactForm(5, $stock);
$posted = ['name' => 'Ann', 'email' => 'ann@example.test', 'message' => 'Price for 2 units?', 'bl_attr_gclid' => 'EAIaIQ-1', 'bl_attr_gad_campaignid' => '24354771502', 'bl_attr_utm_source' => 'google', 'bl_attr_landing' => 'https://example.test/product/t460/', 'bl_attr_first_seen' => '2026-10-14T03:20:00.000Z'];
$GLOBALS['options']['bl_inquiry_mail_template'] = null;
unset($GLOBALS['options']['bl_inquiry_mail_template']);
WPCF7_ContactForm::$current = $stockForm;
foreach (['c', 'a', 'b', 'd'] as $design) {
    $GLOBALS['options']['bl_inquiry_mail_template'] = $design;
    WPCF7_Submission::$instance = new WPCF7_Submission($posted);
    $html = run_filter('wpcf7_mail_html_body', $stock);
    $mail = run_filter('wpcf7_mail_components', ['body' => $html], $stockForm, new WPCF7_Mail('mail'));
    $check(str_contains($mail['body'], '新询盘通知') && str_contains($mail['body'], 'EAIaIQ-1') && str_contains($mail['body'], 'Google Ads 点击'), "design $design lacks the click ID or its label");
    $check(str_contains($mail['body'], '24354771502') && str_contains($mail['body'], 'https://example.test/product/t460/') && str_contains($mail['body'], '2026-10-14T03:20:00.000Z'), "design $design lacks the campaign, landing page or time");
    $check(substr_count($mail['body'], 'EAIaIQ-1') === 1, "design $design shows the click ID more than once");
    WPCF7_Submission::$instance = new WPCF7_Submission(['name' => 'Ann', 'email' => 'ann@example.test', 'bl_attr_utm_campaign' => '<b>x</b>']);
    $plain = run_filter('wpcf7_mail_html_body', $stock);
    $check(str_contains($plain, '营销标记') && !str_contains($plain, '<b>x') && !str_contains($plain, 'Google Ads 点击'), "design $design escaped nothing or labelled tags as a click");
    WPCF7_Submission::$instance = new WPCF7_Submission(['name' => 'Ann', 'email' => 'ann@example.test']);
    $none = run_filter('wpcf7_mail_html_body', $stock);
    $check(!str_contains($none, '广告来源'), "design $design shows an advertisement block for an enquiry without one");
}
$check(str_contains(bl_wp_inquiry_render('c', bl_wp_inquiry_data(bl_wp_inquiry_sample('product'))), 'EAIaIQobChMI-sample'), 'the preview sample has no advertisement lines');

echo "Enquiry attribution: $checks checks passed\n";
