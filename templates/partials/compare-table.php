<?php
/**
 * Side-by-side published figures for every model in one family ($compareCategory), lightest rated load first and models
 * without a rated load (the mixer) last. The listed rows lead in buyer order; any other figure a model publishes follows,
 * so a model or spec added in wp-admin or through the API shows up here without a code change.
 */
$compareSets = [
    'skid-steer-loaders' => ['label' => strtoupper(bl_family_label('loader')), 'rows' => ['Rated load', 'Lifting capacity', 'Bucket capacity', 'Engine power', 'Machine weight', 'Machine width', 'Overall height', 'Length with bucket', 'Gradeability', 'Travel speed', 'Running gear'], 'note' => 'Figures from the BOB-LIFT technical sheets. Engine brand and attachments are set per order, so confirm your configuration with our team.'],
    'mini-dumpers' => ['label' => strtoupper(bl_family_label('dumper')), 'rows' => ['Rated load', 'Hopper capacity', 'Engine power', 'Machine weight', 'Standard dimensions', 'Drive', 'Max travel speed', 'Gradeability', 'Drum capacity', 'Running gear', 'Tipping', 'Dimensions with roof / cab', 'Operating voltage', 'Minimum ground clearance'], 'note' => bl_escape(bl_editable('dumpers')['compare']['note'])],
];
$compareSet = $compareSets[$compareCategory];
$compareProducts = array_values(array_filter(bl_products(), static fn($p) => $p['category'] === $compareCategory && $p['specs']));
usort($compareProducts, static fn($a, $b) => [!($a['load'] ?? 0), $a['load'] ?? 0, $a['model']] <=> [!($b['load'] ?? 0), $b['load'] ?? 0, $b['model']]);
// A row only earns its place if at least one model publishes it; figures outside the listed rows follow in the order models publish them.
// The first eight rows show on phones, so the listed rows lead with the figures that tell the models apart.
$compareRows = $compareSet['rows'];
foreach ($compareProducts as $p) foreach (array_keys($p['specs']) as $row) if (!in_array($row, $compareRows, true)) $compareRows[] = $row;
// One figure published under two names (bl_spec_synonyms) is one row, under the name most models use; each model's
// value is read under whichever name it publishes. Only the display changes, never the data; where a model publishes
// both names with different figures, the two stay rows of their own.
$compareUses = static fn(string $row): int => count(array_filter($compareProducts, static fn($p) => isset($p['specs'][$row])));
$compareLabel = [];
foreach ($compareRows as $row) {
    $names = array_values(array_filter(bl_spec_synonyms($row, $compareProducts), $compareUses));
    // Ties fall to the name listed first in BL_SPEC_SYNONYMS, so every name of a group settles on the same label.
    $compareRank = static fn(string $name): int => (int) array_search($name, array_merge(...BL_SPEC_SYNONYMS), true);
    usort($names, static fn($a, $b) => [$compareUses($b), $compareRank($a)] <=> [$compareUses($a), $compareRank($b)]);
    $compareLabel[$row] = $names[0] ?? $row;
}
$compareRows = array_values(array_unique(array_map(static fn($row) => $compareLabel[$row], $compareRows)));
$compareRows = array_values(array_filter($compareRows, static fn($row) => array_filter($compareProducts, static fn($p) => bl_spec_value($p, $row, $compareProducts) !== null)));
// The listed rows lead, in their order, those most models publish first and a figure only one or two publish after them;
// every other figure follows in the order the models publish it.
$compareCount = static fn(string $row): int => count(array_filter($compareProducts, static fn($p) => bl_spec_value($p, $row, $compareProducts) !== null));
$compareListed = static fn(string $row): bool => (bool) array_intersect(bl_spec_synonyms($row, $compareProducts), $compareSet['rows']);
$compareRows = [
    ...array_filter($compareRows, static fn($row) => $compareListed($row) && $compareCount($row) * 2 >= count($compareProducts)),
    ...array_filter($compareRows, static fn($row) => $compareListed($row) && $compareCount($row) * 2 < count($compareProducts)),
    ...array_filter($compareRows, static fn($row) => !$compareListed($row)),
];
if ($compareProducts): ?>
<section class="bl-compare bl-shell" id="compare-<?= $compareCategory ?>" aria-labelledby="compare-<?= $compareCategory ?>-title">
  <div class="bl-small-section-heading"><div><p class="bl-eyebrow">PUBLISHED FIGURES / <?= bl_escape($compareSet['label']) ?></p><h2 id="compare-<?= $compareCategory ?>-title">COMPARE THE RANGE.</h2></div><p class="bl-compare-note"><?= $compareSet['note'] ?></p></div>
  <div class="bl-compare-scroll" id="compare-<?= $compareCategory ?>-table"><table class="bl-compare-table"><thead><tr><th scope="col">Model</th><?php foreach ($compareProducts as $p): ?><th scope="col"><a href="<?= bl_escape(bl_product_url($p)) ?>"><?php if ($p['image']): ?><img src="<?= bl_escape($p['image']) ?>" alt="" width="200" height="200" loading="lazy"><?php else: ?><i class="bl-compare-noimage">Photo coming soon</i><?php endif; ?><span><?= bl_escape($p['model']) ?></span><small><?= bl_escape($p['name']) ?></small></a></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($compareRows as $row): ?><tr><th scope="row"><?= bl_escape($row) ?></th><?php foreach ($compareProducts as $p): $value = bl_spec_value($p, $row, $compareProducts) ?? '—'; ?><td<?= preg_match('/^[\d.,]+(\s*[–-]\s*[\d.,]+)?\s*\S{0,5}$/u', $value) ? ' class="bl-compare-figure"' : '' ?>><?= bl_escape($value) ?></td><?php endforeach; ?></tr><?php endforeach; ?><tr class="bl-compare-actions"><th scope="row"><span class="bl-visually-hidden">Enquire</span></th><?php foreach ($compareProducts as $p): ?><td><a class="bl-text-link" href="<?= bl_escape(bl_contact_url($p)) ?>">Quote <?= bl_icon('arrow-up-right') ?></a></td><?php endforeach; ?></tr></tbody></table></div>
  <button class="bl-compare-more" type="button" aria-controls="compare-<?= $compareCategory ?>-table" aria-expanded="false" hidden><span>Show all specs</span> <?= bl_icon('arrow-down') ?></button>
</section>
<?php endif; ?>
