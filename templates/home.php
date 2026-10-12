<?php
$content = bl_editable('home');
$allProducts = bl_products();
// Family cards: words from the editable content, the studio photograph unless another was chosen.
$equipment = [];
foreach (['loader' => '01', 'dumper' => '02'] as $key => $index) {
    $equipment[$key] = ['index' => $index, 'image' => bl_family_image($key)] + $content['families'][$key];
}
// Hero slides: the loader picture as it has always been, then the mini dumper. The dumper slide only shows once it has a picture.
$heroSlides = [];
foreach (['loader' => ['hero', '/products/skid-steer-loaders/'], 'dumper' => ['hero_dumper', '/products/mini-dumpers/']] as $key => [$group, $path]) {
    $images = bl_home_hero_images($group);
    if (!$images) continue;
    $heroSlides[] = ['key' => $key, 'images' => $images, 'url' => bl_url($path)] + $content[$group];
}
$approach = $content['approach'];
$approachImage = bl_content_image($approach['image'], ['src' => bl_asset('images/about/people-1600.webp'), 'srcset' => bl_asset('images/about/people-900.webp') . ' 900w, ' . bl_asset('images/about/people-1600.webp') . ' 1600w, ' . bl_asset('images/about/people-2560.webp') . ' 2560w', 'width' => 2560, 'height' => 1707]);
$familyFacts = [];
foreach (['loader' => 'skid-steer-loaders', 'dumper' => 'mini-dumpers'] as $key => $cat) {
    $items = array_values(array_filter($allProducts, static fn($p) => $p['category'] === $cat));
    $loads = array_filter(array_column($items, 'load'));
    $caps = array_filter(array_column($items, 'capacity'));
    $familyFacts[$key] = ['count' => count($items), 'load' => $loads ? bl_load_range([min($loads), max($loads)]) : '', 'capacity' => $caps ? min($caps) . '–' . max($caps) . ' m³' : '', 'url' => bl_url('/products/' . $cat . '/')];
}
?>
<section class="bl-hero" aria-labelledby="hero-title" data-hero>
  <?php /* One slide per family. The loader slide is the page's h1 and shows without the script; the script reveals the others and the switch. */ ?>
  <div class="bl-hero-media">
    <?php foreach ($heroSlides as $i => $slide): ?>
    <?php /* Later slides sit in view behind the first (transparent), so lazy loading would not hold them back: their addresses wait in data- attributes until hero.js sets them after the page has loaded. */ $attr = $i === 0 ? '' : 'data-'; ?>
    <picture class="bl-hero-picture<?= $i === 0 ? ' bl-active' : '' ?>" data-hero-picture<?= $i === 0 ? '' : ' aria-hidden="true"' ?>><source media="(min-width: 801px)" <?= $attr ?>srcset="<?= bl_escape($slide['images']['wide']) ?>"><?php if ($slide['images']['portrait']): ?><source media="<?= BL_HERO_PORTRAIT_MEDIA ?>" <?= $attr ?>srcset="<?= bl_escape($slide['images']['portrait']) ?>"><?php endif; ?><img <?= $attr ?>src="<?= bl_escape($slide['images']['narrow']) ?>" alt="<?= bl_escape($slide['alt']) ?>" <?= $i === 0 ? 'fetchpriority="high"' : 'decoding="async"' ?> width="1942" height="809"></picture>
    <?php endforeach; ?>
  </div>
  <div class="bl-hero-content bl-shell">
    <div class="bl-hero-slides">
      <?php foreach ($heroSlides as $i => $slide): ?>
      <div class="bl-hero-slide<?= $i === 0 ? ' bl-active' : '' ?>" id="hero-slide-<?= $slide['key'] ?>" data-hero-slide<?= $i === 0 ? '' : ' hidden' ?>>
        <p class="bl-eyebrow bl-hero-eyebrow"><span class="bl-status-dot"></span> <?= bl_escape($slide['eyebrow']) ?></p>
        <?php if ($i === 0): ?><h1 id="hero-title" class="bl-hero-title"><?= bl_heading_lines($slide['title'], true) ?></h1><?php else: ?><h2 class="bl-hero-title"><?= bl_heading_lines($slide['title'], true) ?></h2><?php endif; ?>
        <div class="bl-hero-action"><a href="<?= bl_escape($slide['url']) ?>" class="bl-button bl-button-blue"><?= bl_escape($slide['button']) ?> <?= bl_icon('arrow-up-right') ?></a><a href="<?= bl_escape(bl_url('/contact/')) ?>" class="bl-text-link"><?= bl_escape($slide['link']) ?> <?= bl_icon('arrow-up-right') ?></a></div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if (count($heroSlides) > 1): ?>
  <div class="bl-hero-switch-row bl-shell" data-hero-switch hidden>
    <div class="bl-hero-switch" role="group" aria-label="Featured equipment">
      <?php foreach ($heroSlides as $i => $slide): ?>
      <button type="button" class="bl-hero-tab" aria-controls="hero-slide-<?= $slide['key'] ?>" aria-pressed="<?= $i === 0 ? 'true' : 'false' ?>" data-hero-tab><small><?= $equipment[$slide['key']]['index'] ?></small><span><?= bl_escape($equipment[$slide['key']]['label']) ?></span><i aria-hidden="true"></i></button>
      <?php endforeach; ?>
      <button type="button" class="bl-hero-pause" aria-label="Pause the slideshow" data-hero-pause><?= bl_icon('pause') ?><?= bl_icon('play') ?></button>
    </div>
  </div>
  <?php endif; ?>
  <div class="bl-hero-scroll bl-shell">
    <a href="#equipment" class="bl-scroll-link">SCROLL <?= bl_icon('arrow-down') ?></a>
  </div>
