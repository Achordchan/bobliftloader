<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The record behind every product, news story and application (_bl_record) is open to the REST API, so tools outside
 * wp-admin (the WordPress.com connector, scripts) can read and change specs, models and the rest. Only users who may
 * edit the post can write it; a REST write changes only the fields the edit screens offer, cleaned the same way.
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

/**
 * The fields the edit screens let people change, per type. Through REST these are the only ones that can change;
 * everything else in a record (imported sections, posters, dates…) keeps its stored value whatever a client sends.
 */
const BL_REST_FIELDS = [
    'bl_product' => ['model', 'category', 'source', 'image', 'gallery', 'specs', 'videos'],
    'bl_news' => ['category', 'line', 'caption', 'source', 'image', 'products', 'event'],
    'bl_application' => ['hub', 'products', 'facts', 'source', 'image'],
];

/** One editable field, cleaned to the shape and rules the edit screens save. */
function bl_rest_field(string $type, string $key, $value)
{
    $text = is_scalar($value) ? sanitize_text_field((string) $value) : '';
    switch ($key) {
        case 'source':
        case 'image':
            return bl_rest_clean_url($value);
        case 'model':
        case 'caption':
            return $text;
        case 'category':
            if ($type === 'bl_product') return in_array($text, ['skid-steer-loaders', 'mini-dumpers'], true) ? $text : 'skid-steer-loaders';
            return isset(BL_NEWS_CATEGORIES[$text]) ? $text : '';
        case 'line':
            return $text === 'group' ? 'group' : 'compact';
        case 'hub':
            return isset(bl_case_hubs()[$text]) ? $text : '';
        case 'products':
            return bl_rest_product_slugs($value);
        case 'gallery':
            return array_values(array_filter(array_map('bl_rest_clean_url', array_slice(array_values((array) $value), 0, 12))));
        case 'specs':
            $specs = [];
            foreach (array_slice(is_array($value) ? $value : [], 0, 40, true) as $label => $item) {
                $label = sanitize_text_field((string) $label);
                $item = is_scalar($item) ? sanitize_text_field((string) $item) : '';
                if ($label !== '' && $item !== '') $specs[$label] = $item;
            }
            return $specs;
        case 'videos':
            $videos = [];
            foreach (array_slice(array_values((array) $value), 0, 8) as $video) {
                $src = is_array($video) ? bl_rest_clean_url($video['src'] ?? '') : '';
                if ($src) $videos[] = ['src' => $src, 'poster' => bl_rest_clean_url($video['poster'] ?? ''), 'caption' => is_scalar($video['caption'] ?? null) ? sanitize_text_field((string) $video['caption']) : ''];
            }
            return $videos;
        case 'facts':
            $facts = [];
            foreach (['location', 'customer', 'task', 'site', 'duration', 'result'] as $fact) $facts[$fact] = isset($value[$fact]) && is_scalar($value[$fact]) ? sanitize_text_field((string) $value[$fact]) : '';
            return $facts;
        case 'event':
            $input = is_array($value) ? $value : [];
            $event = [];
            foreach (['name', 'venue', 'address', 'booth', 'booth_note', 'source_label'] as $field) $event[$field] = isset($input[$field]) && is_scalar($input[$field]) ? sanitize_text_field((string) $input[$field]) : '';
            foreach (['start', 'end'] as $field) $event[$field] = isset($input[$field]) && is_string($input[$field]) && bl_is_date($input[$field]) ? $input[$field] : '';
            if ($event['end'] && $event['end'] < $event['start']) $event['end'] = '';
            $event['source'] = esc_url_raw(is_scalar($input['source'] ?? null) ? (string) $input['source'] : '', ['http', 'https']);
            return $event['start'] ? $event : [];
    }
    return null;
}

/**
 * A REST write is merged into the stored record before WordPress saves anything: only the editable fields the client
 * sent change (so a missing "line" keeps the old stories' fallback to the bundled data), everything else keeps its
 * stored value, and the record is never written raw or deleted (a null or non-object record is refused).
 */
foreach (array_keys(BL_REST_FIELDS) as $bl_rest_type) {
    add_filter('rest_pre_insert_' . $bl_rest_type, static function ($prepared, WP_REST_Request $request) use ($bl_rest_type) {
        $type = $bl_rest_type;
        $meta = $request->get_param('meta');
        if (is_wp_error($prepared) || !is_array($meta) || !array_key_exists('_bl_record', $meta)) return $prepared;
        $sent = $meta['_bl_record'];
        if (!is_array($sent)) return new WP_Error('bl_record_invalid', 'The BOB-LIFT record must be an object; it cannot be removed through the API.', ['status' => 400]);
        $id = (int) $request->get_param('id');
        $record = $id ? get_post_meta($id, '_bl_record', true) : [];
        if (!is_array($record)) $record = [];
        foreach (BL_REST_FIELDS[$type] as $key) if (array_key_exists($key, $sent)) $record[$key] = bl_rest_field($type, $key, $sent[$key]);
        if ($type === 'bl_product') $record = bl_product_derived($record);
        $meta['_bl_record'] = $record;
        $request->set_param('meta', $meta);
        return $prepared;
    }, 10, 2);
}
unset($bl_rest_type);
