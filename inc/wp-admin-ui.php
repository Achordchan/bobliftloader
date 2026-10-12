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
    wp_enqueue_script('bl-admin-interactions', $uri . '/assets/admin/interactions.js', [], (string) filemtime($dir . '/assets/admin/interactions.js'), true);
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
add_filter('menu_order', static fn(): array => ['index.php', 'separator1', 'bl-content-home', 'edit.php?post_type=bl_product', 'edit.php?post_type=bl_news', 'edit.php?post_type=bl_application', 'edit.php?post_type=page', 'upload.php', 'separator2', 'themes.php', 'plugins.php', 'users.php', 'tools.php', 'options-general.php', 'separator-last']);

/** A post's saved record, or [] — the list columns read it and never write. */
function bl_list_record(int $postId): array
{
    $record = get_post_meta($postId, '_bl_record', true);
    return is_array($record) ? $record : [];
}

// WordPress fills unset labels with "文章" wording ("搜索文章", "查看文章"); name the product and news lists properly.
foreach (['bl_product' => '产品', 'bl_news' => '新闻', 'bl_application' => '应用案例'] as $bl_type => $bl_noun) {
    add_filter('post_type_labels_' . $bl_type, static function (object $labels) use ($bl_noun): object {
        foreach (['search_items' => '搜索' . $bl_noun, 'not_found' => '没有找到' . $bl_noun, 'not_found_in_trash' => '回收站里没有' . $bl_noun, 'view_items' => '查看全部' . $bl_noun, 'all_items' => '全部' . $bl_noun] as $key => $value) $labels->$key = $value;
        return $labels;
    });
}
unset($bl_type, $bl_noun);
add_filter('disable_months_dropdown', static fn(bool $disable, string $postType): bool => $disable || in_array($postType, ['bl_product', 'bl_application'], true), 10, 2);

const BL_LIST_PRODUCT_CATEGORIES = ['skid-steer-loaders' => '滑移装载机', 'mini-dumpers' => '迷你翻斗车'];
const BL_LIST_NEWS_CATEGORIES = ['product-news' => '产品新闻', 'exhibitions' => '展会新闻', 'customer-stories' => '客户故事', 'industry-news' => '行业新闻'];

add_filter('manage_bl_product_posts_columns', static fn(array $columns): array => ['cb' => $columns['cb'], 'bl_thumb' => '<span class="screen-reader-text">图片</span>', 'title' => '产品名称', 'bl_model' => '型号', 'bl_category' => '栏目', 'bl_load' => '额定载荷', 'menu_order' => '排序']);
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
    echo '<label class="screen-reader-text" for="bl-product-category">按栏目筛选</label><select name="bl_product_category" id="bl-product-category"><option value="">全部栏目</option>';
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

add_filter('manage_bl_application_posts_columns', static function (array $columns): array {
    $date = $columns['date'];
    unset($columns['date']);
    return $columns + ['bl_hub' => '所属应用', 'bl_place' => '地点', 'bl_machines' => '使用机型', 'date' => $date];
});

add_action('manage_bl_application_posts_custom_column', static function (string $column, int $postId): void {
    $record = bl_list_record($postId);
    if ($column === 'bl_hub') {
        $hubs = bl_case_hubs();
        echo isset($hubs[$record['hub'] ?? '']) ? esc_html($hubs[$record['hub']]) : '<span class="bl-muted">未归类</span>';
    } elseif ($column === 'bl_place') {
        echo ($record['facts']['location'] ?? '') !== '' ? esc_html((string) $record['facts']['location']) : '—';
    } elseif ($column === 'bl_machines') {
        $names = [];
        foreach ((array) ($record['products'] ?? []) as $slug) if ($product = get_page_by_path((string) $slug, OBJECT, 'bl_product')) $names[] = (string) (bl_list_record($product->ID)['model'] ?? '') ?: get_the_title($product);
        echo $names ? esc_html(implode('、', $names)) : '—';
    }
}, 10, 2);

add_action('restrict_manage_posts', static function (string $postType): void {
    if ($postType !== 'bl_application') return;
    $current = sanitize_key(wp_unslash($_GET['bl_hub'] ?? ''));
    echo '<label class="screen-reader-text" for="bl-hub-filter">按应用筛选</label><select name="bl_hub" id="bl-hub-filter"><option value="">全部应用</option>';
    foreach (bl_case_hubs() as $value => $label) echo '<option value="' . esc_attr($value) . '"' . selected($current, $value, false) . '>' . esc_html($label) . '</option>';
    echo '</select>';
});