</section>

<section class="bl-range" id="equipment" aria-labelledby="range-title">
  <div class="bl-section-top bl-shell bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($content['range']['eyebrow']) ?></p><h2 class="bl-display" id="range-title"><?= bl_heading($content['range']['title'], 'bl-blue-text', true) ?></h2></div>
    <p class="bl-section-description"><?= bl_rich($content['range']['description'], ['{models}' => (string) count($allProducts)]) ?></p>
  </div>
  <div class="bl-range-duo">
    <?php foreach ($equipment as $key => $item): $facts = $familyFacts[$key]; ?>
    <article class="bl-range-card bl-range-<?= $key ?>" aria-labelledby="range-<?= $key ?>-title">
      <a class="bl-range-visual" href="<?= bl_escape($facts['url']) ?>" aria-label="Explore <?= bl_escape($item['label']) ?>"><img<?= ($rangeSrcset = bl_image_srcset($item['image'], [600, 900])) !== '' ? ' srcset="' . bl_escape($rangeSrcset) . '" sizes="(max-width: 800px) 100vw, 50vw"' : '' ?> src="<?= bl_escape($item['image']) ?>" alt="<?= bl_escape($item['alt']) ?>" width="1374" height="1145" loading="lazy"><span class="bl-circle-button"><?= bl_icon('plus') ?></span></a>
      <div class="bl-range-body">
        <p class="bl-eyebrow"><span><span class="bl-status-dot"></span> <?= bl_escape($item['label']) ?></span><span class="bl-range-index"><?= $item['index'] ?> / 02</span></p>
        <h3 id="range-<?= $key ?>-title"><?= bl_heading($item['title']) ?></h3>
        <p class="bl-machine-description"><?= bl_escape($item['description']) ?></p>
        <dl class="bl-range-facts"><div><dt>Models</dt><dd><?= $facts['count'] ?></dd></div><?php if ($facts['load']): ?><div><dt>Rated load</dt><dd><?= bl_escape($facts['load']) ?></dd></div><div><dt><?= $key === 'dumper' ? 'Hopper' : 'Bucket' ?></dt><dd><?= bl_escape($facts['capacity']) ?></dd></div><?php else: ?><div><dt>Running gear</dt><dd><?= bl_escape(bl_family_ranges($key)['gear']) ?></dd></div><div><dt>Controls</dt><dd>Joystick</dd></div><?php endif; ?></dl>
        <a class="bl-button" href="<?= bl_escape($facts['url']) ?>">Explore <?= $key === 'loader' ? 'loaders' : 'dumpers' ?> <?= bl_icon('arrow-up-right') ?></a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <div class="bl-range-assist bl-shell"><p><?= bl_escape($content['range']['assist']) ?></p><a class="bl-text-link" href="#contact"><?= bl_escape($content['range']['assist_link']) ?> <?= bl_icon('arrow-up-right') ?></a></div>
</section>

