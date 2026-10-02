<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** Explicit, idempotent initial import. Existing matching posts are never overwritten. */
function bl_import_initial_content(): array
{
    $counts = ['products' => 0, 'news' => 0, 'pages' => 0];
    foreach (bl_read_data('products') as $order => $record) {
        if (get_page_by_path($record['slug'], OBJECT, 'bl_product')) continue;
        // The stored summary is the generated wording, which the site recognises and rebuilds from the current specs.
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
    $pages = ['home' => 'Home', 'about' => 'About BOB-LIFT', 'applications' => 'Applications', 'contact' => 'Contact BOB-LIFT', 'support' => 'Buying & support', 'privacy' => 'Privacy'];
    foreach ($GLOBALS['bl_application_base'] as $application) $pages['applications/' . bl_application_slug($application)] = $application['title'];
    foreach ($pages as $path => $title) {
        $existing = get_page_by_path($path);
        if ($existing) { $parents[$path] = $existing->ID; continue; }
        $segments = explode('/', $path);
        $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => end($segments), 'post_parent' => count($segments) > 1 ? $parents[$segments[0]] : 0], true);
        if (is_wp_error($id)) throw new RuntimeException($id->get_error_message());
        $parents[$path] = $id;
        $counts['pages']++;
    }
    update_option('bl_application_pages', bl_application_page_paths());
    update_option('show_on_front', 'page');
    update_option('page_on_front', $parents['home']);
    if (!get_option('permalink_structure')) update_option('permalink_structure', '/%postname%/');
    flush_rewrite_rules();
    return $counts;
}

add_action('admin_menu', static function (): void {
    add_management_page('BOB-LIFT 站点初始化', 'BOB-LIFT 站点初始化', 'manage_options', 'bl-site-setup', static function (): void {
        $products = count(bl_read_data('products'));
        $news = count(bl_read_data('news'));
        echo '<div class="wrap bl-setup"><div class="bl-page-head"><div><h1>BOB-LIFT 站点初始化</h1><p>新装的网站用这一页把主题自带的产品、新闻和页面导入 WordPress。已经初始化过的网站一般不需要再用。</p></div></div><hr class="wp-header-end">';
        if (isset($_GET['imported'])) echo '<div class="notice notice-success"><p>初始化已完成。请到 <a href="' . esc_url(admin_url('edit.php?post_type=bl_product')) . '">设备产品</a>、<a href="' . esc_url(admin_url('edit.php?post_type=bl_news')) . '">企业新闻</a> 和 <a href="' . esc_url(admin_url('edit.php?post_type=page')) . '">页面</a> 检查内容。</p></div>';
        echo '<section class="bl-panel"><div class="bl-panel-head"><h2>会做什么</h2></div><div class="bl-panel-body"><ul>';
        echo '<li>导入已核实的 ' . $products . ' 款设备产品和 ' . $news . ' 篇企业新闻。</li><li>建立 ' . (6 + count($GLOBALS['bl_application_base'])) . ' 个页面：Home、About、Applications（含 ' . count($GLOBALS['bl_application_base']) . ' 个应用子页）、Support、Contact、Privacy。</li><li>把 Home 设为网站首页；还没设置固定链接时，改用“文章名”形式的网址。</li>';
        echo '</ul></div></section>';
        echo '<section class="bl-panel"><div class="bl-panel-head"><h2>不会做什么</h2></div><div class="bl-panel-body"><ul>';
        echo '<li>不覆盖已有内容：网址相同的产品、新闻或页面会跳过，编辑改过的内容保持不变。</li><li>不删除任何内容，可以重复执行。</li><li>不改变搜索引擎可见性，不发送邮件。</li>';
        echo '</ul></div></section>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="bl_import_sources">';
        wp_nonce_field('bl_import_sources');
        echo '<div class="bl-setup-action">';
        submit_button('开始导入', 'primary', 'submit', false);
        echo '<span class="bl-field-help">只补上缺少的内容，通常几秒内完成。</span></div></form></div>';
    });
});

add_action('admin_post_bl_import_sources', static function (): void {
    if (!current_user_can('manage_options')) wp_die('没有初始化权限。', '', ['response' => 403]);
    check_admin_referer('bl_import_sources');
    try { bl_import_initial_content(); } catch (RuntimeException $error) { wp_die(esc_html($error->getMessage()), '', ['response' => 500]); }
    wp_safe_redirect(admin_url('tools.php?page=bl-site-setup&imported=1'));
    exit;
});

/** The URL path of every application job page, in the theme's order. */
function bl_application_page_paths(): array
{
    return array_map(static fn($application) => 'applications/' . bl_application_slug($application), $GLOBALS['bl_application_base']);
}

/**
 * Jobs added to the theme after a site was initialised get their pages on the next request, so their URLs are not 404s.
 * Only jobs new since the last recorded list are made: a page an editor trashed or deleted for an older job stays gone.
 * Sites initialised before the list was recorded had the original three. Like the content items this runs under a lock,
 * with the check repeated inside it, so requests arriving together on deployment make each page once; a request that
 * finds the lock taken does not wait. Anything already holding a new job's slug under Applications (a page in any
 * status, a trashed one by the slug it had, or an attachment) is left as it is rather than worked around with a suffixed copy, and a
 * page WordPress would only accept under a suffixed slug is removed again; either way that job is not tried again.
 * Only a failed insert leaves the list unrecorded, to be tried on a later request.
 */
function bl_application_make_pages(): void
{
    $paths = bl_application_page_paths();
    if (get_option('bl_application_pages') === $paths || !($token = bl_lock_acquire('application_pages', 1))) return;
    try {
        $parent = get_page_by_path('applications');
        if (!$parent) return;
        $known = get_option('bl_application_pages');
        if (!is_array($known)) $known = ['applications/landscaping', 'applications/site-preparation', 'applications/material-transport'];
        $complete = true;
        foreach ($GLOBALS['bl_application_base'] as $application) {
            $slug = bl_application_slug($application);
            if (in_array('applications/' . $slug, $known, true)) continue;
            $taken = get_posts(['post_type' => 'any', 'name' => $slug, 'post_parent' => $parent->ID, 'post_status' => array_keys(get_post_stati()), 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true, 'cache_results' => false]);
            // Trashing renames a page to <slug>__trashed and keeps the slug it had in _wp_desired_post_slug.
            $taken = $taken ?: get_posts(['post_type' => 'any', 'post_parent' => $parent->ID, 'post_status' => 'trash', 'meta_key' => '_wp_desired_post_slug', 'meta_value' => $slug, 'numberposts' => 1, 'fields' => 'ids', 'suppress_filters' => true, 'cache_results' => false]);
            if ($taken) continue;
            $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $application['title'], 'post_name' => $slug, 'post_parent' => $parent->ID], true);
            if (is_wp_error($id) || !$id) { $complete = false; continue; }
            if (get_post_field('post_name', (int) $id) !== $slug) wp_delete_post((int) $id, true);
        }
        if ($complete) update_option('bl_application_pages', $paths);
    } finally {
        bl_lock_release('application_pages', $token);
    }
}
add_action('init', 'bl_application_make_pages', 20);
