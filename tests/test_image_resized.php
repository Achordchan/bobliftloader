<?php
/** Cards and thumbnails ask the image CDN for their own size, except for a picture it cannot shrink: one not on this site, already small, or light and carrying Content Credentials (the CDN's re-signed copy would weigh more than the original). */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
const MONTH_IN_SECONDS = 2592000;
$uploadsDir = sys_get_temp_dir() . '/bl-image-resized-' . getmypid();
@mkdir($uploadsDir . '/2026/10', 0777, true);
function wp_upload_dir() { return ['baseurl' => 'https://example.test/wp-content/uploads', 'basedir' => $GLOBALS['uploadsDir']]; }
function get_template_directory() { return dirname(__DIR__); }
function apply_filters($name, $value, ...$args) { return run_filter($name, $value, ...$args); }
// example.test is a reserved, private name, so the fixture site opts in as a public one would be.
add_filter('bl_image_cdn', static fn(bool $public, string $host) => $public || $host === 'example.test', 10, 2);
require __DIR__ . '/../inc/bootstrap.php';
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

// A 1200x800 WebP, with or without a C2PA chunk (of $signature bytes) after its picture.
$webp = static function (bool $credentials, int $signature = 4): string {
    $image = imagecreatetruecolor(1200, 800);
    ob_start();
    imagewebp($image);
    $bytes = (string) ob_get_clean();
    if (!$credentials) return $bytes;
    $body = substr($bytes, 12) . 'C2PA' . pack('V', $signature) . str_repeat('s', $signature);
    return 'RIFF' . pack('V', strlen($body) + 4) . 'WEBP' . $body;
};
file_put_contents($uploadsDir . '/2026/10/plain.webp', $webp(false));
file_put_contents($uploadsDir . '/2026/10/signed.webp', $webp(true));
file_put_contents($uploadsDir . '/2026/10/signed-heavy.webp', $webp(true, BL_IMAGE_SIGNED_MIN));

$plain = '/wp-content/uploads/2026/10/plain.webp';
$check(bl_image_resized($plain, 400) === 'https://i0.wp.com/example.test/wp-content/uploads/2026/10/plain.webp?w=400&ssl=1', 'an upload was not resized by the CDN');
$check(bl_image_resized($plain, 1600) === $plain, 'a picture was asked for wider than it is');
$check(bl_image_srcset($plain, [400, 800, 1600]) === 'https://i0.wp.com/example.test/wp-content/uploads/2026/10/plain.webp?w=400&ssl=1 400w, https://i0.wp.com/example.test/wp-content/uploads/2026/10/plain.webp?w=800&ssl=1 800w, ' . $plain . ' 1200w', 'srcset widths are wrong');
$signed = 'https://example.test/wp-content/uploads/2026/10/signed.webp';
$check(bl_image_resized($signed, 400) === $signed && bl_image_srcset($signed, [400]) === '', 'a light picture with Content Credentials went through the CDN');
// A heavy one is lighter as a re-signed copy, which keeps its credentials.
$heavy = '/wp-content/uploads/2026/10/signed-heavy.webp';
$check(bl_image_resized($heavy, 240) === 'https://i0.wp.com/example.test/wp-content/uploads/2026/10/signed-heavy.webp?w=240&ssl=1' && bl_image_srcset($heavy, [240]) === 'https://i0.wp.com/example.test/wp-content/uploads/2026/10/signed-heavy.webp?w=240&ssl=1 240w, ' . $heavy . ' 1200w', 'a heavy picture with Content Credentials was not resized');
$theme = 'https://example.test/theme/assets/images/range-loader-studio.webp?ver=abc';
$check(bl_image_resized($theme, 480) === 'https://i0.wp.com/example.test/theme/assets/images/range-loader-studio.webp?ver=abc&w=480&ssl=1', 'a theme picture was not resized, or lost its version');
$check(bl_image_resized('http://example.test/wp-content/uploads/2026/10/plain.webp', 400) === 'https://i0.wp.com/example.test/wp-content/uploads/2026/10/plain.webp?w=400', 'a picture served over HTTP was fetched by the CDN over HTTPS');
foreach (['https://example.test:8443/wp-content/uploads/2026/10/plain.webp', 'https://user@example.test/wp-content/uploads/2026/10/plain.webp', 'https://elsewhere.test/wp-content/uploads/2026/10/plain.webp', '/wp-content/uploads/2026/10/missing.webp', '/wp-content/uploads/../../etc/passwd', ''] as $other) $check(bl_image_resized($other, 400) === $other && bl_image_srcset($other, [400]) === '', "$other was resized");

// The CDN reaches a site by its host only, so a site on its own port, or on a local or private name, keeps its own addresses.
$check(bl_image_cdn_host('https://bobliftloader.com') === 'bobliftloader.com' && bl_image_cdn_host('https://example.test') === 'example.test', 'a public site was refused the CDN');
foreach (['https://example.test:8443', 'https://bobliftloader.com:8443', 'http://localhost', 'https://bob-lift.local', 'https://site.localhost', 'http://192.168.31.10', 'http://[::1]', 'https://dev.internal', 'https://staging.test'] as $private) $check(bl_image_cdn_host($private) === '', "$private was given a CDN host");
$GLOBALS['hooks']['bl_image_cdn'][20][] = static fn(bool $public) => false;
$check(bl_image_cdn_host('https://bobliftloader.com') === '', 'the bl_image_cdn filter could not turn the CDN off');
$check(bl_image_resized($plain, 400) === $plain && bl_image_srcset($plain, [400]) === '', 'a site with the CDN off still printed CDN addresses');

array_map('unlink', glob($uploadsDir . '/2026/10/*'));
echo "image resizing ok ($checks checks)\n";
