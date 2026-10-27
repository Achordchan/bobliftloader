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
    public function get_meta(string $name) { return ['url' => 'https://example.test/product/t460/', 'remote_ip' => '', 'timestamp' => 1760412000, 'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1'][$name] ?? ''; }
}
class WPCF7_Mail
{
    public function __construct(private string $name) {}
    public function name(): string { return $this->name; }
}
const BL_ENQUIRY_CARD_MARKER = '<!-- bl-inquiry-card -->'; // as in inc/wp-inquiry.php
const BL_ENQUIRY_FAMILIES_ZH = ['Skid steer loader' => '滑移装载机', 'Mini dumper' => '迷你翻斗车', 'Help me choose' => '帮我选型', 'Parts & service' => '配件与服务']; // as in inc/wp-inquiry.php
// What inc/wp-inquiry-templates.php takes from the rest of the theme.
function bl_wp_inquiry_card_mail(): bool { return true; }
function bl_wp_inquiry_mail(): array { return ['body' => BL_ENQUIRY_CARD_MARKER . "\n<table width=\"100%\"><tr><td>stock card</td></tr></table>"]; }
function bl_products(): array { return []; }
function bl_asset(string $path): string { return 'https://example.test/theme/assets/' . $path; }
function bl_wp_ip_country(string $ip): string { return '加拿大'; }
function bl_wp_inquiry_thumbnail(array $product): string { return ''; }
function bl_wp_inquiry_product_card(string $family, string $model): string { return ''; }
function bl_wp_inquiry_page(string $url): array { return ['url' => $url, 'title' => '']; }
function bl_wp_inquiry_page_html(array $page, string $color): string { return '<a href="' . esc_url($page['url']) . '">' . esc_html($page['url']) . '</a>'; }
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
// The fields sent whatever the cookie choice: a referring host (never this site), the consent answer and touch, cleaned.
$_POST = ['name' => 'Ann', 'bl_page_referrer' => 'WWW.Google.com.cy', 'bl_ad_consent' => 'false', 'bl_touch' => '1', 'bl_attr_referrer' => 'chatgpt.com/path'];
$data = run_filter('wpcf7_posted_data', ['name' => 'Ann', 'bl_page_referrer' => 'stale']);
$check(($data['bl_page_referrer'] ?? '') === 'google.com.cy' && ($data['bl_ad_consent'] ?? '') === 'false' && ($data['bl_touch'] ?? '') === '1' && !isset($data['bl_attr_referrer']), 'the context fields were not cleaned and added: ' . json_encode($data));
$_POST = ['name' => 'Ann', 'bl_page_referrer' => home_url('/'), 'bl_ad_consent' => 'maybe', 'bl_touch' => 'yes', 'bl_attr_referrer' => 'www.chatgpt.com'];
$data = run_filter('wpcf7_posted_data', ['name' => 'Ann']);
$check(!isset($data['bl_page_referrer']) && !isset($data['bl_ad_consent']) && !isset($data['bl_touch']) && ($data['bl_attr_referrer'] ?? '') === 'chatgpt.com', 'a malformed context field was kept, or the kept referrer lost: ' . json_encode($data));
$_POST = ['name' => 'Ann', 'bl_page_referrer' => (string) wp_parse_url(home_url(), PHP_URL_HOST)];
$check(!isset(run_filter('wpcf7_posted_data', ['name' => 'Ann'])['bl_page_referrer']), "this site's own pages counted as a referring site");

// The channel in the crane site's words: a click, then tags, then the site the visitor came from, then why nothing is known.
$channel = static fn(array $attribution, array $context = []): string => bl_wp_inquiry_channel($attribution, $context);
$check($channel([], ['page_referrer' => 'google.com.cy']) === '自然搜索 · Google' && $channel([], ['page_referrer' => 'bing.com']) === '自然搜索 · Bing', 'a search engine was not named');
$check($channel([], ['page_referrer' => 'docs.google.com']) === '外部网站 · docs.google.com' && $channel([], ['page_referrer' => 'bobliftcrane.com']) === '外部网站 · bobliftcrane.com', 'a link from another site was misread');
$check($channel([], ['page_referrer' => 'gemini.google.com']) === 'AI 助手推荐 · Gemini' && $channel(['referrer' => 'chatgpt.com']) === 'AI 助手推荐 · ChatGPT', 'an AI assistant was misread');
$check($channel([], ['page_referrer' => 'm.facebook.com']) === '社交媒体 · Facebook' && $channel([], ['page_referrer' => 'googleadservices.com']) === 'Google Ads 广告 · Google Ads（没有点击编号）', 'a social site or an ad without click ID was misread');
$check($channel(['referrer' => 'bing.com'], ['page_referrer' => 'facebook.com']) === '自然搜索 · Bing', 'the referrer kept since landing did not win over the form page');
$check($channel(['gclid' => 'x', 'utm_term' => 'mini loader'], ['page_referrer' => 'google.com']) === 'Google Ads 广告 · 关键词 mini loader', 'a click did not win over the referrer');
$check($channel([], ['ad_consent' => 'false']) === '来源未知 · 访客没同意广告 Cookie，进站来源没有记录' && str_starts_with($channel([]), '直接访问'), 'a visit with nothing known was misread');

