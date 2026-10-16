<?php
/** The hearing-protection note under a loader's specification, edited per family. Run: php tests/test_spec_safety.php */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
function bl_wp_application_cases() { return []; }
function bl_wp_articles() { return []; }
$GLOBALS['posts'] = [];
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$render = static function (array $product): string {
    $product['excerpt'] = bl_product_excerpt($product);
    ob_start(); (static function ($route) { require __DIR__ . '/../templates/pages/product.php'; })(['record' => $product]); return ob_get_clean();
};
$products = bl_read_data('products');
$loaders = array_values(array_filter($products, static fn(array $p): bool => $p['category'] === 'skid-steer-loaders'));
$dumper = array_values(array_filter($products, static fn(array $p): bool => $p['category'] !== 'skid-steer-loaders'))[0];
$warning = 'Noise at the operator position can exceed 85 dB(A) at full engine speed on some models. Always wear hearing protection when operating the machine.';

// bl_editable() keeps each page's content for the whole request, so each saved state runs in its own process.
$scenario = $argv[1] ?? '';
if ($scenario === 'edited') {
    $GLOBALS['options']['bl_content_loaders'] = ['product' => ['copy' => ['spec_safety' => 'Wear <b>ear</b> defenders.']]];
    $check(str_contains($render($loaders[0]), '<p class="bl-spec-safety">Wear &lt;b&gt;ear&lt;/b&gt; defenders.</p>'), 'edited note not shown escaped');
} elseif ($scenario === 'emptied') {
    $GLOBALS['options']['bl_content_loaders'] = ['product' => ['copy' => ['spec_safety' => '']]];
    $check(!str_contains($render($loaders[0]), 'bl-spec-safety'), 'an emptied note still shows');
} elseif ($scenario === 'dumper') {
    $GLOBALS['options']['bl_content_dumpers'] = ['product' => ['copy' => ['spec_safety' => 'Wear hearing protection.']]];
    $check(str_contains($render($dumper), '<p class="bl-spec-safety">Wear hearing protection.</p>'), 'a dumper note written in WordPress is not shown');
} else {
    // Every loader page carries the note under its specification; no figure beyond the 85 dB(A) threshold is published.
    $check($loaders !== [], 'no loaders in the product data');
    foreach ($loaders as $loader) {
        $check(!empty($loader['specs']), $loader['model'] . ' has no specification, so the note has nowhere to sit');
        $check(str_contains($render($loader), '<p class="bl-spec-safety">' . $warning . '</p>'), $loader['model'] . ' page has no hearing-protection note');
    }
    // Dumpers have no default note.
    $check(!str_contains($render($dumper), 'bl-spec-safety'), 'a dumper page shows the loader hearing note');
    // The note is editable per family: new wording is escaped, an emptied field hides it, and dumpers can have their own.
    foreach (['edited', 'emptied', 'dumper'] as $name) {
        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__) . ' ' . $name . ' 2>&1', $out, $code);
        $check($code === 0, $name . ': ' . implode(' ', $out));
        $out = [];
    }
}

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
if ($scenario === '') echo "spec safety: ok\n";
