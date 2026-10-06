<?php
/** A product with more photographs than the page shows keeps its main image, with its own alternative text, in the set. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
$gallery = array_map(static fn($i) => "images/p$i.webp", range(1, 15));
$product = ['name' => 'QA', 'image' => 'images/p14.webp', 'gallery' => $gallery, 'gallery_alt' => ['images/p14.webp' => 'QA main photo']];
$images = bl_product_gallery($product);
$main = bl_product_main_index($product, $images);
$fail = [];
if (count($images) !== BL_CONTENT_LIMITS['gallery']) $fail[] = 'gallery not limited';
if ($main === null || $images[$main]['src'] !== 'images/p14.webp' || $images[$main]['alt'] !== 'QA main photo') $fail[] = 'main image lost its place or alt text';
if ($fail) { fwrite(STDERR, implode("\n", $fail) . "\n"); exit(1); }
echo "gallery limit ok\n";
