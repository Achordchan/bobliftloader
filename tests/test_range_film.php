<?php
/** The loader range page can carry one 16:9 film under its heading: three fields on the loader page content, shown only when an address is saved. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wordpress.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
function get_the_title($post) { return $post->post_title; }
function get_permalink($post) { return home_url('/products/' . $post->post_name . '/'); }
function get_the_post_thumbnail_url(...$args) { return ''; }
function get_post_thumbnail_id(...$args) { return 0; }
function apply_filters($name, $value, ...$args) { return $value; }
function wp_trim_words($text, $count = 55) { return implode(' ', array_slice(preg_split('/\s+/', trim($text)), 0, $count)); }
// The range page reads its models from product posts; the repo's own product records stand in for the saved ones.
$GLOBALS['posts'] = [];
foreach (array_values(bl_read_data('products')) as $i => $record) {
    $id = $i + 1;
    $GLOBALS['posts'][$id] = new WP_Post($id, 'bl_product', (string) $record['slug'], (string) ($record['name'] ?? $record['model']));
    $GLOBALS['meta'][$id]['_bl_record'] = $record;
}
$film = 'https://videos.files.wordpress.com/QMxdcnWn/range.mp4';
$poster = '/wp-content/uploads/2026/10/range-poster.jpg';
$render = static function (string $category): string { ob_start(); $storyCategory = $category; require __DIR__ . '/../templates/partials/range-story.php'; return ob_get_clean(); };
// bl_editable keeps the first page content it reads, so each saved state is rendered in a process of its own.
if (($argv[1] ?? '') === 'render') {
    if ($argv[2] === 'saved') $GLOBALS['options']['bl_content_loaders'] = ['range_story' => ['film_video' => $film, 'film_poster' => $poster, 'film_caption' => 'The range, eight models.']];
    echo $render($argv[3]);
    exit;
}
$inProcess = static fn(string $state, string $category): string => (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' render ' . $state . ' ' . $category);
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

// The fields are in the editor schema, and empty by default, so the page is unchanged until a film is saved.
$keys = [];
foreach (bl_content_schema('loaders', bl_content_defaults('loaders')) as $section) foreach ($section['fields'] as $field) $keys[$field['key']] = $field['type'];
foreach (['film_video', 'film_poster'] as $name) $check(($keys['range_story.' . $name] ?? '') === 'url', "no address field for $name");
$check(($keys['range_story.film_caption'] ?? '') === 'text', 'no caption field');
$check(!str_contains($inProcess('default', 'skid-steer-loaders'), 'bl-range-film'), 'a film shows before one is saved');

// Saved through the content API, the film appears under the heading, loading only on play.
$clean = bl_content_clean_all('loaders', array_replace_recursive(bl_content_defaults('loaders'), ['range_story' => ['film_video' => $film, 'film_poster' => $poster, 'film_caption' => 'The range, eight models.']]));
$check($clean['range_story']['film_video'] === $film && $clean['range_story']['film_poster'] === $poster, 'film addresses were not kept');
$html = $inProcess('saved', 'skid-steer-loaders');
$check(str_contains($html, '<video controls playsinline preload="none" poster="' . $poster . '" src="' . $film . '">'), 'film not rendered with its poster');
$check(str_contains($html, '<figcaption>The range, eight models.</figcaption>'), 'caption missing');
$check(strpos($html, 'bl-range-film') > strpos($html, 'bl-range-story-head') && strpos($html, 'bl-range-film') < strpos($html, 'bl-range-story-chart'), 'film is not between the heading and the chart');

// The dumper range page has no such field.
$dumpers = $inProcess('saved', 'mini-dumpers');
$check(!str_contains($dumpers, 'bl-range-film'), 'dumper page shows a range film');
echo "Range film: $checks checks passed\n";
