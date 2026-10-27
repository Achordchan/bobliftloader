<?php
declare(strict_types=1);

/**
 * How a visitor reached the site before sending an enquiry, in the crane site's words (inc/inquiry-mail.php there). Two kinds of
 * hidden fields come with the form (both added by assets/js/attribution.js):
 * - bl_attr_*: the Google Ads click, campaign tags, landing page and the site the visitor came from, kept in the browser for 90
 *   days, only for visitors who agreed to advertising storage;
 * - bl_page_referrer, bl_ad_consent, bl_touch: sent with every enquiry whatever the cookie choice, since nothing is kept for them:
 *   the site that linked to the page the form was sent from, whether advertising is agreed to (so the mail can say why nothing
 *   is known) and whether the screen takes touch (iPadOS Safari presents itself as a Mac).
 * Contact Form 7 keeps just the fields the form declares, so they are cleaned and added to the posted data here; Flamingo then
 * keeps them with the enquiry (meta._bl_enquiry.fields and .attribution) and the notification email shows the channel and device
 * (bl_wp_inquiry_attribution_lines) and, at its foot, the raw values (bl_wp_inquiry_tracking_html).
 */
const BL_ENQUIRY_ATTRIBUTION = [
    'gclid' => 255, 'gbraid' => 255, 'wbraid' => 255, 'gad_source' => 120, 'gad_campaignid' => 120,
    'utm_source' => 120, 'utm_medium' => 120, 'utm_campaign' => 120, 'utm_term' => 120, 'utm_content' => 120,
    'landing' => 300, 'first_seen' => 40, 'referrer' => 120,
];
const BL_ENQUIRY_ATTRIBUTION_PREFIX = 'bl_attr_';
// The fields sent with every enquiry, whatever the cookie choice.
const BL_ENQUIRY_CONTEXT = ['bl_page_referrer', 'bl_ad_consent', 'bl_touch'];

/** A referring site as kept: its host name only (no path, no query), lower case, without www.; empty when it is not one. */
function bl_enquiry_referrer_host(string $value): string
{
    $host = (string) preg_replace('/^www\./', '', strtolower(trim($value)));
    return strlen($host) <= 120 && preg_match('/^[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)+$/', $host) ? $host : '';
}

/** One submitted value as stored: one printable line, no markup, within its length; a click ID must also look like one. */
function bl_enquiry_attribution_clean(string $name, $value): string
{
    if (!isset(BL_ENQUIRY_ATTRIBUTION[$name]) || !is_scalar($value)) return '';
    $text = trim((string) preg_replace('/\s+/u', ' ', (string) preg_replace('/[\x00-\x1f\x7f<>]/u', ' ', (string) $value)));
    // A click ID that has been cut no longer matches anything in Google Ads, so it is dropped rather than shortened.
    if (in_array($name, ['gclid', 'gbraid', 'wbraid'], true)) return strlen($text) <= BL_ENQUIRY_ATTRIBUTION[$name] && preg_match('/^[A-Za-z0-9_.~=-]+$/', $text) ? $text : '';
    if ($name === 'referrer') return bl_enquiry_referrer_host($text);
    return function_exists('mb_substr') ? mb_substr($text, 0, BL_ENQUIRY_ATTRIBUTION[$name]) : substr($text, 0, BL_ENQUIRY_ATTRIBUTION[$name]);
}

