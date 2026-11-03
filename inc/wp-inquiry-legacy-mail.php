<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The [bl_…] tags of the old Contact Form 7 notification, kept while CF7 can still take a submission: during the switch a
 * cached page may still post the old form, and its saved Mail tab names these tags. They are filled from the same helpers
 * the inquiry plugin uses, so such a mail still shows the product, the page and the source. CF7 asks for them only when
 * the mail names them; once CF7 is switched off nothing here runs. Remove this file when CF7 is uninstalled.
 */

const BL_LEGACY_MAIL_TAGS = ['bl_page_link', 'bl_page_url', 'bl_enquiry_kind', 'bl_logo', 'bl_site', 'bl_time', 'bl_product_card', 'bl_ip_country', 'bl_channel', 'bl_device', 'bl_language'];

/** The posted CF7 fields in the plugin's names, for its channel and device readings. */
function bl_legacy_mail_fields(array $posted, string $url): array
{
    $text = static fn(string $name): string => is_scalar($posted[$name] ?? null) ? trim((string) $posted[$name]) : '';
    $fields = ['source_page' => $url, 'page_referrer' => '', 'ad_consent' => ''];
    foreach (bl_enquiry_attribution($posted) as $name => $value) $fields['attr_' . $name] = $value;
    if (function_exists('achord_inquiry_referrer_host')) $fields['page_referrer'] = achord_inquiry_referrer_host($text('bl_page_referrer'));
    $fields['ad_consent'] = in_array($text('bl_ad_consent'), ['true', 'false'], true) ? $text('bl_ad_consent') : '';
    $fields['touch'] = $text('bl_touch');
    return $fields;
}

/** One legacy tag's value; null for a tag that is not one of these. */
function bl_legacy_mail_tag(string $name, bool $html, array $posted, array $meta): ?string
{
    if (!in_array($name, BL_LEGACY_MAIL_TAGS, true)) return null;
    $out = static fn(string $text): string => $html ? esc_html($text) : $text;
    $model = is_scalar($posted['model'] ?? null) ? trim((string) $posted['model']) : '';
    $family = $posted['product'] ?? '';
    $family = trim(is_array($family) ? (string) reset($family) : (string) $family);
    $url = (string) ($meta['url'] ?? '');
    switch ($name) {
        case 'bl_language':
            return 'English';
        case 'bl_site':
            return $out((string) wp_parse_url(home_url(), PHP_URL_HOST));
        case 'bl_logo':
            return $html && function_exists('achord_inquiry_mail_logo') ? achord_inquiry_mail_logo() : 'BOB-LIFT';
        case 'bl_time':
            return wp_date('Y-m-d H:i', (int) ($meta['timestamp'] ?? 0) ?: time());
        case 'bl_ip_country':
            $ip = (string) ($meta['remote_ip'] ?? '');
            $country = $ip !== '' && function_exists('achord_inquiry_ip_country') ? achord_inquiry_ip_country($ip) : '';
            return $out($country !== '' ? $country : '未能识别');
        case 'bl_channel':
            $parts = function_exists('achord_inquiry_channel') ? achord_inquiry_channel(bl_legacy_mail_fields($posted, $url)) : [];
            return $out(implode(' · ', array_filter($parts, 'strlen')));
        case 'bl_device':
            $fields = bl_legacy_mail_fields($posted, $url);
            $device = function_exists('achord_inquiry_device') ? achord_inquiry_device((string) ($meta['user_agent'] ?? ''), $fields['touch'] === '1') : '';
            return $out($device !== '' ? $device : '未能识别');
        case 'bl_enquiry_kind':
            return $model !== '' ? '产品询盘' : '询盘表单';
        case 'bl_page_url':
            $page = bl_wp_inquiry_page($url);
            return $html ? bl_wp_inquiry_page_html($page, '#215bdb') : trim($page['title'] . ' ' . $page['url']);
        case 'bl_page_link':
            $kind = $model !== '' ? '（产品询盘）' : '（询盘表单）';
            return $html ? '<a href="' . esc_url($url) . '" target="_blank" style="color:#1995f6;">' . esc_html($url) . '</a>' . $kind : $url . $kind;
        default: // bl_product_card
            return $html ? bl_wp_inquiry_product_card($family, $model) : trim($family . ' ' . $model);
    }
}

add_filter('wpcf7_special_mail_tags', static function ($output, string $name, bool $html) {
    if (!in_array($name, BL_LEGACY_MAIL_TAGS, true) || !class_exists('WPCF7_Submission') || !($submission = WPCF7_Submission::get_instance())) return $output;
    $meta = [];
    foreach (['url', 'timestamp', 'remote_ip', 'user_agent'] as $key) $meta[$key] = $submission->get_meta($key);
    return bl_legacy_mail_tag($name, $html, (array) $submission->get_posted_data(), $meta) ?? $output;
}, 10, 3);
