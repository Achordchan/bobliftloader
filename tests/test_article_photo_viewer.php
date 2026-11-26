<?php
/** The story's zoom viewer: it is on the page for a story with pictures to open, the pictures in the text keep their
 *  markup for the script to mark, and a media-library picture placed at a smaller size names its original upload. */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
if (!function_exists('wp_kses_post')) { function wp_kses_post(string $html): string { return $html; } }
// Attachment 42 is a picture of ordinary size; 43 a large upload WordPress reduced to a -scaled copy, its original kept.
if (!function_exists('wp_get_attachment_url')) { function wp_get_attachment_url(int $id) { return ['42' => 'https://example.test/wp-content/uploads/2026/11/chart.png', '43' => 'https://example.test/wp-content/uploads/2026/11/plan-scaled.png'][(string) $id] ?? false; } }
if (!function_exists('wp_get_original_image_url')) { function wp_get_original_image_url(int $id) { return ['42' => 'https://example.test/wp-content/uploads/2026/11/chart.png', '43' => 'https://example.test/wp-content/uploads/2026/11/plan.png'][(string) $id] ?? false; } }
$failures = [];
$render = static function (array $changes): string {
    $route = ['record' => array_replace(bl_find_record(bl_articles(), 'canton-fair-2026'), $changes)];
    ob_start(); require __DIR__ . '/../templates/pages/article.php'; return (string) ob_get_clean();
};
$viewer = static fn(string $html): string => preg_match('~<dialog class="bl-photo-viewer" id="story-lightbox".*?</dialog>~s', $html, $m) ? $m[0] : '';

// Lead picture and artwork, as before: the viewer is there, with its zoom buttons, and the old lightbox markup is gone.
$html = $render(['hide_cover' => false]);
$dialog = $viewer($html);
if ($dialog === '') $failures[] = 'no viewer for a story with a lead picture';
foreach (['data-viewer-stage', 'data-viewer-image', 'data-viewer-zoom="in"', 'data-viewer-zoom="out"', 'data-viewer-zoom="fit"', 'data-viewer-level', 'data-lightbox-close', 'data-lightbox-step="-1"', 'data-lightbox-step="1"'] as $part) {
    if (!str_contains($dialog, $part)) $failures[] = 'viewer lacks ' . $part;
}
if (str_contains($html, 'class="bl-lightbox"')) $failures[] = 'old lightbox still on the page';
if (substr_count($html, 'data-lightbox-open=') !== 2 || substr_count($html, 'data-lightbox-item=') !== 2) $failures[] = 'lead picture and artwork lost their open buttons';
foreach (['minus', 'plus', 'corners-out', 'x', 'arrow-left'] as $icon) {
    if (!is_file(__DIR__ . '/../assets/icons/' . $icon . '.svg')) $failures[] = 'missing icon ' . $icon;
}

// Text written in WordPress with pictures: the viewer is there, and the pictures keep their markup for the script.
$text = '<h2>One</h2><figure class="wp-block-image"><img class="wp-image-42" src="https://example.test/wp-content/uploads/2026/11/chart-1024x640.png" alt="Payload chart"><figcaption>Payload by model</figcaption></figure>'
    . '<figure><img class="bl-chart-wide" src="https://example.test/wide.png" alt="Wide"><img class="bl-chart-tall" src="https://example.test/tall.png" alt="Tall"></figure>';
$html = $render(['poster' => [], 'content_html' => $text]);
if ($viewer($html) === '') $failures[] = 'no viewer for a story with pictures in its text';
// The original is read with WordPress's HTML tag processor; outside WordPress the text is left as it is.
if (class_exists('WP_HTML_Tag_Processor')) {
    if (!str_contains($html, 'data-full-src="https://example.test/wp-content/uploads/2026/11/chart.png"')) $failures[] = 'media-library picture lost its original upload';
    if (substr_count($html, 'data-full-src=') !== 1) $failures[] = 'a picture placed by address was given an original it does not have';
} elseif (str_contains($html, 'data-full-src=')) $failures[] = 'original named without the tag processor';
if (!str_contains($html, 'class="bl-chart-wide"') || !str_contains($html, 'class="bl-chart-tall"')) $failures[] = 'chart drawings lost their classes';

// The original upload, not WordPress's reduced -scaled copy of a large one; nothing for an unknown attachment.
if (bl_attachment_full_url(43) !== 'https://example.test/wp-content/uploads/2026/11/plan.png') $failures[] = 'a scaled upload opens at its reduced copy';
if (bl_attachment_full_url(42) !== 'https://example.test/wp-content/uploads/2026/11/chart.png') $failures[] = 'an ordinary upload lost its address';
if (bl_attachment_full_url(99) !== '') $failures[] = 'an unknown attachment was given an address';

// The helper alone: only an exact wp-image-{id} class counts, and an unknown attachment leaves the picture as it was.
if (function_exists('bl_content_image_full') && class_exists('WP_HTML_Tag_Processor')) {
    $out = bl_content_image_full('<img class="xwp-image-42" src="a.png"><img class="size-large wp-image-7" src="b.png">');
    if (str_contains($out, 'data-full-src')) $failures[] = 'helper matched a class it should not';
}

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "article photo viewer ok\n";
