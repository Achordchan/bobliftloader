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
        'chartNote' => 'Widths from each model’s published specifications. Where a bucket is wider than your opening, its line turns orange.',
        'legend' => ['bar' => 'Body width', 'sub' => 'Bucket width', 'marker' => 'Your opening'],
        'control' => ['name' => 'opening', 'label' => 'Your narrowest opening', 'hint' => 'Gate, path or doorway', 'step' => 10, 'default' => 1000, 'unit' => 'mm'],
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
    // Words and options are edited in WordPress (网站内容 → 迷你翻斗车, defaults in inc/editable.php); the chart follows the products.
    $about = bl_editable('dumpers')['about'];
    $story = [
        'eyebrow' => bl_escape($about['eyebrow']),
        'title' => bl_heading($about['title']),
        'intro' => $about['intro'],
        'chartEyebrow' => 'RATED LOAD',
        'chartTitle' => 'PICK YOUR PAYLOAD.',
        'chartText' => ucfirst($inWords(count($rows))) . ' models' . ($lightest && $heaviest ? ' from ' . ($lightest['value'] < 1000 ? $lightest['value'] . ' kg' : ($lightest['value'] / 1000) . ' t') . ' to ' . ($heaviest['value'] / 1000) . ' t' : '') . '.' . ($buckets ? ' Bucket capacity runs from ' . min($buckets) . ' m³ to ' . max($buckets) . ' m³ on the wheeled dumpers.' : '') . ($mixer ? ' The ' . $mixer['model'] . ' carries a ' . $mixer['specs']['Drum capacity'] . ' drum instead of a hopper.' : ''),
        'chartNote' => 'Rated loads from each model’s published specifications. Confirm your material and typical load with our team.',
        'legend' => ['bar' => 'Rated load', 'marker' => 'Your load'],
        'control' => ['name' => 'payload', 'label' => 'Your load per trip', 'hint' => 'Per trip, not per day', 'step' => 100, 'default' => 0, 'unit' => 'kg'],
        // The big words are escaped here; a slash between options takes the accent colour, as on the loader page.
        'options' => array_map(static fn(array $o): array => ['big' => str_replace('/', '<i>/</i>', bl_escape($o['big'])), 'title' => $o['title'], 'text' => $o['text']], $about['options']),
        'colour' => false,
    ];
}
if (!$rows) return;
/*
 * The marker is the visitor's own number: the narrowest opening for loaders (default one metre), the load per trip for
 * dumpers (default none). A loader passes on its body width; a bucket wider than the opening is flagged, not failed,
 * since the body is what has to get through. assets/js/ruler.js moves the marker live; without it the form submits.
 */
$control = $story['control'] + ['min' => $scale['from'], 'max' => $scale['to']];
$requested = bl_query($control['name']);
$limit = $requested === '' ? $control['default'] : min(max((int) $requested, $control['min']), $control['max']);
$widthMode = $control['name'] === 'opening';
foreach ($rows as &$row) {
    $row['hit'] = $widthMode ? $row['value'] <= $limit : (!$limit || $row['value'] >= $limit);
    $row['over'] = $widthMode && $row['hit'] && $row['sub'] > $limit;
    if ($widthMode && $row['sub']) $row['note'] = 'Bucket ' . number_format($row['sub']) . ' mm' . ($row['over'] ? "\n" . number_format($row['sub'] - $limit) . ' mm over' : '');
}
unset($row);
$scale['marker'] = $widthMode || $limit ? $limit : null;
$hits = count(array_filter($rows, static fn($r) => $r['hit']));
$noun = $widthMode ? 'loaders' : 'dumpers';
$summary = $widthMode
    ? $hits . ' of ' . count($rows) . ' ' . $noun . ' fit through ' . number_format($limit) . ' mm · ' . count(array_filter($rows, static fn($r) => $r['hit'] && !$r['over'])) . ' with the bucket fitted'
    : ($limit ? $hits . ' of ' . count($rows) . ' ' . $noun . ' are rated for ' . number_format($limit) . ' kg or more' : 'Move the slider to the load you carry per trip.');
