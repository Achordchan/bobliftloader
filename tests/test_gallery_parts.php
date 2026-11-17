<?php
/** The gallery shared out over a product page: a few photographs at the top, the rest in Photos, every drawing in the overview. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
$fail = [];
$hero = BL_CONTENT_LIMITS['hero'];
// Nine photographs and two drawings, the main image eighth.
$gallery = [...array_map(static fn($i) => "images/p$i.webp", range(1, 9)), 'images/side-drawing.webp', 'images/top-drawing.webp'];
$product = ['name' => 'QA', 'model' => 'QA1', 'image' => 'images/p8.webp', 'gallery' => $gallery, 'photos' => [['src' => 'images/extra.webp', 'group' => 'drawing', 'alt' => 'QA drawing']]];
$parts = bl_product_gallery_parts($product);
if (count($parts['hero']) !== $hero) $fail[] = 'top of the page not limited';
if (bl_product_main_index($product, $parts['hero']) === null) $fail[] = 'main image left the top of the page';
if (count($parts['hero']) + count($parts['more']) !== 9) $fail[] = 'a photograph went missing';
if (array_column($parts['more'], 'group') !== array_fill(0, 9 - $hero, 'views')) $fail[] = 'moved photographs not grouped as other views';
if (count($parts['drawings']) !== 3) $fail[] = 'drawings dropped: ' . count($parts['drawings']);
foreach ($parts['hero'] as $image) if (bl_product_is_drawing($image)) $fail[] = 'a drawing at the top of the page';
// A drawing chosen as the main image stays at the top, so the page opens on what it shows.
$product = ['name' => 'QA', 'model' => 'QA1', 'image' => 'images/side-drawing.webp', 'gallery' => ['images/side-drawing.webp', 'images/p1.webp']];
$parts = bl_product_gallery_parts($product);
if (bl_product_main_index($product, $parts['hero']) !== 0 || $parts['drawings']) $fail[] = 'main image drawing moved out of the top';
// Alternative text names a drawing too.
if (!bl_product_is_drawing(['src' => 'images/a.webp', 'alt' => 'FY30 mini dumper dimension drawing: 4500 mm long'])) $fail[] = 'drawing named in alt text not found';
if (bl_product_is_drawing(['src' => 'images/a.webp', 'alt' => 'FY30 tipping its hopper'])) $fail[] = 'photograph taken for a drawing';
if ($fail) { fwrite(STDERR, implode("\n", $fail) . "\n"); exit(1); }
echo "gallery parts ok\n";
