<?php
declare(strict_types=1);

/**
 * Which advertisement brought the enquiry. assets/js/attribution.js adds hidden fields (bl_attr_gclid, bl_attr_utm_source …) to
 * the form, only for visitors who agreed to advertising storage. Contact Form 7 keeps just the fields the form declares, so
 * they are cleaned and added to the posted data here; Flamingo then keeps them with the enquiry (meta._bl_enquiry.fields and
 * .attribution) and the notification email shows the channel (bl_wp_inquiry_attribution_lines) and, at its foot, the raw values (bl_wp_inquiry_tracking_html).
 */
const BL_ENQUIRY_ATTRIBUTION = [
    'gclid' => 255, 'gbraid' => 255, 'wbraid' => 255, 'gad_source' => 120, 'gad_campaignid' => 120,
    'utm_source' => 120, 'utm_medium' => 120, 'utm_campaign' => 120, 'utm_term' => 120, 'utm_content' => 120,
    'landing' => 300, 'first_seen' => 40,
];
const BL_ENQUIRY_ATTRIBUTION_PREFIX = 'bl_attr_';

/** One submitted value as stored: one printable line, no markup, within its length; a click ID must also look like one. */
function bl_enquiry_attribution_clean(string $name, $value): string
{
    if (!isset(BL_ENQUIRY_ATTRIBUTION[$name]) || !is_scalar($value)) return '';
    $text = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\x00-\x1f\x7f<>]/u', ' ', (string) $value)));
    // A click ID that has been cut no longer matches anything in Google Ads, so it is dropped rather than shortened.
    if (in_array($name, ['gclid', 'gbraid', 'wbraid'], true)) return strlen($text) <= BL_ENQUIRY_ATTRIBUTION[$name] && preg_match('/^[A-Za-z0-9_.~=-]+$/', $text) ? $text : '';
    return function_exists('mb_substr') ? mb_substr($text, 0, BL_ENQUIRY_ATTRIBUTION[$name]) : substr($text, 0, BL_ENQUIRY_ATTRIBUTION[$name]);
}

add_filter('wpcf7_posted_data', static function ($data) {
    if (!is_array($data)) return $data;
    // Any other form (or a submission whose form cannot be told) is left exactly as it is, even if it declares fields with this prefix.
    $form = class_exists('WPCF7_ContactForm') ? WPCF7_ContactForm::get_current() : null;
    if (!$form || (int) $form->id() !== bl_wp_inquiry_form_id()) return $data;
    foreach (array_keys($data) as $key) if (str_starts_with((string) $key, BL_ENQUIRY_ATTRIBUTION_PREFIX)) unset($data[$key]);
    $posted = function_exists('wp_unslash') ? wp_unslash($_POST) : $_POST;
    foreach (array_keys(BL_ENQUIRY_ATTRIBUTION) as $name) {
        $value = bl_enquiry_attribution_clean($name, $posted[BL_ENQUIRY_ATTRIBUTION_PREFIX . $name] ?? '');
        if ($value !== '') $data[BL_ENQUIRY_ATTRIBUTION_PREFIX . $name] = $value;
    }
    return $data;
});

/** The attribution fields of one set of posted data (or Flamingo fields), without the prefix. */
function bl_enquiry_attribution(array $fields): array
{
    $found = [];
    foreach (array_keys(BL_ENQUIRY_ATTRIBUTION) as $name) {
        $value = $fields[BL_ENQUIRY_ATTRIBUTION_PREFIX . $name] ?? '';
        $value = bl_enquiry_attribution_clean($name, is_array($value) ? implode(', ', $value) : $value);
        if ($value !== '') $found[$name] = $value;
    }
    return $found;
}

/**
 * How the visitor reached the site, in the words the crane site's mail uses: "Google Ads 广告 · 广告系列 …" for a click, "营销链接 · …"
 * for campaign tags alone. Empty when nothing was kept: the visitor came without either, or did not agree to advertising storage.
 */
