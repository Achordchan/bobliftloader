<?php
/** Stand artwork on a story: it replaces the lead picture (cut from it) unless a featured image is set, and text written in WordPress takes it at the section 'after' names, as imported sections do. */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
if (!function_exists('wp_kses_post')) { function wp_kses_post(string $html): string { return $html; } }
$failures = [];
$render = static function (array $changes): string {
    $route = ['record' => array_replace(bl_find_record(bl_articles(), 'canton-fair-2026'), $changes)];
    ob_start(); require __DIR__ . '/../templates/pages/article.php'; return (string) ob_get_clean();
};
$body = '<h2>One</h2><p>a</p><h2>Two</h2><p>b</p><h2>Three</h2><p>c</p>';
$html = $render([]);
if (str_contains($html, 'bl-story-figure')) $failures[] = 'lead picture repeats the stand artwork';
if (!preg_match('~A real machine, outside</h2>.*?bl-story-poster.*?<h2>One machine~s', $html)) $failures[] = 'imported sections lost the artwork position';
$html = $render(['content_html' => $body]);
if (!preg_match('~<h2>One</h2><p>a</p><figure class="bl-story-poster">.*?</figure><h2>Two</h2>~s', $html)) $failures[] = 'WordPress text ignores poster.after';
$html = $render(['content_html' => $body, 'poster' => ['after' => 9] + bl_find_record(bl_articles(), 'canton-fair-2026')['poster']]);
if (!preg_match('~<p>c</p><figure class="bl-story-poster">~', $html)) $failures[] = 'artwork not placed after the text when the section is missing';
$html = $render(['content_html' => $body, 'image_id' => 7]);
if (!str_contains($html, 'bl-story-figure')) $failures[] = 'featured image hidden';
$html = $render(['poster' => [], 'content_html' => $body]);
if (!str_contains($html, 'bl-story-figure') || str_contains($html, 'bl-story-poster')) $failures[] = 'story without artwork lost its lead picture';
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "article poster ok\n";
