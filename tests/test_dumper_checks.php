<?php
/**
 * The wheeled site dumpers show their own factory checks once a list is saved; the tracked models and any page without
 * that list keep the family's checks. Each saved-content scenario runs in its own process (bl_editable() caches per
 * request). Run: php tests/test_dumper_checks.php
 */
declare(strict_types=1);
define('BL_TEST_REAL_PRODUCT_READER', true);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-content-editor.php';
function bl_wp_products() { return []; }

$failures = 0;
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) { $failures++; fwrite(STDERR, "FAIL: $message\n"); } };
$wheeled = ['category' => 'mini-dumpers', 'model' => 'FY30', 'slug' => 'fy30', 'specs' => ['Drive' => '4×4']];
$tracked = ['category' => 'mini-dumpers', 'model' => 'BL-T500-HD', 'slug' => 't500', 'specs' => ['Running gear' => 'Rubber tracks']];
$loader = ['category' => 'skid-steer-loaders', 'model' => 'BL-T360-H', 'slug' => 't360', 'specs' => ['Running gear' => 'Rubber tracks']];
$titles = static fn(array $product): array => array_column(bl_product_factory($product)['checks'] ?? [], 'title');
$shared = ['Check before start-up', 'Run and travel test', 'Leaks, cooling and final check'];

$scenario = $argv[1] ?? '';
if ($scenario === 'saved') {
    $GLOBALS['options']['bl_content_dumpers']['product']['factory']['checks_wheeled'] = [
        ['title' => 'Appearance check', 'text' => 'We inspect the machine.'], ['title' => '', 'text' => 'a row with no name is dropped'], ['title' => 'Tyre pressure', 'text' => ''],
    ];
    $check($titles($wheeled) === ['Appearance check', 'Tyre pressure'], 'wheeled dumper does not show its own checks: ' . json_encode($titles($wheeled)));
    $check($titles($tracked) === $shared, 'tracked dumper lost the family checks');
    $check(!array_key_exists('checks_wheeled', bl_product_factory($wheeled)), 'the wheeled list leaks into the block');
} elseif ($scenario === 'none') {
    $check($titles($wheeled) === $shared && $titles($tracked) === $shared, 'without a wheeled list every dumper shows the family checks');
    $check($titles($loader) === ['Check before start-up', 'Run and travel test', 'Leaks, cooling and final check'], 'loader checks changed');
    $fields = array_merge(...array_map(static fn($section) => array_column($section['fields'] ?? [], 'key'), bl_content_schema('dumpers')));
    $check(in_array('product.factory.checks_wheeled', $fields, true) && !in_array('product.factory.checks_wheeled', array_merge(...array_map(static fn($section) => array_column($section['fields'] ?? [], 'key'), bl_content_schema('loaders'))), true), 'editor field belongs to the dumper page only');
} else {
    foreach (['saved', 'none'] as $name) {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $name . ' 2>&1', $out, $code);
        if ($code !== 0) { $failures++; fwrite(STDERR, implode("\n", $out) . "\n"); }
        $out = [];
    }
    if ($failures) exit(1);
    echo "dumper factory checks ok\n";
    exit(0);
}
exit($failures ? 1 : 0);
