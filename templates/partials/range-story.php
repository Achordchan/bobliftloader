<?php
/**
 * Range introduction on a family page, between the catalogue and the comparison: what the family is for, one
 * chart drawn from the published specifications, and what is configured per order. Expects $storyCategory.
 */
$storyProducts = array_values(array_filter(bl_products(), static fn($p) => $p['category'] === $storyCategory));
$number = static fn(string $value): float => preg_match('/([\d,.]+)/', $value, $m) ? (float) str_replace(',', '', $m[1]) : 0;
$inWords = static fn(int $n): string => ['no', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten', 'eleven', 'twelve'][$n] ?? (string) $n;
$rows = [];

if ($storyCategory === 'skid-steer-loaders') {
    foreach ($storyProducts as $p) {
        $body = (int) $number($p['specs']['Machine width'] ?? '');
        $bucket = (int) $number($p['specs']['Bucket width'] ?? '');
        if ($body) $rows[] = ['model' => $p['model'], 'url' => bl_product_url($p), 'value' => $body, 'sub' => $bucket, 'label' => number_format($body) . ' mm', 'note' => $bucket ? 'Bucket ' . number_format($bucket) . ' mm' : '', 'hit' => $body < 1000];
    }
    usort($rows, static fn($a, $b) => [$a['value'], $a['model']] <=> [$b['value'], $b['model']]);
    $scale = ['from' => 800, 'to' => 1300, 'step' => 100, 'tick' => static fn(int $v): string => number_format($v), 'unit' => 'mm', 'marker' => 1000];
    $under = count(array_filter($rows, static fn($r) => $r['hit']));
    $widest = $rows ? max(array_column($rows, 'value')) : 0;
    $narrowest = $rows[0] ?? null;
    $brands = [];
    $electric = false;
    foreach ($storyProducts as $p) foreach (array_filter(array_map('trim', explode(' / ', $p['specs']['Engine options'] ?? ''))) as $option) {
        if (stripos($option, 'electric') !== false) $electric = true; else $brands[$option] = true;
    }
    $brands = array_keys($brands);
    $story = [
        'eyebrow' => 'ABOUT THE LOADERS',
        'title' => 'MADE FOR THE<br>TIGHT SPOTS.',
        'intro' => 'A skid steer turns by running its left and right tracks or wheels at different speeds, so it spins on the spot. We build ours in Xuzhou for yards, gardens, greenhouses and building sites where a full-size loader won’t fit.',
        'chartEyebrow' => 'MACHINE WIDTH',
        'chartTitle' => 'NARROW BY DESIGN.',
        'chartText' => ($under === count($rows) ? 'Every one of our ' . $inWords(count($rows)) . ' loaders is' : ucfirst($inWords($under)) . ' of our ' . $inWords(count($rows)) . ' loaders ' . ($under === 1 ? 'is' : 'are')) . ' under one metre wide across the body' . ($widest >= 1000 ? ', and the widest is ' . number_format($widest) . ' mm' : '') . '.' . ($narrowest && $narrowest['sub'] && $narrowest['sub'] < 1000 ? ' The narrowest, ' . $narrowest['model'] . ', stays under a metre with its bucket fitted too.' : ''),
        'chartNote' => 'Bucket widths vary by model, so check the spec sheet against your narrowest opening.',
        'legend' => ['Body width', 'Bucket width', '1 metre'],
        'options' => [
            ['big' => 'TRACKS <i>/</i> TIRES', 'title' => 'Running gear.', 'text' => 'Rubber tracks for soft ground and slopes, rubber tires for concrete and asphalt.'],
            ['big' => 'STAND-ON <i>/</i> SEATED', 'title' => 'Operator position.', 'text' => 'Step on the platform for quick on-and-off work, or take the seat for longer shifts. The model name tells you which.', 'link' => ['#model-names', 'How our model names work']],
            ['big' => count($brands) . ' <i>BRANDS</i>', 'title' => 'Engine.', 'text' => (count($brands) > 1 ? implode(', ', array_slice($brands, 0, -1)) . ' or ' . end($brands) : implode('', $brands)) . ($electric ? ', with electric versions of selected models' : '') . '.'],
        ],
        'colour' => true,
    ];
} else {
    $mixers = [];
    foreach ($storyProducts as $p) {
        if (!$p['load']) { if (!empty($p['specs']['Drum capacity'])) $mixers[] = $p; continue; }
        $tracked = stripos($p['specs']['Running gear'] ?? '', 'track') !== false;
        $rows[] = ['model' => $p['model'], 'url' => bl_product_url($p), 'value' => (int) $p['load'], 'sub' => 0, 'label' => number_format((int) $p['load']) . ' kg', 'note' => !empty($p['specs']['Bucket capacity']) ? $p['specs']['Bucket capacity'] . ' bucket' : ($tracked ? 'Tracked' : ''), 'hit' => true];
    }
    usort($rows, static fn($a, $b) => [$a['value'], $a['model']] <=> [$b['value'], $b['model']]);
    $top = $rows ? (int) ceil(max(array_column($rows, 'value')) / 1000) * 1000 : 5000;
    $scale = ['from' => 0, 'to' => $top, 'step' => 1000, 'tick' => static fn(int $v): string => $v ? ($v / 1000) . ' t' : '0', 'unit' => '', 'marker' => null];
    $buckets = array_filter(array_map(static fn($p) => $number($p['specs']['Bucket capacity'] ?? ''), $storyProducts));
    $lightest = $rows[0] ?? null;
    $heaviest = end($rows) ?: null;
    $mixer = $mixers[0] ?? null;
    $story = [
        'eyebrow' => 'ABOUT THE DUMPERS',
        'title' => 'FROM THE GARDEN<br>PATH TO THE SITE.',
        'intro' => 'Mini dumpers carry soil, gravel, concrete and site waste over ground a truck can’t reach. The range runs from compact tracked dumpers that fit narrow paths to wheeled site dumpers for heavier loads.',
        'chartEyebrow' => 'RATED LOAD',
        'chartTitle' => 'PICK YOUR PAYLOAD.',
        'chartText' => ucfirst($inWords(count($rows))) . ' models' . ($lightest && $heaviest ? ' from ' . ($lightest['value'] < 1000 ? $lightest['value'] . ' kg' : ($lightest['value'] / 1000) . ' t') . ' to ' . ($heaviest['value'] / 1000) . ' t' : '') . '.' . ($buckets ? ' Bucket capacity runs from ' . min($buckets) . ' m³ to ' . max($buckets) . ' m³ on the wheeled dumpers.' : '') . ($mixer ? ' The ' . $mixer['model'] . ' carries a ' . $mixer['specs']['Drum capacity'] . ' drum instead of a hopper.' : ''),
        'chartNote' => 'Rated loads from each model’s published specifications. Confirm your material and typical load with our team.',
        'legend' => ['Rated load'],
        'options' => [
            ['big' => 'WHEELS <i>/</i> TRACKS', 'title' => 'Running gear.', 'text' => 'Wheeled site dumpers for firm ground and heavier loads; tracked models for soft ground and narrow access.'],
            ['big' => 'OPEN <i>/</i> ROOF <i>/</i> CAB', 'title' => 'Operator cover.', 'text' => 'Wheeled models come open or with a roof or cab. Each spec sheet lists the height with roof / cab.'],
            ['big' => 'TIP <i>/</i> MIX', 'title' => 'Hopper or drum.', 'text' => 'Tracked dumpers tip hydraulically to 100°. The mixer version carries a drum to mix concrete on site.'],
        ],
        'colour' => false,
    ];
}
if (!$rows) return;
$at = static fn(int $v): string => rtrim(rtrim(number_format(($v - $scale['from']) / ($scale['to'] - $scale['from']) * 100, 2, '.', ''), '0'), '.') . '%';
?>
<section class="bl-range-story bl-shell" id="about-the-range" aria-labelledby="range-story-title">
  <div class="bl-range-story-head bl-reveal">
    <div><p class="bl-eyebrow"><span class="bl-status-dot"></span> <?= $story['eyebrow'] ?></p><h2 id="range-story-title"><?= $story['title'] ?></h2></div>
    <p><?= bl_escape($story['intro']) ?></p>
  </div>
  <div class="bl-range-story-chart">
    <div class="bl-range-story-copy bl-reveal">
      <p class="bl-eyebrow"><?= $story['chartEyebrow'] ?></p>
      <h3><?= $story['chartTitle'] ?></h3>
      <p><?= bl_escape($story['chartText']) ?></p>
      <p class="bl-range-story-note"><?= bl_escape($story['chartNote']) ?></p>
    </div>
    <figure class="bl-ruler bl-reveal<?= $scale['marker'] ? ' bl-ruler-marked' : '' ?>"<?= $scale['marker'] ? ' style="--marker:' . $at($scale['marker']) . '"' : '' ?>>
      <figcaption class="bl-ruler-legend"><?php foreach ($story['legend'] as $i => $label): ?><span class="bl-ruler-key-<?= ['bar', 'sub', 'marker'][$i] ?>"><?= bl_escape($label) ?></span><?php endforeach; ?></figcaption>
      <ol class="bl-ruler-rows">
        <?php foreach ($rows as $i => $row): ?>
        <li class="<?= $row['hit'] ? 'bl-ruler-hit' : 'bl-ruler-miss' ?>" style="--i:<?= $i ?>"><a href="<?= bl_escape($row['url']) ?>">
          <span class="bl-ruler-model"><?= bl_escape($row['model']) ?></span>
          <span class="bl-ruler-track" aria-hidden="true"><i class="bl-ruler-bar" style="--w:<?= $at($row['value']) ?>"></i><?php if ($row['sub']): ?><i class="bl-ruler-sub" style="--w:<?= $at(min($row['sub'], $scale['to'])) ?>"></i><?php endif; ?></span>
          <span class="bl-ruler-value"><b><?= bl_escape($row['label']) ?></b><?php if ($row['note']): ?><small><?= bl_escape($row['note']) ?></small><?php endif; ?></span>
        </a></li>
        <?php endforeach; ?>
      </ol>
      <div class="bl-ruler-axis" aria-hidden="true"><span></span><span class="bl-ruler-ticks"><?php for ($tick = $scale['from']; $tick <= $scale['to']; $tick += $scale['step']): ?><i style="--at:<?= $at($tick) ?>"><?= bl_escape($scale['tick']($tick)) ?></i><?php endfor; ?></span><span><?= $scale['unit'] ?></span></div>
    </figure>
  </div>
  <ul class="bl-range-story-options<?= $story['colour'] ? ' bl-range-story-with-colour' : '' ?>">
    <?php foreach ($story['options'] as $option): ?>
    <li class="bl-reveal"><strong><?= $option['big'] ?></strong><h3><?= bl_escape($option['title']) ?></h3><p><?= bl_escape($option['text']) ?></p><?php if (!empty($option['link'])): ?><a class="bl-text-link" href="<?= bl_escape($option['link'][0]) ?>"><?= bl_escape($option['link'][1]) ?> <?= bl_icon('arrow-down') ?></a><?php endif; ?></li>
    <?php endforeach; ?>
    <?php if ($story['colour']): ?>
    <li class="bl-range-story-colour bl-reveal">
      <img src="<?= bl_escape(bl_asset('images/about/ready-900.webp')) ?>" alt="Finished loaders in red, yellow, blue and green at the BOB-LIFT factory in Xuzhou, each painted to its customer’s order" width="900" height="600" loading="lazy">
      <div><span class="bl-range-story-swatches" aria-hidden="true"><i style="--c:#d63a22"></i><i style="--c:#f0b21b"></i><i style="--c:#1f5cc8"></i><i style="--c:#56693f"></i><i style="--c:#ea6a1f"></i><i style="--c:#23272a"></i></span><h3>Painted to order.</h3><p>Tell us the colour you want. The loaders on our Xuzhou factory floor are painted for other customers’ orders.</p></div>
    </li>
    <?php endif; ?>
  </ul>
</section>