// What Flamingo keeps, read back without the prefix.
$attribution = bl_enquiry_attribution(['name' => 'Ann', 'bl_attr_gclid' => 'EAIaIQ-1', 'bl_attr_gad_campaignid' => '24354771502', 'bl_attr_gbraid' => "x<y"]);
$check($attribution === ['gclid' => 'EAIaIQ-1', 'gad_campaignid' => '24354771502'], 'the stored attribution was not read back: ' . json_encode($attribution));
$check(bl_enquiry_attribution(['name' => 'Ann']) === [], 'attribution found in an enquiry without it');

// The notification card: the channel block sits in front of the reply button, the raw values after the reply line, and only
// the theme's card gets them.
$button = '<tr><td align="center" style="padding:32px 36px 12px;">';
$replyLine = '<tr><td align="center">也可以直接回复这封邮件，回信会发到 ann@example.test</td></tr>';
$card = '<table width="100%"><tr><td>body</td></tr>' . $button . 'reply</td></tr>' . $replyLine . '</table>';
$form = new WPCF7_ContactForm(5, BL_ENQUIRY_CARD_MARKER . "\n" . $card);
WPCF7_Submission::$instance = new WPCF7_Submission(['bl_attr_gclid' => 'EAIaIQ-1', 'bl_attr_gad_campaignid' => '24354771502', 'bl_attr_utm_campaign' => 'loaders <1>']);
$out = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail'));
$at = strpos($out['body'], '来源渠道');
$check($at !== false && $at < strpos($out['body'], $button), 'the channel block is missing or after the button');
$check(str_contains($out['body'], 'Google Ads 广告 · 广告系列 24354771502') && str_contains($out['body'], '手机 · iPhone · Safari'), 'the channel or device line is wrong');
$raw = strpos($out['body'], 'EAIaIQ-1');
$check($raw !== false && $raw > strpos($out['body'], '也可以直接回复') && str_contains($out['body'], '广告追踪参数') && !str_contains($out['body'], '<1>'), 'the raw values are missing, not at the foot, or not escaped');
$check(!str_contains($out['body'], '广告来源'), 'the old 广告来源 line is still there');
// A saved card whose reply-help sentence was edited away still gets the raw values, just above its button row.
$customCard = '<table width="100%"><tr><td>body</td></tr>' . $button . '<table><tr><td>reply</td></tr></table></td></tr><tr><td>Write back soon.</td></tr></table>';
$custom = run_filter('wpcf7_mail_components', ['body' => $customCard], new WPCF7_ContactForm(5, BL_ENQUIRY_CARD_MARKER . "\n" . $customCard), new WPCF7_Mail('mail'));
$check(str_contains($custom['body'], 'EAIaIQ-1') && strpos($custom['body'], 'EAIaIQ-1') < strpos($custom['body'], $button), 'a card with edited reply text lost the raw values or got them outside the card');
// The same with a plain reply link instead of a nested button table.
$linkCard = '<table width="100%"><tr><td>body</td></tr>' . $button . '<a href="mailto:x">reply</a></td></tr></table>';
$link = run_filter('wpcf7_mail_components', ['body' => $linkCard], new WPCF7_ContactForm(5, BL_ENQUIRY_CARD_MARKER . "\n" . $linkCard), new WPCF7_Mail('mail'));
$check(str_contains($link['body'], 'EAIaIQ-1') && strpos($link['body'], 'EAIaIQ-1') < strpos($link['body'], $button) && str_ends_with($link['body'], '</td></tr></table>'), 'a card with a plain reply link lost the raw values or got them outside the card');
$autoreply = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail_2'));
$check($autoreply['body'] === $card, 'the autoresponder got the block');
$edited = run_filter('wpcf7_mail_components', ['body' => $card], new WPCF7_ContactForm(5, 'plain edited body'), new WPCF7_Mail('mail'));
$check($edited['body'] === $card, 'an edited mail got the block');
WPCF7_Submission::$instance = new WPCF7_Submission(['name' => 'Ann']);
$none = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail'));
$check(str_contains($none['body'], '来源渠道') && str_contains($none['body'], '直接访问') && !str_contains($none['body'], '广告追踪参数'), 'an enquiry without attribution lacks its channel line or got a raw block');
// From version 7 the card has its own 来源渠道 cell ([bl_channel]): no second block, the raw values still at the foot.
WPCF7_Submission::$instance = new WPCF7_Submission(['bl_attr_gclid' => 'EAIaIQ-1']);
$v7 = run_filter('wpcf7_mail_components', ['body' => $card], new WPCF7_ContactForm(5, BL_ENQUIRY_CARD_MARKER . "\n<table>[bl_channel]</table>"), new WPCF7_Mail('mail'));
$check(!str_contains($v7['body'], '来源渠道') && str_contains($v7['body'], 'EAIaIQ-1'), 'a version 7 card got the old channel block, or lost its raw values');
WPCF7_Submission::$instance = new WPCF7_Submission(['bl_attr_utm_source' => 'newsletter']);
$tagsOnly = run_filter('wpcf7_mail_components', ['body' => $card], $form, new WPCF7_Mail('mail'));
$check(str_contains($tagsOnly['body'], '营销链接 · newsletter') && !str_contains($tagsOnly['body'], 'Google Ads 广告'), 'tags alone were labelled as a Google Ads click');

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
    $check(str_contains($mail['body'], '新询盘通知') && str_contains($mail['body'], 'Google Ads 广告 · 广告系列 24354771502') && str_contains($mail['body'], '手机 · iPhone · Safari'), "design $design lacks the channel or device");
    $check(str_contains($mail['body'], 'EAIaIQ-1') && str_contains($mail['body'], 'https://example.test/product/t460/') && str_contains($mail['body'], '2026-10-14T03:20:00.000Z'), "design $design lacks the click ID, landing page or time");
    $check(substr_count($mail['body'], 'EAIaIQ-1') === 1 && substr_count($mail['body'], '来源渠道') === 1, "design $design shows the click ID or channel more than once");
    $check(strpos($mail['body'], 'EAIaIQ-1') > strpos($mail['body'], '立即回复客户'), "design $design shows the raw values above the reply button");
    WPCF7_Submission::$instance = new WPCF7_Submission(['name' => 'Ann', 'email' => 'ann@example.test', 'bl_attr_utm_campaign' => '<b>x</b>']);
    $plain = run_filter('wpcf7_mail_html_body', $stock);
    $check(str_contains($plain, '营销链接') && !str_contains($plain, '<b>x') && !str_contains($plain, 'Google Ads 广告'), "design $design escaped nothing or labelled tags as a click");
    WPCF7_Submission::$instance = new WPCF7_Submission(['name' => 'Ann', 'email' => 'ann@example.test']);
    $none = run_filter('wpcf7_mail_html_body', $stock);
    $check(str_contains($none, '直接访问') && !str_contains($none, '广告追踪参数'), "design $design shows raw values for an enquiry without them");
    WPCF7_Submission::$instance = new WPCF7_Submission(['name' => 'Ann', 'email' => 'ann@example.test', 'bl_page_referrer' => 'www.google.com.cy']);
    $check(str_contains(run_filter('wpcf7_mail_html_body', $stock), '自然搜索 · Google'), "design $design does not name a visit from Google search");
}
$check(str_contains(bl_wp_inquiry_render('c', bl_wp_inquiry_data(bl_wp_inquiry_sample('product'))), 'EAIaIQobChMI-sample'), 'the preview sample has no advertisement lines');

