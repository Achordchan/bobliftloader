<?php
declare(strict_types=1);

/**
 * Which advertisement brought the enquiry. assets/js/attribution.js adds hidden fields (bl_attr_gclid, bl_attr_utm_source …) to
 * the form, only for visitors who agreed to advertising storage. Contact Form 7 keeps just the fields the form declares, so
 * they are cleaned and added to the posted data here; Flamingo then keeps them with the enquiry (meta._bl_enquiry.fields and
 * .attribution) and the notification email shows them (bl_wp_inquiry_attribution_block).
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
 * What the visitor arrived with, as [label, value as safe HTML, monospace?] lines for the notification mail. The first line says
 * what kind of arrival it is; none at all when nothing was sent. Every design in inc/mail-templates/ and the classic card use it.
 */
function bl_wp_inquiry_attribution_lines(array $attribution): array
{
    if (!$attribution) return [];
    $ads = isset($attribution['gclid']) || isset($attribution['gbraid']) || isset($attribution['wbraid']) || isset($attribution['gad_source']);
    $labels = [
        'gclid' => ['gclid', true], 'gbraid' => ['gbraid', true], 'wbraid' => ['wbraid', true], 'gad_campaignid' => ['广告系列编号', true],
        'utm_source' => ['UTM 来源', false], 'utm_medium' => ['UTM 媒介', false], 'utm_campaign' => ['UTM 系列', false], 'utm_term' => ['UTM 关键词', false], 'utm_content' => ['UTM 内容', false],
        'landing' => ['落地页', false], 'first_seen' => ['落地时间', true],
    ];
    $lines = [['广告来源', '<b>' . ($ads ? 'Google Ads 点击' : '营销标记') . '</b>', false]];
    foreach ($labels as $name => [$label, $mono]) {
        if (isset($attribution[$name])) $lines[] = [$label, '<span style="word-break:break-all;">' . esc_html($attribution[$name]) . '</span>', $mono];
    }
    return $lines;
}

/** The same lines as one grey block of the classic notification card. Empty when nothing was sent. */
function bl_wp_inquiry_attribution_block(array $attribution): string
{
    $lines = bl_wp_inquiry_attribution_lines($attribution);
    if (!$lines) return '';
    $rows = '';
    foreach ($lines as [$label, $value]) {
        $rows .= '<div style="padding:3px 0;font-size:13px;line-height:1.6;color:#334155;word-break:break-all;"><span style="color:#64748b;">' . esc_html($label) . '</span> ' . $value . '</div>';
    }
    return '<tr><td style="padding:20px 36px 0;"><table width="100%" cellspacing="0" cellpadding="0" style="background:#f5f7fa;border-radius:8px;"><tr><td style="padding:14px 18px;">' . $rows . '</td></tr></table></td></tr>' . "\n";
}

// The block goes in front of the card's reply button. Only the theme's card is touched: an edited mail without the marker, or
// the autoresponder, is left as it is.
add_filter('wpcf7_mail_components', static function (array $components, $form, $mail): array {
    if (!$form || !$mail || (int) $form->id() !== bl_wp_inquiry_form_id() || $mail->name() !== 'mail') return $components;
    $body = (string) ($components['body'] ?? '');
    $submission = class_exists('WPCF7_Submission') ? WPCF7_Submission::get_instance() : null;
    if (!$submission || !str_contains($body, '<table') || !str_contains((string) (((array) $form->prop('mail'))['body'] ?? ''), BL_ENQUIRY_CARD_MARKER)) return $components;
    $block = bl_wp_inquiry_attribution_block(bl_enquiry_attribution((array) $submission->get_posted_data()));
    $button = '<tr><td align="center" style="padding:32px 36px 12px;">';
    $at = $block === '' ? false : strpos($body, $button);
    if ($at !== false) $components['body'] = substr($body, 0, $at) . $block . substr($body, $at);
    return $components;
}, 20, 3);
