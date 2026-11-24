<?php
declare(strict_types=1);

/**
 * IndexNow: tells Bing (and the other IndexNow engines) which pages changed, so ChatGPT search and Copilot, which
 * answer from the Bing index, see new and updated pages sooner than the next sitemap read.
 *
 * Every page of this site is in bl_sitemap_paths(), including the Spanish copies, and most of them are not WordPress
 * posts, so this does not hang off post hooks. An hourly cron run compares each sitemap page's version (the stored
 * modification time of the post behind it, or of the site's content as a whole for the pages built from many records)
 * with the version last submitted, and sends the pages that are new, changed or gone. Publishing a story or product
 * also asks for a run two minutes later. A failed POST changes nothing, so the next run sends the same pages again.
 *
 * Only the live site submits: WordPress on bobliftloader.com with search engines allowed (blog_public '1'; a private
 * WordPress.com site is -1). The key is a random 32-character hex string kept in an option and served at /<key>.txt.
 */

const BL_INDEXNOW_ENDPOINT = 'https://api.indexnow.org/indexnow';
const BL_INDEXNOW_HOST = 'bobliftloader.com';
const BL_INDEXNOW_KEY_OPTION = 'bl_indexnow_key';
const BL_INDEXNOW_SENT_OPTION = 'bl_indexnow_sent';
const BL_INDEXNOW_LOCK_OPTION = 'bl_indexnow_lock';
const BL_INDEXNOW_KEY_CHECK = 'bl_indexnow_key_file_ok';
const BL_INDEXNOW_HOOK = 'bl_indexnow_run';
const BL_INDEXNOW_POST_TYPES = ['bl_news', 'bl_product', 'bl_application', 'bl_page_content'];
const BL_INDEXNOW_BATCH = 10000;
const BL_INDEXNOW_LOCK_SECONDS = 600;

function bl_indexnow_valid_key(mixed $key): bool
{
    return is_string($key) && preg_match('/^[a-f0-9]{32}\z/', $key) === 1;
}

/** The host of a site address, lower case, without IPv6 brackets or a trailing DNS dot. */
function bl_indexnow_host(string $url): string
{
    return rtrim(trim(strtolower((string) parse_url($url, PHP_URL_HOST)), '[]'), '.');
}

function bl_indexnow_enabled(): bool
{
    if (!function_exists('bl_is_wordpress') || !bl_is_wordpress()) return false;
    if ((string) get_option('blog_public') !== '1') return false;
    return bl_indexnow_host(home_url('/')) === BL_INDEXNOW_HOST;
}

/** The stored key, or '' when there is none yet (or it is broken) and $create is false. */
function bl_indexnow_key(bool $create = false): string
{
    $key = get_option(BL_INDEXNOW_KEY_OPTION, '');
    if (bl_indexnow_valid_key($key)) return $key;
    if (!$create) return '';
    $fresh = bin2hex(random_bytes(16));
    // add_option fails when another request stored a key first; that key is then the one everyone uses.
    if (!add_option(BL_INDEXNOW_KEY_OPTION, $fresh, '', false) && !bl_indexnow_valid_key(get_option(BL_INDEXNOW_KEY_OPTION, ''))) {
        update_option(BL_INDEXNOW_KEY_OPTION, $fresh, false);
    }
    wp_cache_delete(BL_INDEXNOW_KEY_OPTION, 'options');
    $key = get_option(BL_INDEXNOW_KEY_OPTION, '');
    return bl_indexnow_valid_key($key) ? $key : '';
}

/** Answers /<key>.txt with the key and stops; any other path is left to the normal routing. */
function bl_indexnow_serve_key(string $path): void
{
    if (!preg_match('~^/([a-f0-9]{32})\.txt\z~', $path, $match) || !bl_indexnow_enabled()) return;
    $key = bl_indexnow_key();
    if ($key === '' || !hash_equals($key, $match[1])) return;
    status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=86400');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo $key;
    exit;
}

/** A sitemap path without its language folder (/es/news/x/ → /news/x/). */
function bl_indexnow_english_path(string $path): string
{
    foreach (array_keys(BL_I18N_LANGUAGES) as $code) {
        if ($code !== 'en' && str_starts_with($path, '/' . $code . '/')) return substr($path, strlen($code) + 1);
    }
    return $path;
}

/**
 * Each path's version: the modification time of the story, product or application case behind it, in every language;
 * any other page (home, ranges, applications, support, the news list…) is built from many records, so it takes the
 * newest modification of all of them. $modified is [post type => [slug => time]].
 */
function bl_indexnow_versions(array $paths, array $modified): array
{
    $site = '';
    foreach ($modified as $times) foreach ($times as $time) if ((string) $time > $site) $site = (string) $time;
    $types = ['news' => 'bl_news', 'product' => 'bl_product', 'application' => 'bl_application'];
    $versions = [];
    foreach ($paths as $path) {
        $version = $site;
        if (preg_match('~^/(news|product|application)/([^/]+)/\z~', bl_indexnow_english_path($path), $match)) {
            $version = (string) ($modified[$types[$match[1]]][$match[2]] ?? $site);
        }
        $versions[$path] = $version;
    }
    return $versions;
}

/** Paths whose version differs from the one last sent, new paths, and paths that have left the sitemap. */
function bl_indexnow_changed(array $sent, array $now): array
{
    $changed = [];
    foreach ($now as $path => $version) if (!array_key_exists($path, $sent) || (string) $sent[$path] !== (string) $version) $changed[] = (string) $path;
    foreach ($sent as $path => $version) if (!array_key_exists($path, $now)) $changed[] = (string) $path;
    return $changed;
}

