<?php
declare(strict_types=1);
defined('ABSPATH') || exit;

function bl_register_content_types(): void
{
    register_post_type('bl_product', [
        'labels' => ['name' => '设备产品', 'singular_name' => '设备产品', 'add_new_item' => '新增设备产品', 'edit_item' => '编辑设备产品', 'view_item' => '查看产品页', 'featured_image' => '产品主图', 'set_featured_image' => '设置产品主图', 'remove_featured_image' => '移除产品主图', 'use_featured_image' => '用作产品主图'],
        'public' => true, 'show_in_rest' => true, 'has_archive' => 'products', 'rewrite' => ['slug' => 'product', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields'], 'menu_icon' => 'dashicons-products',
    ]);
    register_post_type('bl_news', [
        'labels' => ['name' => '企业新闻', 'singular_name' => '企业新闻', 'add_new_item' => '新增企业新闻', 'edit_item' => '编辑企业新闻', 'view_item' => '查看文章', 'featured_image' => '封面图片', 'set_featured_image' => '设置封面图片', 'remove_featured_image' => '移除封面图片', 'use_featured_image' => '用作封面图片'],
        'public' => true, 'show_in_rest' => true, 'has_archive' => 'news', 'rewrite' => ['slug' => 'news', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'], 'menu_icon' => 'dashicons-media-document',
    ]);
    // A specific application (one job, one site, the machines used), written in the block editor. Its hub is a saved choice.
    register_post_type('bl_application', [
        'labels' => ['name' => '应用案例', 'singular_name' => '应用案例', 'add_new' => '新增案例', 'add_new_item' => '新增应用案例', 'edit_item' => '编辑应用案例', 'new_item' => '新应用案例', 'view_item' => '查看案例页', 'featured_image' => '封面图片', 'set_featured_image' => '设置封面图片', 'remove_featured_image' => '移除封面图片', 'use_featured_image' => '用作封面图片'],
        'public' => true, 'show_in_rest' => true, 'has_archive' => false, 'rewrite' => ['slug' => 'application', 'with_front' => false],
        'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'], 'menu_icon' => 'dashicons-location-alt',
    ]);
    add_rewrite_tag('%bl_category%', '(skid-steer-loaders|mini-dumpers)');
    add_rewrite_rule('^products/(skid-steer-loaders|mini-dumpers)/?$', 'index.php?post_type=bl_product&bl_category=$matches[1]', 'top');
}

function bl_wp_image(string $path): string
{
    return str_starts_with($path, 'images/') || str_starts_with($path, 'videos/') ? bl_asset($path) : esc_url_raw($path);
}

function bl_wp_products(): array
{
    return array_map('bl_wp_product_record', get_posts(['post_type' => 'bl_product', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC']));
}

/** One product as the front end reads it, from a post (the saved one, or WordPress's preview copy of it). */
function bl_wp_product_record(WP_Post $post): array
{
    $data = get_post_meta($post->ID, '_bl_record', true);
    if (!is_array($data)) $data = [];
    $record = array_replace(['model' => '', 'category' => 'skid-steer-loaders', 'load' => 0, 'capacity' => '', 'power' => '', 'image' => '', 'gallery' => [], 'source' => '', 'specs' => []], $data);
    $record['slug'] = $post->post_name;
    $record['name'] = get_the_title($post);
    $record['url'] = get_permalink($post);
    // The import kept the original name in the record, so a summary generated before a rename is still recognised.
    if (bl_is_generated_product_excerpt($post->post_excerpt, [$post->post_title, $record['name'], $data['name'] ?? ''])) $record['excerpt'] = bl_product_excerpt($record);
    else $record['excerpt'] = $post->post_excerpt ?: (wp_trim_words(wp_strip_all_tags($post->post_content), 35) ?: bl_product_excerpt($record));
    $record['content_html'] = $post->post_content ? apply_filters('the_content', $post->post_content) : '';
    $record['image'] = get_the_post_thumbnail_url($post, 'large') ?: bl_wp_image($record['image']);
    $record['gallery'] = array_values(array_filter(array_map('bl_wp_image', (array) $record['gallery'])));
    return $record;
}

function bl_wp_articles(): array
{
    return array_map('bl_wp_article_record', get_posts(['post_type' => 'bl_news', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']));
}

/** One news article as the front end reads it, from a post (the saved one, or WordPress's preview copy of it). */
function bl_wp_article_record(WP_Post $post): array
{
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
    return $record;
}

/** The application cases (each a page of its own under /application/), newest first. */
function bl_wp_application_cases(): array
{
    // Password-protected cases are left out: the site has no password form, so they must not be listed, searched or shown.
    return array_map('bl_wp_application_record', get_posts(['post_type' => 'bl_application', 'post_status' => 'publish', 'has_password' => false, 'numberposts' => -1, 'orderby' => 'date', 'order' => 'DESC']));
}

/** One case as the front end reads it, from a post (the saved one, or WordPress's preview copy of it). */
function bl_wp_application_record(WP_Post $post): array
{
    $meta = get_post_meta($post->ID, '_bl_record', true);
    $record = array_replace(['hub' => '', 'products' => [], 'facts' => []], is_array($meta) ? $meta : []);
    $hub = bl_application_hub((string) $record['hub']);
    $record['hub'] = $hub ? bl_application_slug($hub) : '';
    $record['id'] = $post->ID;
    $record['slug'] = $post->post_name;
    $record['title'] = get_the_title($post);
    $record['url'] = get_permalink($post);
    $record['date'] = get_the_date('Y-m-d', $post);
    // The list carries no rendered body (every page reads it for cards and search); the case page renders its own.
    $record['excerpt'] = $post->post_excerpt ?: wp_trim_words(wp_strip_all_tags(strip_shortcodes($post->post_content)), 35);
    if (get_the_modified_date('Y-m-d', $post) > $record['date']) $record['updated'] = get_the_modified_date('Y-m-d', $post);
    // No cover chosen: the job's own scene picture, so a card is never empty.
    $record['image'] = get_the_post_thumbnail_url($post, 'large') ?: ($hub['image'] ?? bl_asset('images/app-site-prep.webp'));
    return $record;
}

/** One case's body, rendered by WordPress (blocks, embeds, shortcodes). Empty for a protected or missing case. */
function bl_wp_application_case_content(int $id): string
{
    $post = get_post($id);
    return $post && $post->post_type === 'bl_application' && $post->post_status === 'publish' && !post_password_required($post) ? apply_filters('the_content', $post->post_content) : '';
}

/**
 * What an editor is previewing: a product, news article or application case, only for someone allowed to edit it. WordPress
 * has already swapped in the unsaved copy (the draft itself, or the autosave of a published post).
 */
function bl_wp_preview(): ?array
{
    if (!is_preview() || !is_singular(['bl_product', 'bl_news', 'bl_application'])) return null;
    $post = get_queried_object();
    if (!$post instanceof WP_Post || !current_user_can('edit_post', $post->ID)) return null;
    // A draft has no name yet, so the address it will get is not final; the preview is never linked or indexed.
    $slug = $post->post_name ?: (string) $post->ID;
    if ($post->post_type === 'bl_product') {
        $record = bl_wp_product_record($post);
        return ['type' => 'product', 'title' => bl_product_page_title($record), 'record' => $record, 'path' => '/product/' . $slug . '/'];
    }
    if ($post->post_type === 'bl_news') {
        $record = bl_wp_article_record($post);
        return ['type' => 'article', 'title' => $record['title'], 'record' => $record, 'path' => '/news/' . $slug . '/'];
    }
    $record = bl_wp_application_record($post);
    $record['slug'] = $slug;
    $record['content_html'] = apply_filters('the_content', $post->post_content);
    return ['type' => 'application_case', 'title' => $record['title'], 'record' => $record, 'path' => '/application/' . $slug . '/'];
}

function bl_page_editor_content(): string
{
    if (!is_page()) return '';
    $post = get_queried_object();
    return $post instanceof WP_Post && $post->post_content ? apply_filters('the_content', $post->post_content) : '';
}
