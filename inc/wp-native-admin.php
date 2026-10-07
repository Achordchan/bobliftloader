<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Structured pages keep native publishing controls without offering an unused body editor. */
function bl_page_body_is_structured(?WP_Post $post): bool
{
    $page = bl_content_page_for_post($post);
    if (!in_array($page, ['home', 'support', 'applications', 'contact', 'thanks'], true)) return false;
    if ($page !== 'applications' || !$post->post_parent) return true;
    // Under Applications only the scenario pages (built-in or added, shown or hidden) render from 网站内容; any other child
    // page is an ordinary page whose body shows, so it keeps its editor.
    $slugs = array_map('bl_application_slug', $GLOBALS['bl_application_base'] ?? []);
    $slugs = array_merge($slugs, array_keys((array) (bl_editable('applications')['jobs'] ?? [])));
    return in_array($post->post_name, $slugs, true);
}

// Privacy retains the native classic body editor so its guidance sits above the body without a notice bar.
add_filter('use_block_editor_for_post', static fn(bool $use, WP_Post $post): bool => (bl_page_body_is_structured($post) || bl_content_page_for_post($post) === 'privacy') ? false : $use, 99, 2);
add_action('load-post.php', static function (): void {
    $id = abs((int) ($_GET['post'] ?? $_POST['post_ID'] ?? 0));
    if (bl_page_body_is_structured(get_post($id))) remove_post_type_support('page', 'editor');
});

/** No content input is emitted: WordPress preserves the existing post_content on title/status updates. */
add_action('edit_form_after_title', static function (WP_Post $post): void {
    $page = bl_content_page_for_post($post);
    // Only About, Privacy and pages that really render from 网站内容 get a card; an ordinary child page under
    // Applications shows its own body, so it gets none.
    if (!$page || (!in_array($page, ['privacy', 'about'], true) && !bl_page_body_is_structured($post))) return;
    if ($page === 'privacy') $message = '正文在这里写；与询盘模式相关的段落在 网站内容 → 隐私说明';
    elseif ($page === 'about') $message = '公司介绍在这里写；其他文字与图片在 网站内容 → 关于我们';
    else $message = '本页编辑器正文不在网站上显示。内容在 网站内容 → ' . BL_CONTENT_PAGES[$page]['menu'] . '。这里只管理标题、网址和发布状态。';
    echo '<div class="bl-panel"><div class="bl-panel-body"><p>' . esc_html($message) . '</p><a class="button" href="' . esc_url(admin_url('admin.php?page=bl-content-' . $page)) . '">打开网站内容 → ' . esc_html(BL_CONTENT_PAGES[$page]['menu']) . '</a></div></div>';
});

function bl_native_site_icon_note(): void
{
    echo '<p class="bl-card-note">浏览器图标使用 WordPress 原生站点图标，在 <a href="' . esc_url(admin_url('options-general.php')) . '">设置 → 常规 → 站点图标</a> 中修改。未设置时继续使用原有备用图标。</p>';
}

/** Read only: copy the general policy, excluding the separately rendered inquiry paragraph. */
function bl_privacy_migration_card(): void
{
    $post = get_page_by_path('privacy', OBJECT, 'page');
    if ($post && trim($post->post_content) !== '') return;
    $link = $post ? admin_url('post.php?post=' . $post->ID . '&action=edit') : admin_url('edit.php?post_type=page');
    echo '<div class="bl-panel" data-bl-privacy-copy><div class="bl-panel-body"><p>页面正文为空，前台仍使用原有隐私正文或主题默认正文。复制后，在 <a href="' . esc_url($link) . '">页面 → 隐私说明</a> 的“文本”编辑器中粘贴并保存；询盘模式段落仍由本页管理，显示在新正文之后。</p><button type="button" class="button" data-bl-copy-policy>复制到剪贴板</button> <span role="status" data-bl-copy-status></span><textarea class="large-text code" rows="8" readonly hidden aria-label="待复制的隐私正文">' . esc_textarea(bl_privacy_body('intro') . bl_privacy_body('ending')) . '</textarea></div></div>';
}

add_action('admin_menu', static function (): void {
    global $submenu;
    // Core may append a return URL to customize.php; remove every such menu entry for all roles.
    foreach ($submenu['themes.php'] ?? [] as $key => $item) {
        // Fonts: this theme ships its own typefaces and never reads the font library, so the screen would change nothing.
        if (strtok(html_entity_decode((string) $item[2]), '?') === 'customize.php' || in_array($item[2], ['theme-editor.php', 'font-library.php'], true)) unset($submenu['themes.php'][$key]);
    }
}, 999);

add_action('admin_bar_menu', static function (WP_Admin_Bar $bar): void {
    $bar->remove_node('customize');
}, 999);

add_action('load-theme-editor.php', static function (): void {
    wp_die('主题由 GitHub 部署，在这里改会被覆盖。请通过主题代码仓库修改并部署。', '主题文件编辑已关闭', ['response' => 403]);
});