/** The request bodies for a list of paths, at most BL_INDEXNOW_BATCH URLs each. */
function bl_indexnow_payloads(array $paths, string $base, string $key): array
{
    $payloads = [];
    foreach (array_chunk(array_values($paths), BL_INDEXNOW_BATCH) as $chunk) {
        $payloads[] = [
            'paths' => $chunk,
            'body' => ['host' => BL_INDEXNOW_HOST, 'key' => $key, 'keyLocation' => $base . '/' . $key . '.txt', 'urlList' => array_map(static fn(string $path): string => $base . $path, $chunk)],
        ];
    }
    return $payloads;
}

/** [post type => [slug => modification time (GMT)]] for the published posts the pages are built from. */
function bl_indexnow_modified(): array
{
    global $wpdb;
    $types = "'" . implode("','", array_map('esc_sql', BL_INDEXNOW_POST_TYPES)) . "'";
    $rows = $wpdb->get_results("SELECT post_type, post_name, post_modified_gmt FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ($types)", ARRAY_A);
    $modified = array_fill_keys(BL_INDEXNOW_POST_TYPES, []);
    foreach ((array) $rows as $row) $modified[$row['post_type']][$row['post_name']] = (string) $row['post_modified_gmt'];
    return $modified;
}

/** Whether /<key>.txt answers with the key, checked over HTTPS at most once a day while it does. */
function bl_indexnow_key_file_ok(string $key): bool
{
    if (get_transient(BL_INDEXNOW_KEY_CHECK) === $key) return true;
    $response = wp_remote_get(bl_public_base() . '/' . $key . '.txt', ['timeout' => 10, 'redirection' => 0]);
    if (wp_remote_retrieve_response_code($response) !== 200 || trim((string) wp_remote_retrieve_body($response)) !== $key) {
        error_log('IndexNow: the key file is not served at ' . bl_public_base() . '/' . $key . '.txt; nothing sent.');
        return false;
    }
    set_transient(BL_INDEXNOW_KEY_CHECK, $key, DAY_IN_SECONDS);
    return true;
}

/** Holds the run for BL_INDEXNOW_LOCK_SECONDS; false while another run holds it. */
function bl_indexnow_take_lock(): bool
{
    $until = time() + BL_INDEXNOW_LOCK_SECONDS;
    if (add_option(BL_INDEXNOW_LOCK_OPTION, $until, '', false)) return true;
    wp_cache_delete(BL_INDEXNOW_LOCK_OPTION, 'options');
    $held = (int) get_option(BL_INDEXNOW_LOCK_OPTION, 0);
    if ($held > time()) return false;
    // A run that died left its lock; take it over only if nobody else did in the meantime.
    global $wpdb;
    return (bool) $wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value = %s WHERE option_name = %s AND option_value = %s", (string) $until, BL_INDEXNOW_LOCK_OPTION, (string) $held));
}

function bl_indexnow_run(): void
{
    if (!bl_indexnow_enabled() || !bl_indexnow_take_lock()) return;
    try {
        $key = bl_indexnow_key(true);
        if ($key === '' || !bl_indexnow_key_file_ok($key)) return;
        $sent = get_option(BL_INDEXNOW_SENT_OPTION, []);
        if (!is_array($sent)) $sent = [];
        $now = bl_indexnow_versions(bl_sitemap_paths(), bl_indexnow_modified());
        foreach (bl_indexnow_payloads(bl_indexnow_changed($sent, $now), bl_public_base(), $key) as $payload) {
            $response = wp_remote_post(BL_INDEXNOW_ENDPOINT, ['timeout' => 15, 'headers' => ['Content-Type' => 'application/json; charset=utf-8'], 'body' => wp_json_encode($payload['body'])]);
            $code = (int) wp_remote_retrieve_response_code($response);
            if ($code !== 200 && $code !== 202) {
                // Left as it was, so the next run sends these pages again.
                error_log('IndexNow: ' . count($payload['paths']) . ' URLs not accepted (' . ($code ?: (is_wp_error($response) ? $response->get_error_message() : 'no response')) . ').');
                return;
            }
            foreach ($payload['paths'] as $path) {
                if (array_key_exists($path, $now)) $sent[$path] = $now[$path];
                else unset($sent[$path]);
            }
            update_option(BL_INDEXNOW_SENT_OPTION, $sent, false);
        }
    } finally {
        delete_option(BL_INDEXNOW_LOCK_OPTION);
    }
}

if (function_exists('add_action')) {
    add_action(BL_INDEXNOW_HOOK, 'bl_indexnow_run');
    add_action('parse_request', static fn(WP $wp) => bl_indexnow_serve_key('/' . $wp->request), 1);
    add_action('init', static function (): void {
        if (!bl_indexnow_enabled()) return;
        if (!wp_next_scheduled(BL_INDEXNOW_HOOK)) wp_schedule_event(time() + 60, 'hourly', BL_INDEXNOW_HOOK);
    }, 20);
    // A story, product or application case going live is sent within minutes rather than at the next hourly run.
    add_action('transition_post_status', static function (string $new, string $old, WP_Post $post): void {
        if ($new !== 'publish' || !in_array($post->post_type, BL_INDEXNOW_POST_TYPES, true) || !bl_indexnow_enabled()) return;
        wp_schedule_single_event(time() + 120, BL_INDEXNOW_HOOK);
    }, 10, 3);
    add_action('switch_theme', static fn() => wp_clear_scheduled_hook(BL_INDEXNOW_HOOK));
}
