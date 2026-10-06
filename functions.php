<?php
declare(strict_types=1);
defined('ABSPATH') || exit;
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/languages.php';
require_once __DIR__ . '/inc/content.php';
require_once __DIR__ . '/inc/editable.php';
require_once __DIR__ . '/inc/support.php';
require_once __DIR__ . '/inc/product-page.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/routes.php';
require_once __DIR__ . '/inc/llms.php';
require_once __DIR__ . '/inc/wordpress.php';
require_once __DIR__ . '/inc/wp-admin.php';
require_once __DIR__ . '/inc/wp-import.php';
require_once __DIR__ . '/inc/wp-support.php';
require_once __DIR__ . '/inc/wp-product-page.php';
require_once __DIR__ . '/inc/wp-content-editor.php';
require_once __DIR__ . '/inc/wp-admin-ui.php';
require_once __DIR__ . '/inc/wp-inquiry.php';
require_once __DIR__ . '/inc/wp-inquiry-templates.php';
require_once __DIR__ . '/inc/wp-rest.php';
require_once __DIR__ . '/inc/wp-tracking.php';

add_action('after_setup_theme', static function (): void {
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);
});
// Our shared document renders one canonical, title and robots block for every route.
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'wp_robots');
// index.php renders one icon set; honor an administrator's site icon before theme defaults.
remove_action('wp_head', 'wp_site_icon', 99);
add_filter('get_site_icon_url', static fn($url, $size = 512) => get_option('site_icon') ? $url : bl_content_asset('global', 'brand.icon', (int) $size >= 180 ? 'images/icons/icon-192.png' : 'images/icons/icon-32.png'), 99, 2);
// The theme lists its own related products, articles and cases, so the platform's "Related" block (Jetpack) stays out of
// the content it filters; otherwise it shows a second, Chinese-labelled list inside product overviews.
add_filter('jetpack_relatedposts_filter_enabled_for_request', '__return_false');
add_action('wp', static function (): void {
    if (!class_exists('Jetpack_RelatedPosts')) return;
    $related = Jetpack_RelatedPosts::init();
    remove_filter('the_content', [$related, 'filter_add_target_to_dom'], 40);
}, 20);
// Search and AI crawlers are welcome: drop the per-bot blocks the platform adds to robots.txt.
add_filter('robots_txt', 'bl_allow_ai_crawlers', 99999);
add_action('init', 'bl_register_content_types');
// /llms.txt for AI assistants (inc/llms.php); its opening text is a setting editable over REST.
add_action('init', static function (): void {
    register_setting('bl_llms', BL_LLMS_TXT_INTRO_OPTION, ['type' => 'string', 'description' => 'llms.txt 开头的介绍（Markdown），留空用主题默认文字。', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '', 'show_in_rest' => true]);
});
add_action('parse_request', static fn(WP $wp) => bl_serve_llms_txt('/' . $wp->request), 1);
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
