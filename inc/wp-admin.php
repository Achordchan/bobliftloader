<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

add_action('add_meta_boxes', static function (): void {
    add_meta_box('bl-product-details', '产品资料与来源', 'bl_product_meta_box', 'bl_product', 'normal', 'high');
    add_meta_box('bl-news-details', '新闻分类与来源', 'bl_news_meta_box', 'bl_news', 'normal', 'high');
});

function bl_meta_input(string $label, string $key, string $value, bool $textarea = false): void
{
    echo '<p><label for="bl-' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label><br>';
    if ($textarea) echo '<textarea class="widefat" rows="5" id="bl-' . esc_attr($key) . '" name="bl_record[' . esc_attr($key) . ']">' . esc_textarea($value) . '</textarea>';
    else echo '<input class="widefat" type="text" id="bl-' . esc_attr($key) . '" name="bl_record[' . esc_attr($key) . ']" value="' . esc_attr($value) . '">';
    echo '</p>';
}

function bl_product_meta_box(WP_Post $post): void
{
    $record = get_post_meta($post->ID, '_bl_record', true);
    if (!is_array($record)) $record = [];
    wp_nonce_field('bl_save_record', 'bl_record_nonce');
    echo '<p>名称、正文、摘要、特色图片使用 WordPress 编辑器管理。以下字段用于产品卡片、参数表和来源链接。</p>';
    bl_meta_input('型号', 'model', (string) ($record['model'] ?? ''));
    echo '<p><label for="bl-category"><strong>产品分类</strong></label><br><select id="bl-category" name="bl_record[category]">';
    foreach (['skid-steer-loaders' => '滑移装载机', 'mini-dumpers' => '迷你翻斗车'] as $value => $label) echo '<option value="' . esc_attr($value) . '" ' . selected($record['category'] ?? '', $value, false) . '>' . esc_html($label) . '</option>';
    echo '</select></p>';
    bl_meta_input('原始产品来源网址', 'source', (string) ($record['source'] ?? ''));
    bl_meta_input('备用产品图片（优先使用特色图片）', 'image', (string) ($record['image'] ?? ''));
    bl_meta_input('图库（每行一个图片网址或主题 images/ 路径）', 'gallery', implode("\n", (array) ($record['gallery'] ?? [])), true);
    $rows = [];
    foreach ((array) ($record['specs'] ?? []) as $key => $value) $rows[] = $key . ' | ' . $value;
    bl_meta_input('规格参数（每行：英文字段名 | 参数值；Rated load 请填写 kg，筛选值自动同步）', 'specs', implode("\n", $rows), true);
}

function bl_news_meta_box(WP_Post $post): void
{
    $record = get_post_meta($post->ID, '_bl_record', true);
    if (!is_array($record)) $record = [];
    wp_nonce_field('bl_save_record', 'bl_record_nonce');
    echo '<p>正文、摘要、发布日期和特色图片使用 WordPress 编辑器管理。转载摘要应保留来源。</p><label for="bl-category">分类</label><select id="bl-category" name="bl_record[category]">';
    foreach (['Company news' => '公司新闻', 'Exhibitions' => '展会活动'] as $value => $label) echo '<option value="' . esc_attr($value) . '" ' . selected($record['category'] ?? '', $value, false) . '>' . esc_html($label) . '</option>';
    echo '</select>';
    bl_meta_input('原始文章网址（原创可留空）', 'source', (string) ($record['source'] ?? ''));
    bl_meta_input('备用图片（优先使用特色图片）', 'image', (string) ($record['image'] ?? ''));
    bl_meta_input('图片说明', 'caption', (string) ($record['caption'] ?? ''));
}

add_action('save_post', static function (int $postId): void {
    if (!in_array(get_post_type($postId), ['bl_product', 'bl_news'], true) || wp_is_post_revision($postId) || wp_is_post_autosave($postId)) return;
    if (!current_user_can('edit_post', $postId) || !isset($_POST['bl_record_nonce']) || !is_string($_POST['bl_record_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bl_record_nonce'])), 'bl_save_record')) return;
    if (!isset($_POST['bl_record']) || !is_array($_POST['bl_record'])) return;
    $input = wp_unslash($_POST['bl_record']);
    $text = static fn(string $key): string => isset($input[$key]) && is_scalar($input[$key]) ? sanitize_text_field((string) $input[$key]) : '';
    $record = get_post_meta($postId, '_bl_record', true);
    if (!is_array($record)) $record = [];
    $record['source'] = esc_url_raw($text('source'), ['http', 'https']);
    $image = $text('image');
    $record['image'] = str_starts_with($image, 'images/') && !str_contains($image, '..') ? $image : esc_url_raw($image, ['http', 'https']);
    if (get_post_type($postId) === 'bl_product') {
        $record['model'] = $text('model');
        $record['category'] = in_array($text('category'), ['skid-steer-loaders', 'mini-dumpers'], true) ? $text('category') : 'skid-steer-loaders';
        $record['gallery'] = [];
        if (isset($input['gallery']) && is_string($input['gallery'])) foreach (array_slice(preg_split('/\R/', $input['gallery']), 0, 12) as $line) {
            $line = trim(sanitize_text_field($line));
            $url = str_starts_with($line, 'images/') && !str_contains($line, '..') ? $line : esc_url_raw($line, ['http', 'https']);
            if ($url) $record['gallery'][] = $url;
        }
        $record['specs'] = [];
        if (isset($input['specs']) && is_string($input['specs'])) foreach (array_slice(preg_split('/\R/', $input['specs']), 0, 40) as $line) {
            $parts = explode('|', $line, 2);
            if (count($parts) === 2 && trim($parts[0]) && trim($parts[1])) $record['specs'][sanitize_text_field(trim($parts[0]))] = sanitize_text_field(trim($parts[1]));
        }
        $record['load'] = 0;
        if (preg_match('/^([\d,. ]+)\s*kg$/i', $record['specs']['Rated load'] ?? '', $matches)) {
            $record['load'] = min(1000000, max(0, (int) str_replace([',', ' '], '', $matches[1])));
        }
        // Bucket and power feed the home page ranges and excerpts, so they follow the spec rows too.
        $record['capacity'] = preg_match('/^([\d.]+)\s*m³$/u', $record['specs']['Bucket capacity'] ?? '', $matches) ? $matches[1] : '';
        $record['power'] = preg_match('/^([\d.]+)\s*kW$/i', $record['specs']['Engine power'] ?? '', $matches) ? $matches[1] : '';
    } else {
        $record['category'] = $text('category') === 'Exhibitions' ? 'Exhibitions' : 'Company news';
        $record['caption'] = $text('caption');
    }
    update_post_meta($postId, '_bl_record', $record);
});
