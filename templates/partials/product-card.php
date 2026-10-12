<?php
$cardSpecs = bl_key_specs($product, true) ?: ['Running gear' => $product['specs']['Running gear'] ?? '—', 'Controls' => $product['specs']['Controls'] ?? '—', 'Spec sheet' => 'On request'];
// The model is the card title, so the line under it is the rest of the product name ("Tracked Stand-on Mini Skid Steer Loader").
$cardLine = trim(preg_replace('/^' . preg_quote($product['model'], '/') . '\s*/', '', $product['name'])) ?: $product['name'];
// The picture comes in the sizes the card shows it at: a row on a phone's range page holds it about 124 pixels wide.
$cardSrcset = $product['image'] ? bl_image_srcset($product['image'], [240, 400, 600, 800, 1200]) : '';
$cardSizes = str_contains($attributes ?? '', 'data-catalog-card') ? '(max-width: 600px) 124px, (max-width: 800px) 50vw, (max-width: 1500px) 33vw, 25vw' : '(max-width: 600px) 100vw, (max-width: 800px) 50vw, 33vw';
?>
<article class="bl-product-card"<?= $attributes ?? '' ?>>
  <a class="bl-product-card-image" href="<?= bl_escape(bl_product_url($product)) ?>" tabindex="-1" aria-hidden="true"><?php if ($product['image']): ?><img<?= $cardSrcset !== '' ? ' srcset="' . bl_escape($cardSrcset) . '" sizes="' . $cardSizes . '"' : '' ?> src="<?= bl_escape($product['image']) ?>" alt="<?= bl_escape($product['name']) ?>" width="800" height="650" loading="lazy"><?php else: ?><span class="bl-product-image-empty">Photo coming soon</span><?php endif; ?><span class="bl-card-arrow"><?= bl_icon('arrow-up-right') ?></span></a>
  <div class="bl-product-card-content"><<?= $heading ?>><a href="<?= bl_escape(bl_product_url($product)) ?>"><?= bl_escape($product['model']) ?></a></<?= $heading ?>><p class="bl-product-card-line"><?= bl_escape($cardLine) ?></p>
    <dl><?php foreach ($cardSpecs as $label => $value): ?><div><dt><?= $label ?></dt><dd><?= bl_escape($value) ?></dd></div><?php endforeach; ?></dl>
  </div>
</article>
