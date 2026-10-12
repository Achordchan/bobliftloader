<?php
/** A story's contents list: built from its <h2> headings (WordPress text or imported sections), ids kept or added, shown from three headings up. */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
if (!function_exists('wp_kses_post')) { function wp_kses_post(string $html): string { return $html; } }
$failures = [];
$render = static function (array $changes): string {
    $route = ['record' => array_replace(bl_find_record(bl_articles(), 'canton-fair-2026'), $changes)];
    ob_start(); require __DIR__ . '/../templates/pages/article.php'; return (string) ob_get_clean();
};
$list = bl_story_contents('<p>x</p><h2>Loads &amp; limits</h2><p>a</p><h2 class="k" id="kept">Kept id</h2><h2>Loads &amp; limits</h2><h2><em>4x4</em> drive</h2><h2> </h2>');
if (array_column($list['items'], 0) !== ['loads-limits', 'kept', 'loads-limits-2', '4x4-drive']) $failures[] = 'ids: ' . json_encode($list['items']);
if (array_column($list['items'], 1)[0] !== 'Loads & limits') $failures[] = 'entry text not decoded';
if (!str_contains($list['html'], '<h2 id="loads-limits">Loads &amp; limits</h2>') || !str_contains($list['html'], '<h2 class="k" id="kept">')) $failures[] = 'ids not written into the text';
if (bl_story_contents('<p>no headings</p>')['items'] !== []) $failures[] = 'headings invented';
// A generated id never takes one the text (or the page) already uses, whichever heading comes first; entities in a kept id are decoded.
$list = bl_story_contents('<h2>Overview</h2><h2 id="overview">Details</h2><h2 id="research&amp;development">R</h2><h2 id="100%-capacity">C</h2><h2>Top</h2>');
if (array_column($list['items'], 0) !== ['overview-2', 'overview', 'research&development', '100%-capacity', 'top-2']) $failures[] = 'reserved ids: ' . json_encode($list['items']);
// ids stay aligned with the headings when one has no text
$list = bl_story_contents('<h2>A</h2><h2> </h2><h2>C</h2><h2>D</h2>');
if ($list['ids'] !== ['a', null, 'c', 'd']) $failures[] = 'ids misaligned: ' . json_encode($list['ids']);
$html = $render(['content_html' => '<h2>One</h2><h2 id="100%-capacity">Two</h2><h2>Three</h2>']);
if (!str_contains($html, 'href="#100%25-capacity"')) $failures[] = 'fragment not URL-encoded';
$html = $render(['content_html' => '', '_bl_body_managed' => false, 'sections' => [['heading' => 'First', 'text' => 'a'], ['heading' => ' ', 'text' => 'b'], ['heading' => 'Third', 'text' => 'c'], ['heading' => 'Fourth', 'text' => 'd'], ['heading' => 'Fifth', 'text' => 'e']]]);
if (!str_contains($html, '<h2 id="third">Third</h2>') || !str_contains($html, '<h2 id="fifth">Fifth</h2>') || !str_contains($html, 'href="#fourth"')) $failures[] = 'sections with an empty heading got wrong ids';
$html = $render(['content_html' => '<h2>One</h2><p>a</p><h2>Two</h2><p>b</p><h2>Three</h2><p>c</p>']);
if (substr_count($html, 'data-story-contents') !== 2 || !str_contains($html, '<h2 id="two">Two</h2>') || !str_contains($html, 'href="#three"')) $failures[] = 'contents not rendered for WordPress text';
$html = $render(['content_html' => '<h2>One</h2><p>a</p><h2>Two</h2><p>b</p>']);
if (str_contains($html, 'data-story-contents')) $failures[] = 'two headings should not make a list';
$html = $render([]);
if (!preg_match_all('~<h2 id="([^"]+)">~', $html, $ids) || !str_contains($html, 'data-story-contents') || count(array_filter($ids[1], static fn($id) => str_contains($html, 'href="#' . $id . '"'))) < 3) $failures[] = 'imported sections lost their contents';
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "article contents ok\n";
