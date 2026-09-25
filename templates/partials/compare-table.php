<?php
/** Side-by-side published figures for one family ($compareCategory), lightest rated load first. Models without a rated load (the mixer) stay on their own page. */
$compareSets = [
    'skid-steer-loaders' => ['label' => 'SKID STEER LOADERS', 'rows' => ['Rated load', 'Lifting capacity', 'Bucket capacity', 'Engine power', 'Machine weight', 'Travel speed', 'Gradeability', 'Machine width', 'Overall height', 'Length with bucket', 'Running gear'], 'note' => 'Figures from the BOB-LIFT technical sheets. Engine brand and attachments are set per order, so confirm your configuration with our team.'],
    'mini-dumpers' => ['label' => 'MINI DUMPERS', 'rows' => ['Rated load', 'Bucket capacity', 'Engine power', 'Standard dimensions', 'Dimensions with roof / cab', 'Operating voltage', 'Minimum ground clearance'], 'note' => 'Figures transcribed from the BOB-LIFT product pages and technical sheets. Confirm the final configuration with our team before ordering.'],
];
$compareSet = $compareSets[$compareCategory];
$compareProducts = array_values(array_filter(bl_products(), static fn($p) => $p['category'] === $compareCategory && $p['specs'] && ($p['load'] ?? 0) > 0));
usort($compareProducts, static fn($a, $b) => $a['load'] <=> $b['load']);
// A row only earns its place if at least one model publishes it.
$compareRows = array_values(array_filter($compareSet['rows'], static fn($row) => array_filter($compareProducts, static fn($p) => isset($p['specs'][$row]))));
if ($compareProducts): ?>
<section class="bl-compare bl-shell" id="compare-<?= $compareCategory ?>" aria-labelledby="compare-<?= $compareCategory ?>-title">
  <div class="bl-small-section-heading"><div><p class="bl-eyebrow">PUBLISHED FIGURES / <?= $compareSet['label'] ?></p><h2 id="compare-<?= $compareCategory ?>-title">COMPARE THE RANGE.</h2></div><p class="bl-compare-note"><?= $compareSet['note'] ?></p></div>
  <div class="bl-compare-scroll"><table class="bl-compare-table"><thead><tr><th scope="col">Model</th><?php foreach ($compareProducts as $p): ?><th scope="col"><a href="<?= bl_escape(bl_product_url($p)) ?>"><?php if ($p['image']): ?><img src="<?= bl_escape($p['image']) ?>" alt="" width="200" height="200" loading="lazy"><?php else: ?><i class="bl-compare-noimage">Photo coming soon</i><?php endif; ?><span><?= bl_escape($p['model']) ?></span><small><?= bl_escape($p['name']) ?></small></a></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($compareRows as $row): ?><tr><th scope="row"><?= $row ?></th><?php foreach ($compareProducts as $p): ?><td><?= bl_escape($p['specs'][$row] ?? '—') ?></td><?php endforeach; ?></tr><?php endforeach; ?><tr class="bl-compare-actions"><th scope="row"><span class="bl-visually-hidden">Enquire</span></th><?php foreach ($compareProducts as $p): ?><td><a class="bl-text-link" href="<?= bl_escape(bl_contact_url($p)) ?>">Quote <?= bl_icon('arrow-up-right') ?></a></td><?php endforeach; ?></tr></tbody></table></div>
</section>
<?php endif; ?>
