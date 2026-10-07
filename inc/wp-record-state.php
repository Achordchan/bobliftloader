<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

require_once __DIR__ . '/wp-record-history.php';
require_once __DIR__ . '/wp-news-body.php';

/** Serialize one record's history and value writes; a failed value write leaves its before-image available. */
function bl_record_locked(int $postId, callable $compute, bool $forceHistory = false): bool|WP_Error
{
    $token = bl_lock_acquire('record_' . $postId, 1);
    if ($token === '') return new WP_Error('bl_record_busy', '该记录正在保存，请稍后重试。', ['status' => 409]);
    try {
        wp_cache_delete($postId, 'post_meta');
        $previous = get_post_meta($postId, '_bl_record', true);
        $previous = is_array($previous) ? $previous : [];
        $next = $compute($previous);
        if (is_wp_error($next)) return $next;
        if ($next === $previous && !$forceHistory) return true;
        $backup = bl_record_history_save($postId, $previous, $next, $forceHistory);
        if (is_wp_error($backup)) return $backup;
        if ($next === $previous) return true;
        if (!bl_lock_owned('record_' . $postId, $token)) return new WP_Error('bl_record_busy', '记录保存锁已失效。', ['status' => 409]);
        $GLOBALS['bl_record_writing'][$postId] = true;
        try { $saved = update_post_meta($postId, '_bl_record', wp_slash($next)); }
        finally { unset($GLOBALS['bl_record_writing'][$postId]); }
        if ($saved === false) return new WP_Error('bl_record_write_failed', '记录保存失败。', ['status' => 500]);
        return true;
    } finally { bl_lock_release('record_' . $postId, $token); }
}

add_filter('update_post_metadata', static function ($check, $postId, $key, $value) {
    if ($check !== null || $key !== '_bl_record' || !empty($GLOBALS['bl_record_writing'][$postId]) || !in_array(get_post_type($postId), ['bl_product', 'bl_news', 'bl_application'], true)) return $check;
    if (!is_array($value)) return false;
    return !is_wp_error(bl_record_locked((int) $postId, static fn() => $value));
}, 10, 4);
