<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/**
 * 产品页模块: what one product page shows beyond its specification (inc/product-page.php reads it). Kept inside the
 * product's _bl_record beside the gallery and videos, so the page and the REST API read it with the rest. Anything left
 * empty falls back to the family's shared block under 网站内容, or the section stays off the page.
 */
add_action('add_meta_boxes_bl_product', static function (): void {
    add_meta_box('bl-product-page', '概述', static fn($post) => bl_product_page_meta_box($post), 'bl_product', 'bl_main', 'default');
    foreach (['photos' => '媒体（照片）', 'attachments' => '属具', 'faq' => '常见问题'] as $kind => $title) {
        add_meta_box('bl-product-' . $kind, $title, static fn($post) => bl_product_page_meta_box($post, $kind), 'bl_product', 'normal', 'default');
    }
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
    return '<li class="bl-row" data-bl-page-kind="' . esc_attr($kind) . '"><div class="bl-row-head"><button type="button" class="button-link" data-bl-fold aria-expanded="false"><span class="bl-row-title">' . esc_html($title) . '</span></button>' . bl_admin_row_tools() . '</div><div data-bl-fold-body hidden><div class="bl-row-body bl-page-fields">' . $body . '</div></div></li>';
}

function bl_product_page_rows(string $kind, array $items, int $max, string $add): string
{
    $html = '<ol class="bl-rows" data-bl-rows="page-' . $kind . '" data-bl-max="' . $max . '" data-bl-drawing-max="' . BL_CONTENT_LIMITS['drawing'] . '">';
    foreach ($items as $item) if (is_array($item)) $html .= bl_product_page_row($kind, $item);
    return $html . '</ol><template data-bl-template="page-' . $kind . '">' . bl_product_page_row($kind, []) . '</template>'
        . '<p class="bl-rows-add"><button type="button" class="button" data-bl="add" data-bl-target="page-' . $kind . '"><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span> ' . esc_html($add) . '</button></p>';
}

function bl_product_page_meta_box(WP_Post $post, string $kind = 'overview'): void
{
    $record = get_post_meta($post->ID, '_bl_record', true);
    if (!is_array($record)) $record = [];
    if ($kind === 'overview') {
        wp_nonce_field('bl_save_product_page', 'bl_page_nonce');
        echo '<p class="bl-box-intro">搜索描述用于搜索结果，亮点显示在概述区。正文在下方“详细介绍”中填写；留空的亮点不显示。前台文案用英文填写。</p>';

        $description = (string) ($record['seo_description'] ?? '');
        bl_admin_field('搜索描述', 'bl-seo-description', '<textarea id="bl-seo-description" name="bl_page[seo_description]" rows="3" class="large-text" maxlength="200">' . esc_textarea($description) . '</textarea>', '谷歌搜索结果里显示的一段话，建议 140–155 个字符，写清机型、关键参数和适用场景。留空时用“卡片摘要”。');

        $lead = is_array($record['lead'] ?? null) ? $record['lead'] : [];
        bl_admin_field('交期', 'bl-lead-value', bl_admin_input('bl-lead-value', 'bl_page[lead][value]', (string) ($lead['value'] ?? ''), 'text', ' maxlength="24" class="small-text"') . ' ' . bl_admin_input('', 'bl_page[lead][unit]', (string) ($lead['unit'] ?? ''), 'text', ' maxlength="24" class="regular-text" placeholder="working days" aria-label="单位"'), '这台机器自己的交期，显示在产品页首屏和“Shipping”里，例如 10 和 working days。数字留空时用“采购与售后 → 采购条款”里的统一交期；单位留空时用统一的单位。');

        bl_admin_field('亮点', '', bl_product_page_rows('highlights', (array) ($record['highlights'] ?? []), BL_CONTENT_LIMITS['highlights'], '添加亮点'), '显示在概述旁，最多 3 条，写这台机器和同系列其他型号不同的地方。');

    }
    if ($kind === 'attachments') {
        echo bl_content_selector('attachments', 'bl_page[attachments]', (array) ($record['attachments'] ?? []));
        echo '<p class="bl-field-help">只在滑移装载机的产品页显示；一个都不勾时，使用“网站内容 → 滑移装载机”里的默认属具。</p>';
    }
    if ($kind === 'faq') {
        bl_admin_field('常见问题', '', bl_product_page_rows('faq', (array) ($record['faq'] ?? []), BL_CONTENT_LIMITS['faq'], '添加问题'), '这台机器专有的问题排在前面，再接系列通用问题，页面最多显示 5 条。链接地址可写站内路径，如 /support/#containers。');

    }
    if ($kind === 'photos') {
        bl_admin_field('实拍照片', '', bl_product_page_rows('photos', (array) ($record['photos'] ?? []), BL_CONTENT_LIMITS['photos'], '添加照片'), '作业现场、细节、生产车间、包装发货的真实照片显示在“Photos”板块；尺寸图显示在规格表旁。只放这台机器或同系列的真实照片，最多 24 张；尺寸图前台最多显示 1 条，超出保留但不显示。');
    }
}

add_action('save_post_bl_product', static function (int $postId): void {
    if (!bl_product_submission_complete()) return;
    if (wp_is_post_revision($postId) || wp_is_post_autosave($postId) || !current_user_can('edit_post', $postId)) return;
    if (!isset($_POST['bl_page_nonce']) || !is_string($_POST['bl_page_nonce'])) return;
    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['bl_page_nonce'])), 'bl_save_product_page')) return;
    $input = wp_unslash($_POST['bl_page'] ?? []);
    if (!is_array($input)) $input = [];
    $result = bl_record_locked($postId, static function (array $record) use ($input): array {
        // The REST cleaner (inc/wp-rest.php) holds the rules, so a value saved here and one written through the API match.
        $record['seo_description'] = bl_rest_field('bl_product', 'seo_description', is_scalar($input['seo_description'] ?? null) ? (string) $input['seo_description'] : '');
        $record['lead'] = bl_rest_field('bl_product', 'lead', is_array($input['lead'] ?? null) ? $input['lead'] : []);
        $record['highlights'] = bl_rest_field('bl_product', 'highlights', bl_admin_posted_rows($input['highlights'] ?? [], ['title', 'text']));
        $record['faq'] = bl_rest_field('bl_product', 'faq', bl_admin_posted_rows($input['faq'] ?? [], ['q', 'a', 'link_label', 'link_url']));
        $record['attachments'] = bl_rest_field('bl_product', 'attachments', (array) ($input['attachments'] ?? []));
        $record['photos'] = bl_rest_field('bl_product', 'photos', bl_admin_posted_rows($input['photos'] ?? [], ['src', 'group', 'alt', 'caption']));
        return $record;
    });
    if (is_wp_error($result)) wp_die(esc_html($result->get_error_message()), '', ['response' => 500, 'back_link' => true]);
}, 20);
