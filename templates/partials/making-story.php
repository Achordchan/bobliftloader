<?php
/**
 * How the loaders are made, on the skid steer loader family page. Workshop photography from both loader production
 * halls (厂1, 厂2) at the BOB-LIFT factory in Xuzhou, confirmed by BOB-LIFT 2026-09-24. No dumper production
 * photography has been supplied yet, so the dumper page has no equivalent section.
 */
$makingSteps = [
    ['image' => 'weld', 'title' => 'Cut and weld', 'text' => 'Frames start as steel tube and plate, cut and welded by hand on the fabrication floor.', 'alt' => 'Welder at work on a loader frame at the BOB-LIFT factory in Xuzhou, sparks flying from the torch'],
    ['image' => 'bucket', 'title' => 'Form the bucket', 'text' => 'Buckets are shaped from manganese steel plate and welded before they go to paint.', 'alt' => 'Bare manganese-steel loader bucket on the workshop floor before painting'],
    ['image' => 'paint', 'title' => 'Paint to order', 'text' => 'Frames and lift arms are painted in the colour on each order before they reach the assembly floor.', 'alt' => 'Seated loader frames painted blue for a customer order, waiting for assembly'],
    ['image' => 'hydraulics', 'title' => 'Fit the hydraulics', 'text' => 'Cylinders, hoses and fittings are routed along the lift arms, where the machine does its work.', 'alt' => 'Close-up of a yellow lift arm pivot with a hydraulic cylinder and hose fittings'],
    ['image' => 'assembly', 'title' => 'Assemble', 'text' => 'Frames, engines, tracks and arms come together on the assembly floor, one station at a time.', 'alt' => 'Technicians assembling a black tracked loader frame on the assembly floor'],
    ['image' => 'ready', 'title' => 'Ready to ship', 'text' => 'Finished loaders, each in the colour its customer ordered, wait for packing and export.', 'alt' => 'Finished mini skid steer loaders in customer-ordered red, yellow and blue at the BOB-LIFT factory in Xuzhou'],
];
?>
<section class="bl-making" id="how-they-are-made" aria-labelledby="making-title">
  <div class="bl-making-layout bl-shell">
    <div class="bl-making-aside">
      <p class="bl-eyebrow">HOW OUR LOADERS ARE MADE</p>
      <h2 id="making-title">FROM STEEL<br>TO <span class="bl-blue-text">SHIPMENT.</span></h2>
      <p>Photographed at our factory in Xuzhou, Jiangsu: the steps every loader goes through before it leaves.</p>
      <ol class="bl-making-steps"><?php foreach ($makingSteps as $i => $step): ?><li><a href="#making-<?= $i + 1 ?>" data-making-step<?= $i === 0 ? ' aria-current="step"' : '' ?>><span>0<?= $i + 1 ?></span><?= bl_escape($step['title']) ?></a></li><?php endforeach; ?></ol>
    </div>
    <div class="bl-making-figures" role="region" aria-labelledby="making-title" data-swipe-row>
      <?php foreach ($makingSteps as $i => $step): $base = 'images/about/' . $step['image']; ?>
      <figure class="bl-making-figure bl-reveal" id="making-<?= $i + 1 ?>">
        <img srcset="<?= bl_escape(bl_asset($base . '-900.webp')) ?> 900w, <?= bl_escape(bl_asset($base . '-1600.webp')) ?> 1600w" sizes="(max-width: 1000px) 100vw, 58vw" src="<?= bl_escape(bl_asset($base . '-1600.webp')) ?>" alt="<?= bl_escape($step['alt']) ?>" width="1600" height="1067" loading="lazy">
        <figcaption><span>0<?= $i + 1 ?> / 0<?= count($makingSteps) ?></span><strong><?= bl_escape($step['title']) ?></strong><p><?= bl_escape($step['text']) ?></p></figcaption>
      </figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
