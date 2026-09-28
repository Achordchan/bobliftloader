<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

function bl_register_content_types(): void
{
    register_post_type('bl_product', [
        'labels' => ['name' => '设备产品', 'singular_name' => '设备产品', 'add_new_item' => '新增设备产品', 'edit_item' => '编辑设备产品', 'view_item' => '查看产品页', 'featured_image' => '产品主图', 'set_featured_image' => '设置产品主图', 'remove_featured_image' => '移除产品主图', 'use_featured_image' => '用作产品主图'],
        'public' => true, 'show_in_rest' => true, 'has_archive' => 'products', 'rewrite' => ['slug' => 'product', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'], 'menu_icon' => 'dashicons-products',
    ]);
    register_post_type('bl_news', [
        'labels' => ['name' => '企业新闻', 'singular_name' => '企业新闻', 'add_new_item' => '新增企业新闻', 'edit_item' => '编辑企业新闻', 'view_item' => '查看文章', 'featured_image' => '封面图片', 'set_featured_image' => '设置封面图片', 'remove_featured_image' => '移除封面图片', 'use_featured_image' => '用作封面图片'],
        'public' => true, 'show_in_rest' => true, 'has_archive' => 'news', 'rewrite' => ['slug' => 'news', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail'], 'menu_icon' => 'dashicons-media-document',
    ]);
    add_rewrite_tag('%bl_category%', '(skid-steer-loaders|mini-dumpers)');
    add_rewrite_rule('^products/(skid-steer-loaders|mini-dumpers)/?$', 'index.php?post_type=bl_product&bl_category=$matches[1]', 'top');
}

function bl_wp_image(string $path): string
{
    return str_starts_with($path, 'images/') ? bl_asset($path) : esc_url_raw($path);
}

function bl_wp_products(): array
{
    $result = [];
    foreach (get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']) as $post) {
        $data = get_post_meta($post->ID, '_bl_record', true);
        if (!is_array($data)) $data = [];
        $record = array_replace(['model' => '', 'category' => 'skid-steer-loaders', 'load' => 0, 'capacity' => '', 'power' => '', 'image' => '', 'gallery' => [], 'source' => '', 'specs' => []], $data);
        $record['slug'] = $post->post_name;
        $record['name'] = get_the_title($post);
        $record['url'] = get_permalink($post);
        $record['excerpt'] = $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 35);
        $record['content_html'] = $post->post_content ? apply_filters('the_content', $post->post_content) : '';
        $record['image'] = get_the_post_thumbnail_url($post, 'large') ?: bl_wp_image($record['image']);
        $record['gallery'] = array_values(array_filter(array_map('bl_wp_image', (array) $record['gallery'])));
        $result[] = $record;
    }
    return $result;
}

function bl_wp_articles(): array
{
    $result = [];
    foreach (get_posts(['post_type' => 'bl_news', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']) as $post) {
        $meta = get_post_meta($post->ID, '_bl_record', true);
        if (!is_array($meta)) $meta = [];
        $meta['line'] = bl_news_line($meta, $post->post_name);
        $record = array_replace(['category' => '', 'line' => 'compact', 'source' => '', 'image' => '', 'caption' => '', 'sections' => [], 'event' => [], 'poster' => [], 'products' => []], $meta);
        if (!empty($record['poster']['image'])) $record['poster']['image'] = bl_wp_image($record['poster']['image']);
        $record['slug'] = $post->post_name;
        $record['title'] = get_the_title($post);
        $record['url'] = get_permalink($post);
        $record['date'] = get_the_date('Y-m-d', $post);
        $record['excerpt'] = $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags($post->post_content), 35);
        $record['content_html'] = apply_filters('the_content', $post->post_content);
        // The person who wrote it: name, job title from their profile, and avatar. No contact details are shown.
        $author = (int) $post->post_author;
        if ($author) $record['author'] = ['name' => get_the_author_meta('display_name', $author), 'title' => (string) get_user_meta($author, 'bl_title', true), 'avatar' => (string) get_avatar_url($author, ['size' => 128])];
        if (get_the_modified_date('Y-m-d', $post) > $record['date']) $record['updated'] = get_the_modified_date('Y-m-d', $post);
        $record['image'] = get_the_post_thumbnail_url($post, 'large') ?: bl_wp_image($record['image']);
        if (!$record['image']) $record['image'] = bl_asset('images/company/boblift-workshop-aerial-mobile.webp');
        $result[] = $record;
    }
    return $result;
}

function bl_page_editor_content(): string
{
    if (!is_page()) return '';
    $post = get_queried_object();
    return $post instanceof WP_Post && $post->post_content ? apply_filters('the_content', $post->post_content) : '';
}
