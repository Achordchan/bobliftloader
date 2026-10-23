<?php
/** The page an enquiry came from: named by its title, its address without ad-tracking parameters, for the mail and DingTalk. */
declare(strict_types=1);

$GLOBALS['filters'] = [];
// What inc/wp-inquiry.php takes from WordPress and the rest of the theme.
function add_action(...$args) {}
function add_filter($name, $callback, $priority = 10, $args = 1) { $GLOBALS['filters'][$name] = $callback; }
function home_url($path = '') { return ($GLOBALS['home'] ?? 'https://bobliftloader.com') . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function url_to_postid($url) { return str_contains($url, '/privacy-old/') || str_contains($url, 'page_id=9') ? 9 : 0; }
function get_the_title($id) { return 'Old &amp; privacy'; }
function esc_url($value) { return htmlspecialchars($value, ENT_QUOTES); }
function esc_html($value) { return htmlspecialchars($value, ENT_QUOTES); }
function bl_plain_title(string $title): string { return html_entity_decode(strip_tags($title), ENT_QUOTES | ENT_HTML5, 'UTF-8'); }
function bl_resolve_route(?string $path = null): array
{
    $titles = ['/' => 'Mini Skid Steer Loaders & Mini Dumpers', '/products/skid-steer-loaders/' => 'Mini Skid Steer Loaders', '/contact/' => 'Contact BOB-LIFT'];
    return isset($titles[$path]) ? ['status' => 200, 'title' => $titles[$path]] : ['status' => 404, 'title' => 'Page not found'];
}
require __DIR__ . '/../inc/wp-inquiry.php';

$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $message\n"); exit(1); }
    $checks++;
};

$page = bl_wp_inquiry_page('/?gad_source=2&gad_campaignid=22474194610&gclid=Cj0KCQjw6O_x');
$check($page === ['url' => 'https://bobliftloader.com/', 'title' => 'Mini Skid Steer Loaders & Mini Dumpers'], 'a site-relative address with ad parameters is not the home page by its title: ' . json_encode($page));
$page = bl_wp_inquiry_page('https://www.bobliftloader.com/products/skid-steer-loaders/?utm_source=google&utm_medium=cpc&color=red#models');
$check($page === ['url' => 'https://www.bobliftloader.com/products/skid-steer-loaders/?color=red#models', 'title' => 'Mini Skid Steer Loaders'], 'other parameters and the fragment are lost, or the www address is not recognised: ' . json_encode($page));
$check(bl_wp_inquiry_page('https://bobliftloader.com/privacy-old/')['title'] === 'Old & privacy', 'a WordPress page outside the theme routes has no title');
$check(bl_wp_inquiry_page('https://bobliftloader.com/?page_id=9&gclid=1') === ['url' => 'https://bobliftloader.com/?page_id=9', 'title' => 'Old & privacy'], 'a page named by its ID is taken for the home page');
$check(bl_wp_inquiry_page('https://bobliftloader.com/?page_id=12')['title'] === '', 'an unknown post ID is named as the home page');
$check(bl_wp_inquiry_page('https://bobliftloader.com/?s=loader&gclid=1') === ['url' => 'https://bobliftloader.com/?s=loader', 'title' => ''] && bl_wp_inquiry_page('https://bobliftloader.com?cat=5')['title'] === '', 'a search or listing at the home address is named as the home page');
$check(bl_wp_inquiry_page('https://bobliftloader.com/#top')['title'] === 'Mini Skid Steer Loaders & Mini Dumpers', 'the home page with a fragment loses its title');
$check(bl_wp_inquiry_page('https://bobliftloader.com/nowhere/?gclid=1') === ['url' => 'https://bobliftloader.com/nowhere/', 'title' => ''], 'an unknown page gets a title');
$check(bl_wp_inquiry_page('https://elsewhere.example/contact/?gclid=1')['title'] === '', "another site's page is named as one of ours");
$check(bl_wp_inquiry_page('//elsewhere.example/contact/')['url'] === '//elsewhere.example/contact/', 'a protocol-relative address is taken as one of ours');
$check(bl_wp_inquiry_page('') === ['url' => '', 'title' => ''], 'an empty address is not left empty');
$GLOBALS['home'] = 'https://example.test:8443/shop';
$check(bl_wp_inquiry_page('/shop/contact/?gclid=1')['url'] === 'https://example.test:8443/shop/contact/', 'a site in a folder repeats the folder in a path from the root');
unset($GLOBALS['home']);

$html = bl_wp_inquiry_page_html(['url' => 'https://bobliftloader.com/?a=1&b=2', 'title' => 'Loaders <&>'], '#215bdb');
$check(str_contains($html, '<b style="color:#0b1f33;">Loaders &lt;&amp;&gt;</b><br><a href="https://bobliftloader.com/?a=1&amp;b=2"'), 'the mail does not show the title above the link, escaped: ' . $html);
$check(!str_contains(bl_wp_inquiry_page_html(['url' => 'https://bobliftloader.com/x/', 'title' => ''], '#215bdb'), '<b'), 'an unknown page shows an empty title');

// The DingTalk plugin's filters: the site name heads the message, and the theme names its own routes.
$check(($GLOBALS['filters']['achord_dingtalk_site_name'])('') === '装载设备站' && ($GLOBALS['filters']['achord_dingtalk_site_name'])('Loader') === 'Loader', 'the DingTalk site name is wrong, or overrides the setting');
$check(($GLOBALS['filters']['achord_dingtalk_page_title'])('', 'https://bobliftloader.com/contact/?gclid=1') === 'Contact BOB-LIFT' && ($GLOBALS['filters']['achord_dingtalk_page_title'])('Kept', 'https://bobliftloader.com/contact/') === 'Kept', 'the DingTalk page title is wrong, or overrides the plugin');

echo "Enquiry page: $checks checks passed\n";
