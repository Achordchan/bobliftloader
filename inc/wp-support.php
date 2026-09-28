<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Admin for the Support content that changes: product videos (a box on each product) and the catalogue PDF on the
 * Support page's Downloads list. Videos are kept inside the product's _bl_record so the front end reads them with the rest.
 */
// The box sits with the other product boxes under the title (see inc/wp-admin.php), after 图片 and before 来源.
add_action('add_meta_boxes_bl_product', static function (): void {
    add_meta_box('bl-product-video', '视频', 'bl_product_videos_meta_box', 'bl_product', 'bl_main', 'default');
});

function bl_product_video_row(array $video): string
{
    $picker = static fn(string $field, string $type, string $label, string $placeholder): string => '<div class="bl-media-input">' . bl_admin_input('', 'bl_videos[' . $field . '][]', (string) ($video[$field] ?? ''), 'text', ' class="regular-text code" aria-label="' . esc_attr($label) . '" placeholder="' . esc_attr($placeholder) . '"') . '<button type="button" class="button" data-bl="media-' . $type . '">' . esc_html('选择' . ($type === 'video' ? '视频' : '图片')) . '</button></div>';
    $poster = (string) ($video['poster'] ?? '');
    $title = (string) ($video['caption'] ?? '') ?: '新视频';
    return '<li class="bl-row"><div class="bl-row-head"><span class="bl-row-title">' . esc_html($title) . '</span>' . bl_admin_row_tools() . '</div><div class="bl-row-body bl-video-body">'
        . '<div class="bl-video-poster">' . ($poster ? '<img src="' . esc_url(bl_wp_image($poster)) . '" alt="">' : '<span class="dashicons dashicons-format-video" aria-hidden="true"></span>') . '</div><div class="bl-video-fields">'
        . '<div class="bl-mini-field"><span aria-hidden="true">视频</span>' . $picker('src', 'video', '视频网址', 'MP4 网址或 videos/… 路径') . '</div>'
        . '<div class="bl-mini-field"><span aria-hidden="true">封面</span>' . $picker('poster', 'image', '封面图网址', '图片网址或 images/… 路径') . '</div>'
        . '<label class="bl-mini-field"><span>说明</span>' . bl_admin_input('', 'bl_videos[caption][]', (string) ($video['caption'] ?? ''), 'text', ' class="regular-text bl-video-caption" placeholder="英文，如 Loading with the bucket"') . '</label></div></div></li>';
}

function bl_product_videos_meta_box(WP_Post $post): void
{
    $record = get_post_meta($post->ID, '_bl_record', true);
    wp_nonce_field('bl_save_videos', 'bl_videos_nonce');
    echo '<p class="bl-box-intro">显示在产品页大图下方，访客点缩略图后在大图位置播放。MP4 建议 960px 宽、3 MB 以内；封面图用作缩略图和播放前的画面。最多 8 个。</p>';
    echo '<ol class="bl-rows" data-bl-rows="videos" data-bl-max="8">';
    foreach ((array) (is_array($record) ? ($record['videos'] ?? []) : []) as $video) if (is_array($video)) echo bl_product_video_row($video);
    echo '</ol><template data-bl-template="videos">' . bl_product_video_row([]) . '</template>';
    echo '<p class="bl-rows-add"><button type="button" class="button" data-bl="add" data-bl-target="videos"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> 添加视频</button></p>';
}

add_action('save_post_bl_product', static function (int $postId): void {
    if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || !current_user_can('edit_post', $postId)) return;
    if (!isset($_POST['bl_videos_nonce']) || !is_string($_POST['bl_videos_nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bl_videos_nonce'])), 'bl_save_videos')) return;
    $url = static function (string $value): string {
        $value = trim(sanitize_text_field($value));
        return preg_match('~^(videos|images)/[\w./-]+$~', $value) && !str_contains($value, '..') ? $value : esc_url_raw($value, ['http', 'https']);
    };
    // Rows arrive as bl_videos[src][], [poster][], [caption][]; an older form sent one "src | poster | caption" line each.
    // With the box submitted but no rows left, the list is emptied.
    $videos = [];
    foreach (array_slice(bl_admin_posted_rows(wp_unslash($_POST['bl_videos'] ?? []), ['src', 'poster', 'caption']), 0, 8) as $row) {
        $src = $url($row['src']);
        if ($src) $videos[] = ['src' => $src, 'poster' => $url($row['poster']), 'caption' => sanitize_text_field($row['caption'])];
    }
    $record = get_post_meta($postId, '_bl_record', true);
    if (!is_array($record)) $record = [];
    $record['videos'] = $videos;
    update_post_meta($postId, '_bl_record', $record);
}, 20);