add_filter('wpcf7_posted_data', static function ($data) {
    if (!is_array($data)) return $data;
    // Any other form (or a submission whose form cannot be told) is left exactly as it is, even if it declares fields with this prefix.
    $form = class_exists('WPCF7_ContactForm') ? WPCF7_ContactForm::get_current() : null;
    if (!$form || (int) $form->id() !== bl_wp_inquiry_form_id()) return $data;
    foreach (array_keys($data) as $key) if (str_starts_with((string) $key, BL_ENQUIRY_ATTRIBUTION_PREFIX) || in_array($key, BL_ENQUIRY_CONTEXT, true)) unset($data[$key]);
    $posted = function_exists('wp_unslash') ? wp_unslash($_POST) : $_POST;
    foreach (array_keys(BL_ENQUIRY_ATTRIBUTION) as $name) {
        $value = bl_enquiry_attribution_clean($name, $posted[BL_ENQUIRY_ATTRIBUTION_PREFIX . $name] ?? '');
        if ($value !== '') $data[BL_ENQUIRY_ATTRIBUTION_PREFIX . $name] = $value;
    }
    foreach (bl_enquiry_context($posted) as $name => $value) if ($value !== '') $data['bl_' . $name] = $value;
    return $data;
});

/**
 * The fields sent with every enquiry, cleaned: page_referrer (a host, never this site's own), ad_consent ('true', 'false' or
 * '' when not known) and touch ('1' or ''). Reads the posted data or Flamingo's fields alike.
 */
function bl_enquiry_context(array $fields): array
{
    $text = static fn(string $name): string => is_scalar($fields[$name] ?? null) ? trim((string) $fields[$name]) : '';
    $referrer = bl_enquiry_referrer_host($text('bl_page_referrer'));
    $own = (string) preg_replace('/^www\./', '', strtolower((string) (function_exists('home_url') ? wp_parse_url(home_url(), PHP_URL_HOST) : '')));
    return [
        'page_referrer' => $referrer !== $own ? $referrer : '',
        'ad_consent' => in_array($text('bl_ad_consent'), ['true', 'false'], true) ? $text('bl_ad_consent') : '',
        'touch' => $text('bl_touch') === '1' ? '1' : '',
    ];
}

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
 * How the visitor reached the site, for the sales team, as [channel, detail], in the crane site's words (boblift_inquiry_channel
 * there). A Google Ads click ID wins, then campaign tags, then the site the visitor came from (kept since landing when advertising
 * was agreed to, else only the referrer of the page the form was sent from, which needs no storage).
 */