<section class="bl-applications" id="applications" aria-labelledby="application-title">
  <div class="bl-section-top bl-shell bl-reveal"><div><p class="bl-eyebrow"><?= bl_escape($content['applications']['eyebrow']) ?></p><h2 class="bl-display" id="application-title"><?= bl_heading($content['applications']['title'], 'bl-outline-text') ?></h2></div><p class="bl-section-description"><?= bl_rich($content['applications']['description']) ?></p></div>
  <div class="bl-application-strip bl-shell" role="region" aria-labelledby="application-title" data-swipe-row>
    <?php foreach ($applications as $index => $app): ?>
    <a class="bl-application-tile" href="<?= bl_escape(bl_url('/applications/' . bl_application_slug($app) . '/')) ?>"><span class="bl-photo-label">APPLICATION / 0<?= $index + 1 ?></span><img src="<?= bl_escape($app['image']) ?>" alt="<?= bl_escape($app['alt']) ?>" loading="lazy" width="1586" height="992"><div class="bl-application-tile-body"><div><span class="bl-eyebrow"><?= bl_escape($app['title']) ?></span><h3><?= $app['subtitle'] ?></h3><p><?= bl_escape($app['description']) ?></p></div><span class="bl-circle-button"><?= bl_icon('arrow-up-right') ?></span></div></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="bl-approach bl-shell" id="approach" aria-labelledby="approach-title">
  <div class="bl-section-top bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($approach['eyebrow']) ?></p><h2 class="bl-display" id="approach-title"><?= bl_heading($approach['title']) ?></h2></div>
    <div class="bl-approach-intro"><p><?= bl_escape($approach['text']) ?></p><a class="bl-text-link" href="<?= bl_escape(bl_url('/about/')) ?>"><?= bl_escape($approach['link']) ?> <?= bl_icon('arrow-up-right') ?></a></div>
  </div>
  <?php /* A fixed landscape frame: the photograph keeps its shape at every width instead of stretching to a column height. */ ?>
  <figure class="bl-approach-photo bl-reveal"><img<?= bl_image_attributes($approachImage, '(max-width: 2240px) 100vw, 2240px') ?> alt="<?= bl_escape($approach['alt']) ?>" loading="lazy"><?php if ($approach['caption'] !== ''): ?><figcaption><?= bl_escape($approach['caption']) ?></figcaption><?php endif; ?></figure>
  <?php /* Company-level facts only: model counts and load ranges already sit in The Range above. */ ?>
  <?php if ($approach['facts']): ?><ul class="bl-approach-facts bl-reveal" style="--facts:<?= max(3, count($approach['facts'])) ?>" aria-label="BOB-LIFT at a glance"><?php foreach ($approach['facts'] as $fact): ?><li><strong><?= bl_escape($fact['value']) ?><?php if ($fact['unit'] !== ''): ?><small><?= bl_escape($fact['unit']) ?></small><?php endif; ?></strong><span><?= bl_escape($fact['label']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
  <ol class="bl-approach-steps">
    <?php foreach ($approach['steps'] as $i => $step): ?>
    <li class="bl-reveal"><span><?= sprintf('%02d', $i + 1) ?></span><h3><?= bl_escape($step['title']) ?></h3><p><?= bl_rich($step['text']) ?></p></li>
    <?php endforeach; ?>
  </ol>
</section>


<section class="bl-home-journal bl-shell"><div class="bl-small-section-heading"><div><p class="bl-eyebrow"><?= bl_escape($content['journal']['eyebrow']) ?></p><h2><?= bl_escape($content['journal']['title']) ?></h2></div><a class="bl-text-link" href="<?= bl_escape(bl_url('/news/')) ?>"><?= bl_escape($content['journal']['link']) ?> <?= bl_icon('arrow-up-right') ?></a></div><div class="bl-news-grid"><?php foreach (array_slice(bl_articles(), 0, 3) as $article) bl_news_card($article); ?></div></section>

<section class="bl-contact" id="contact" aria-labelledby="contact-title">
  <div class="bl-contact-layout bl-shell">
    <div class="bl-contact-copy bl-reveal"><p class="bl-eyebrow"><?= bl_escape($content['contact']['eyebrow']) ?></p><h2 id="contact-title"><?= bl_heading($content['contact']['title']) ?></h2><p><?= bl_rich($content['contact']['text']) ?></p><a class="bl-contact-email" href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>"><?= bl_escape(bl_company('email')) ?> <?= bl_icon('arrow-up-right') ?></a><span class="bl-contact-signoff"><?= bl_escape($content['contact']['signoff']) ?></span></div>
    <?php require __DIR__ . '/partials/inquiry-form.php'; ?>
  </div>
</section>