// 资料下载 sits under 网站内容 (registered after that menu, priority 20). The old Settings address forwards here.
add_action('admin_menu', static function (): void {
    if ($GLOBALS['pagenow'] === 'options-general.php' && ($_GET['page'] ?? '') === 'bl-downloads') {
        wp_safe_redirect(admin_url('admin.php?page=bl-downloads'));
        exit;
    }
    add_submenu_page('bl-content-home', '资料下载', '资料下载', 'edit_pages', 'bl-downloads', 'bl_downloads_screen');
}, 20);

function bl_downloads_screen(): void
{
    $url = (string) get_option('bl_catalogue_url');
    $size = (int) get_option('bl_catalogue_size');
    $name = $url ? wp_basename((string) parse_url($url, PHP_URL_PATH)) : '';
    echo '<div class="wrap bl-downloads"><div class="bl-page-head"><div><h1>资料下载</h1><p>Support 页“资料下载”一栏里供访客下载的产品画册。栏目标题和说明文字在<a href="' . esc_url(admin_url('admin.php?page=bl-content-support')) . '">采购与售后</a>里改。</p></div>';
    echo '<div class="bl-page-head-actions"><a class="button" href="' . esc_url(home_url('/support/#downloads')) . '" target="_blank" rel="noopener">查看 Support 页</a></div></div><hr class="wp-header-end">';
    settings_errors();
    echo '<form method="post" action="options.php">';
    settings_fields('bl_downloads');
    echo '<section class="bl-panel"><div class="bl-panel-head"><div><h2>产品画册 PDF</h2><p>换画册时先上传新 PDF，再选中它保存。</p></div></div><div class="bl-panel-body">';
    echo '<div class="bl-field"><div class="bl-field-label">当前画册</div><div class="bl-field-control bl-dl-current" data-default="使用主题自带占位画册">';
    if ($url) echo '<strong class="bl-dl-name">' . esc_html($name) . '</strong> <span class="bl-dl-size">' . esc_html($size ? size_format($size, 1) : '大小未知') . '</span>';
    else echo '<span class="bl-chip bl-chip-warn">使用主题自带占位画册</span>';
    echo '</div></div>';
    echo '<div class="bl-field"><label class="bl-field-label" for="bl-catalogue-url">PDF 网址</label><div class="bl-field-control"><input class="code" type="url" id="bl-catalogue-url" name="bl_catalogue_url" value="' . esc_attr($url) . '" placeholder="https://…/catalogue.pdf">';
    echo '<p class="bl-dl-tools"><button type="button" class="button" id="bl-catalogue-pick">从媒体库选择 PDF</button> <button type="button" class="button-link" id="bl-catalogue-clear"' . ($url ? '' : ' hidden') . '>清空，改用占位画册</button></p>';
    echo '<p class="bl-field-help">用上面的按钮选择或上传 PDF，网址会自动填好；也可以直接粘贴网址。文件大小只对媒体库里的文件自动读取。留空保存即恢复主题自带的占位画册。</p></div></div>';
    echo '</div></section>';
    submit_button('保存画册设置');
    echo '</form></div>';
}

// Editors who can change the other 网站内容 screens can change the catalogue too (options.php asks manage_options otherwise).
add_filter('option_page_capability_bl_downloads', static fn(): string => 'edit_pages');
add_action('admin_init', static function (): void {
    register_setting('bl_downloads', 'bl_catalogue_url', ['type' => 'string', 'sanitize_callback' => static fn($value): string => esc_url_raw((string) $value, ['http', 'https'])]);
});

// Keep the file size beside the address, so the Downloads list can show it without fetching the file on every visit.
$bl_catalogue_size = static function ($old, $value): void {
    $id = $value ? attachment_url_to_postid((string) $value) : 0;
    $file = $id ? get_attached_file($id) : '';
    update_option('bl_catalogue_size', $file && is_file($file) ? (int) filesize($file) : 0);
};
add_action('update_option_bl_catalogue_url', $bl_catalogue_size, 10, 2);
add_action('add_option_bl_catalogue_url', static fn($option, $value) => $bl_catalogue_size(null, $value), 10, 2);