// Devices from real User-Agents.
$check(bl_wp_inquiry_device('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Safari/537.36') === '电脑 · Windows · Chrome', 'a Windows Chrome was misread');
$check(bl_wp_inquiry_device('Mozilla/5.0 (Linux; Android 14; SM-S918B) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/141.0.0.0 Mobile Safari/537.36') === '手机 · Android · Chrome', 'an Android phone was misread');
$check(bl_wp_inquiry_device('') === '', 'an empty User-Agent gave a device');
$mac = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Safari/605.1.15';
$check(bl_wp_inquiry_device($mac, true) === '平板 · iPad · Safari' && bl_wp_inquiry_device($mac) === '电脑 · Mac · Safari', 'an iPad in desktop mode was not told from a Mac');

// The DingTalk plugin's 来源渠道 line: the same channel for the theme's form, for every enquiry; other forms keep the plugin's own.
$check(run_filter('achord_dingtalk_cf7_channel', '', ['bl_page_referrer' => 'google.com'], new WPCF7_ContactForm(5)) === '自然搜索 · Google', 'DingTalk did not get the channel');
$check(str_starts_with(run_filter('achord_dingtalk_cf7_channel', '', [], new WPCF7_ContactForm(5)), '直接访问'), 'DingTalk got no channel for a direct visit');
$check(run_filter('achord_dingtalk_cf7_channel', 'kept', ['bl_page_referrer' => 'google.com'], new WPCF7_ContactForm(6)) === 'kept', 'another form got the theme channel');

echo "Enquiry attribution: $checks checks passed\n";
