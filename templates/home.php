<?php
$allProducts = bl_products();
$familyFacts = [];
foreach (['loader' => 'skid-steer-loaders', 'dumper' => 'mini-dumpers'] as $key => $cat) {
    $items = array_values(array_filter($allProducts, static fn($p) => $p['category'] === $cat));
    $loads = array_filter(array_column($items, 'load'));
    $caps = array_filter(array_column($items, 'capacity'));
    $familyFacts[$key] = ['count' => count($items), 'short' => $loads ? (max($loads) < 1000 ? min($loads) . '–' . max($loads) . ' kg' : (min($loads) / 1000) . '–' . (max($loads) / 1000) . ' t') : '', 'load' => $loads ? number_format(min($loads)) . '–' . number_format(max($loads)) . ' kg' : '', 'capacity' => $caps ? min($caps) . '–' . max($caps) . ' m³' : '', 'url' => bl_url('/products/' . $cat . '/')];
}
?>
<section class="bl-hero" aria-labelledby="hero-title">
  <div class="bl-hero-media"><picture><source media="(min-width: 801px)" srcset="<?= bl_escape(bl_asset('images/hero-banner-2k.webp')) ?>"><img src="<?= bl_escape(bl_asset('images/hero-real-loader.webp')) ?>" alt="Illustrative stone-quarry scene at dusk based on BOB-LIFT BL-T1000-H product photography" fetchpriority="high" width="1942" height="809"></picture></div>
  <div class="bl-hero-content bl-shell">
    <p class="bl-eyebrow bl-hero-eyebrow"><span class="bl-status-dot"></span> COMPACT EQUIPMENT. OPEN POSSIBILITIES.</p>
    <h1 id="hero-title"><span>SMALL SIZE.</span><span>SERIOUS</span><span>CAPABILITY<span class="bl-period">.</span></span></h1>
    <div class="bl-hero-action"><a href="<?= bl_escape(bl_url('/products/skid-steer-loaders/')) ?>" class="bl-button bl-button-blue">Explore the range <?= bl_icon('arrow-up-right') ?></a><a href="<?= bl_escape(bl_url('/contact/')) ?>" class="bl-text-link">Talk to our team <?= bl_icon('arrow-up-right') ?></a></div>
  </div>
  <div class="bl-hero-entries bl-shell">
    <?php foreach ($equipment as $key => $item): $facts = $familyFacts[$key]; ?>
    <a class="bl-hero-entry" href="<?= bl_escape($facts['url']) ?>"><img src="<?= bl_escape($item['image']) ?>" alt="" width="240" height="200" loading="eager"><span><small><?= $item['index'] ?> / <?= $facts['count'] ?> models<?= $facts['short'] ? ' · ' . bl_escape($facts['short']) : '' ?></small><strong><?= bl_escape($item['label']) ?></strong></span><?= bl_icon('arrow-up-right') ?></a>
    <?php endforeach; ?>
    <a href="#intro" class="bl-scroll-link">SCROLL <?= bl_icon('arrow-down') ?></a>
  </div>
</section>

<section class="bl-intro bl-shell" id="intro" aria-labelledby="intro-title">
  <p class="bl-eyebrow bl-reveal"><span class="bl-status-dot"></span> MADE FOR YOUR EVERYDAY</p>
  <div class="bl-intro-lead">
    <h2 id="intro-title" class="bl-reveal">Less space.<br><span class="bl-blue-text">More possibilities.</span></h2>
    <div class="bl-intro-copy bl-reveal"><p>Great work doesn’t always come with plenty of room. Find compact equipment for the ground you’re breaking, the materials you’re moving and the next job on your list.</p><a href="#equipment" class="bl-text-link">Meet your next machine <?= bl_icon('arrow-up-right') ?></a></div>
  </div>
  <?php /* Company-level facts only: model counts and load ranges already sit in the hero cards and The Range just below. */ ?>
  <ul class="bl-intro-facts bl-reveal" aria-label="BOB-LIFT at a glance"><li><strong>37,000<small>m²</small></strong><span>Own factory in Xuzhou, China</span></li><li><strong>200+</strong><span>Countries and regions served</span></li><li><strong>ISO 9001</strong><span>Certified quality management</span></li></ul>
