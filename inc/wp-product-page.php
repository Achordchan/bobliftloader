<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * 产品页模块: what one product page shows beyond its specification (inc/product-page.php reads it). Kept inside the
 * product's _bl_record beside the gallery and videos, so the page and the REST API read it with the rest. Anything left
 * empty falls back to the family's shared block under 网站内容, or the section stays off the page.
 */
add_action('add_meta_boxes_bl_product', static function (): void {
    add_meta_box('bl-product-page', '产品页模块', 'bl_product_page_meta_box', 'bl_product', 'bl_main', 'default');
});

function bl_product_page_row(string $kind, array $item): string
{
    $field = static fn(string $label, string $name, string $value, string $placeholder, string $extra = ''): string => '<label class="bl-mini-field"><span>' . esc_html($label) . '</span>' . bl_admin_input('', 'bl_page[' . $kind . '][' . $name . '][]', $value, 'text', ' class="regular-text" placeholder="' . esc_attr($placeholder) . '"' . $extra) . '</label>';
    $title = match ($kind) {
        'highlights' => (string) ($item['title'] ?? '') ?: '新亮点',
        'faq' => (string) ($item['q'] ?? '') ?: '新问题',
        default => (string) ($item['caption'] ?? '') ?: (string) ($item['alt'] ?? '') ?: '新照片',
    };
    $body = match ($kind) {
        'highlights' => $field('标题', 'title', (string) ($item['title'] ?? ''), '英文，如 Fits through a garden gate', ' data-bl-title') . $field('说明', 'text', (string) ($item['text'] ?? ''), '一句话，写实际参数或用途'),
        'faq' => $field('问题', 'q', (string) ($item['q'] ?? ''), '英文问题', ' data-bl-title') . '<label class="bl-mini-field"><span>回答</span><textarea name="bl_page[faq][a][]" rows="3" class="large-text">' . esc_textarea((string) ($item['a'] ?? '')) . '</textarea></label>'
            . $field('链接文字', 'link_label', (string) ($item['link_label'] ?? ''), '选填，如 Warranty and parts') . $field('链接地址', 'link_url', (string) ($item['link_url'] ?? ''), '选填，如 /support/#after-delivery'),
        default => '<div class="bl-mini-field"><span aria-hidden="true">照片</span>' . bl_admin_media('bl_page[photos][src][]', (string) ($item['src'] ?? ''), 'image', '未选择照片') . '</div>'
            . '<label class="bl-mini-field"><span>类别</span>' . bl_admin_select('', 'bl_page[photos][group][]', array_combine(array_keys(BL_PHOTO_GROUPS), ['作业现场 At work', '细节 Details', '生产车间 In the factory', '包装发货 Packing & delivery', '尺寸图 Dimension drawing']), (string) ($item['group'] ?? 'at-work')) . '</label>'
            . $field('替代文字', 'alt', (string) ($item['alt'] ?? ''), '英文，描述画面，如 FY-30 tipping gravel on a farm track') . $field('图注', 'caption', (string) ($item['caption'] ?? ''), '选填，显示在照片下方', ' data-bl-title'),
    };
    return '<li class="bl-row"><div class="bl-row-head"><span class="bl-row-title">' . esc_html($title) . '</span>' . bl_admin_row_tools() . '</div><div class="bl-row-body bl-page-fields">' . $body . '</div></li>';
}

function bl_product_page_rows(string $kind, array $items, int $max, string $add): string
{
    $html = '<ol class="bl-rows" data-bl-rows="page-' . $kind . '" data-bl-max="' . $max . '">';
    foreach ($items as $item) if (is_array($item)) $html .= bl_product_page_row($kind, $item);
    return $html . '</ol><template data-bl-template="page-' . $kind . '">' . bl_product_page_row($kind, []) . '</template>'
        . '<p class="bl-rows-add"><button type="button" class="button" data-bl="add" data-bl-target="page-' . $kind . '"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> ' . esc_html($add) . '</button></p>';
}

