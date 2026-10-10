<?php
/** An attachment card can carry a short silent film: the editor field keeps its address (bl_content_clean_url screens the scheme, as for every address field), and the card shows it with the picture as poster. */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wordpress.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };

$film = 'https://videos.files.wordpress.com/AbCd1234/brush-cutter-1.mp4';
$base = bl_content_defaults('loaders');
$rows = $base['attachments']['items'];
$check(count($rows) >= 2, 'no default attachments to test with');

// The field is in the editor schema as an address, so a film written through the API or the editor is kept, not dropped.
$field = null;
foreach (bl_content_schema('loaders', $base) as $section) foreach ($section['fields'] as $candidate) if ($candidate['key'] === 'attachments.items') $field = $candidate;
$check($field !== null && ($field['fields']['video']['type'] ?? '') === 'url', 'attachment rows have no film field');

$rows[0]['video'] = $film;
$clean = bl_content_clean_all('loaders', array_replace_recursive($base, ['attachments' => ['items' => $rows]]));
$check($clean['attachments']['items'][0]['video'] === $film, 'a film address was not kept');
$check($clean['attachments']['items'][1]['video'] === '', 'a card without a film gained one');

// The family page and the loader pages read the film from the same rows.
$GLOBALS['options']['bl_content_loaders'] = ['attachments' => ['items' => $clean['attachments']['items']]];
$groups = bl_attachments();
$byName = [];
foreach ($groups as $items) foreach ($items as $item) $byName[$item['name']] = $item;
$withFilm = $clean['attachments']['items'][0]['name'];
$check(($byName[$withFilm]['video'] ?? '') === $film, 'bl_attachments dropped the film');

// Markup: the film loads nothing until the script plays it, and keeps the picture as its poster.
$html = bl_attachment_media(['name' => 'Brush cutter', 'image' => '/img/brush.webp', 'video' => $film], 'Brush cutter attachment');
foreach (['<div class="bl-attachment-film"><video ', '</video></div>', 'src="' . $film . '"', 'poster="/img/brush.webp"', 'preload="none"', ' muted ', ' loop ', ' playsinline ', 'data-bl-loop', 'aria-label="Brush cutter attachment, in use"'] as $needle) {
    $check(str_contains($html, $needle), "film markup lacks $needle");
}
$check(!str_contains($html, 'autoplay') && !str_contains($html, 'controls'), 'film plays or shows controls before the script runs');
$decorative = bl_attachment_media(['image' => '/img/brush.webp', 'video' => $film]);
$check(str_contains($decorative, 'aria-hidden="true"') && !str_contains($decorative, 'aria-label'), 'a card with no description should hide its film from screen readers');
$check(str_contains(bl_attachment_media(['image' => '/img/brush.webp', 'video' => $film . '"><script>']), '&quot;&gt;&lt;script&gt;'), 'film address was not escaped');

// Without a film the card is the picture it always was; without either it is empty.
$picture = bl_attachment_media(['image' => '/img/forks.webp', 'video' => ''], 'Pallet forks attachment');
$check($picture === '<img src="/img/forks.webp" alt="Pallet forks attachment" width="480" height="360" loading="lazy">', 'the picture card changed');
$check(bl_attachment_media(['image' => '', 'video' => '']) === '', 'a card with no media should print nothing');
$poster = bl_attachment_media(['image' => '', 'video' => $film], 'Auger attachment');
$check(!str_contains($poster, 'poster='), 'a film with no picture printed an empty poster');

echo "attachment videos ok ($checks checks)\n";
