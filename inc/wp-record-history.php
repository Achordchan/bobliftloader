<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Save the before-image under the record's lock, before writing the new record. */
function bl_record_history_save(int $postId, array $previous, array $next, bool $separate = false): bool|WP_Error
{
    $history = get_post_meta($postId, '_bl_record_history', true);
    $history = is_array($history) ? $history : [];
    // One product form saves through several boxes in one request; they share one entry holding the record as it was
    // before the first of them, and its change count follows the request's latest result.
    $form = !$separate && ($_POST['bl_product_complete'] ?? '') === '1';
    $entry = $form ? ($GLOBALS['bl_record_history_entry'][$postId] ?? '') : '';
    // A restore always gets its own entry, so it can itself be undone.
    if ($entry !== '' && ($history[0]['id'] ?? '') === $entry) {
        $changes = bl_content_change_count((array) $history[0]['record'], $next);
        if ($changes === (int) ($history[0]['changes'] ?? -1)) return true;
        $history[0]['changes'] = $changes;
        // An unchanged count needs no write; a refused write stops this box from saving, as for a new entry.
        if (!update_post_meta($postId, '_bl_record_history', wp_slash($history))) {
            return new WP_Error('bl_record_backup_failed', '修改历史保存失败，本次未保存。', ['status' => 500]);
        }
        return true;
    }
    $id = wp_generate_uuid4();
    $GLOBALS['bl_record_history_entry'][$postId] = $form ? $id : '';
    array_unshift($history, ['id' => $id, 'saved_at' => gmdate('c'), 'user_id' => get_current_user_id(), 'record' => $previous, 'changes' => bl_content_change_count($previous, $next)]);
    $history = array_slice($history, 0, 10);
    if (!update_post_meta($postId, '_bl_record_history', wp_slash($history))) {
        return new WP_Error('bl_record_backup_failed', '修改历史保存失败，本次未保存。', ['status' => 500]);
    }
    return true;
}

/** Restore the whole selected record, backing up the current value under the same record lock. */
function bl_record_history_restore(int $postId, string $snapshotId): bool|WP_Error
{
    if (!in_array(get_post_type($postId), ['bl_product', 'bl_news', 'bl_application'], true)) return new WP_Error('bl_record_invalid', '记录类型无效。', ['status' => 400]);
    return bl_record_locked($postId, static function (array $previous) use ($postId, $snapshotId) {
        foreach ((array) get_post_meta($postId, '_bl_record_history', true) as $entry) {
            if (($entry['id'] ?? '') !== $snapshotId || !is_array($entry['record'] ?? null)) continue;
            return get_post_type($postId) === 'bl_product' ? bl_product_model_aliases($entry['record'], $previous) : $entry['record'];
        }
        return new WP_Error('bl_record_snapshot_missing', '没有可恢复的快照。', ['status' => 404]);
    }, true);
}

add_action('admin_post_bl_restore_record', static function (): void {
    $postId = (int) ($_POST['bl_post_id'] ?? 0);
    if (!current_user_can('edit_post', $postId)) wp_die('没有权限恢复该记录。', '', ['response' => 403]);
    check_admin_referer('bl_restore_record_' . $postId);
    $snapshot = isset($_POST['bl_snapshot']) && is_string($_POST['bl_snapshot']) ? wp_unslash($_POST['bl_snapshot']) : '';
    $result = bl_record_history_restore($postId, $snapshot);
    if (is_wp_error($result)) wp_die(esc_html($result->get_error_message()), '', ['response' => 409, 'back_link' => true]);
    wp_safe_redirect(admin_url('post.php?post=' . $postId . '&action=edit'));
    exit;
});

foreach (['bl_product', 'bl_news', 'bl_application'] as $type) {
    add_action('add_meta_boxes_' . $type, static function () use ($type): void {
        add_meta_box('bl-record-history', '记录历史', 'bl_record_history_screen', $type, 'normal', 'low');
    });
}

function bl_record_history_screen(WP_Post $post): void
{
    $history = get_post_meta($post->ID, '_bl_record_history', true);
    echo '<details class="bl-history"><summary>修改历史</summary>';
    if (!is_array($history) || !$history) { echo '<p>暂无历史版本。</p></details>'; return; }
    echo '<p>恢复整份记录，当前内容会先存进历史。</p><table class="widefat striped bl-history-table"><thead><tr><th>保存时间</th><th>谁保存的</th><th>这次改了</th><th><span class="screen-reader-text">操作</span></th></tr></thead><tbody>';
    foreach ($history as $entry) {
        if (!is_array($entry) || empty($entry['id'])) continue;
        $user = get_userdata((int) ($entry['user_id'] ?? 0));
        $time = strtotime((string) ($entry['saved_at'] ?? '')) ?: 0;
        $when = $time ? wp_date('Y-m-d H:i', $time) : '—';
        $formId = 'bl-record-restore-' . $entry['id'];
        // Forms live outside WordPress's editor form; each button submits only its selected history entry.
        $GLOBALS['bl_record_history_forms'][$formId] = ['post_id' => $post->ID, 'entry' => $entry['id'], 'when' => $when];
        echo '<tr><td>' . esc_html($when) . '</td><td>' . esc_html($user ? $user->display_name : '—') . '</td><td>' . (int) ($entry['changes'] ?? 0) . ' 处</td><td class="bl-history-action"><button type="submit" class="button button-small" form="' . esc_attr($formId) . '">恢复到保存前</button></td></tr>';
    }
    echo '</tbody></table></details>';
}
add_action('admin_footer', static function (): void {
    foreach ($GLOBALS['bl_record_history_forms'] ?? [] as $id => $form) {
        echo '<form id="' . esc_attr($id) . '" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'把本页恢复成 ' . esc_attr($form['when']) . ' 这次保存之前的内容吗？当前内容会先存进历史，可以再恢复回来。\')">';
        foreach (['action' => 'bl_restore_record', 'bl_post_id' => $form['post_id'], 'bl_snapshot' => $form['entry']] as $name => $value) echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr((string) $value) . '">';
        wp_nonce_field('bl_restore_record_' . $form['post_id']);
        echo '</form>';
    }
    unset($GLOBALS['bl_record_history_forms']);
}, 100); // after WordPress prints the block editor's boxes (admin_footer, default priority), which fill this list