function bl_wp_inquiry_channel_parts(array $attribution, array $context = []): array
{
    $value = static fn(string $name): string => (string) ($attribution[$name] ?? '');
    if ($value('gclid') !== '' || $value('gbraid') !== '' || $value('wbraid') !== '' || $value('gad_source') !== '') {
        $detail = $value('utm_term') !== '' ? '关键词 ' . $value('utm_term') : ($value('gad_campaignid') !== '' ? '广告系列 ' . $value('gad_campaignid') : '');
        return ['Google Ads 广告', $detail];
    }
    // Any campaign tag marks a marketing link; the detail is whichever of source, medium and campaign it carries.
    $tags = array_values(array_filter(array_map($value, ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content']), 'strlen'));
    if ($tags) return ['营销链接', implode(' / ', array_slice($tags, 0, 3))];
    $host = $value('referrer') !== '' ? $value('referrer') : (string) ($context['page_referrer'] ?? '');
    if ($host !== '') {
        $named = static function (array $list) use ($host): string {
            foreach ($list as $pattern => $name) if (preg_match('/(?:^|\.)' . $pattern . '$/', $host)) return $name;
            return '';
        };
        // AI assistants first: gemini.google.com is not Google Search.
        $ai = $named(['chatgpt\.com' => 'ChatGPT', 'openai\.com' => 'ChatGPT', 'perplexity\.ai' => 'Perplexity', 'gemini\.google\.com' => 'Gemini', 'copilot\.microsoft\.com' => 'Copilot', 'claude\.ai' => 'Claude', 'deepseek\.com' => 'DeepSeek']);
        if ($ai !== '') return ['AI 助手推荐', $ai];
        $ads = $named(['googleadservices\.com' => 'Google Ads', 'doubleclick\.net' => 'Google Ads', 'syndicatedsearch\.goog' => 'Google 搜索网络合作伙伴']);
        if ($ads !== '') return ['Google Ads 广告', $ads . '（没有点击编号）'];
        // Search engines by their search hosts only: docs.google.com or mail.yahoo.com is a link, not a search.
        foreach (['/^(?:m\.)?google\.(?:com?\.)?[a-z]{2,3}$/' => 'Google', '/^(?:cn\.)?bing\.com$/' => 'Bing', '/^(?:[a-z]{2}\.)?search\.yahoo\.(?:com|co\.jp)$/' => 'Yahoo', '/^yandex\.(?:com|ru|com\.tr|by|kz|uz)$/' => 'Yandex', '/^ya\.ru$/' => 'Yandex', '/^(?:m\.)?baidu\.com$/' => '百度', '/^(?:html\.)?duckduckgo\.com$/' => 'DuckDuckGo', '/^ecosia\.org$/' => 'Ecosia', '/^(?:m\.)?search\.naver\.com$/' => 'Naver', '/^(?:search\.)?seznam\.cz$/' => 'Seznam', '/^(?:m\.)?sogou\.com$/' => '搜狗', '/^(?:m\.)?so\.com$/' => '360 搜索', '/^search\.brave\.com$/' => 'Brave'] as $pattern => $name) {
            if (preg_match($pattern, $host)) return ['自然搜索', $name];
        }
        $social = $named(['facebook\.com' => 'Facebook', 'fb\.com' => 'Facebook', 'instagram\.com' => 'Instagram', 'linkedin\.com' => 'LinkedIn', 'lnkd\.in' => 'LinkedIn', 'youtube\.com' => 'YouTube', 'youtu\.be' => 'YouTube', 'x\.com' => 'X', 't\.co' => 'X', 'twitter\.com' => 'X', 'whatsapp\.com' => 'WhatsApp', 'tiktok\.com' => 'TikTok', 'vk\.com' => 'VK', 'pinterest\.[a-z.]+' => 'Pinterest', 'reddit\.com' => 'Reddit', 't\.me' => 'Telegram']);
        if ($social !== '') return ['社交媒体', $social];
        return ['外部网站', $host];
    }
    if (($context['ad_consent'] ?? '') === 'false') return ['来源未知', '访客没同意广告 Cookie，进站来源没有记录'];
    return ['直接访问', '直接输入网址或收藏夹，也可能是来源被浏览器隐藏'];
}

/** The channel on one line, e.g. "自然搜索 · Google" (the DingTalk message and Flamingo). */
function bl_wp_inquiry_channel(array $attribution, array $context = []): string
{
    return implode(' · ', array_filter(bl_wp_inquiry_channel_parts($attribution, $context), 'strlen'));
}

/**
 * The visitor's device from the browser's User-Agent, e.g. "手机 · iPhone · Safari" (the same summary as the crane site). iPadOS
 * Safari calls itself a Mac; the form says whether the screen takes touch, which tells the two apart. Only the summary is shown.
 */
function bl_wp_inquiry_device(string $agent, bool $touch = false): string
{
    if ($agent === '') return '';
    $has = static fn(string $pattern): bool => (bool) preg_match('/' . $pattern . '/i', $agent);
    $ipad = $has('iPad') || ($has('Macintosh') && $touch);
    $system = match (true) {
        $ipad => 'iPad',
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
        $ipad, $has('Tablet') || ($has('Android') && !$has('Mobile')) => '平板',
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
function bl_wp_inquiry_attribution_lines(array $attribution, string $agent = '', array $context = []): array
{
    $lines = [['来源渠道', esc_html(bl_wp_inquiry_channel($attribution, $context)), false]];
    $device = bl_wp_inquiry_device($agent, ($context['touch'] ?? '') === '1');
    if ($device !== '') $lines[] = ['访问设备', esc_html($device), false];
    return $lines;
}

/** Chinese labels of the raw tracking values at the mail's foot. */
const BL_ENQUIRY_TRACKING_LABELS = [
    'gclid' => 'gclid', 'gbraid' => 'gbraid', 'wbraid' => 'wbraid', 'gad_source' => 'gad_source', 'gad_campaignid' => '广告系列编号',
    'utm_source' => 'UTM 来源', 'utm_medium' => 'UTM 媒介', 'utm_campaign' => 'UTM 系列', 'utm_term' => 'UTM 关键词', 'utm_content' => 'UTM 内容',
    'landing' => '落地页', 'first_seen' => '落地时间', 'referrer' => '进站前网站',
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
function bl_wp_inquiry_attribution_block(array $attribution, string $agent = '', array $context = []): string
{
    $rows = '';
    foreach (bl_wp_inquiry_attribution_lines($attribution, $agent, $context) as [$label, $value]) {
        $rows .= '<div style="padding:3px 0;font-size:14px;line-height:1.6;color:#0b1f33;word-break:break-word;"><span style="color:#64748b;">' . esc_html($label) . '</span>&nbsp;&nbsp;' . $value . '</div>';
    }
    return '<tr><td style="padding:12px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fa;border-radius:8px;"><tr><td style="padding:12px 18px;">' . $rows . '</td></tr></table></td></tr>' . "\n";
}

// The classic card gets the raw values after the reply line, and a card saved before version 7 (no 来源渠道 cell of its own) also
// the channel block in front of its reply button. Only the theme's card is touched: an edited mail without the marker, or the
// autoresponder, is left as it is.
add_filter('wpcf7_mail_components', static function (array $components, $form, $mail): array {
    if (!$form || !$mail || (int) $form->id() !== bl_wp_inquiry_form_id() || $mail->name() !== 'mail') return $components;
    $body = (string) ($components['body'] ?? '');
    $submission = class_exists('WPCF7_Submission') ? WPCF7_Submission::get_instance() : null;
    if (!$submission || !str_contains($body, '<table') || !str_contains((string) (((array) $form->prop('mail'))['body'] ?? ''), BL_ENQUIRY_CARD_MARKER)) return $components;
    // A drawn design (wpcf7_mail_html_body) already carries both; it has no classic button.
    $button = '<tr><td align="center" style="padding:32px 36px 12px;">';
    $at = strpos($body, $button);
    if ($at === false) return $components;
    $posted = (array) $submission->get_posted_data();
    $attribution = bl_enquiry_attribution($posted);
    if (!str_contains((string) (((array) $form->prop('mail'))['body'] ?? ''), '[bl_channel]')) {
        $body = substr($body, 0, $at) . bl_wp_inquiry_attribution_block($attribution, (string) $submission->get_meta('user_agent'), bl_enquiry_context($posted)) . substr($body, $at);
    }
    $tracking = bl_wp_inquiry_tracking_html($attribution);
    if ($tracking !== '') {
        // After the reply line when the card still has it; a saved card whose reply text was edited or removed gets the values
        // just above its reply button, the row that is known to be inside the card (as they were placed before).
        $reply = strpos($body, '也可以直接回复这封邮件');
        $end = $reply !== false ? strpos($body, '</tr>', $reply) : false;
        $end = $end !== false ? $end + strlen('</tr>') : strpos($body, $button);
        if ($end !== false) $body = substr($body, 0, $end) . "\n" . '<tr><td style="padding:0 36px 28px;">' . $tracking . '</td></tr>' . substr($body, $end);
    }
    $components['body'] = $body;
    return $components;
}, 20, 3);

// The DingTalk plugin's 来源渠道 line for this theme's form: the same channel as the mail, for every enquiry (not only ad visits).
add_filter('achord_dingtalk_cf7_channel', static function ($channel, $posted = [], $form = null) {
    if (!$form || !is_object($form) || (int) $form->id() !== bl_wp_inquiry_form_id() || !is_array($posted)) return $channel;
    return bl_wp_inquiry_channel(bl_enquiry_attribution($posted), bl_enquiry_context($posted));
}, 10, 3);
