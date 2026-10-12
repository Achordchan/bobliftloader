<?php
/** A family page can carry one 16:9 film: three catalogue fields on the family page content, a button beside the catalogue download opens it in a dialog, and nothing shows until an address is saved. */
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
$render = static function (string $category): string {
    $route = ['category' => $category, 'path' => '/products/' . $category . '/', 'type' => 'products'];
    ob_start(); require __DIR__ . '/../templates/pages/products.php'; return ob_get_clean();
};
// bl_editable keeps the first page content it reads, so each saved state is rendered in a process of its own.
if (($argv[1] ?? '') === 'render') {
    $saved = ['catalog' => ['film_video' => $film, 'film_poster' => $poster, 'film_caption' => 'The range, <eight> models.']];
    if ($argv[2] === 'legacy') $GLOBALS['options']['bl_content_loaders'] = ['range_story' => ['film_video' => $film, 'film_poster' => $poster, 'film_caption' => 'Saved before the move.']];
    if ($argv[2] === 'both') $GLOBALS['options']['bl_content_loaders'] = ['range_story' => ['film_video' => 'https://old.example/film.mp4'], 'catalog' => ['film_video' => $film]];
    if ($argv[2] === 'saved') { $GLOBALS['options']['bl_content_loaders'] = $saved; $GLOBALS['options']['bl_content_dumpers'] = $saved; }
    echo $render($argv[3]);
    exit;
}
$inProcess = static fn(string $state, string $category): string => (string) shell_exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' render ' . $state . ' ' . $category . ' 2>&1');
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

// The fields are in each family's editor schema, and empty by default, so the page is unchanged until a film is saved.
foreach (['loaders', 'dumpers'] as $page) {
    $keys = [];
    foreach (bl_content_schema($page, bl_content_defaults($page)) as $section) foreach ($section['fields'] as $field) $keys[$field['key']] = $field['type'];
    foreach (['film_video', 'film_poster'] as $name) $check(($keys['catalog.' . $name] ?? '') === 'url', "$page: no address field for $name");
    $check(($keys['catalog.film_caption'] ?? '') === 'text' && ($keys['catalog.hero_film'] ?? '') === 'text', "$page: no caption or button field");
    $clean = bl_content_clean_all($page, array_replace_recursive(bl_content_defaults($page), ['catalog' => ['film_video' => $film, 'film_poster' => $poster]]));
    $check($clean['catalog']['film_video'] === $film && $clean['catalog']['film_poster'] === $poster, "$page: film addresses were not kept");
}
foreach (['skid-steer-loaders', 'mini-dumpers'] as $category) {
    $default = $inProcess('default', $category);
    $check(str_contains($default, 'bl-page-heading') && !str_contains($default, 'data-film-open') && !str_contains($default, 'film-dialog'), "$category: a film button shows before one is saved");
    // Saved through the content API, the button sits in the hero beside the catalogue download and the film loads only on play.
    $html = $inProcess('saved', $category);
    $check(str_contains($html, 'data-film-open="film-dialog"') && str_contains($html, 'Watch the range film'), "$category: film button missing");
    $check(str_contains($html, '<video controls playsinline preload="none" data-poster="' . $poster . '" data-src="' . $film . '"></video>') && !str_contains($html, ' src="' . $film . '"'), "$category: film is not held back until played");
    $check(str_contains($html, 'The range, &lt;eight&gt; models.</p>'), "$category: caption missing or not escaped");
    $check(strpos($html, 'bl-catalog-actions') > strpos($html, 'bl-page-heading') && strpos($html, 'film-dialog') > strpos($html, 'bl-catalog-actions'), "$category: button is not in the hero");
    $check(!str_contains($html, 'bl-range-film'), "$category: the film still shows in the range story");
}
// A film saved under range_story before the fields moved still shows on the loader page; a film already saved in the new place wins, and the dumper page ignores the old place.
$legacy = $inProcess('legacy', 'skid-steer-loaders');
$check(str_contains($legacy, 'data-film-open="film-dialog"') && str_contains($legacy, 'data-src="' . $film . '"') && str_contains($legacy, 'data-poster="' . $poster . '"') && str_contains($legacy, 'Saved before the move.</p>'), 'a film saved under range_story was lost');
$both = $inProcess('both', 'skid-steer-loaders');
$check(str_contains($both, 'data-src="' . $film . '"') && !str_contains($both, 'old.example'), 'the old address overrode the new one');
$migrated = bl_content_migrate('loaders', ['range_story' => ['film_video' => $film, 'film_caption' => 'x', 'intro' => 'kept']]);
$check($migrated['catalog']['film_video'] === $film && $migrated['catalog']['film_caption'] === 'x' && !isset($migrated['range_story']['film_video']) && $migrated['range_story']['intro'] === 'kept', 'migration moved the wrong keys');
$check(!isset(bl_content_migrate('dumpers', ['range_story' => ['film_video' => $film]])['catalog']), 'the dumper page picked up a loader film');
// A client still writing the old names is not ignored: the old names land in the new fields, the new names win, and a clear stays a clear.
$prepare = static fn(array $previous, array $input) => bl_content_prepare('loaders', $previous, $input);
$old = $prepare([], ['range_story' => ['film_video' => $film, 'film_poster' => $poster, 'film_caption' => 'Old names']]);
$check(is_array($old) && $old['catalog']['film_video'] === $film && $old['catalog']['film_poster'] === $poster && $old['catalog']['film_caption'] === 'Old names' && !isset($old['range_story']['film_video']), 'a write under the old names was ignored');
$mixed = $prepare([], ['range_story' => ['film_video' => 'https://old.example/film.mp4'], 'catalog' => ['film_video' => $film]]);
$check(is_array($mixed) && $mixed['catalog']['film_video'] === $film, 'the old name overrode the new one in a write');
$cleared = $prepare(['catalog' => ['film_video' => $film, 'film_poster' => $poster]], ['range_story' => ['film_video' => '', 'film_poster' => '']]);
$check(is_array($cleared) && bl_content_resolve('loaders', $cleared)['catalog']['film_video'] === '' && bl_content_resolve('loaders', $cleared)['catalog']['film_poster'] === '', 'a clear under the old names was ignored');
$stored = $prepare(['range_story' => ['film_video' => $film]], ['range_story' => ['intro' => 'Other change']]);
$check(is_array($stored) && $stored['catalog']['film_video'] === $film && $stored['range_story']['intro'] === 'Other change', 'saving another field lost the stored film');
echo "Range film: $checks checks passed\n";
