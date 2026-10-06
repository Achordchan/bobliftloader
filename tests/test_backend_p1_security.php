<?php
/** Load WordPress's actual KSES and URL escaping functions without bootstrapping its database. */
declare(strict_types=1);
define('ABSPATH', dirname(__DIR__) . '/.wordpress/');
define('WPINC', 'wp-includes');
if (!is_dir(ABSPATH . WPINC)) {
    echo "skip: backend P1 security requires .wordpress/wp-includes\n";
    exit(0);
}
function apply_filters($hook, $value, ...$args) { return $value; }
function wp_allowed_protocols() { return ['http', 'https', 'mailto', 'tel']; }
function home_url($path = '') { return 'https://example.test/site' . $path; }
function bl_page_editor_content() { return ''; }
function bl_wp_products() { return []; }
function get_template_directory_uri() { return 'https://example.test/site/theme'; }
function get_option($key, $default = false) { return $GLOBALS['options'][$key] ?? $default; }
require ABSPATH . WPINC . '/formatting.php';
require ABSPATH . WPINC . '/kses.php';
require ABSPATH . WPINC . '/class-wp-token-map.php';
foreach (['html5-named-character-references', 'class-wp-html-attribute-token', 'class-wp-html-span', 'class-wp-html-doctype-info', 'class-wp-html-text-replacement', 'class-wp-html-decoder', 'class-wp-html-tag-processor'] as $file) require ABSPATH . WPINC . '/html-api/' . $file . '.php';
require __DIR__ . '/../inc/bootstrap.php';
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$cases = ['javascript:alert(document.domain)', 'JaVaScRiPt:alert(1)', 'data:text/html,bad', "java\nscript:alert(1)"];
if (isset($argv[1])) {
    $url = $cases[(int) $argv[1]];
    $GLOBALS['options']['bl_content_global'] = ['company' => ['domain' => $url, 'website' => $url]];
    $GLOBALS['options']['bl_content_privacy'] = ['body' => ['intro' => '<a href="{company.domain}">Open</a><script>alert(1)</script>']];
    $html = bl_privacy_body('intro');
    $check(!preg_match('/href="(?:javascript|data):/i', $html) && !str_contains($html, '<script>'), 'expanded HTML bypassed KSES');
    $check(bl_company_url('website') === '', 'company URL allows executable protocol');
    exit;
}
foreach (array_keys($cases) as $case) {
    passthru(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $case, $status);
    $check($status === 0, 'isolated unsafe URL test failed');
}
foreach (['intro', 'submitted', 'draft', 'ending'] as $section) {
    $default = bl_content_value('privacy', 'body.' . $section);
    $expanded = preg_replace_callback('/\{company\.([a-z_]+)\}/', static fn($m) => bl_escape(bl_company($m[1])), $default);
    $check(bl_privacy_body($section) === $expanded, 'real KSES changed default bytes');
}
$GLOBALS['options']['bl_content_global'] = [];
$GLOBALS['options']['bl_content_about'] = ['hero' => ['link_url' => '/contact/']];
ob_start(); (static function () { require __DIR__ . '/../templates/pages/about.php'; })(); $html = ob_get_clean();
$check(str_contains($html, 'href="https://example.test/site/contact/"'), 'About hero lost installation subdirectory');
$check(bl_application_link('#group') === '#group', 'same-page anchor changed');
echo "backend P1 real KSES / URL / subdirectory regressions ok\n";
