<?php
/** The product photo in the notification mail: a WebP kept in the media library as a path from the site root becomes a JPEG at the site's own address, never a broken relative picture. */
declare(strict_types=1);

$root = sys_get_temp_dir() . '/bl-thumb-' . bin2hex(random_bytes(4));
mkdir($root . '/uploads/2026/10', 0777, true);
mkdir($root . '/theme/assets/images/products', 0777, true);
mkdir($root . '/uploads/bl-mail', 0777, true);
$GLOBALS['root'] = $root;
$GLOBALS['posts'] = [];

// What inc/wp-inquiry.php takes from WordPress.
function add_action(...$args) {}
function add_filter(...$args) {}
function home_url($path = '') { return 'https://example.test' . $path; }
function wp_parse_url($url, $component = -1) { return parse_url($url, $component); }
function trailingslashit($value) { return rtrim($value, '/') . '/'; }
function wp_upload_dir() { return ['basedir' => $GLOBALS['root'] . '/uploads', 'baseurl' => 'https://example.test/wp-content/uploads']; }
function get_template_directory() { return $GLOBALS['root'] . '/theme'; }
function get_page_by_path($slug, $output, $type) { return $GLOBALS['posts'][$slug] ?? null; }
function get_post_thumbnail_id($post) { return 0; }
function get_attached_file($id) { return ''; }
function get_post_meta($id, $key, $single) { return $GLOBALS['meta'][$id][$key] ?? ''; }
function is_wp_error($value) { return $value instanceof WP_Error; }
class WP_Error {}
const OBJECT = 'OBJECT';
class GdEditor
{
    public function __construct(private $image) {}
    public function resize(int $w, int $h, bool $crop) { $this->image = imagescale($this->image, $w, $h); return true; }
    public function save(string $path, string $mime) { return imagejpeg($this->image, $path) ? true : new WP_Error(); }
}
function wp_get_image_editor($file) { $image = @imagecreatefromwebp($file); return $image ? new GdEditor($image) : new WP_Error(); }
require __DIR__ . '/../inc/wp-inquiry.php';

$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

$image = imagecreatetruecolor(400, 300);
imagewebp($image, $root . '/uploads/2026/10/t1000.webp');
imagewebp($image, $root . '/theme/assets/images/products/bl-1000-0.webp');
$product = static function (string $slug, string $stored, string $image) use ($check): array {
    $post = (object) ['ID' => count($GLOBALS['posts']) + 1, 'post_name' => $slug];
    $GLOBALS['posts'][$slug] = $post;
    $GLOBALS['meta'][$post->ID]['_bl_record'] = ['image' => $stored];
    return ['slug' => $slug, 'image' => $image];
};

// Address from the media library, as the product records keep it: a path from the site root.
$check(bl_wp_inquiry_uploads_file('/wp-content/uploads/2026/10/t1000.webp') === $root . '/uploads/2026/10/t1000.webp', 'a media library path did not lead to its file');
$check(bl_wp_inquiry_uploads_file('https://example.test/wp-content/uploads/2026/10/t1000.webp') === $root . '/uploads/2026/10/t1000.webp', 'a full media library address did not lead to its file');
$check(bl_wp_inquiry_uploads_file('https://other.test/wp-content/uploads/2026/10/t1000.webp') === '', 'an address on another site led to a file here');
$check(bl_wp_inquiry_uploads_file('/wp-content/uploads/../../wp-config.php') === '', 'a path out of the uploads folder was accepted');
$check(bl_wp_inquiry_uploads_file('/wp-content/uploads/%2e%2e/secret.php') === '', 'an encoded path out of the uploads folder was accepted');
$check(bl_wp_inquiry_uploads_file('/product/x/') === '', 'a page address led to a file');

$url = bl_wp_inquiry_thumbnail($product('t1000', '/wp-content/uploads/2026/10/t1000.webp', '/wp-content/uploads/2026/10/t1000.webp'));
$check(str_starts_with($url, 'https://example.test/wp-content/uploads/bl-mail/') && str_ends_with($url, '-208.jpg'), 'the media library photo was not made into a JPEG: ' . $url);
$jpeg = $root . '/uploads/bl-mail/' . basename($url);
$check(is_file($jpeg) && getimagesize($jpeg)['mime'] === 'image/jpeg', 'the JPEG was not saved');
$check(bl_wp_inquiry_thumbnail($product('t1000', '/wp-content/uploads/2026/10/t1000.webp', '/x.webp')) === $url, 'the same photo got a second JPEG');

// The theme's own catalogue image still works.
$url = bl_wp_inquiry_thumbnail($product('t1000b', 'images/products/bl-1000-0.webp', 'https://example.test/theme/assets/images/products/bl-1000-0.webp'));
$check(str_ends_with($url, '-208.jpg'), 'the theme catalogue image was not made into a JPEG');

// When no JPEG can be made (a file that is gone, or one the editor cannot read) the address is still complete.
$missing = bl_wp_inquiry_thumbnail($product('gone', '/wp-content/uploads/2026/10/gone.webp', '/wp-content/uploads/2026/10/gone.webp'));
$check($missing === 'https://example.test/wp-content/uploads/2026/10/gone.webp', 'a missing file gave a relative or wrong address: ' . $missing);
file_put_contents($root . '/uploads/2026/10/bad.webp', 'not an image');
$bad = bl_wp_inquiry_thumbnail($product('bad', '/wp-content/uploads/2026/10/bad.webp', '/wp-content/uploads/2026/10/bad.webp'));
$check($bad === 'https://example.test/wp-content/uploads/2026/10/bad.webp', 'an unreadable file gave a relative or wrong address: ' . $bad);
$full = bl_wp_inquiry_thumbnail($product('cdn', 'https://cdn.test/a.jpg', 'https://cdn.test/a.jpg'));
$check($full === 'https://cdn.test/a.jpg', 'a full address from elsewhere was changed');
$check(bl_wp_inquiry_absolute_url('//cdn.test/a.jpg') === '//cdn.test/a.jpg' && bl_wp_inquiry_absolute_url('') === '', 'a protocol-relative or empty address was changed');

echo "enquiry thumbnail ok ($checks checks)\n";
