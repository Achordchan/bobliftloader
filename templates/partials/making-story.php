<?php
/**
 * How the machines are made, on a family page ($category). The loader steps are workshop photography from both loader
 * production halls (厂1, 厂2) at the BOB-LIFT factory in Xuzhou, confirmed by BOB-LIFT 2026-09-24. The dumper steps are
 * photographs and words saved in WordPress (网站内容 → 迷你翻斗车 → 制造流程); with none saved the section is left out.
 */
$makingLoader = $category === 'skid-steer-loaders';
$makingPage = $makingLoader ? 'loaders' : 'dumpers';
$making = bl_editable($makingPage)['making'];
$makingSteps = $making['steps'];
if (!$makingSteps) return;
$makingTitle = $makingLoader ? bl_content_html('loaders', 'making.title') : bl_heading($making['title']);
$makingStockImages = $makingLoader ? array_column(bl_loader_story_definition()['defaults']['making']['steps'], 'image') : [];
$makingStockImages = [...$makingStockImages, ...array_map(static fn($image) => preg_replace('~^https?://[^/]+~', '', $image), $makingStockImages)];
?>
<section class="bl-making" id="how-they-are-made" aria-labelledby="making-title">
  <div class="bl-making-layout bl-shell">
    <div class="bl-making-aside">
      <p class="bl-eyebrow"><?= bl_content_text($makingPage, 'making.eyebrow') ?></p>
      <h2 id="making-title"><?= $makingTitle ?></h2>
      <p><?= bl_content_text($makingPage, 'making.intro') ?></p>
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
