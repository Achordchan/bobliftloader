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
remove_action('wp_head', 'wp_robots', 1); // core adds it at priority 1; without the number nothing is removed and a second robots tag is printed
// No emoji script or styles: every browser the site supports draws emoji itself, and the theme's pages use none.
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
remove_action('wp_print_styles', 'print_emoji_styles');
// The home page is all theme template, no editor blocks, so the block styles the platform inlines would only slow its first paint.
add_action('wp_enqueue_scripts', static function (): void {
    if (!is_front_page()) return;
    foreach (['wp-block-library', 'wp-block-library-theme', 'classic-theme-styles', 'global-styles', 'jetpack-global-styles-frontend-style'] as $handle) wp_dequeue_style($handle);
}, 100);
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
add_action('template_redirect', 'bl_wp_application_route', 0);
// /llms.txt for AI assistants (inc/llms.php); its opening text is a setting editable over REST.
add_action('init', static function (): void {
    register_setting('bl_llms', BL_LLMS_TXT_INTRO_OPTION, ['type' => 'string', 'description' => 'llms.txt 开头的介绍（Markdown），留空用主题默认文字。', 'sanitize_callback' => 'sanitize_textarea_field', 'default' => '', 'show_in_rest' => true]);
});
add_action('parse_request', static fn(WP $wp) => bl_serve_llms_txt('/' . $wp->request), 1);
// One sitemap, the theme's (inc/routes.php): the core one is off, its old addresses lead to ours, and robots.txt names it.
add_filter('wp_sitemaps_enabled', '__return_false');
add_action('parse_request', static function (WP $wp): void {
    $path = '/' . $wp->request;
    if (bl_indexable() && preg_match('~^/wp-sitemap[a-z0-9_-]*\.xml$~', $path)) {
        wp_safe_redirect(bl_public_base() . '/sitemap.xml', 301);
        exit;
    }
    bl_serve_sitemap($path);
}, 1);
add_filter('robots_txt', static fn(string $robots, $public = true) => $public && !preg_match('~^\s*sitemap\s*:~im', $robots) ? rtrim($robots) . "\n\nSitemap: " . bl_public_base() . "/sitemap.xml\n" : $robots, 100000, 2);
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
