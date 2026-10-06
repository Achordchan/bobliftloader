<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

function bl_can_manage_tracking(): bool
{
    return current_user_can('manage_options') && current_user_can('unfiltered_html');
}

/** Raw scripts are isolated from REST proxies; writes still use the P0 revision, lock and snapshot flow. */
function bl_tracking_screen(): void
{
    if (!bl_can_manage_tracking()) return;
    $saved = bl_content_saved_now('tracking');
    $values = bl_content_resolve('tracking', $saved);
    $revision = bl_content_revision($saved);
    $recovery = bl_content_recovery('tracking');
    if ($recovery) {
        $values = bl_content_resolve('tracking', $recovery['input']);
        $revision = $recovery['revision'];
        echo '<p role="alert"><span class="bl-chip">未保存</span> ' . esc_html($recovery['message']) . '</p>';
    }
    echo '<details class="bl-panel" id="bl-tracking"' . ($recovery ? ' open' : '') . '><summary class="bl-panel-head">统计与跟踪代码</summary><div class="bl-panel-body"><p>仅同时拥有网站设置权限和未过滤 HTML 权限的用户可见、可保存。代码按原文输出，请粘贴完整代码。</p><p>GTM 的 script 放入 head，noscript 放入 body 开头；GA4 只需 head 那段。本区使用下方按钮独立保存。</p>';
    // The privacy page promises no tracking; once a code is in, point the editor at the sentence to change.
    $privacy = (string) (bl_editable('privacy')['body']['ending'] ?? '');
    if (trim(implode('', array_map('strval', $values))) !== '' && stripos($privacy, 'does not use advertising or analytics tracking') !== false) {
        echo '<p role="status"><span class="bl-chip bl-chip-warn">请同步隐私说明</span> 已填写统计或跟踪代码，但隐私说明仍写着本站不使用广告或分析跟踪。<a href="' . esc_url(admin_url('admin.php?page=bl-content-privacy')) . '">去修改隐私说明</a></p>';
    }
    echo '<form class="bl-ce-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bl_save_content"><input type="hidden" name="bl_page" value="tracking">';
    wp_nonce_field('bl_save_content_tracking');
    echo bl_content_revision_input($revision);
    foreach (['head' => '插入 head 内', 'body_open' => '紧跟 body 开头', 'footer' => 'body 结尾'] as $key => $label) {
        echo '<p><label for="bl-tracking-' . $key . '">' . esc_html($label) . '</label></p><textarea class="large-text code" rows="8" id="bl-tracking-' . $key . '" name="bl_content[' . $key . ']">' . esc_textarea($values[$key]) . '</textarea>';
    }
    echo '<input type="hidden" name="bl_content_complete" value="1">';
    echo '<p class="bl-savebar-status" role="status">尚未修改</p>';
    submit_button('保存统计与跟踪代码');
    echo '</form></div></details>';
}

function bl_render_tracking(string $position): void
{
    if (!in_array($position, ['head', 'body_open', 'footer'], true)) return;
    $saved = get_option('bl_content_tracking', []);
    echo bl_content_resolve('tracking', is_array($saved) ? $saved : [])[$position]; // Authorized administrators intentionally supply executable markup.
}

add_action('wp_head', static fn() => bl_render_tracking('head'), 1);
add_action('wp_body_open', static fn() => bl_render_tracking('body_open'), 1);
add_action('wp_footer', static fn() => bl_render_tracking('footer'), 99);
