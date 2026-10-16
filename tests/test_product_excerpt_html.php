<?php
/** A product summary saved as HTML (the rendered "<p>…</p>" form) reads as plain text: the generated wording is still
 * recognised, so the hero shows no summary, and a written one shows without visible tags. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wordpress.php';
function get_the_title($post) { return $post->post_title; }
function get_permalink($post) { return home_url('/product/' . $post->post_name . '/'); }
function get_the_post_thumbnail_url(...$args) { return ''; }
function get_post_thumbnail_id(...$args) { return 0; }
function apply_filters($name, $value, ...$args) { return $value; }
function wp_trim_words($text, $count = 55) { return implode(' ', array_slice(preg_split('/\s+/', trim($text)), 0, $count)); }
$GLOBALS['posts'] = [];
$record = null;
foreach (bl_read_data('products') as $candidate) if (($candidate['model'] ?? '') === 'BL-T390-C') $record = $candidate;
if (!$record) throw new RuntimeException('BL-T390-C missing from the seed data');
$post = new WP_Post(1, 'bl_product', (string) $record['slug'], (string) $record['name']);
$GLOBALS['posts'][1] = $post;
$GLOBALS['meta'][1]['_bl_record'] = $record;
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

$generated = bl_product_excerpt(bl_wp_product_record($post));
// Saved wrapped in a paragraph, with the trailing newline the rendered form carries.
$post->post_excerpt = '<p>' . $generated . "</p>\n";
$read = bl_wp_product_record($post);
$check($read['excerpt'] === $generated, 'a tag-wrapped generated summary was not read as the generated one');
$check($read['excerpt'] === bl_product_excerpt($read), 'the hero would treat the generated summary as written');

// A written summary keeps its words, loses its tags and decodes its entities once.
$post->post_excerpt = "<p>Seated &amp; tracked,\n for soft ground.</p>";
$check(bl_wp_product_record($post)['excerpt'] === 'Seated & tracked, for soft ground.', 'a written HTML summary kept its tags or entities');
$post->post_excerpt = 'Plain words stay as they are.';
$check(bl_wp_product_record($post)['excerpt'] === 'Plain words stay as they are.', 'a plain summary changed');
$post->post_excerpt = '<p> </p>';
$check(bl_wp_product_record($post)['excerpt'] !== '', 'an empty paragraph did not fall back to a summary');
echo "ok - $checks product excerpt checks\n";
