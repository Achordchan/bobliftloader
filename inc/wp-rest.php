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
            'description' => 'BOB-LIFT record: model, category, specs (label => value; writes may also send an ordered list of {"key","value"} rows), images, videos, event and links.',
            // Edit context only: the record also holds where product data came from, which the site never shows.
            'show_in_rest' => ['schema' => ['type' => 'object', 'context' => ['edit'], 'additionalProperties' => true, 'properties' => $type === 'bl_application' ? ['image' => ['type' => 'string', 'deprecated' => true, 'description' => '已弃用：封面以特色图片 featured_media 为准；此字段仅保留历史数据。']] : []]],
            'auth_callback' => static fn(bool $allowed, string $key, int $postId): bool => current_user_can('edit_post', $postId),
        ]);
    }
});

add_action('init', static function (): void {
    register_meta('user', 'bl_title', [
        'type' => 'string', 'single' => true, 'show_in_rest' => true,
        'description' => '新闻作者职位，显示在文章署名中。',
        'sanitize_callback' => 'sanitize_text_field',
        'auth_callback' => static fn($allowed, $key, $userId): bool => current_user_can('edit_user', (int) $userId),
    ]);
});

/** A theme path (images/…, videos/…) or an http(s) address, as the edit screens accept. */
function bl_rest_clean_url($value): string
{
    $value = is_scalar($value) ? trim(sanitize_text_field((string) $value)) : '';
    return preg_match('~^(videos|images)/[\w./-]+$~', $value) && !str_contains($value, '..') ? $value : esc_url_raw($value, ['http', 'https']);
}

/** Product slugs that exist, as the news and case boxes keep them. */
function bl_rest_product_slugs($value, array $existing = []): array
{
    $known = array_map(static fn($p) => $p->post_name, get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1]));
    return array_values(array_intersect(array_map(static fn($slug): string => sanitize_title(is_scalar($slug) ? (string) $slug : ''), (array) $value), array_merge($known, $existing)));
}

/**
 * The fields the edit screens let people change, per type. Through REST these are the only ones that can change;
 * everything else in a record (imported sections, posters, dates…) keeps its stored value whatever a client sends.
 */
const BL_REST_FIELDS = [
    'bl_product' => ['model', 'category', 'source', 'spec_source', 'image', 'gallery', 'gallery_alt', 'specs', 'videos', 'seo_description', 'highlights', 'faq', 'attachments', 'photos'],
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
        case 'spec_source':
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
            return array_values(array_filter(array_map('bl_rest_clean_url', array_values((array) $value))));
        case 'specs':
            $specs = [];
            // A {"key","value"} list arrives already converted (rest_pre_insert_bl_product, priority 9); convert here too.
            $value = bl_specs_normalize($value)[0] ?? [];
            foreach ((is_array($value) ? $value : []) as $label => $item) {
                $label = sanitize_text_field((string) $label);
                $item = is_scalar($item) ? sanitize_text_field((string) $item) : '';
                if ($label !== '' && $item !== '') $specs[$label] = $item;
            }
            return $specs;
        case 'videos':
            $videos = [];
            foreach (array_values((array) $value) as $video) {
                $src = is_array($video) ? bl_rest_clean_url($video['src'] ?? '') : '';
                if ($src) $videos[] = ['src' => $src, 'poster' => bl_rest_clean_url($video['poster'] ?? ''), 'caption' => is_scalar($video['caption'] ?? null) ? sanitize_text_field((string) $video['caption']) : ''];
            }
            return $videos;
        case 'gallery_alt':
            // Alternative text per gallery photograph, keyed by the photograph's stored address.
            $alts = [];
            foreach ((is_array($value) ? $value : []) as $path => $alt) {
                $path = bl_rest_clean_url($path);
                $alt = is_scalar($alt) ? mb_substr(sanitize_text_field((string) $alt), 0, 160) : '';
                if ($path !== '' && $alt !== '') $alts[$path] = $alt;
            }
            return $alts;
        case 'seo_description':
            return mb_substr($text, 0, 200);
        case 'highlights':
            $items = [];
            foreach (array_values((array) $value) as $item) {
                $title = is_array($item) && is_scalar($item['title'] ?? null) ? mb_substr(sanitize_text_field((string) $item['title']), 0, 80) : '';
                if ($title !== '') $items[] = ['title' => $title, 'text' => is_scalar($item['text'] ?? null) ? mb_substr(sanitize_text_field((string) $item['text']), 0, 240) : ''];
            }
            return $items;
        case 'faq':
            $items = [];
            foreach (array_values((array) $value) as $item) {
                if (!is_array($item)) continue;
                $field = static fn(string $name): string => is_scalar($item[$name] ?? null) ? sanitize_text_field((string) $item[$name]) : '';
                // A link goes to a page on this site (/support/…, #section) or a full address.
                $url = $field('link_url');
                $url = preg_match('~^[/#][\w./#?=&%-]*$~', $url) && !str_starts_with($url, '//') ? $url : esc_url_raw($url, ['http', 'https']);
                $label = $url !== '' ? $field('link_label') : '';
                if ($field('q') !== '' && $field('a') !== '') $items[] = ['q' => $field('q'), 'a' => $field('a'), 'link_label' => $label, 'link_url' => $label !== '' ? $url : ''];
            }
            return $items;
        case 'attachments':
            return array_values(array_unique(array_filter(array_map(static fn($name): string => is_scalar($name) ? sanitize_text_field((string) $name) : '', (array) $value))));
        case 'photos':
            $photos = [];
            foreach (array_values((array) $value) as $photo) {
                $src = is_array($photo) ? bl_rest_clean_url($photo['src'] ?? '') : '';
                $group = is_array($photo) && is_scalar($photo['group'] ?? null) ? (string) $photo['group'] : '';
                if ($src === '' || !isset(BL_PHOTO_GROUPS[$group])) continue;
                $photos[] = ['src' => $src, 'group' => $group, 'alt' => is_scalar($photo['alt'] ?? null) ? mb_substr(sanitize_text_field((string) $photo['alt']), 0, 160) : '', 'caption' => is_scalar($photo['caption'] ?? null) ? mb_substr(sanitize_text_field((string) $photo['caption']), 0, 200) : ''];
            }
            return $photos;
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
            return $event;
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
        foreach (BL_REST_FIELDS[$type] as $key) if (array_key_exists($key, $sent)) $record[$key] = $key === 'products' ? bl_rest_product_slugs($sent[$key], (array) ($record['products'] ?? [])) : bl_rest_field($type, $key, $sent[$key]);
        if ($type === 'bl_product') $record = bl_product_derived($record);
        $meta['_bl_record'] = $record;
        $request->set_param('meta', $meta);
        return $prepared;
    }, 10, 2);
}
unset($bl_rest_type);

