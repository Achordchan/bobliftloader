<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * The admin's overall shape: the shared stylesheet, the menu (grouped, with the unused Posts and Comments hidden), the
 * product and news list columns, and a dashboard of shortcuts. Everything here only reads; saving lives with each screen.
 */
add_action('admin_enqueue_scripts', static function (string $hook): void {
    $dir = get_template_directory();
    $uri = get_template_directory_uri();
    wp_enqueue_style('bl-admin', $uri . '/assets/admin/admin.css', [], (string) filemtime($dir . '/assets/admin/admin.css'));
    wp_enqueue_style('bl-admin-ui', $uri . '/assets/admin/admin-ui.css', ['bl-admin'], (string) filemtime($dir . '/assets/admin/admin-ui.css'));
    if (!str_ends_with($hook, '_page_bl-downloads')) return;
    wp_enqueue_media();
    wp_enqueue_script('bl-admin-ui', $uri . '/assets/admin/admin-ui.js', ['jquery', 'media-editor'], (string) filemtime($dir . '/assets/admin/admin-ui.js'), true);
});

// The site publishes no blog posts and takes no comments: their menus and "new" shortcuts are hidden, and comments and
// pings are closed everywhere (wp-comments-post.php then refuses them). Existing data is kept.
add_filter('comments_open', '__return_false', 20);
add_filter('pings_open', '__return_false', 20);
add_filter('comments_array', '__return_empty_array', 20);
add_action('init', static function (): void {
    foreach (get_post_types() as $type) if (post_type_supports($type, 'comments')) {
        remove_post_type_support($type, 'comments');
        remove_post_type_support($type, 'trackbacks');
    }
}, 100);
add_action('admin_menu', static function (): void {
    remove_menu_page('edit.php');
    remove_menu_page('edit-comments.php');
}, 99);

add_action('admin_bar_menu', static function (WP_Admin_Bar $bar): void {
    $bar->remove_node('new-post');
    $bar->remove_node('comments');
    $news = $bar->get_node('new-bl_news');
    $new = $bar->get_node('new-content');
    if ($news && $new) $bar->add_node(['id' => 'new-content', 'href' => $news->href]);
}, 99);

add_filter('custom_menu_order', '__return_true');
add_filter('menu_order', static fn(): array => ['index.php', 'separator1', 'bl-content-home', 'edit.php?post_type=bl_product', 'edit.php?post_type=bl_news', 'edit.php?post_type=page', 'upload.php', 'separator2', 'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php', 'separator-last']);

/** A post's saved record, or [] — the list columns read it and never write. */
function bl_list_record(int $postId): array
{
    $record = get_post_meta($postId, '_bl_record', true);
    return is_array($record) ? $record : [];
}

// WordPress fills unset labels with "文章" wording ("搜索文章", "查看文章"); name the product and news lists properly.
foreach (['bl_product' => '产品', 'bl_news' => '新闻'] as $bl_type => $bl_noun) {
    add_filter('post_type_labels_' . $bl_type, static function (object $labels) use ($bl_noun): object {
        foreach (['search_items' => '搜索' . $bl_noun, 'not_found' => '没有找到' . $bl_noun, 'not_found_in_trash' => '回收站里没有' . $bl_noun, 'view_items' => '查看全部' . $bl_noun, 'all_items' => '全部' . $bl_noun] as $key => $value) $labels->$key = $value;
        return $labels;
    });
}
unset($bl_type, $bl_noun);
add_filter('disable_months_dropdown', static fn(bool $disable, string $postType): bool => $disable || $postType === 'bl_product', 10, 2);

const BL_LIST_PRODUCT_CATEGORIES = ['skid-steer-loaders' => '滑移装载机', 'mini-dumpers' => '迷你翻斗车'];
const BL_LIST_NEWS_CATEGORIES = ['product-news' => '产品新闻', 'customer-stories' => '应用案例', 'industry-news' => '行业新闻'];

