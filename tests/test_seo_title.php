<?php
declare(strict_types=1);
// php tests/test_seo_title.php — a news story or case can carry its own search title (_bl_seo_title): it feeds <title>, og:title
// and twitter:title, falls back to the H1 when empty, never doubles the brand suffix, and leaves the Article headline on the H1.
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$render = static function (array $route): string { ob_start(); bl_render_seo($route); return ob_get_clean(); };
$story = static fn(string $seoTitle): array => ['type' => 'article', 'path' => '/news/oem-mini-dumper/', 'status' => 200, 'title' => 'OEM Mini Dumpers: Your Logo, Colour, MOQ and Lead Time', 'description' => 'OEM', 'record' => ['title' => 'OEM Mini Dumpers: Your Logo, Colour, MOQ and Lead Time', 'seo_title' => $seoTitle, 'excerpt' => 'OEM', 'image' => '/wp-content/uploads/cover.webp', 'date' => '2026-11-10', 'source' => '', 'content_html' => '']];
$tags = static function (string $head, string $title) use ($check): void {
    $escaped = bl_escape($title);
    $check(str_contains($head, '<title>' . $escaped . '</title>'), 'title: expected ' . $title . ' in ' . $head);
    $check(str_contains($head, '<meta property="og:title" content="' . $escaped . '">'), 'og:title does not match ' . $title);
    $check(str_contains($head, '<meta name="twitter:title" content="' . $escaped . '">'), 'twitter:title does not match ' . $title);
};

// Empty (or only spaces): the H1 with the brand, as before the field existed.
foreach (['', '   '] as $empty) $tags($render($story($empty)), 'OEM Mini Dumpers: Your Logo, Colour, MOQ and Lead Time | BOB-LIFT');
$tags($render(['seo_title' => null] + $story('')), 'OEM Mini Dumpers: Your Logo, Colour, MOQ and Lead Time | BOB-LIFT');

// Filled: the search title replaces the H1 in the head, the brand is added once.
$head = $render($story('OEM Mini Dumper Manufacturer in China'));
$tags($head, 'OEM Mini Dumper Manufacturer in China | BOB-LIFT');
$check(!str_contains($head, '<title>OEM Mini Dumpers: Your Logo'), 'the H1 still leads the title');

// Already branded: used as written, never "| BOB-LIFT | BOB-LIFT".
foreach (['OEM Mini Dumper Manufacturer | BOB-LIFT', 'OEM Mini Dumper Manufacturer | BOB-LIFT Loader', 'OEM Mini Dumper Manufacturer – BOB-LIFT', 'OEM Mini Dumper Manufacturer |BOB-LIFT  '] as $branded) {
    $title = bl_seo_title($story($branded));
    $check($title === trim($branded), 'branded title changed: ' . $branded . ' => ' . $title);
    $check(substr_count($title, 'BOB-LIFT') === 1, 'brand doubled: ' . $title);
}
// The brand inside the text (not as a suffix) still gets the suffix.
$check(bl_seo_title($story('Why BOB-LIFT Dumpers Use Kubota Engines')) === 'Why BOB-LIFT Dumpers Use Kubota Engines | BOB-LIFT', 'a mid-title brand was taken as the suffix');

// The Article headline and the WebPage name stay on the H1, the page's visible heading.
$graph = bl_structured_data($story('OEM Mini Dumper Manufacturer in China'))['@graph'];
$article = array_values(array_filter($graph, static fn($node) => $node['@type'] === 'Article'))[0] ?? [];
$check(($article['headline'] ?? '') === 'OEM Mini Dumpers: Your Logo, Colour, MOQ and Lead Time', 'Article headline left the H1: ' . ($article['headline'] ?? ''));

// Cases read the same field; other pages ignore it, and the home page keeps the site name.
$case = ['type' => 'application_case', 'path' => '/application/x/', 'status' => 200, 'title' => 'Case H1', 'description' => 'd', 'record' => ['title' => 'Case H1', 'seo_title' => 'Case Search Title', 'hub' => '', 'excerpt' => 'd', 'image' => '/x.webp', 'date' => '2026-11-10']];
$check(bl_seo_title($case) === 'Case Search Title | BOB-LIFT', 'cases ignore the search title');
$check(bl_seo_title(['type' => 'product', 'title' => 'T390C', 'record' => ['seo_title' => 'Ignored']]) === 'T390C | BOB-LIFT', 'a product read seo_title');
$check(bl_seo_title(['type' => 'home', 'title' => 'Home', 'record' => null]) === 'Home | BOB-LIFT Loader', 'home lost the site name');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "seo title ok\n";
