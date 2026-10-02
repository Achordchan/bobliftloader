<?php
$application = $route['record'];
$slug = bl_application_slug($application);
$position = (int) array_search($slug, array_map('bl_application_slug', $applications), true);
$ranges = bl_family_ranges($application['product']);
$category = $application['product'] === 'dumper' ? 'mini-dumpers' : 'skid-steer-loaders';
$familyLabel = $application['product'] === 'dumper' ? 'Mini dumpers' : 'Mini skid steer loaders';
// The plate: the family's published ranges, with the figure that decides this job last and lit.
$plate = [
    'width' => ['label' => 'Widest point', 'value' => number_format($ranges['width'][0]) . '–' . number_format($ranges['width'][1]), 'unit' => 'mm'],
    'load' => ['label' => 'Rated load', 'value' => str_replace([' kg', ' t'], '', bl_load_range($ranges['load'])), 'unit' => $ranges['load'][1] < 1000 ? 'kg' : 't'],
    'gear' => ['label' => 'Running gear', 'value' => $ranges['gear'], 'unit' => ''],
];
$focusKey = $application['focus'] === 'width' ? 'width' : 'load';
$plate = ['models' => ['label' => $familyLabel, 'value' => (string) $ranges['count'], 'unit' => 'models']] + array_diff_key($plate, [$focusKey => 1]) + [$focusKey => $plate[$focusKey]];
if ($application['focus'] === 'width') $plate['width'] = ['label' => $application['product'] === 'dumper' ? 'Narrowest model' : 'Narrowest, bucket fitted', 'value' => number_format($ranges['width'][0]), 'unit' => 'mm'];
$others = array_values(array_filter($applications, static fn($a) => bl_application_slug($a) !== $slug));
// The machines for this job, ordered by the figure that decides it; the family page's range chart takes it from there.
$dimensions = array_column(bl_model_dimensions(), null, 'slug');
$family = array_values(array_filter(bl_products(), static fn($p) => $p['category'] === $category && isset($dimensions[$p['slug']])));
$noun = $application['product'] === 'dumper' ? 'dumper' : 'loader';
$order = [
    'width' => ['sort' => static fn($p) => $dimensions[$p['slug']]['width'], 'label' => $noun === 'dumper' ? 'Narrowest first' : 'Narrowest first, bucket fitted', 'link' => ['#about-the-range', $noun === 'dumper' ? 'Check every dumper’s width and load' : 'Check your gate width against every loader']],
    'load' => ['sort' => static fn($p) => -$dimensions[$p['slug']]['load'], 'label' => 'Highest rated load first', 'link' => ['#compare-' . $category, 'Compare all ' . count($family) . ' ' . $noun . 's']],
    'payload' => ['sort' => static fn($p) => $dimensions[$p['slug']]['load'] ?: PHP_INT_MAX, 'label' => 'Lightest rated load first', 'link' => ['#about-the-range', 'Check your load per trip against every dumper']],
][$application['focus']];
usort($family, static fn($a, $b) => [$order['sort']($a), $a['model']] <=> [$order['sort']($b), $b['model']]);
// A job that names its machines shows those, in its own order; the rest show the family's first three by the deciding figure.
$named = array_values(array_filter(bl_application_models($application), static fn($p) => $p['category'] === $category));
if ($named) $order['label'] = 'Chosen for this job';
$machines = $named ?: array_slice($family, 0, 3);
?>
<section class="bl-apps-hero bl-app-hero" aria-labelledby="application-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => 'Applications', 'url' => bl_url('/applications/')], ['label' => $application['title']]]); ?>
    <div class="bl-apps-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> APPLICATION 0<?= $position + 1 ?> / 0<?= count($applications) ?> · <?= bl_escape(strtoupper($familyLabel)) ?></p>
        <h1 id="application-title"><?= bl_escape(strtoupper($application['title'])) ?><span class="bl-blue-text">.</span></h1>
        <p class="bl-app-subtitle"><?= bl_escape($application['subtitle']) ?></p>
      </div>
      <div class="bl-apps-hero-side">
        <p><?= bl_escape($application['description']) ?></p>
        <a class="bl-text-link" href="#machines">The machines for this job <?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <div class="bl-app-stage">
    <figure class="bl-app-photo">
      <img src="<?= bl_escape($application['image']) ?>" alt="<?= bl_escape($application['alt']) ?>" width="1586" height="992" fetchpriority="high">
      <figcaption>Illustration based on BOB-LIFT product photography</figcaption>
    </figure>
    <dl class="bl-app-plate bl-shell" aria-label="<?= bl_escape($familyLabel) ?> at a glance">
      <?php foreach ($plate as $fact): ?><div><dt><?= bl_escape($fact['label']) ?></dt><dd><?= bl_escape($fact['value']) ?><?php if ($fact['unit']): ?><small><?= bl_escape($fact['unit']) ?></small><?php endif; ?></dd></div><?php endforeach; ?>
    </dl>
  </div>