add_filter('manage_bl_product_posts_columns', static fn(array $columns): array => ['cb' => $columns['cb'], 'bl_thumb' => '<span class="screen-reader-text">图片</span>', 'title' => '产品名称', 'bl_model' => '型号', 'bl_category' => '分类', 'bl_load' => '额定载荷', 'menu_order' => '排序']);
add_filter('manage_edit-bl_product_sortable_columns', static fn(array $columns): array => $columns + ['menu_order' => 'menu_order']);

add_action('manage_bl_product_posts_custom_column', static function (string $column, int $postId): void {
    $record = bl_list_record($postId);
    if ($column === 'bl_thumb') {
        $image = get_the_post_thumbnail_url($postId, 'thumbnail') ?: (($record['image'] ?? '') ? bl_wp_image((string) $record['image']) : bl_wp_image((string) (((array) ($record['gallery'] ?? []))[0] ?? '')));
        echo $image ? '<img src="' . esc_url($image) . '" alt="" decoding="async">' : '<span class="bl-thumb-empty" aria-hidden="true"></span>';
    } elseif ($column === 'bl_model') {
        echo ($record['model'] ?? '') !== '' ? esc_html((string) $record['model']) : '—';
    } elseif ($column === 'bl_category') {
        echo esc_html(BL_LIST_PRODUCT_CATEGORIES[$record['category'] ?? 'skid-steer-loaders'] ?? (string) $record['category']);
    } elseif ($column === 'bl_load') {
        $load = (int) ($record['load'] ?? 0);
        echo $load ? esc_html(number_format($load) . ' kg') : '—';
    } elseif ($column === 'menu_order') {
        echo (int) get_post_field('menu_order', $postId);
    }
}, 10, 2);

add_action('restrict_manage_posts', static function (string $postType): void {
    if ($postType !== 'bl_product') return;
    $current = sanitize_key(wp_unslash($_GET['bl_product_category'] ?? ''));
    echo '<label class="screen-reader-text" for="bl-product-category">按分类筛选</label><select name="bl_product_category" id="bl-product-category"><option value="">全部分类</option>';
    foreach (BL_LIST_PRODUCT_CATEGORIES as $value => $label) echo '<option value="' . esc_attr($value) . '"' . selected($current, $value, false) . '>' . esc_html($label) . '</option>';
    echo '</select>';
});

// Products list in the site's own order unless a column is clicked; the category filter matches the saved record.
add_action('pre_get_posts', static function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'bl_product' || ($GLOBALS['pagenow'] ?? '') !== 'edit.php') return;
    if (!isset($_GET['orderby'])) $query->set('orderby', ['menu_order' => 'ASC', 'title' => 'ASC']);
    $category = sanitize_key(wp_unslash($_GET['bl_product_category'] ?? ''));
    if (!isset(BL_LIST_PRODUCT_CATEGORIES[$category])) return;
    $ids = [];
    foreach (get_posts(['post_type' => 'bl_product', 'post_status' => array_keys(get_post_stati()), 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true]) as $id) {
        if ((bl_list_record((int) $id)['category'] ?? 'skid-steer-loaders') === $category) $ids[] = (int) $id;
    }
    $query->set('post__in', $ids ?: [0]);
});

add_filter('manage_bl_news_posts_columns', static function (array $columns): array {
    $date = $columns['date'];
    unset($columns['date']);
    return $columns + ['bl_news_category' => '栏目', 'bl_line' => '归属', 'bl_event' => '活动日期', 'date' => $date];
});

