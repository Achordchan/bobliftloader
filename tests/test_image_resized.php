<?php
/** Cards and thumbnails ask the image CDN for their own size, except for a picture it cannot shrink: one not on this site, already small, or carrying Content Credentials (the CDN's re-signed copy weighs more than the original). */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
const MONTH_IN_SECONDS = 2592000;
$uploadsDir = sys_get_temp_dir() . '/bl-image-resized-' . getmypid();
@mkdir($uploadsDir . '/2026/10', 0777, true);
function wp_upload_dir() { return ['baseurl' => 'https://example.test/wp-content/uploads', 'basedir' => $GLOBALS['uploadsDir']]; }
function get_template_directory() { return dirname(__DIR__); }
require __DIR__ . '/../inc/bootstrap.php';
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

// A 1200x800 WebP, with or without a C2PA chunk after its picture.
$webp = static function (bool $credentials): string {
    $image = imagecreatetruecolor(1200, 800);
    ob_start();
    imagewebp($image);
    $bytes = (string) ob_get_clean();
    if (!$credentials) return $bytes;
    $body = substr($bytes, 12) . 'C2PA' . pack('V', 4) . 'sign';
    return 'RIFF' . pack('V', strlen($body) + 4) . 'WEBP' . $body;
};
file_put_contents($uploadsDir . '/2026/10/plain.webp', $webp(false));
file_put_contents($uploadsDir . '/2026/10/signed.webp', $webp(true));

$plain = '/wp-content/uploads/2026/10/plain.webp';
$check(bl_image_resized($plain, 400) === 'https://i0.wp.com/example.test/wp-content/uploads/2026/10/plain.webp?w=400&ssl=1', 'an upload was not resized by the CDN');
$check(bl_image_resized($plain, 1600) === $plain, 'a picture was asked for wider than it is');
$check(bl_image_srcset($plain, [400, 800, 1600]) === 'https://i0.wp.com/example.test/wp-content/uploads/2026/10/plain.webp?w=400&ssl=1 400w, https://i0.wp.com/example.test/wp-content/uploads/2026/10/plain.webp?w=800&ssl=1 800w, ' . $plain . ' 1200w', 'srcset widths are wrong');
$signed = 'https://example.test/wp-content/uploads/2026/10/signed.webp';
$check(bl_image_resized($signed, 400) === $signed && bl_image_srcset($signed, [400]) === '', 'a picture with Content Credentials went through the CDN');
$theme = 'https://example.test/theme/assets/images/range-loader-studio.webp?ver=abc';
$check(bl_image_resized($theme, 480) === 'https://i0.wp.com/example.test/theme/assets/images/range-loader-studio.webp?ver=abc&w=480&ssl=1', 'a theme picture was not resized, or lost its version');
foreach (['https://elsewhere.test/wp-content/uploads/2026/10/plain.webp', '/wp-content/uploads/2026/10/missing.webp', '/wp-content/uploads/../../etc/passwd', ''] as $other) $check(bl_image_resized($other, 400) === $other && bl_image_srcset($other, [400]) === '', "$other was resized");

// The CDN reaches a site by its host only, so a site on its own port keeps its own addresses.
$check(bl_image_cdn_host('https://example.test') === 'example.test' && bl_image_cdn_host('https://example.test:8443') === '', 'a site on its own port was given a CDN host');

array_map('unlink', glob($uploadsDir . '/2026/10/*'));
echo "image resizing ok ($checks checks)\n";
