<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The record behind every product, news story and application (_bl_record) is open to the REST API, so tools outside
 * wp-admin (the WordPress.com connector, scripts) can read and change specs, models and the rest. Only users who may
 * edit the post can write it, and a REST write is cleaned the same way the edit screen cleans its own form.
 */
// The REST API only carries meta for types that support custom fields; the generic Custom Fields box stays off the
// product screen, where the record has its own boxes.
add_action('add_meta_boxes_bl_product', static fn() => remove_meta_box('postcustom', 'bl_product', 'normal'), 99);

add_action('init', static function (): void {
    foreach (['bl_product', 'bl_news', 'bl_application'] as $type) {
        register_post_meta($type, '_bl_record', [
            'type' => 'object',
            'single' => true,
            'description' => 'BOB-LIFT record: model, category, specs (label => value), images, videos, event and links.',
            // Edit context only: the record also holds where product data came from, which the site never shows.
            'show_in_rest' => ['schema' => ['type' => 'object', 'context' => ['edit'], 'additionalProperties' => true]],
            'auth_callback' => static fn(bool $allowed, string $key, int $postId): bool => current_user_can('edit_post', $postId),
        ]);
    }
});

/** Text stays plain text (tags go, "&" stays "&", line breaks stay), numbers and true/false stay, anything else goes. */
function bl_rest_clean($value)
{
    if (is_array($value)) {
        $clean = [];
        foreach ($value as $key => $item) {
            $item = bl_rest_clean($item);
            if ($item !== null) $clean[is_string($key) ? sanitize_text_field($key) : $key] = $item;
        }
        return $clean;
    }
    if (is_string($value)) return sanitize_textarea_field($value);
    return is_int($value) || is_float($value) || is_bool($value) ? $value : null;
}

/** A theme path (images/…, videos/…) or an http(s) address, as the edit screens accept. */
function bl_rest_clean_url($value): string
{
    $value = is_scalar($value) ? trim(sanitize_text_field((string) $value)) : '';
    return preg_match('~^(videos|images)/[\w./-]+$~', $value) && !str_contains($value, '..') ? $value : esc_url_raw($value, ['http', 'https']);
}

/** Product slugs that exist, as the news and case boxes keep them. */
function bl_rest_product_slugs($value): array
{
    $known = array_map(static fn($p) => $p->post_name, get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1]));
    return array_values(array_intersect(array_map(static fn($slug): string => sanitize_title(is_scalar($slug) ? (string) $slug : ''), (array) $value), $known));
}

/** A REST-written record, brought to the same shape and rules the edit screens save. */
function bl_rest_normalize(string $type, array $record): array
{
    // Every field a page turns into a link is checked as an address below, so no javascript: link can be stored.
    $record = bl_rest_clean($record);
    $text = static fn(string $key): string => isset($record[$key]) && is_scalar($record[$key]) ? sanitize_text_field((string) $record[$key]) : '';
    $record['source'] = bl_rest_clean_url($record['source'] ?? '');
    $record['image'] = bl_rest_clean_url($record['image'] ?? '');
    if ($type === 'bl_product') {
        $record['model'] = $text('model');
        $record['category'] = in_array($text('category'), ['skid-steer-loaders', 'mini-dumpers'], true) ? $text('category') : 'skid-steer-loaders';
        $record['gallery'] = array_values(array_filter(array_map('bl_rest_clean_url', array_slice(array_values((array) ($record['gallery'] ?? [])), 0, 12))));
        $specs = [];
        foreach (array_slice(is_array($record['specs'] ?? null) ? $record['specs'] : [], 0, 40, true) as $key => $value) {
            $key = sanitize_text_field((string) $key);
            $value = is_scalar($value) ? sanitize_text_field((string) $value) : '';
            if ($key !== '' && $value !== '') $specs[$key] = $value;
        }
        $record['specs'] = $specs;
        $videos = [];
        foreach (array_slice(array_values((array) ($record['videos'] ?? [])), 0, 8) as $video) {
            $src = is_array($video) ? bl_rest_clean_url($video['src'] ?? '') : '';
            if ($src) $videos[] = ['src' => $src, 'poster' => bl_rest_clean_url($video['poster'] ?? ''), 'caption' => sanitize_text_field(is_scalar($video['caption'] ?? '') ? (string) ($video['caption'] ?? '') : '')];
        }
        $record['videos'] = $videos;
        return bl_product_derived($record);
    }
    $record['products'] = bl_rest_product_slugs($record['products'] ?? []);
    if ($type === 'bl_application') {
        $record['hub'] = isset(bl_case_hubs()[$text('hub')]) ? $text('hub') : '';
        $facts = is_array($record['facts'] ?? null) ? $record['facts'] : [];
        $record['facts'] = [];
        foreach (['location', 'customer', 'task', 'site', 'duration', 'result'] as $key) $record['facts'][$key] = isset($facts[$key]) && is_scalar($facts[$key]) ? sanitize_text_field((string) $facts[$key]) : '';
        return $record;
    }
    $record['category'] = isset(BL_NEWS_CATEGORIES[$text('category')]) ? $text('category') : '';
    $record['line'] = $text('line') === 'group' ? 'group' : 'compact';
    $record['caption'] = $text('caption');
    $input = is_array($record['event'] ?? null) ? $record['event'] : [];
    $event = [];
    foreach (['name', 'venue', 'address', 'booth', 'booth_note', 'source_label'] as $key) $event[$key] = isset($input[$key]) && is_scalar($input[$key]) ? sanitize_text_field((string) $input[$key]) : '';
    foreach (['start', 'end'] as $key) $event[$key] = isset($input[$key]) && is_string($input[$key]) && bl_is_date($input[$key]) ? $input[$key] : '';
    if ($event['end'] && $event['end'] < $event['start']) $event['end'] = '';
    $event['source'] = esc_url_raw(is_scalar($input['source'] ?? '') ? (string) ($input['source'] ?? '') : '', ['http', 'https']);
    $record['event'] = $event['start'] ? $event : [];
    return $record;
}

foreach (['bl_product', 'bl_news', 'bl_application'] as $bl_rest_type) {
    add_action('rest_after_insert_' . $bl_rest_type, static function (WP_Post $post, WP_REST_Request $request): void {
        $meta = $request->get_param('meta');
        if (!is_array($meta) || !array_key_exists('_bl_record', $meta)) return;
        $record = get_post_meta($post->ID, '_bl_record', true);
        if (is_array($record)) update_post_meta($post->ID, '_bl_record', bl_rest_normalize($post->post_type, $record));
    }, 10, 2);
}
unset($bl_rest_type);
