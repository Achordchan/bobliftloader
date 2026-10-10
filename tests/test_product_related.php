<?php
/** Dumper product pages carry no Jobs block, and the model cards under the comparison say what they are. Run: php tests/test_product_related.php */
declare(strict_types=1);
require __DIR__ . '/../inc/bootstrap.php';
$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };
$render = static function (array $product): string {
    $product['excerpt'] = bl_product_excerpt($product);
    ob_start(); (static function ($route) { require __DIR__ . '/../templates/pages/product.php'; })(['record' => $product]); return ob_get_clean();
};
$products = bl_read_data('products');
$dumpers = array_values(array_filter($products, static fn(array $p): bool => $p['category'] === 'mini-dumpers'));
$loader = array_values(array_filter($products, static fn(array $p): bool => $p['category'] === 'skid-steer-loaders'))[0];
$check(count($dumpers) >= 4, 'fewer than four dumpers to render');

// Every dumper page: the Jobs block and its Jobs link in the section navigation are gone; the applications block stays.
foreach ($dumpers as $dumper) {
    $html = $render($dumper);
    $name = $dumper['slug'];
    $check(!str_contains($html, 'bl-product-jobs') && !str_contains($html, 'Jobs for the'), $name . ' still renders the Jobs block');
    $check(!str_contains($html, 'id="works-with"') && !str_contains($html, 'href="#works-with"'), $name . ' still has a works-with section or nav entry');
    $check(!str_contains($html, 'Also suits'), $name . ' still lists other models that suit a job');
    // A comparison above the cards: the cards carry their own label and the region is named by it.
    if (str_contains($html, 'bl-product-compare') && str_contains($html, 'bl-product-card')) {
        $check(str_contains($html, '<p class="bl-related-more" id="related-more">More models in the range</p>'), $name . ' cards under the comparison have no label');
        $check(str_contains($html, 'aria-labelledby="related-more" data-swipe-row'), $name . ' card row is not named by its label');
    }
}

// Loaders keep their attachments section and its navigation entry.
$html = $render($loader);
$check(str_contains($html, 'id="works-with"') && str_contains($html, 'href="#works-with"'), 'a loader lost its attachments section');
$check(str_contains($html, '>Attachments</a>'), 'the loader navigation lost Attachments');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "product related ok\n";
