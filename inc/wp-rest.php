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

/**
 * The 网站内容 screens (home, Support, Applications, loaders) through REST. Each screen has one item of the hidden type
 * bl_page_content, slug = the screen's key; its _bl_content meta reads as the page's current content (defaults with the
 * saved changes) and writes through the same cleaning and "keep only what differs" saving as the screen. The item itself
 * holds nothing: the content stays in the bl_content_{page} option the site reads.
 *
 * A write sends only what should change: groups merge key by key, a list (FAQ, jobs, attachments…) is replaced whole.
 */
add_action('init', static function (): void {
    register_post_type('bl_page_content', [
        'label' => '网站内容（接口）',
        'public' => false,
        'show_ui' => false,
        'show_in_rest' => true,
        'rest_base' => 'bl_page_content',
        'supports' => ['title', 'custom-fields'],
        'capability_type' => 'page',
        'map_meta_cap' => true,
        // Items are made by the theme, one per screen; they are never created or removed through the API.
        'capabilities' => ['create_posts' => 'do_not_allow', 'delete_posts' => 'do_not_allow', 'delete_published_posts' => 'do_not_allow', 'delete_others_posts' => 'do_not_allow'],
    ]);
    register_post_meta('bl_page_content', '_bl_content', [
        'type' => 'object',
        'single' => true,
        'description' => 'The page\'s current content, shaped like the defaults in inc/editable.php. Send only what changes; lists are replaced whole.',
        'show_in_rest' => ['schema' => ['type' => 'object', 'context' => ['edit'], 'additionalProperties' => true]],
        'auth_callback' => static fn(bool $allowed, string $key, int $postId): bool => current_user_can('edit_post', $postId),
    ]);
});

/** The screen an item stands for, or '' for anything else. */
function bl_page_content_key(int $postId): string
{
    $post = get_post($postId);
    return $post && $post->post_type === 'bl_page_content' && isset(BL_CONTENT_PAGES[$post->post_name]) ? $post->post_name : '';
}

/** A page's content as it stands now (bl_editable() caches per request, which a write in the same request must not see). */
function bl_page_content_current(string $page): array
{
    $saved = get_option('bl_content_' . $page);
    $content = bl_content_defaults($page);
    return is_array($saved) && $saved ? bl_merge_content($content, $saved) : $content;
}

// One private item per screen (only editors see them in the API), made once and again whenever a screen is added. Under
// a lock, with the existence check repeated inside it, so requests arriving together on deployment make each item once.
add_action('init', static function (): void {
    if (get_option('bl_page_content_items') === array_keys(BL_CONTENT_PAGES) || !($token = bl_lock_acquire('page_content_items'))) return;
    try {
        foreach (BL_CONTENT_PAGES as $page => $info) {
            $found = get_posts(['post_type' => 'bl_page_content', 'name' => $page, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true, 'cache_results' => false]);
            if (!$found) wp_insert_post(['post_type' => 'bl_page_content', 'post_status' => 'private', 'post_name' => $page, 'post_title' => $info['title']]);
        }
        update_option('bl_page_content_items', array_keys(BL_CONTENT_PAGES), true);
    } finally {
        bl_lock_release('page_content_items', $token);
    }
}, 20);

// The slug is the item's identity and the item stays private: neither changes after it is made, through any path.
add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    if ($data['post_type'] !== 'bl_page_content' || empty($postarr['ID']) || !($stored = get_post((int) $postarr['ID']))) return $data;
    $data['post_name'] = $stored->post_name;
    $data['post_status'] = $stored->post_status;
    return $data;
}, 10, 2);

add_filter('get_post_metadata', static function ($value, $postId, $key, $single) {
    if ($key !== '_bl_content' || !($page = bl_page_content_key((int) $postId))) return $value;
    return [bl_page_content_current($page)];
}, 10, 4);

// Nothing is ever stored on the item itself.
add_filter('update_post_metadata', static fn($check, $postId, $key) => $key === '_bl_content' && bl_page_content_key((int) $postId) ? true : $check, 10, 3);

add_filter('rest_pre_insert_bl_page_content', static function ($prepared, WP_REST_Request $request) {
    $meta = $request->get_param('meta');
    if (is_wp_error($prepared) || !is_array($meta) || !array_key_exists('_bl_content', $meta)) return $prepared;
    $page = bl_page_content_key((int) $request->get_param('id'));
    $sent = $meta['_bl_content'];
    if (!$page) return new WP_Error('bl_content_unknown', 'Unknown content page.', ['status' => 404]);
    if (!is_array($sent)) return new WP_Error('bl_content_invalid', 'The content must be an object.', ['status' => 400]);
    // Starting from the content saved right now keeps everything not sent, loading figures of unpublished models included;
    // the lock keeps two writes to the same page from both starting from the same content.
    $saved = bl_content_locked($page, static function (array $previous) use ($page, $sent): array {
        $current = bl_merge_content(bl_content_defaults($page), $previous);
        return bl_content_diff(bl_content_clean_all($page, bl_merge_content($current, $sent)), bl_content_clean_all($page, bl_content_defaults($page)));
    });
    if (!$saved) return new WP_Error('bl_content_busy', 'Another save of this page is in progress; nothing was saved. Try again.', ['status' => 409]);
    unset($meta['_bl_content']);
    $request->set_param('meta', $meta);
    return $prepared;
}, 10, 2);