</section>

<section class="bl-app-checks bl-shell" aria-labelledby="checks-title">
  <div class="bl-app-checks-head bl-reveal">
    <div><p class="bl-eyebrow">01 / BEFORE YOU ENQUIRE</p><h2 id="checks-title">START WITH<br><span class="bl-blue-text">THE JOB.</span></h2></div>
    <div><p>Three things decide which machine suits <?= bl_escape(strtolower($application['title'])) ?>. Have them to hand when you contact us.</p><a class="bl-button bl-button-blue" href="<?= bl_escape(bl_contact_url(null, $application['title'])) ?>">Discuss this application <?= bl_icon('arrow-up-right') ?></a></div>
  </div>
  <ol class="bl-app-checks-list">
    <?php foreach ($application['checks'] as $i => $check): ?>
    <li class="bl-reveal"><span class="bl-app-check-index">0<?= $i + 1 ?></span><h3><?= bl_escape($check['title']) ?></h3><p><?= bl_escape($check['text']) ?></p><p class="bl-app-check-measure"><span>You’ll need</span><?= bl_escape($check['measure']) ?></p><?php if (!empty($check['link'])): ?><a class="bl-text-link" href="<?= bl_escape(bl_url($check['link'][0])) ?>"><?= bl_escape($check['link'][1]) ?> <?= bl_icon('arrow-up-right') ?></a><?php endif; ?></li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="bl-app-machines bl-shell" id="machines" aria-labelledby="machines-title">
  <div class="bl-app-machines-head bl-reveal">
    <div><p class="bl-eyebrow">02 / THE MACHINES</p><h2 id="machines-title">BUILT FOR<br><span class="bl-blue-text"><?= bl_escape(strtoupper($application['title'])) ?>.</span></h2></div>
    <div><p><?= bl_escape($familyLabel) ?>, <?= bl_escape(strtolower($order['label'])) ?>. Every figure is from the model’s published specifications.</p><a class="bl-text-link" href="<?= bl_escape(bl_url('/products/' . $category . '/' . $order['link'][0])) ?>"><?= bl_escape($order['link'][1]) ?> <?= bl_icon('arrow-up-right') ?></a></div>
  </div>
  <div class="bl-product-grid"><?php foreach ($machines as $product) bl_product_card($product); ?></div>
</section>

<?php $cases = bl_application_cases($slug); if ($cases): ?>
<section class="bl-app-cases bl-shell" id="in-practice" aria-labelledby="cases-title">
  <div class="bl-app-machines-head bl-reveal">
    <div><p class="bl-eyebrow">03 / IN PRACTICE</p><h2 id="cases-title">REAL JOBS.<br><span class="bl-blue-text"><?= bl_escape(strtoupper($application['title'])) ?>.</span></h2></div>
    <div><p>How BOB-LIFT equipment has been put to work on <?= bl_escape(strtolower($application['title'])) ?> jobs: the site, the machine and the result.</p></div>
  </div>
  <div class="bl-news-grid"><?php foreach (array_slice($cases, 0, 6) as $case) bl_case_card($case); ?></div>
</section>
<?php endif; ?>
<section class="bl-app-more bl-shell" aria-labelledby="more-title">
  <div class="bl-app-more-head bl-reveal"><div><p class="bl-eyebrow"><?= $cases ? '04' : '03' ?> / OTHER JOBS</p><h2 id="more-title">MORE WORK.<br>SAME TEAM.</h2></div><a class="bl-text-link" href="<?= bl_escape(bl_url('/products/' . $category . '/')) ?>">All <?= bl_escape(strtolower($familyLabel)) ?> <?= bl_icon('arrow-up-right') ?></a></div>
  <ul class="bl-app-more-list">
    <?php foreach ($others as $other): $focus = bl_application_focus($other); ?>
    <li class="bl-reveal"><a href="<?= bl_escape(bl_url('/applications/' . bl_application_slug($other) . '/')) ?>">
      <span class="bl-app-more-photo"><img src="<?= bl_escape($other['image']) ?>" alt="<?= bl_escape($other['alt']) ?>" width="1586" height="992" loading="lazy"></span>
      <span class="bl-app-more-body"><span class="bl-app-more-title"><?= bl_escape($other['title']) ?></span><span class="bl-app-more-focus"><b><?= bl_escape($focus['value']) ?> <?= bl_escape($focus['unit']) ?></b> <?= bl_escape(strtolower($focus['label'])) ?></span></span>
      <span class="bl-circle-button"><?= bl_icon('arrow-up-right') ?></span>
    </a></li>
    <?php endforeach; ?>
  </ul>
</section>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