</section>

<section class="bl-range" id="equipment" aria-labelledby="range-title">
  <div class="bl-section-top bl-shell bl-reveal">
    <div><p class="bl-eyebrow">01 / THE EQUIPMENT</p><h2 class="bl-display" id="range-title">THE RANGE<span class="bl-period">.</span></h2></div>
    <p class="bl-section-description">Two families. <?= count($allProducts) ?> models.<br>Built for the space you actually have.</p>
  </div>
  <div class="bl-range-duo">
    <?php foreach ($equipment as $key => $item): $facts = $familyFacts[$key]; ?>
    <article class="bl-range-card bl-range-<?= $key ?>" aria-labelledby="range-<?= $key ?>-title">
      <a class="bl-range-visual" href="<?= bl_escape($facts['url']) ?>" aria-label="Explore <?= bl_escape($item['label']) ?>"><img src="<?= bl_escape($item['image']) ?>" alt="<?= bl_escape($item['alt']) ?>" width="1374" height="1145" loading="lazy"><span class="bl-circle-button"><?= bl_icon('plus') ?></span></a>
      <div class="bl-range-body">
        <p class="bl-eyebrow"><span><span class="bl-status-dot"></span> <?= bl_escape($item['label']) ?></span><span class="bl-range-index"><?= $item['index'] ?> / 02</span></p>
        <h3 id="range-<?= $key ?>-title"><?= $item['title'] ?></h3>
        <p class="bl-machine-description"><?= bl_escape($item['description']) ?></p>
        <dl class="bl-range-facts"><div><dt>Models</dt><dd><?= $facts['count'] ?></dd></div><?php if ($facts['load']): ?><div><dt>Rated load</dt><dd><?= bl_escape($facts['load']) ?></dd></div><div><dt>Bucket</dt><dd><?= bl_escape($facts['capacity']) ?></dd></div><?php else: ?><div><dt>Running gear</dt><dd>Tracks / tires</dd></div><div><dt>Controls</dt><dd>Joystick</dd></div><?php endif; ?></dl>
        <a class="bl-button" href="<?= bl_escape($facts['url']) ?>">Explore <?= $key === 'loader' ? 'loaders' : 'dumpers' ?> <?= bl_icon('arrow-up-right') ?></a>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <div class="bl-range-assist bl-shell"><p>Different jobs. Different requirements.</p><a class="bl-text-link" href="#contact">Let’s find your fit <?= bl_icon('arrow-up-right') ?></a></div>
</section>

<section class="bl-applications" id="applications" aria-labelledby="application-title">
  <div class="bl-section-top bl-shell bl-reveal"><div><p class="bl-eyebrow">02 / IN YOUR ELEMENT</p><h2 class="bl-display" id="application-title">WORK WITHOUT<br><span class="bl-outline-text">LIMITS.</span></h2></div><p class="bl-section-description">Every site has its own challenges. <br>Start with yours.</p></div>
  <div class="bl-application-strip bl-shell">
    <?php foreach ($applications as $index => $app): ?>
    <a class="bl-application-tile" href="<?= bl_escape(bl_url('/applications/' . bl_application_slug($app) . '/')) ?>"><span class="bl-photo-label">APPLICATION / 0<?= $index + 1 ?></span><img src="<?= bl_escape($app['image']) ?>" alt="<?= bl_escape($app['alt']) ?>" loading="lazy" width="1586" height="992"><div class="bl-application-tile-body"><div><span class="bl-eyebrow"><?= bl_escape($app['title']) ?></span><h3><?= $app['subtitle'] ?></h3><p><?= bl_escape($app['description']) ?></p></div><span class="bl-circle-button"><?= bl_icon('arrow-up-right') ?></span></div></a>
    <?php endforeach; ?>
  </div>
