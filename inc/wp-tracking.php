<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__ . '/consent.php';

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
    // Code saved before the two groups existed sits in the three original places and now counts as analytics; saving this screen
    // once (it then carries the 'grouped' mark) is the editor's confirmation that nothing advertising is left there; until then
    // bl_tracking_code() prints none of it.
    if (($saved['grouped'] ?? '') !== '1' && array_filter(array_map('trim', array_intersect_key($saved, array_flip(['head', 'body_open', 'footer']))))) {
        echo '<div class="notice notice-warning"><p><strong>原有代码已暂停加载，请先核对。</strong>分组之前保存的代码无法判断是统计还是广告，所以在你核对之前网站不会输出它们。请把 Google Ads、Meta 像素等广告代码剪切到“广告类”，其余留在“统计类”，然后保存本页，保存后才恢复（统计类要访客同意 Analytics 才加载）。</p></div>';
    }
    // Code is written into the page as it is read (bl_tracking_markup); a plain external <script src> can then be skipped by Chrome on very slow connections, so such code is held back (bl_tracking_code).
    $blocking = [];
    foreach (BL_TRACKING_SLOTS as $slots) foreach ($slots as $key) if (bl_tracking_has_blocking_script((string) ($values[$key] ?? ''))) $blocking[] = $key;
    if ($blocking) {
        echo '<div class="notice notice-warning"><p><strong>有代码已暂停加载：外部脚本没有写 async 或 defer。</strong>' . esc_html(implode('、', $blocking)) . ' 里的 &lt;script src="…"&gt; 请改成 &lt;script async src="…"&gt;（或 defer），再保存。访客同意后代码是随页面读取写入的，没有 async/defer 的外部脚本在网速很慢、浏览器没缓存时会被 Chrome 直接跳过，后面依赖它的代码也会出错，所以这样的代码目前不输出。GA4、Google Ads、Meta、Clarity、Yandex 官方给的代码本来就是内联或 async 的。</p></div>';
    }
    // The privacy page promises no tracking; once a code is in, point the editor at the sentence to change.
    $privacy = (string) (bl_editable('privacy')['body']['ending'] ?? '');
    if (trim(implode('', array_map('strval', $values))) !== '' && stripos($privacy, 'does not use advertising or analytics tracking') !== false) {
        echo '<div class="notice notice-warning"><p><span class="bl-chip bl-chip-warn">请同步隐私说明</span> 已填写统计或跟踪代码，但隐私说明仍写着本站不使用广告或分析跟踪。<a href="' . esc_url(admin_url('admin.php?page=bl-content-privacy')) . '">去修改隐私说明</a></p></div>';
    }
    echo '<form class="bl-ce-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bl_save_content"><input type="hidden" name="bl_page" value="tracking">';
    wp_nonce_field('bl_save_content_tracking');
    echo bl_content_revision_input($revision);
    echo '<div class="bl-panel" id="bl-tracking"><div class="bl-panel-body">';
    $positions = [
        'head' => ['放进 &lt;head&gt;', 'GA4 的整段代码，或 GTM 的第一段（&lt;script&gt; 开头的那段）。大多数工具只需要这一格。'],
        'body_open' => ['紧跟 &lt;body&gt; 开头', 'GTM 的第二段（&lt;noscript&gt; 开头的那段）。访客同意后才由脚本加载，&lt;noscript&gt; 部分不会生效，可以不填。'],
        'footer' => ['放在页面最底部', '只有工具明确要求“放在 &lt;/body&gt; 之前”时才用。'],
    ];
    $categories = [
        'analytics' => ['统计类代码', 'Google Analytics（GA4）、Microsoft Clarity、Yandex Metrica、热力图等只用来统计访问的工具。访客在 Cookie 横幅里同意“Analytics”后才加载。'],
        'advertising' => ['广告类代码', 'Google Ads 转化、Meta 像素、TikTok、LinkedIn、Yandex 广告等用于投放和再营销的工具。访客同意“Advertising”后才加载。'],
    ];
    foreach ($categories as $category => [$title, $note]) {
        echo '<h2 class="bl-ce-heading">' . esc_html($title) . '</h2><p class="bl-field-help">' . esc_html($note) . '</p>';
        foreach ($positions as $position => [$label, $help]) {
            $key = BL_TRACKING_SLOTS[$category][$position];
            echo '<div class="bl-field bl-ce-wide"><div class="bl-field-label"><label for="bl-tracking-' . $key . '">' . $label . '</label></div><div class="bl-field-control"><textarea class="large-text code" rows="6" spellcheck="false" id="bl-tracking-' . $key . '" name="bl_content[' . $key . ']">' . esc_textarea($values[$key]) . '</textarea><p class="bl-field-help">' . $help . '</p></div></div>';
        }
    }
    echo '<p class="bl-field-help">代码按原样输出到每个页面，请粘贴工具给出的完整代码；访客点同意之前它们都不会运行。填了任何代码，页面才会出现 Cookie 横幅和页脚的“Cookie settings”，横幅文字在“全站设置 → Cookie 同意横幅”。只有管理员能看到和保存这一页。留空的格子不会输出任何东西。</p>';
    echo '</div></div><input type="hidden" name="bl_content[grouped]" value="1"><input type="hidden" name="bl_content_complete" value="1"><div class="bl-savebar">';
    submit_button('保存', 'primary', 'submit', false);
    echo '<span class="bl-savebar-status" role="status">没有未保存的修改</span></div></form>';
    bl_content_history_screen('tracking', $revision);
}

/**
 * Each slot's code is printed with bl_tracking_markup(): inert until the visitor's cookie agrees to its consent category, and
 * then written into the page as the page is read, exactly as if pasted there. Agreeing in the banner reloads the page.
 */
function bl_render_tracking(string $position): void
{
    if (!in_array($position, ['head', 'body_open', 'footer'], true)) return;
    foreach (BL_TRACKING_SLOTS as $category => $slots) {
        $code = bl_tracking_code($slots[$position]);
        if ($code !== '') echo bl_tracking_markup($category, $code); // Authorized administrators intentionally supply executable markup.
    }
}

add_action('wp_head', static fn() => bl_render_tracking('head'), 1);
add_action('wp_body_open', static fn() => bl_render_tracking('body_open'), 1);
add_action('wp_footer', static fn() => bl_render_tracking('footer'), 99);