function bl_wp_inquiry_channel(array $attribution): string
{
    if (isset($attribution['gclid']) || isset($attribution['gbraid']) || isset($attribution['wbraid']) || isset($attribution['gad_source'])) {
        $detail = isset($attribution['utm_term']) ? '关键词 ' . $attribution['utm_term'] : (isset($attribution['gad_campaignid']) ? '广告系列 ' . $attribution['gad_campaignid'] : '');
        return 'Google Ads 广告' . ($detail !== '' ? ' · ' . $detail : '');
    }
    $tags = array_values(array_filter(array_map(static fn(string $name): string => (string) ($attribution[$name] ?? ''), ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']), 'strlen'));
    return $tags ? '营销链接 · ' . implode(' / ', array_slice($tags, 0, 3)) : '';
}

/**
 * The visitor's device from the browser's User-Agent, e.g. "手机 · iPhone · Safari" (the same summary as the crane site). An
 * iPad in desktop mode calls itself a Mac and is counted as one; only the summary is shown, never the raw string.
 */
function bl_wp_inquiry_device(string $agent): string
{
    if ($agent === '') return '';
    $has = static fn(string $pattern): bool => (bool) preg_match('/' . $pattern . '/i', $agent);
    $system = match (true) {
        $has('iPad') => 'iPad',
        $has('iPhone|iPod') => 'iPhone',
        $has('HarmonyOS|OpenHarmony') => '鸿蒙',
        $has('Android') => 'Android',
        $has('Windows') => 'Windows',
        $has('Macintosh|Mac OS X') => 'Mac',
        $has('CrOS') => 'ChromeOS',
        $has('Linux') => 'Linux',
        default => '',
    };
    $kind = match (true) {
        $has('iPad|Tablet') || ($has('Android') && !$has('Mobile')) => '平板',
        $has('Mobi|iPhone|iPod|Android|HarmonyOS') => '手机',
        $has('bot|crawl|spider') => '程序',
        default => '电脑',
    };
    $browser = match (true) {
        $has('MicroMessenger') => '微信',
        $has('WhatsApp') => 'WhatsApp',
        $has('FBAN|FBAV|FB_IAB') => 'Facebook 内置浏览器',
        $has('Instagram') => 'Instagram 内置浏览器',
        $has('LinkedInApp') => 'LinkedIn 内置浏览器',
        $has('Edg(?:e|A|iOS)?\/') => 'Edge',
        $has('SamsungBrowser') => '三星浏览器',
        $has('OPR\/|Opera') => 'Opera',
        $has('YaBrowser') => 'Yandex 浏览器',
        $has('Firefox|FxiOS') => 'Firefox',
        $has('Chrome|CriOS') => 'Chrome',
        $has('Safari') => 'Safari',
        default => '',
    };
    return implode(' · ', array_filter([$kind, $system, $browser], 'strlen'));
}

/**
 * The lines the sales team reads next to the country, IP and time, as [label, value as safe HTML, monospace?]: where the visitor came
 * from (来源渠道, always) and on what (访问设备, when the browser said). The raw click IDs and tags are left to the mail's foot
 * (bl_wp_inquiry_tracking_html). Every design in inc/mail-templates/ and the classic card use it.
 */
function bl_wp_inquiry_attribution_lines(array $attribution, string $agent = ''): array
{
    $channel = bl_wp_inquiry_channel($attribution);
    $lines = [['来源渠道', $channel !== '' ? esc_html($channel) : '<span style="color:#8a97a6;">没有广告记录（自然搜索、直接访问，或访客没同意广告 Cookie）</span>', false]];
    $device = bl_wp_inquiry_device($agent);
    if ($device !== '') $lines[] = ['访问设备', esc_html($device), false];
    return $lines;
}

/** Chinese labels of the raw tracking values at the mail's foot. */
const BL_ENQUIRY_TRACKING_LABELS = [
    'gclid' => 'gclid', 'gbraid' => 'gbraid', 'wbraid' => 'wbraid', 'gad_source' => 'gad_source', 'gad_campaignid' => '广告系列编号',
    'utm_source' => 'UTM 来源', 'utm_medium' => 'UTM 媒介', 'utm_campaign' => 'UTM 系列', 'utm_term' => 'UTM 关键词', 'utm_content' => 'UTM 内容',
    'landing' => '落地页', 'first_seen' => '落地时间',
];

/**
 * The raw ad tracking values, in small grey type under a dashed line, for the foot of the mail: whoever checks the ads finds
 * them there, the sales team reads 来源渠道 instead. Empty when nothing was kept.
 */
function bl_wp_inquiry_tracking_html(array $attribution): string
{
    $rows = '';
    foreach (BL_ENQUIRY_TRACKING_LABELS as $name => $label) {
        if (isset($attribution[$name])) $rows .= '<div style="padding:2px 0;font-size:12px;line-height:1.6;color:#64748b;word-break:break-all;">' . esc_html($label) . ' <span style="color:#334155;">' . esc_html($attribution[$name]) . '</span></div>';
    }
    if ($rows === '') return '';
    return '<div style="border-top:1px dashed #e3e8ef;padding-top:14px;font-family:-apple-system,BlinkMacSystemFont,\'PingFang SC\',\'Microsoft YaHei\',\'Helvetica Neue\',Arial,sans-serif;"><div style="padding-bottom:6px;font-size:12px;font-weight:bold;color:#94a3b8;">广告追踪参数（看广告效果用，跟进客户可以不看）</div>' . $rows . '</div>';
}

/** The 来源渠道 and 访问设备 lines as one grey block of the classic notification card. */
function bl_wp_inquiry_attribution_block(array $attribution, string $agent = ''): string
{
    $rows = '';
    foreach (bl_wp_inquiry_attribution_lines($attribution, $agent) as [$label, $value]) {
        $rows .= '<div style="padding:3px 0;font-size:14px;line-height:1.6;color:#0b1f33;word-break:break-word;"><span style="color:#64748b;">' . esc_html($label) . '</span>&nbsp;&nbsp;' . $value . '</div>';
    }
    return '<tr><td style="padding:12px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fa;border-radius:8px;"><tr><td style="padding:12px 18px;">' . $rows . '</td></tr></table></td></tr>' . "\n";
}

// The classic card gets the channel block in front of its reply button and the raw values after the reply line. Only the theme's
// card is touched: an edited mail without the marker, or the autoresponder, is left as it is.
add_filter('wpcf7_mail_components', static function (array $components, $form, $mail): array {
    if (!$form || !$mail || (int) $form->id() !== bl_wp_inquiry_form_id() || $mail->name() !== 'mail') return $components;
    $body = (string) ($components['body'] ?? '');
    $submission = class_exists('WPCF7_Submission') ? WPCF7_Submission::get_instance() : null;
    if (!$submission || !str_contains($body, '<table') || !str_contains((string) (((array) $form->prop('mail'))['body'] ?? ''), BL_ENQUIRY_CARD_MARKER)) return $components;
    // A drawn design (wpcf7_mail_html_body) already carries both; it has no classic button.
    $button = '<tr><td align="center" style="padding:32px 36px 12px;">';
    $at = strpos($body, $button);
    if ($at === false) return $components;
    $attribution = bl_enquiry_attribution((array) $submission->get_posted_data());
    $body = substr($body, 0, $at) . bl_wp_inquiry_attribution_block($attribution, (string) $submission->get_meta('user_agent')) . substr($body, $at);
    $tracking = bl_wp_inquiry_tracking_html($attribution);
    $reply = '也可以直接回复这封邮件，回信会发到';
    if ($tracking !== '' && ($end = strpos($body, '</tr>', (int) strpos($body, $reply))) !== false && str_contains($body, $reply)) {
        $end += strlen('</tr>');
        $body = substr($body, 0, $end) . "\n" . '<tr><td style="padding:0 36px 28px;">' . $tracking . '</td></tr>' . substr($body, $end);
    }
    $components['body'] = $body;
    return $components;
}, 20, 3);