</section>

<section class="bl-approach bl-shell" id="approach" aria-labelledby="approach-title">
  <div class="bl-section-top bl-reveal">
    <div><p class="bl-eyebrow">03 / PEOPLE BEHIND THE MACHINE</p><h2 class="bl-display" id="approach-title">BUILT BY PEOPLE<br>YOU CAN <span class="bl-blue-text">TALK TO.</span></h2></div>
    <div class="bl-approach-intro"><p>Our loaders are welded, painted and assembled by our own team at our factory in Xuzhou. Tell us about your work and talk it through with the people behind the machines.</p><a class="bl-text-link" href="<?= bl_escape(bl_url('/about/')) ?>">Meet our company <?= bl_icon('arrow-up-right') ?></a></div>
  </div>
  <?php /* A fixed landscape frame: the photograph keeps its shape at every width instead of stretching to a column height. */ ?>
  <figure class="bl-approach-photo bl-reveal"><img srcset="<?= bl_escape(bl_asset('images/about/people-900.webp')) ?> 900w, <?= bl_escape(bl_asset('images/about/people-1600.webp')) ?> 1600w, <?= bl_escape(bl_asset('images/about/people-2560.webp')) ?> 2560w" sizes="(max-width: 2240px) 100vw, 2240px" src="<?= bl_escape(bl_asset('images/about/people-1600.webp')) ?>" alt="A BOB-LIFT technician assembling a tracked loader at the factory in Xuzhou" loading="lazy" width="2560" height="1707"><figcaption>Loader assembly · BOB-LIFT factory, Xuzhou</figcaption></figure>
  <ol class="bl-approach-steps">
    <li class="bl-reveal"><span>01</span><h3>Start with the right fit.</h3><p>Your site, your materials, your priorities. Tell us what you need to do and we’ll discuss the equipment options with you.</p></li>
    <li class="bl-reveal"><span>02</span><h3>Know what you’re choosing.</h3><p>Ask for model specifications, configuration options and product documentation before you decide, and confirm the details that matter to your work.</p></li>
    <li class="bl-reveal"><span>03</span><h3>Keep the conversation going.</h3><p>Contact the same team for equipment questions, <a href="<?= bl_escape(bl_url('/support/') . '#after-delivery') ?>">parts and service</a>, with a 12-month warranty on the whole machine. Have a machine already? Include its model and serial number.</p></li>
  </ol>
</section>


<section class="bl-home-journal bl-shell"><div class="bl-small-section-heading"><div><p class="bl-eyebrow">FROM THE COMPANY</p><h2>INSIDE BOB-LIFT.</h2></div><a class="bl-text-link" href="<?= bl_escape(bl_url('/news/')) ?>">All company stories <?= bl_icon('arrow-up-right') ?></a></div><div class="bl-news-grid"><?php foreach (array_slice(bl_articles(), 0, 3) as $article) bl_news_card($article); ?></div></section>

<section class="bl-contact" id="contact" aria-labelledby="contact-title">
  <div class="bl-contact-layout bl-shell">
    <div class="bl-contact-copy bl-reveal"><p class="bl-eyebrow">YOUR NEXT PROJECT STARTS HERE</p><h2 id="contact-title">LET’S GET<br>TO <span class="bl-blue-text">WORK.</span></h2><p>A question. A project. A new partnership.<br>Tell us what you have in mind.</p><a class="bl-contact-email" href="mailto:info@boblift.com">info@boblift.com <?= bl_icon('arrow-up-right') ?></a><span class="bl-contact-signoff">REAL PEOPLE. PRACTICAL CONVERSATIONS.</span></div>
    <?php require __DIR__ . '/partials/inquiry-form.php'; ?>
  </div>
</section>