/**
 * The 网站内容 screens (home, Support, Applications, loaders, dumpers) through REST. Each screen has one item of the hidden type
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
    return bl_content_resolve($page, is_array($saved) ? $saved : []);
}

/** REST content and its revision must describe the same snapshot, even if another request saves during serialization. */
function bl_rest_content_snapshot(string $page, bool $refresh = false): array
{
    return bl_rest_content_state($page, $refresh)['saved'];
}

/** One read per request: the payload and its revision describe the same stored content. */
function bl_rest_content_state(string $page, bool $refresh = false): array
{
    static $states = [];
    if ($refresh || !isset($states[$page])) {
        $saved = bl_content_saved_now($page);
        $states[$page] = ['saved' => $saved, 'revision' => bl_content_revision($saved, $page)];
    }
    return $states[$page];
}

// One private item per screen (only editors see them in the API), made once and again whenever a screen is added. Under
// a lock, with the existence check repeated inside it, so requests arriving together on deployment make each item once;
// a request that finds the lock taken does not wait for it.
function bl_page_content_make_items(): void
{
    if (get_option('bl_page_content_items') === array_keys(BL_CONTENT_PAGES) || !($token = bl_lock_acquire('page_content_items', 1))) return;
    try {
        // The marker is set only once every item is there under its own slug, so a failed insert is tried again later.
        $complete = true;
        foreach (BL_CONTENT_PAGES as $page => $info) {
            $page = (string) $page;
            $found = get_posts(['post_type' => 'bl_page_content', 'name' => $page, 'post_status' => 'any', 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true, 'cache_results' => false]);
            if ($found) continue;
            $id = wp_insert_post(['post_type' => 'bl_page_content', 'post_status' => 'private', 'post_name' => $page, 'post_title' => $info['title']], true);
            if (is_wp_error($id) || !$id || bl_page_content_key((int) $id) !== $page) $complete = false;
        }
        if ($complete) update_option('bl_page_content_items', array_keys(BL_CONTENT_PAGES), true);
    } finally {
        bl_lock_release('page_content_items', $token);
    }
}
add_action('init', 'bl_page_content_make_items', 20);

// The slug is the item's identity and the item stays private: neither changes after it is made, through any path.
add_filter('wp_insert_post_data', static function (array $data, array $postarr): array {
    if ($data['post_type'] !== 'bl_page_content' || empty($postarr['ID']) || !($stored = get_post((int) $postarr['ID']))) return $data;
    $data['post_name'] = $stored->post_name;
    $data['post_status'] = $stored->post_status;
    return $data;
}, 10, 2);

add_filter('get_post_metadata', static function ($value, $postId, $key, $single) {
    if ($key !== '_bl_content' || !($page = bl_page_content_key((int) $postId))) return $value;
    return [bl_content_resolve($page, bl_rest_content_snapshot($page))];
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
    $expected = $request->get_param('bl_content_revision');
    if ($expected !== null && !is_string($expected)) return new WP_Error('bl_content_revision_invalid', '内容版本无效。', ['status' => 400]);
    $saved = bl_content_locked($page, static fn(array $previous): array|WP_Error => bl_content_prepare($page, $previous, $sent), $expected);
    if (is_wp_error($saved)) return $saved;
    bl_rest_content_snapshot($page, true);
    unset($meta['_bl_content']);
    $request->set_param('meta', $meta);
    return $prepared;
}, 10, 2);

// Existing clients may omit the token for partial updates; clients editing a full snapshot can opt into conflict checks.
add_action('rest_api_init', static function (): void {
    register_rest_field('bl_page_content', 'bl_content_revision', [
        'get_callback' => static function (array $post): string {
            $page = bl_page_content_key((int) $post['id']);
            return $page ? bl_rest_content_state($page)['revision'] : '';
        },
        'schema' => ['type' => 'string', 'context' => ['edit'], 'description' => '保存时回传此版本；过期版本返回 409，省略时按当前内容合并。'],
    ]);
});
