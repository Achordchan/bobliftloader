<?php
/**
 * The mini dumper family page: how-they-are-made steps and the model-name key come from the saved content, appear only
 * when there is something to show, and the product page decodes dumper names. Run: php tests/test_dumper_page.php
 */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
$GLOBALS['posts'] = [];

$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$render = static function (string $partial, string $category): string {
    ob_start();
    (static function (string $category) use ($partial) { require __DIR__ . '/../templates/partials/' . $partial . '.php'; })($category);
    return ob_get_clean();
};

// bl_editable() keeps each page's content for the request, so every saved-content scenario runs in a process of its own.
$scenario = $argv[1] ?? '';
$steps = [
    ['image' => '/wp-content/uploads/2026/10/frame.jpg', 'title' => 'Welded frames', 'text' => 'A welded frame on the floor.', 'alt' => 'A welded steel chassis frame'],
    ['image' => '/wp-content/uploads/2026/10/racks.jpg', 'title' => 'Ready to load', 'text' => 'Machines strapped on racks.', 'alt' => 'Tracked dumpers on racks'],
];
if ($scenario === 'steps') {
    $GLOBALS['options']['bl_content_dumpers']['making'] = ['steps' => $steps, 'eyebrow' => 'HOW OUR DUMPERS ARE MADE', 'title' => "FROM STEEL\nTO *SHIPMENT.*"];
    $html = $render('making-story', 'mini-dumpers');
    $check(str_contains($html, 'id="how-they-are-made"') && str_contains($html, '>Welded frames</a>') && str_contains($html, 'src="/wp-content/uploads/2026/10/racks.jpg"'), 'saved dumper steps not rendered');
    $check(str_contains($html, 'TO <span class="bl-blue-text">SHIPMENT.</span>') && str_contains($html, 'FROM STEEL<br>'), 'dumper heading lost its line break or accent');
    $check(!str_contains($html, '900w'), 'dumper photographs got fabricated responsive variants');
    $check(str_contains($html, '02 / 02'), 'step numbering missing');
    $category = 'mini-dumpers';
    ob_start(); $scope = []; require __DIR__ . '/../templates/partials/section-nav.php'; $nav = ob_get_clean();
    $check(str_contains($nav, 'href="#how-they-are-made"') && str_contains($nav, 'href="#model-names"'), 'dumper page navigation lacks the new sections');
} elseif ($scenario === 'one') {
    $GLOBALS['options']['bl_content_dumpers']['names'] = ['wheeled' => []];
    $check(count(bl_dumper_name_groups()) === 1 && !str_contains($render('dumper-names', 'mini-dumpers'), 'FJZ') && str_contains($render('dumper-names', 'mini-dumpers'), '<dt>BL</dt>'), 'an emptied group still shows');
} elseif ($scenario === 'none') {
    $GLOBALS['options']['bl_content_dumpers']['names'] = ['wheeled' => [], 'tracked' => []];
    $check(bl_dumper_name_groups() === [] && trim($render('dumper-names', 'mini-dumpers')) === '', 'an empty key still shows');
    $category = 'mini-dumpers';
    ob_start(); $scope = []; require __DIR__ . '/../templates/partials/section-nav.php'; $nav = ob_get_clean();
    $check(!str_contains($nav, 'href="#model-names"') && !str_contains($nav, 'href="#how-they-are-made"'), 'navigation links to sections that are not shown');
} elseif ($scenario === '') {
    foreach (['steps', 'one', 'none'] as $name) {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $name . ' 2>&1', $out, $code);
        if ($code !== 0) { $failures++; fwrite(STDERR, implode("\n", $out) . "\n"); }
        $out = [];
    }
    // With no saved steps the how-they-are-made section is left out, on the dumper page only.
    $check(bl_content_defaults('dumpers')['making']['steps'] === [], 'dumper steps ship with the theme');
    $check(trim($render('making-story', 'mini-dumpers')) === '', 'dumper steps section shown with no steps');
    $check(str_contains($render('making-story', 'skid-steer-loaders'), 'id="how-they-are-made"'), 'loader steps section missing');
    // The model-name key shows both groups by default.
    $check(count(bl_dumper_name_groups()) === 2, 'default name key is not two groups');
    $html = $render('dumper-names', 'mini-dumpers');
    $check(str_contains($html, 'id="model-names"') && str_contains($html, '<dt>FY</dt>') && str_contains($html, 'BL-T200-HJ mixing drum') && str_contains($html, '<i>/</i>'), 'name key missing its parts');
}
if ($scenario !== '') { if ($failures) exit(1); exit(0); }

// Product pages decode the dumper names.
$check(bl_model_parts('FY30') === [['FY', 'Hydraulic tipping'], ['30', '3,000 kg load']], 'FY30 not decoded');
$check(bl_model_parts('FY20-4')[2] === ['-4', 'Four-wheel drive'] && bl_model_parts('FY20-2')[2] === ['-2', 'Two-wheel drive'], 'FY20 drive not decoded');
$check(bl_model_parts('FJZ30')[0] === ['FJZ', 'Self-loading'] && bl_model_parts('FC10')[1] === ['10', '1,000 kg load'], 'FJZ or FC not decoded');
$check(bl_model_parts('BL-T500-HD')[2] === ['HD', 'Dumping'] && bl_model_parts('BL-T200-HJ')[1] === ['200', 'Drum, litres'], 'tracked names not decoded');
$check(bl_model_parts('BL-T360-H')[0] === ['T', 'Tracked'] && bl_model_parts('Unknown') === null, 'loader names or unknown names changed');

// The editor offers both new sections.
$titles = array_column(bl_content_schema('dumpers'), 'title');
$check(in_array('制造流程', $titles, true) && in_array('型号命名', $titles, true), 'editor sections missing');

if ($failures) exit(1);
echo "OK\n";
