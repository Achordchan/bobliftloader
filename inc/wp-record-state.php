<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

require_once __DIR__ . '/wp-record-history.php';
require_once __DIR__ . '/wp-news-body.php';

/** Serialize one record's history and value writes; a failed value write leaves its before-image available. */
function bl_record_locked(int $postId, callable $compute, bool $forceHistory = false): bool|WP_Error
{
    // A product form submission holds its record's lock for the whole request (see below), so its boxes share it.
    $held = $GLOBALS['bl_record_held'][$postId] ?? '';
    $token = $held !== '' ? $held : bl_lock_acquire('record_' . $postId, 1);
    if ($token === '') return new WP_Error('bl_record_busy', '该记录正在保存，请稍后重试。', ['status' => 409]);
    try {
        wp_cache_delete($postId, 'post_meta');
        $previous = get_post_meta($postId, '_bl_record', true);
        $previous = is_array($previous) ? $previous : [];
        $next = $compute($previous);
        if (is_wp_error($next)) return $next;
        if ($next === $previous && !$forceHistory) return true;
        // A save that outlived its lock must not touch the history either: another request owns this record now.
        if (!bl_lock_owned('record_' . $postId, $token)) return new WP_Error('bl_record_busy', '记录保存锁已失效。', ['status' => 409]);
        $backup = bl_record_history_save($postId, $previous, $next, $forceHistory);
        if (is_wp_error($backup)) return $backup;
        if ($next === $previous) return true;
        if (!bl_lock_owned('record_' . $postId, $token)) return new WP_Error('bl_record_busy', '记录保存锁已失效。', ['status' => 409]);
        $GLOBALS['bl_record_writing'][$postId] = true;
        try { $saved = update_post_meta($postId, '_bl_record', wp_slash($next)); }
        finally { unset($GLOBALS['bl_record_writing'][$postId]); }
        if ($saved === false) return new WP_Error('bl_record_write_failed', '记录保存失败。', ['status' => 500]);
        return true;
    } finally { if ($held === '') bl_lock_release('record_' . $postId, $token); }
}

// The classic product form saves through several boxes (main fields, product page, videos). One lock, taken before core
// writes and kept to the end of the request, makes them one save with one before-image no other writer can split.
add_action('load-post.php', static function (): void {
    if (($_POST['action'] ?? '') !== 'editpost' || ($_POST['bl_product_complete'] ?? '') !== '1') return;
    $postId = isset($_POST['post_ID']) && is_scalar($_POST['post_ID']) ? (int) $_POST['post_ID'] : 0;
    if (!$postId || get_post_type($postId) !== 'bl_product' || !current_user_can('edit_post', $postId)) return;
    if (!empty($GLOBALS['bl_record_held'][$postId])) return;
    $token = bl_lock_acquire('record_' . $postId, 5);
    if ($token === '') wp_die('这个产品正在被另一个保存使用，请稍后再保存。', '', ['response' => 409, 'back_link' => true]);
    $GLOBALS['bl_record_held'][$postId] = $token;
    register_shutdown_function(static function () use ($postId, $token): void {
        unset($GLOBALS['bl_record_held'][$postId]);
        bl_lock_release('record_' . $postId, $token);
    });
}, 5);

add_filter('update_post_metadata', static function ($check, $postId, $key, $value) {
    if ($check !== null || $key !== '_bl_record' || !empty($GLOBALS['bl_record_writing'][$postId]) || !in_array(get_post_type($postId), ['bl_product', 'bl_news', 'bl_application'], true)) return $check;
    if (!is_array($value)) return false;
    // Model history is merged from the value read under the lock, so two quick renames both keep their old names.
    $product = get_post_type($postId) === 'bl_product';
    return !is_wp_error(bl_record_locked((int) $postId, static fn(array $locked) => $product ? bl_product_model_aliases($value, $locked) : $value));
}, 10, 4);
