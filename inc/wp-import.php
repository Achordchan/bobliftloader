<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Explicit, idempotent initial import. Existing matching posts are never overwritten. */
function bl_import_initial_content(): array
{
    $counts = ['products' => 0, 'news' => 0, 'pages' => 0];
    foreach (bl_read_data('products') as $order => $record) {
        if (get_page_by_path($record['slug'], OBJECT, 'bl_product')) continue;
        $excerpt = bl_product_excerpt($record);
        $id = wp_insert_post(['post_type' => 'bl_product', 'post_status' => 'publish', 'post_title' => $record['name'], 'post_name' => $record['slug'], 'post_excerpt' => $excerpt, 'menu_order' => $order], true);
        if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
        update_post_meta($id, '_bl_record', $record);
        $counts['products']++;
    }
    foreach (bl_read_data('news') as $record) {
        if (get_page_by_path($record['slug'], OBJECT, 'bl_news')) continue;
        $content = '';
        foreach ($record['sections'] as $section) $content .= '<h2>' . esc_html($section['heading']) . '</h2><p>' . esc_html($section['text']) . '</p>';
        $id = wp_insert_post(['post_type' => 'bl_news', 'post_status' => 'publish', 'post_title' => $record['title'], 'post_name' => $record['slug'], 'post_excerpt' => $record['excerpt'], 'post_content' => $content, 'post_date' => $record['date'] . ' 09:00:00'], true);
        if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
        update_post_meta($id, '_bl_record', $record);
        $counts['news']++;
    }
    $parents = [];
    foreach (['home' => 'Home', 'about' => 'About BOB-LIFT', 'applications' => 'Applications', 'contact' => 'Contact BOB-LIFT', 'privacy' => 'Privacy', 'applications/landscaping' => 'Landscaping', 'applications/site-preparation' => 'Site preparation', 'applications/material-transport' => 'Material transport'] as $path => $title) {
        $existing = get_page_by_path($path);
        if ($existing) { $parents[$path] = $existing->ID; continue; }
        $segments = explode('/', $path);
        $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => end($segments), 'post_parent' => count($segments) > 1 ? $parents[$segments[0]] : 0], true);
        if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
        $parents[$path] = $id;
        $counts['pages']++;
    }
    update_option('show_on_front', 'page');
    update_option('page_on_front', $parents['home']);
    if (!get_option('permalink_structure')) update_option('permalink_structure', '/%postname%/');
    flush_rewrite_rules();
    return $counts;
}

add_action('admin_menu', static function (): void {
    add_management_page('BOB-LIFT 站点初始化', 'BOB-LIFT 站点初始化', 'manage_options', 'bl-site-setup', static function (): void {
        echo '<div class="wrap"><h1>BOB-LIFT 站点初始化</h1><p>导入已核实的 ' . count(bl_read_data('products')) . ' 款产品、3 篇主站新闻摘要和 8 个页面。已有同路径内容会跳过，不会覆盖。</p><p>初始化会将 Home 设为静态首页；未设置固定链接时启用文章名形式。不会改变搜索引擎可见性，也不会发送邮件。</p>';
        if (isset($_GET['imported'])) echo '<div class="notice notice-success"><p>初始化已完成，请前往设备产品、企业新闻和页面检查内容。</p></div>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bl_import_sources">';
        wp_nonce_field('bl_import_sources');
        submit_button('导入初始内容并设置首页');
        echo '</form></div>';
    });
});

add_action('admin_post_bl_import_sources', static function (): void {
    if (!current_user_can('manage_options')) wp_die('没有初始化权限。', '', ['response' => 403]);
    check_admin_referer('bl_import_sources');
    try { bl_import_initial_content(); } catch (RuntimeException $error) { wp_die(esc_html($error->getMessage()), '', ['response' => 500]); }
    wp_safe_redirect(admin_url('tools.php?page=bl-site-setup&imported=1'));
    exit;
});
