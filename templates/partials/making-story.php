<?php
/**
 * How the loaders are made, on the skid steer loader family page. Workshop photography from both loader production
 * halls (厂1, 厂2) at the BOB-LIFT factory in Xuzhou, confirmed by BOB-LIFT 2026-09-24. No dumper production
 * photography has been supplied yet, so the dumper page has no equivalent section.
 */
$making = bl_editable('loaders')['making'];
$makingSteps = $making['steps'];
$makingStockImages = array_column(bl_loader_story_definition()['defaults']['making']['steps'], 'image');
$makingStockImages = [...$makingStockImages, ...array_map(static fn($image) => preg_replace('~^https?://[^/]+~', '', $image), $makingStockImages)];
?>
<section class="bl-making" id="how-they-are-made" aria-labelledby="making-title">
  <div class="bl-making-layout bl-shell">
    <div class="bl-making-aside">
      <p class="bl-eyebrow"><?= bl_content_text('loaders', 'making.eyebrow') ?></p>
      <h2 id="making-title"><?= bl_content_html('loaders', 'making.title') ?></h2>
      <p><?= bl_content_text('loaders', 'making.intro') ?></p>
      <ol class="bl-making-steps"><?php foreach ($makingSteps as $i => $step): ?><li><a href="#making-<?= $i + 1 ?>" data-making-step<?= $i === 0 ? ' aria-current="step"' : '' ?>><span>0<?= $i + 1 ?></span><?= bl_escape($step['title']) ?></a></li><?php endforeach; ?></ol>
    </div>
    <div class="bl-making-figures" role="region" aria-labelledby="making-title" data-swipe-row>
      <?php foreach ($makingSteps as $i => $step): $src = bl_product_media($step['image']); $stock = in_array($step['image'], $makingStockImages, true); $small = $stock ? bl_asset('images/about/' . basename((string) parse_url(str_replace('-1600.webp', '-900.webp', $step['image']), PHP_URL_PATH))) : $src; ?>
      <figure class="bl-making-figure bl-reveal" id="making-<?= $i + 1 ?>">
        <?php if ($src !== ''): ?><img <?php if ($stock): ?>srcset="<?= bl_escape($small) ?> 900w, <?= bl_escape($src) ?> 1600w"<?php endif; ?> sizes="(max-width: 1000px) 100vw, 58vw" src="<?= bl_escape($src) ?>" alt="<?= bl_escape($step['alt']) ?>" width="1600" height="1067" loading="lazy"><?php endif; echo "\n"; ?>
        <figcaption><span>0<?= $i + 1 ?> / 0<?= count($makingSteps) ?></span><strong><?= bl_escape($step['title']) ?></strong><p><?= bl_escape($step['text']) ?></p></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
