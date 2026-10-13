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
    // Code is written into the page as it is read (bl_tracking_markup); a plain external <script src> can then be skipped by Chrome on very slow connections, so the editor is told which slot has one.
    $blocking = [];
    foreach (BL_TRACKING_SLOTS as $slots) foreach ($slots as $key) if (bl_tracking_has_blocking_script((string) ($values[$key] ?? ''))) $blocking[] = $key;
    if ($blocking) {
        echo '<div class="notice notice-warning"><p><strong>有外部脚本没有写 async 或 defer。</strong>' . esc_html(implode('、', $blocking)) . ' 里的 &lt;script src="…"&gt; 在访客网速极慢（2G 级）、浏览器没有缓存时，可能被 Chrome 直接跳过，后面依赖它的代码也会出错；其余情况下按原样顺序执行，不受影响。如果这个工具提供 async 或 defer 的写法，建议改用。GA4、Google Ads、Meta、Clarity、Yandex 官方给的代码本来就是内联或 async 的。</p></div>';
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
    $google = [
        'google_tag' => ['Google 标签 ID', '填 GA4 的衡量 ID（G- 开头）和 Google Ads 的转化 ID（AW- 开头），多个用逗号隔开；其他开头的 ID（GT-、DC- 等）不会生效，请按工具给的代码贴到下面的格子。主题会自动加载 Google 标签并开启同意模式 v2：欧洲经济区、英国、瑞士的访客在同意前默认拒绝（只发不带 Cookie 的匿名信号），其他地区默认允许；访客在横幅里的选择随时更新。填了这里就不要再把 GA4 或 Google Ads 的代码贴进下面的格子，否则会重复计数。'],
        'google_conversion' => ['询盘转化（Google Ads）', '在 Google Ads 建好「提交潜在客户表单」转化后，填它的 send_to 值，形如 AW-123456789/AbCdEfGh。访客发出询盘、到达成功页时上报一次；同时总会上报 GA4 的 generate_lead 事件。'],
    ];
    echo '<h2 class="bl-ce-heading">Google 标签（同意模式）</h2>';
    $confirmed = ($values['google_regional'] ?? '') === '1';
    echo '<p class="bl-field-help">勾选下面的确认之前，Google 标签和其他代码一样，访客同意后才加载。勾选后才按地区默认：欧洲经济区、英国、瑞士默认拒绝（只发不带 Cookie 的匿名信号），其他地区默认允许。</p>';
    if (bl_google_tag_ids() && !$confirmed) echo '<div class="notice notice-warning"><p><strong>Google 标签目前仍是“同意后才加载”。</strong>先把“全站设置 → Cookie 同意横幅”的说明和隐私说明改成分地区的写法（主题默认文字可参考），再勾选下面的确认并保存。</p></div>';
    foreach ($google as $key => [$label, $help]) {
        echo '<div class="bl-field bl-ce-wide"><div class="bl-field-label"><label for="bl-tracking-' . $key . '">' . esc_html($label) . '</label></div><div class="bl-field-control"><input class="large-text code" type="text" spellcheck="false" id="bl-tracking-' . $key . '" name="bl_content[' . $key . ']" value="' . esc_attr((string) ($values[$key] ?? '')) . '"><p class="bl-field-help">' . esc_html($help) . '</p></div></div>';
    }
    echo '<div class="bl-field bl-ce-wide"><div class="bl-field-label">按地区默认</div><div class="bl-field-control"><input type="hidden" name="bl_content[google_regional]" value=""><label><input type="checkbox" id="bl-tracking-google_regional" name="bl_content[google_regional]" value="1"' . ($confirmed ? ' checked' : '') . '> 我已确认横幅说明和隐私说明写明了：欧洲经济区、英国、瑞士同意后才使用 Cookie，其他地区默认开启、可随时关闭</label></div></div>';
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

add_action('wp_head', static fn() => print(bl_google_tag_markup()), 0);
add_action('wp_head', static fn() => bl_render_tracking('head'), 1);
add_action('wp_body_open', static fn() => bl_render_tracking('body_open'), 1);
add_action('wp_footer', static fn() => bl_render_tracking('footer'), 99);
