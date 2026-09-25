<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/wordpress.php';
require_once __DIR__ . '/inc/wp-admin.php';
require_once __DIR__ . '/inc/wp-import.php';

add_action('after_setup_theme', static function (): void {
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
});
// Our shared document renders one canonical, title and robots block for every route.
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_robots');
add_action('init', 'bl_register_content_types');
add_action('after_switch_theme', static function (): void {
    bl_register_content_types();
    flush_rewrite_rules();
});