// The hub is kept in the saved record, so the filter picks matching cases by id.
add_action('pre_get_posts', static function (WP_Query $query): void {
    if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'bl_application' || ($GLOBALS['pagenow'] ?? '') !== 'edit.php') return;
    $hub = sanitize_key(wp_unslash($_GET['bl_hub'] ?? ''));
    if (!isset(bl_case_hubs()[$hub])) return;
    $ids = [];
    foreach (get_posts(['post_type' => 'bl_application', 'post_status' => array_keys(get_post_stati()), 'numberposts' => -1, 'fields' => 'ids', 'suppress_filters' => true]) as $id) {
        if ((bl_list_record((int) $id)['hub'] ?? '') === $hub) $ids[] = (int) $id;
    }
    $query->set('post__in', $ids ?: [0]);
});

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
            ['采购与售后', 'admin.php?page=bl-content-support', 'edit_pages', '采购流程、质保、配件和常见问题'],
            ['应用场景', 'admin.php?page=bl-content-applications', 'edit_pages', '应用列表页和 ' . count(bl_editable('applications')['jobs']) . ' 个应用详情页'],
            ['全站设置', 'admin.php?page=bl-content-global', 'edit_pages', '公司与联系方式、页眉页脚、品牌图片'],
            ['滑移装载机', 'admin.php?page=bl-content-loaders', 'edit_pages', '栏目与产品公共模块、搜索信息'],
            ['迷你翻斗车', 'admin.php?page=bl-content-dumpers', 'edit_pages', '栏目与产品公共模块、搜索信息'],
            ['关于我们', 'admin.php?page=bl-content-about', 'edit_pages', '首屏、公司概况和工厂；公司介绍仍编辑原生页面正文'],
            ['联系我们', 'admin.php?page=bl-content-contact', 'edit_pages', '联系页文字、照片与预约邮件'],
            ['询盘成功页', 'admin.php?page=bl-content-thanks', 'edit_pages', '询盘提交成功后的提示文字与链接'],
            ['隐私说明', 'admin.php?page=bl-content-privacy', 'edit_pages', '询盘模式、首屏与搜索设置；正文在原生页面中编辑'],
            ['新闻列表', 'admin.php?page=bl-content-news', 'edit_pages', '搜索标题、描述与分享图片'],
            ['未找到页面', 'admin.php?page=bl-content-404', 'edit_pages', '404 文案与链接'],
            ['换产品画册', 'admin.php?page=bl-downloads', 'edit_pages', 'Support 页可下载的 PDF'],
            ['询盘邮件样式', 'admin.php?page=bl-inquiry-mail', 'edit_pages', '通知邮件的模板，可随时切换'],
        ],
        '产品与新闻' => [
            ['管理设备产品', 'edit.php?post_type=bl_product', 'edit_posts', '已发布 ' . $count('bl_product') . ' 款'],
            ['写一篇新闻', 'post-new.php?post_type=bl_news', 'edit_posts', ''],
            ['全部企业新闻', 'edit.php?post_type=bl_news', 'edit_posts', '已发布 ' . $count('bl_news') . ' 篇'],
            ['写一个应用案例', 'post-new.php?post_type=bl_application', 'edit_posts', '已发布 ' . $count('bl_application') . ' 个，每个有自己的页面'],
        ],
        '图片与网站' => [
            ['媒体库', 'upload.php', 'upload_files', '上传图片、视频和 PDF'],
            ['其他页面', 'edit.php?post_type=page', 'edit_pages', '原生页面的标题、网址与发布状态；关于我们、隐私说明的正文在页面中编辑'],
        ],
    ];
    usort($groups['页面内容'], static fn($a, $b) => array_search(str_replace('admin.php?page=', '', $a[1]), BL_CONTENT_MENU_ORDER, true) <=> array_search(str_replace('admin.php?page=', '', $b[1]), BL_CONTENT_MENU_ORDER, true));
    if (bl_can_manage_tracking()) $groups['页面内容'][] = ['统计代码', 'admin.php?page=bl-content-tracking', 'manage_options', '全站统计与跟踪代码'];
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
