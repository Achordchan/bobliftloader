<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/content.php';
require_once __DIR__ . '/inc/editable.php';
require_once __DIR__ . '/inc/support.php';
require_once __DIR__ . '/inc/product-page.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/wordpress.php';
require_once __DIR__ . '/inc/wp-admin.php';
require_once __DIR__ . '/inc/wp-import.php';
require_once __DIR__ . '/inc/wp-support.php';
require_once __DIR__ . '/inc/wp-product-page.php';
require_once __DIR__ . '/inc/wp-content-editor.php';
require_once __DIR__ . '/inc/wp-admin-ui.php';
require_once __DIR__ . '/inc/wp-inquiry.php';
require_once __DIR__ . '/inc/wp-rest.php';

add_action('after_setup_theme', static function (): void {
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
});
// Our shared document renders one canonical, title and robots block for every route.
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_robots');
// The icons are the theme's own (index.php): drop the platform's default site icon so only ours is advertised, and point /favicon.ico at ours.
remove_action('wp_head', 'wp_site_icon', 99);
add_filter('get_site_icon_url', static fn($url, $size = 512) => bl_asset((int) $size >= 180 ? 'images/icons/icon-192.png' : 'images/icons/icon-32.png'), 99, 2);
// Search and AI crawlers are welcome: drop the per-bot blocks the platform adds to robots.txt.
add_filter('robots_txt', 'bl_allow_ai_crawlers', 99999);
add_action('init', 'bl_register_content_types');
// New URL rules (application cases) need one flush on sites that already run the theme; bump the number when the rules change.
add_action('init', static function (): void {
    if (get_option('bl_rewrite_version') === '2') return;
    flush_rewrite_rules(false);
    update_option('bl_rewrite_version', '2');
}, 99);
add_action('after_switch_theme', static function (): void {
    bl_register_content_types();
    flush_rewrite_rules();
});
