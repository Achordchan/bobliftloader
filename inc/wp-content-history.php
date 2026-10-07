<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Count changed leaf values, excluding persistence bookkeeping. */
function bl_content_change_count(array $before, array $after): int
{
    $count = 0;
    foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
        if (is_string($key) && str_starts_with($key, '_bl_')) continue;
        $a = $before[$key] ?? null;
        $b = $after[$key] ?? null;
        $count += is_array($a) && is_array($b) ? bl_content_change_count($a, $b) : (int) ($a !== $b);
    }
    return $count;
}

function bl_content_history_screen(string $page, string $revision): void
{
    $history = get_option('bl_content_snapshots_' . $page, []);
    if (!is_array($history) || !$history) return;
    // Each entry is the page as it stood just before someone saved over it, so restoring brings that earlier text back.
    echo '<details class="bl-panel bl-history"><summary class="bl-panel-head"><h2 class="bl-card-title"><span class="bl-card-name">历史版本</span><span class="bl-card-meta">最近 ' . count($history) . ' 次保存前的内容，可恢复</span></h2></summary><div class="bl-panel-body">';
    echo '<p>恢复整份页面内容，当前内容会先存进历史。</p>';
    echo '<table class="widefat striped bl-history-table"><thead><tr><th>保存时间</th><th>谁保存的</th><th>这次改了</th><th><span class="screen-reader-text">操作</span></th></tr></thead><tbody>';
    foreach ($history as $entry) {
        if (!is_array($entry) || empty($entry['id'])) continue;
        $user = get_userdata((int) ($entry['user_id'] ?? 0));
        $operator = $user ? $user->display_name : '—';
        $time = strtotime((string) ($entry['saved_at'] ?? '')) ?: 0;
        $when = $time ? wp_date('Y-m-d H:i', $time) : '—';
        echo '<tr><td>' . esc_html($when) . '</td><td>' . esc_html($operator) . '</td><td>' . (isset($entry['changes']) ? (int) $entry['changes'] . ' 处' : '—') . '</td><td class="bl-history-action">';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" onsubmit="return confirm(\'把本页恢复成 ' . esc_attr($when) . ' 这次保存之前的内容吗？当前内容会先存进历史，可以再恢复回来。\')">';
        echo '<input type="hidden" name="action" value="bl_restore_content"><input type="hidden" name="bl_page" value="' . esc_attr($page) . '"><input type="hidden" name="bl_snapshot" value="' . esc_attr($entry['id']) . '">';
        wp_nonce_field('bl_restore_content_' . $page);
        echo bl_content_revision_input($revision);
        echo '<button type="submit" class="button button-small">恢复到保存前</button></form></td></tr>';
    }
    echo '</tbody></table></div></details>';
}