function bl_product_page_meta_box(WP_Post $post): void
{
    $record = get_post_meta($post->ID, '_bl_record', true);
    if (!is_array($record)) $record = [];
    wp_nonce_field('bl_save_product_page', 'bl_page_nonce');
    echo '<p class="bl-box-intro">产品页规格表下方的内容。留空的部分使用“网站内容 → 滑移装载机 / 迷你翻斗车 → 产品页通用模块”里的系列默认值；没有内容的板块不显示。全部用英文填写。</p>';

    $description = (string) ($record['seo_description'] ?? '');
    bl_admin_field('搜索描述', 'bl-seo-description', '<textarea id="bl-seo-description" name="bl_page[seo_description]" rows="3" class="large-text" maxlength="200">' . esc_textarea($description) . '</textarea>', '谷歌搜索结果里显示的一段话，建议 140–155 个字符，写清机型、关键参数和适用场景。留空时用“卡片摘要”。');

    bl_admin_field('亮点', '', bl_product_page_rows('highlights', (array) ($record['highlights'] ?? []), 3, '添加亮点'), '显示在概述旁，最多 3 条，写这台机器和同系列其他型号不同的地方。');

    $chosen = array_map('strval', (array) ($record['attachments'] ?? []));
    $boxes = '';
    foreach (bl_attachments() as $items) {
        foreach ($items as $item) {
            $name = (string) $item['name'];
            $boxes .= '<label class="bl-check"><input type="checkbox" name="bl_page[attachments][]" value="' . esc_attr($name) . '"' . checked(in_array($name, $chosen, true), true, false) . '> ' . esc_html($name) . '</label>';
        }
    }
    bl_admin_field('配套属具', '', '<div class="bl-checks">' . $boxes . '</div>', '只用于滑移装载机，最多显示 8 个。一个都不勾时，显示系列默认属具。属具的图片和说明在“网站内容 → 滑移装载机”里改。');

    bl_admin_field('常见问题', '', bl_product_page_rows('faq', (array) ($record['faq'] ?? []), 6, '添加问题'), '这台机器专有的问题排在前面，再接系列通用问题，页面最多显示 5 条。链接地址可写站内路径，如 /support/#containers。');

    bl_admin_field('实拍照片', '', bl_product_page_rows('photos', (array) ($record['photos'] ?? []), 24, '添加照片'), '作业现场、细节、生产车间、包装发货的真实照片显示在“Photos”板块；尺寸图显示在规格表旁。只放这台机器或同系列的真实照片，最多 24 张。');
}

add_action('save_post_bl_product', static function (int $postId): void {
    if (!bl_product_submission_complete()) return;
    if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || !current_user_can('edit_post', $postId)) return;
    if (!isset($_POST['bl_page_nonce']) || !is_string($_POST['bl_page_nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bl_page_nonce'])), 'bl_save_product_page')) return;
    $input = wp_unslash($_POST['bl_page'] ?? []);
    if (!is_array($input)) $input = [];
    $record = get_post_meta($postId, '_bl_record', true);
    if (!is_array($record)) $record = [];
    // The REST cleaner (inc/wp-rest.php) holds the rules, so a value saved here and one written through the API match.
    $record['seo_description'] = bl_rest_field('bl_product', 'seo_description', is_scalar($input['seo_description'] ?? null) ? (string) $input['seo_description'] : '');
    $record['highlights'] = bl_rest_field('bl_product', 'highlights', bl_admin_posted_rows($input['highlights'] ?? [], ['title', 'text']));
    $record['faq'] = bl_rest_field('bl_product', 'faq', bl_admin_posted_rows($input['faq'] ?? [], ['q', 'a', 'link_label', 'link_url']));
    $record['attachments'] = bl_rest_field('bl_product', 'attachments', (array) ($input['attachments'] ?? []));
    $record['photos'] = bl_rest_field('bl_product', 'photos', bl_admin_posted_rows($input['photos'] ?? [], ['src', 'group', 'alt', 'caption']));
    update_post_meta($postId, '_bl_record', $record);
}, 20);
