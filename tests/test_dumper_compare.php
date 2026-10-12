<?php
/**
 * Dumper range page: the comparison splits into wheeled and tracked tables, and the catalogue filters by drive.
 * Runs against the shipped sample data. Run: php tests/test_dumper_compare.php
 */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
// Four dumpers shaped like the live records: two wheeled with different drives, a tracked dumper and the tracked mixer.
function bl_wp_products() {
    $make = static fn(string $model, int $load, array $specs) => ['slug' => strtolower($model), 'model' => $model, 'name' => $model . ' dumper', 'category' => 'mini-dumpers', 'image' => '', 'gallery' => [], 'excerpt' => '', 'load' => $load, 'specs' => $specs];
    return [
        $make('FY30', 3000, ['Rated load' => '3,000 kg', 'Hopper capacity' => '1.2 m³', 'Drive' => '4×4', 'Tipping' => 'Hydraulic', 'Dimensions with roof / cab' => '2,300 mm']),
        $make('FC10', 1000, ['Rated load' => '1,000 kg', 'Hopper capacity' => '0.5 m³', 'Drive' => '4×2', 'Tipping' => 'Mechanical']),
        $make('BL-T500-HD', 500, ['Rated load' => '500 kg', 'Hopper capacity' => '0.3 m³', 'Running gear' => 'Rubber tracks', 'Tipping' => 'Hydraulic']),
        $make('BL-T200-HJ', 0, ['Drum capacity' => '200 L', 'Running gear' => 'Rubber tracks']),
        ['category' => 'skid-steer-loaders'] + $make('BL-T360-H', 360, ['Rated load' => '360 kg', 'Running gear' => 'Rubber tracks']),
    ];
}
function get_permalink($post) { return '/'; }

$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$render = static function (string $category): string {
    ob_start();
    (static function (string $compareCategory) { require __DIR__ . '/../templates/partials/compare-table.php'; })($category);
    return ob_get_clean();
};

    // The dumpers compare in two tables, wheeled then tracked, each keeping only the rows its own models publish.
    $html = $render('mini-dumpers');
    $check(substr_count($html, '<section class="bl-compare') === 2 && str_contains($html, 'id="compare-mini-dumpers"') && str_contains($html, 'id="compare-mini-dumpers-tracked"'), 'dumper comparison is not two tables');
    [$wheeled, $tracked] = explode('id="compare-mini-dumpers-tracked"', $html, 2);
    $check(str_contains($wheeled, '<span>FY30</span>') && !str_contains($wheeled, 'BL-T500-HD') && !str_contains($wheeled, 'BL-T200-HJ'), 'tracked models leaked into the wheeled table');
    $check(str_contains($tracked, '<span>BL-T500-HD</span>') && str_contains($tracked, '<span>BL-T200-HJ</span>') && !str_contains($tracked, '<span>FY30</span>'), 'wheeled models leaked into the tracked table');
    $check(str_contains($tracked, 'Drum capacity') && !str_contains($wheeled, 'Drum capacity'), 'a row only one kind publishes sits in the wrong table');
    $check(str_contains($wheeled, '<th scope="row">Drive</th>') && !str_contains($tracked, '<th scope="row">Drive</th>'), 'drive row misplaced');
    $check(substr_count($html, 'id="compare-mini-dumpers-title"') === 1, 'duplicate or missing heading ids');
    $loaders = $render('skid-steer-loaders');
    $check(substr_count($loaders, '<section class="bl-compare') === 1 && !str_contains($loaders, 'bl-compare-kind'), 'the loader comparison changed');

    $facet = static fn(array $specs): string => bl_product_facets(['category' => 'mini-dumpers', 'specs' => $specs, 'load' => 3000], 'mini-dumpers')['drive'];
    $check($facet(['Drive' => '4×4']) === '4x4' && $facet(['Drive' => '4×2']) === '4x2' && $facet(['Drive' => '4x4 hydraulic']) === '4x4', 'drive facet misreads the spec');
    $check($facet([]) === '' && $facet(['Drive' => 'Rubber tracks']) === '', 'tracked models should have no drive facet');
    $filters = bl_catalog_filters('mini-dumpers');
    $check(array_keys($filters['drive']['options']) === ['4x2', '4x4'], 'dumper drive filter missing');
    $check(!isset(bl_catalog_filters('skid-steer-loaders')['drive']), 'loaders got a drive filter');
    $counts = [];
    foreach (bl_products() as $p) if ($p['category'] === 'mini-dumpers') { $d = bl_product_facets($p, 'mini-dumpers')['drive']; $counts[$d] = ($counts[$d] ?? 0) + 1; }
    $check($counts === ['4x4' => 1, '4x2' => 1, '' => 2], 'drive facets of the sample dumpers: ' . json_encode($counts));

if ($failures) exit(1);
echo "dumper comparison and drive filter ok\n";
