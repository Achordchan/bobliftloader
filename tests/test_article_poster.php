<?php
/** Stand artwork on a story: it replaces the lead picture (cut from it) unless a featured image is set, and text written in WordPress takes it at the section 'after' names, as imported sections do. */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
if (!function_exists('wp_kses_post')) { function wp_kses_post(string $html): string { return $html; } }
// A featured image's srcset comes from the media library (the image CDN's widths on WordPress.com).
if (!function_exists('wp_get_attachment_image_srcset')) { function wp_get_attachment_image_srcset(int $id, string $size): string { return $id === 7 && $size === 'large' ? 'https://i0.wp.com/example.test/cover.webp?w=768 768w, https://i0.wp.com/example.test/cover.webp?w=1024 1024w' : ''; } }
$failures = [];
$render = static function (array $changes): string {
    $route = ['record' => array_replace(bl_find_record(bl_articles(), 'canton-fair-2026'), $changes)];
    ob_start(); require __DIR__ . '/../templates/pages/article.php'; return (string) ob_get_clean();
};
$body = '<h2>One</h2><p>a</p><h2>Two</h2><p>b</p><h2>Three</h2><p>c</p>';
$html = $render([]);
if (str_contains($html, 'bl-story-figure')) $failures[] = 'lead picture repeats the stand artwork';
$html = $render(['hide_cover' => false]);
if (!str_contains($html, 'bl-story-figure') || !str_contains($html, 'bl-story-poster')) $failures[] = 'a cover not marked as cut from the artwork was hidden';
if (!preg_match('~A real machine, outside</h2>.*?bl-story-poster.*?<h2[^>]*>One machine~s', $html)) $failures[] = 'imported sections lost the artwork position';
$html = $render(['content_html' => $body]);
if (!preg_match('~<h2[^>]*>One</h2><p>a</p><figure class="bl-story-poster">.*?</figure><h2[^>]*>Two</h2>~s', $html)) $failures[] = 'WordPress text ignores poster.after';
$html = $render(['content_html' => $body, 'poster' => ['after' => 9] + bl_find_record(bl_articles(), 'canton-fair-2026')['poster']]);
if (!preg_match('~<p>c</p><figure class="bl-story-poster">~', $html)) $failures[] = 'artwork not placed after the text when the section is missing';
$html = $render(['content_html' => $body, 'image_id' => 7]);
if (!str_contains($html, 'bl-story-figure')) $failures[] = 'featured image hidden';
if (!str_contains($html, '<img srcset="https://i0.wp.com/example.test/cover.webp?w=768 768w, https://i0.wp.com/example.test/cover.webp?w=1024 1024w" sizes="(max-width: 1000px) 100vw, 70vw" src=')) $failures[] = 'featured image lost its srcset';
$html = $render(['content_html' => $body, 'hide_cover' => false]);
if (!preg_match('~<figure class="bl-story-figure"><span class="bl-story-zoom-frame"><img src=~', $html)) $failures[] = 'a cover outside the media library was given a srcset';
$html = $render(['poster' => [], 'content_html' => $body]);
if (!str_contains($html, 'bl-story-figure') || str_contains($html, 'bl-story-poster')) $failures[] = 'story without artwork lost its lead picture';
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "article poster ok\n";