$ask = bl_contact_url(null, '', $widthMode ? ['width' => $limit] : ['load' => $limit, 'equipment' => 'dumper']);
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
      <form class="bl-ruler-control" action="#about-the-range" method="get" data-ruler-control data-unit="<?= $control['unit'] ?>" data-noun="<?= $noun ?>" data-contact="<?= bl_escape(bl_url('/contact/')) ?>">
        <div class="bl-ruler-control-label"><label for="ruler-<?= $control['name'] ?>"><?= bl_escape($control['label']) ?></label><output for="ruler-<?= $control['name'] ?>" data-ruler-out><?= $limit ? number_format($limit) . ' ' . $control['unit'] : 'Any' ?></output></div>
        <input class="bl-ruler-range" type="range" id="ruler-<?= $control['name'] ?>" name="<?= $control['name'] ?>" min="<?= $control['min'] ?>" max="<?= $control['max'] ?>" step="<?= $control['step'] ?>" value="<?= $limit ?>" style="--p:<?= round(($limit - $control['min']) / ($control['max'] - $control['min']) * 100, 2) ?>%" aria-describedby="ruler-summary">
        <p class="bl-ruler-control-hint"><span><?= bl_escape($scale['tick']($control['min'])) ?><?= $widthMode ? ' mm' : '' ?></span><?= bl_escape($control['hint']) ?><span><?= bl_escape($scale['tick']($control['max'])) ?><?= $widthMode ? ' mm' : '' ?></span></p>
        <p class="bl-ruler-summary" id="ruler-summary" aria-live="polite" data-ruler-summary><?= bl_escape($summary) ?></p>
        <a class="bl-text-link" href="<?= bl_escape($ask) ?>" data-ruler-ask>Ask about your site <?= bl_icon('arrow-up-right') ?></a>
        <noscript><button class="bl-button bl-button-blue" type="submit">Check <?= bl_icon('arrow-down') ?></button></noscript>
      </form>
      <p class="bl-range-story-note"><?= bl_escape($story['chartNote']) ?></p>
    </div>
    <figure class="bl-ruler bl-reveal<?= $scale['marker'] ? ' bl-ruler-marked' : '' ?>" data-ruler data-from="<?= $scale['from'] ?>" data-to="<?= $scale['to'] ?>"<?= $scale['marker'] ? ' style="--marker:' . $at($scale['marker']) . '"' : '' ?>>
      <figcaption class="bl-ruler-legend"><?php foreach ($story['legend'] as $key => $label): ?><span class="bl-ruler-key-<?= $key ?>"><?= bl_escape($label) ?></span><?php endforeach; ?></figcaption>
      <ol class="bl-ruler-rows">
        <?php foreach ($rows as $i => $row): ?>
        <li class="<?= $row['hit'] ? 'bl-ruler-hit' : 'bl-ruler-miss' ?><?= $row['over'] ? ' bl-ruler-over' : '' ?>" style="--i:<?= $i ?>" data-ruler-row data-value="<?= $row['value'] ?>" data-sub="<?= $row['sub'] ?>"><a href="<?= bl_escape($row['url']) ?>">
          <span class="bl-ruler-model"><?= bl_escape($row['model']) ?></span>
          <span class="bl-ruler-track" aria-hidden="true"><i class="bl-ruler-bar" style="--w:<?= $at($row['value']) ?>"></i><?php if ($row['sub']): ?><i class="bl-ruler-sub" style="--w:<?= $at(min($row['sub'], $scale['to'])) ?>"></i><?php endif; ?></span>
          <span class="bl-ruler-value"><b><?= bl_escape($row['label']) ?></b><?php if ($row['note']): ?><small data-ruler-note><?= bl_escape($row['note']) ?></small><?php endif; ?></span>
        </a></li>
        <?php endforeach; ?>
      </ol>
      <div class="bl-ruler-axis" aria-hidden="true"><span></span><span class="bl-ruler-ticks"><?php for ($tick = $scale['from']; $tick <= $scale['to']; $tick += $scale['step']): ?><i style="--at:<?= $at($tick) ?>"><?= bl_escape($scale['tick']($tick)) ?></i><?php endfor; ?></span><span><?= $scale['unit'] ?></span></div>
    </figure>
  </div>
  <ul class="bl-range-story-options<?= $story['colour'] ? ' bl-range-story-with-colour' : '' ?>" style="--options:<?= max(1, min(4, count($story['options']))) ?>">
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
