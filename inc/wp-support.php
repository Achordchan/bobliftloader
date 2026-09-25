<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * Admin for the Support content that changes: product videos (a box on each product) and the catalogue PDF on the
 * Support page's Downloads list. Videos are kept inside the product's _bl_record so the front end reads them with the rest.
 */
add_action('add_meta_boxes', static function (): void {
    add_meta_box('bl-product-videos', '产品视频', 'bl_product_videos_meta_box', 'bl_product', 'normal', 'default');
});

function bl_product_videos_meta_box(WP_Post $post): void
{
    $record = get_post_meta($post->ID, '_bl_record', true);
    $lines = [];
    foreach ((array) (is_array($record) ? ($record['videos'] ?? []) : []) as $video) $lines[] = implode(' | ', array_filter([$video['src'] ?? '', $video['poster'] ?? '', $video['caption'] ?? '']));
    wp_nonce_field('bl_save_videos', 'bl_videos_nonce');
    echo '<p>视频显示在产品图库里，访客点击缩略图后播放。先在“媒体”上传 MP4（建议 960px 宽、3 MB 以内）和封面图，再把网址填在这里。</p>';
    echo '<p><label for="bl-videos"><strong>每行一个视频：视频网址 | 封面图网址 | 说明</strong></label><br><textarea class="widefat" rows="5" id="bl-videos" name="bl_videos" placeholder="https://…/bucket.mp4 | https://…/bucket.jpg | Loading with the bucket">' . esc_textarea(implode("\n", $lines)) . '</textarea></p>';
}

add_action('save_post_bl_product', static function (int $postId): void {
    if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || !current_user_can('edit_post', $postId)) return;
    if (!isset($_POST['bl_videos_nonce'], $_POST['bl_videos']) || !is_string($_POST['bl_videos_nonce']) || !is_string($_POST['bl_videos'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bl_videos_nonce'])), 'bl_save_videos')) return;
    $url = static function (string $value): string {
        $value = trim(sanitize_text_field($value));
        return preg_match('~^(videos|images)/[\w./-]+$~', $value) && !str_contains($value, '..') ? $value : esc_url_raw($value, ['http', 'https']);
    };
    $videos = [];
    foreach (array_slice(preg_split('/\R/', wp_unslash($_POST['bl_videos'])), 0, 8) as $line) {
        $parts = array_map('trim', explode('|', $line));
        $src = $url($parts[0] ?? '');
        if ($src) $videos[] = ['src' => $src, 'poster' => $url($parts[1] ?? ''), 'caption' => sanitize_text_field($parts[2] ?? '')];
    }
    $record = get_post_meta($postId, '_bl_record', true);
    if (!is_array($record)) $record = [];
    $record['videos'] = $videos;
    update_post_meta($postId, '_bl_record', $record);
}, 20);

add_action('admin_menu', static function (): void {
    add_options_page('BOB-LIFT 资料下载', 'BOB-LIFT 资料下载', 'manage_options', 'bl-downloads', static function (): void {
        echo '<div class="wrap"><h1>BOB-LIFT 资料下载</h1><p>Support 页“资料下载”里的产品画册。先在“媒体”上传 PDF，再把文件网址填在这里；留空则使用主题自带的占位画册。</p><form method="post" action="options.php">';
        settings_fields('bl_downloads');
        echo '<table class="form-table"><tr><th><label for="bl-catalogue-url">画册 PDF 网址</label></th><td><input class="regular-text code" type="url" id="bl-catalogue-url" name="bl_catalogue_url" value="' . esc_attr((string) get_option('bl_catalogue_url')) . '"></td></tr></table>';
        submit_button();
        echo '</form></div>';
    });
});

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