add_action('manage_bl_news_posts_custom_column', static function (string $column, int $postId): void {
    $record = bl_list_record($postId);
    $record['line'] = bl_news_line($record, (string) get_post_field('post_name', $postId));
    if ($column === 'bl_news_category') {
        $category = bl_news_category($record);
        echo $category ? esc_html(BL_LIST_NEWS_CATEGORIES[$category]) : '<span class="bl-muted">不归入栏目</span>';
    } elseif ($column === 'bl_line') {
        echo $record['line'] === 'group' ? '<span class="bl-chip">集团</span>' : '<span class="bl-chip bl-chip-info">本站</span>';
    } elseif ($column === 'bl_event') {
        $event = (array) ($record['event'] ?? []);
        $start = (string) ($event['start'] ?? '');
        if (!bl_is_date($start)) { echo '—'; return; }
        $end = bl_is_date((string) ($event['end'] ?? '')) ? (string) $event['end'] : $start;
        $state = bl_event_status(['start' => $start, 'end' => $end])['state'];
        echo esc_html($start . ($end !== $start ? ' 至 ' . (substr($end, 0, 4) === substr($start, 0, 4) ? substr($end, 5) : $end) : ''));
        echo ' <span class="bl-chip' . ['upcoming' => ' bl-chip-info', 'live' => ' bl-chip-ok', 'past' => ''][$state] . '">' . ['upcoming' => '未开始', 'live' => '进行中', 'past' => '已结束'][$state] . '</span>';
        if (!empty($event['name'])) echo '<br><span class="bl-muted">' . esc_html((string) $event['name']) . '</span>';
    }
}, 10, 2);

// Dashboard: WordPress's own welcome, quick draft, news and activity boxes say nothing about this site; site health stays.
add_action('wp_dashboard_setup', static function (): void {
    remove_action('welcome_panel', 'wp_welcome_panel');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    wp_add_dashboard_widget('bl_dashboard', 'BOB-LIFT 网站管理', 'bl_dashboard_widget', null, null, 'normal', 'high');
    $boxes = &$GLOBALS['wp_meta_boxes']['dashboard'];
    if (isset($boxes['normal']['core']['dashboard_site_health'])) {
        $boxes['side']['core']['dashboard_site_health'] = $boxes['normal']['core']['dashboard_site_health'];
        unset($boxes['normal']['core']['dashboard_site_health']);
    }
});

function bl_dashboard_widget(): void
{
    $count = static fn(string $type): int => (int) (wp_count_posts($type)->publish ?? 0);
    $groups = [
        '页面内容' => [
            ['首页内容', 'admin.php?page=bl-content-home', 'edit_pages', '首屏、产品、应用和联系区的文字与图片'],
            ['采购与售后（Support）', 'admin.php?page=bl-content-support', 'edit_pages', '采购流程、质保、配件和常见问题'],
            ['应用场景（Applications）', 'admin.php?page=bl-content-applications', 'edit_pages', '应用列表页和景观、场地、运输三个应用页'],
            ['换产品画册', 'admin.php?page=bl-downloads', 'edit_pages', 'Support 页可下载的 PDF'],
        ],
        '产品与新闻' => [
            ['管理设备产品', 'edit.php?post_type=bl_product', 'edit_posts', '已发布 ' . $count('bl_product') . ' 款'],
            ['写一篇新闻', 'post-new.php?post_type=bl_news', 'edit_posts', ''],
            ['全部企业新闻', 'edit.php?post_type=bl_news', 'edit_posts', '已发布 ' . $count('bl_news') . ' 篇'],
        ],
        '图片与网站' => [
            ['媒体库', 'upload.php', 'upload_files', '上传图片、视频和 PDF'],
            ['其他页面', 'edit.php?post_type=page', 'edit_pages', '关于、联系、隐私等页面的正文'],
        ],
    ];
    echo '<div class="bl-dash">';
    foreach ($groups as $title => $links) {
        $items = '';
        foreach ($links as [$label, $path, $cap, $note]) {
            if (!current_user_can($cap)) continue;
            $items .= '<li><a href="' . esc_url(admin_url($path)) . '">' . esc_html($label) . '</a>' . ($note ? '<span>' . esc_html($note) . '</span>' : '') . '</li>';
        }
        if ($items) echo '<div class="bl-dash-group"><h3 class="bl-subgroup-title">' . esc_html($title) . '</h3><ul>' . $items . '</ul></div>';
    }
    echo '</div><p class="bl-dash-foot"><a class="button" href="' . esc_url(home_url('/')) . '" target="_blank" rel="noopener">查看网站</a>';
    if (!get_option('blog_public')) echo ' <span class="bl-chip bl-chip-warn">搜索引擎不可见</span>' . (current_user_can('manage_options') ? ' <a href="' . esc_url(admin_url('options-reading.php')) . '">阅读设置</a>' : '');
    echo '</p>';
}
