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

/** Strings keep only the HTML a post may carry, numbers and true/false stay, anything else goes. */
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
    if (is_string($value)) return wp_kses_post($value);
    return is_int($value) || is_float($value) || is_bool($value) ? $value : null;
}

function bl_rest_clean_url(string $value): string
{
    $value = trim($value);
    return preg_match('~^(videos|images)/[\w./-]+$~', $value) && !str_contains($value, '..') ? $value : esc_url_raw($value, ['http', 'https']);
}

foreach (['bl_product', 'bl_news', 'bl_application'] as $bl_rest_type) {
    add_action('rest_after_insert_' . $bl_rest_type, static function (WP_Post $post, WP_REST_Request $request): void {
        $meta = $request->get_param('meta');
        if (!is_array($meta) || !array_key_exists('_bl_record', $meta)) return;
        $record = get_post_meta($post->ID, '_bl_record', true);
        if (!is_array($record)) return;
        $record = bl_rest_clean($record);
        foreach (['source', 'image'] as $key) if (isset($record[$key])) $record[$key] = bl_rest_clean_url((string) $record[$key]);
        if ($post->post_type === 'bl_product') {
            $record['model'] = sanitize_text_field((string) ($record['model'] ?? ''));
            $record['category'] = in_array($record['category'] ?? '', ['skid-steer-loaders', 'mini-dumpers'], true) ? $record['category'] : 'skid-steer-loaders';
            $record['gallery'] = array_values(array_filter(array_map(static fn($line): string => bl_rest_clean_url((string) $line), array_slice((array) ($record['gallery'] ?? []), 0, 12))));
            $specs = [];
            foreach (array_slice((array) ($record['specs'] ?? []), 0, 40, true) as $key => $value) {
                $key = sanitize_text_field((string) $key);
                $value = is_scalar($value) ? sanitize_text_field((string) $value) : '';
                if ($key !== '' && $value !== '') $specs[$key] = $value;
            }
            $record['specs'] = $specs;
            $record = bl_product_derived($record);
        }
        update_post_meta($post->ID, '_bl_record', $record);
    }, 10, 2);
}
unset($bl_rest_type);
