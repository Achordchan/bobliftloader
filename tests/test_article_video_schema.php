<?php
declare(strict_types=1);
// php tests/test_article_video_schema.php — a film set into a story's text becomes a VideoObject; a half-described one does not.
require __DIR__ . '/../inc/seo.php';
if (!function_exists('bl_public_base')) { function bl_public_base(): string { return 'https://bobliftloader.com'; } }

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$film = '<figure class="wp-block-video bl-article-video" data-upload-date="2026-10-14T03:55:17+00:00" data-duration="PT57S"><video controls playsinline preload="none" title="BL-T750-H diesel mini skid steer clearing snow" poster="/wp-content/uploads/2026/10/poster.jpg" src="https://videos.files.wordpress.com/abc/film.mp4"></video><figcaption>The BL-T750-H  with a <a href="/x/">snow blower</a>.</figcaption></figure>';
$videos = bl_article_videos('<h2>Diesel</h2><p>Text — with “quotes”.</p>' . $film);
$check(count($videos) === 1, 'the film was not found');
$v = $videos[0] ?? [];
$check(($v['@type'] ?? '') === 'VideoObject', 'not a VideoObject');
$check(($v['name'] ?? '') === 'BL-T750-H diesel mini skid steer clearing snow', 'name not taken from the title');
$check(($v['description'] ?? '') === 'The BL-T750-H with a snow blower.', 'description not the caption text: ' . ($v['description'] ?? ''));
$check(($v['thumbnailUrl'] ?? '') === 'https://bobliftloader.com/wp-content/uploads/2026/10/poster.jpg', 'poster not made absolute');
$check(($v['contentUrl'] ?? '') === 'https://videos.files.wordpress.com/abc/film.mp4', 'content URL changed');
$check(($v['uploadDate'] ?? '') === '2026-10-14T03:55:17+00:00' && ($v['duration'] ?? '') === 'PT57S', 'date or duration lost');

// Without an upload date or a poster Google rejects the node, so it is left out; plain videos are not counted.
$check(bl_article_videos(str_replace(' data-upload-date="2026-10-14T03:55:17+00:00"', '', $film)) === [], 'film without a date was marked up');
$check(bl_article_videos(str_replace(' poster="/wp-content/uploads/2026/10/poster.jpg"', '', $film)) === [], 'film without a poster was marked up');
$check(bl_article_videos('<video src="a.mp4" poster="p.jpg" title="x"></video>') === [], 'a video outside a bl-article-video figure was counted');
$check(bl_article_videos('') === [], 'empty text gave videos');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "ok\n";
