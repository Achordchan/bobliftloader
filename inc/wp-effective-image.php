<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

/** One effective-image control replaces the native duplicate; fallback fields retain their historical keys. */
foreach (['bl_product', 'bl_news'] as $bl_image_type) add_action('add_meta_boxes_' . $bl_image_type, static function () use ($bl_image_type): void {
    remove_meta_box('postimagediv', $bl_image_type, 'side');
    add_meta_box('bl-effective-image', '当前生效图片', 'bl_effective_image_box', $bl_image_type, 'side', 'default');
}, 30);
unset($bl_image_type);

function bl_effective_image_box(WP_Post $post): void
{
    $record = bl_admin_record($post);
    $featured = get_the_post_thumbnail_url($post, 'large') ?: '';
    $fallback = bl_wp_image((string) ($record['image'] ?? ''));
    $default = $post->post_type === 'bl_news' ? bl_asset('images/company/boblift-workshop-aerial-mobile.webp') : '';
    $src = $featured ?: ($fallback ?: $default);
    echo '<div data-bl-effective-image data-bl-base="' . esc_url(get_template_directory_uri() . '/assets/') . '" data-bl-default="' . esc_url($default) . '" data-bl-featured="' . esc_url($featured) . '"><img width="240" src="' . esc_url($src) . '" alt="当前生效图片"' . ($src === '' ? ' hidden' : '') . '><p data-bl-image-source>来源：' . ($featured ? '特色图片' : ($fallback ? '备用图片' : ($default ? '主题默认图片' : '未设置图片'))) . '</p>';
    echo '<input type="hidden" name="_thumbnail_id" value="' . (int) get_post_thumbnail_id($post) . '"><button type="button" class="button" data-bl-featured-pick>选择特色图片</button> <button type="button" class="button-link" data-bl-featured-clear' . ($featured === '' ? ' hidden' : '') . '>移除图片</button></div>';
}
