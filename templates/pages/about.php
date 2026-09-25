<?php
/** Company facts transcribed from www.boblift.com (About BOB-LIFT, homepage) on 2026-09-20. Keep figures in sync with the main site. */
$companyFacts = [
    ['label' => 'Factory', 'value' => 'Xuzhou', 'unit' => 'China'],
    ['label' => 'Factory area', 'value' => '37,000', 'unit' => 'm²'],
    ['label' => 'Countries & regions served', 'value' => '200+', 'unit' => ''],
    ['label' => 'Quality system', 'value' => 'ISO 9001', 'unit' => ''],
];
$certificates = [
    ['file' => 'cert-iso9001', 'label' => 'ISO 9001 quality management'],
    ['file' => 'cert-ce-1', 'label' => 'CE certification'],
    ['file' => 'cert-ce-2', 'label' => 'CE certification'],
    ['file' => 'cert-eac', 'label' => 'EAC certification'],
];
$families = [];
foreach (['skid-steer-loaders' => 'Mini skid steer loaders', 'mini-dumpers' => 'Mini dumpers'] as $key => $label) {
    $families[] = ['label' => $label, 'url' => bl_url('/products/' . $key . '/'), 'count' => count(array_filter(bl_products(), static fn($p) => $p['category'] === $key))];
}
?>
<section class="bl-about-hero" aria-labelledby="about-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => 'About us']]); ?>
    <div class="bl-about-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> ABOUT US / COMPACT EQUIPMENT</p>
        <h1 id="about-title"><span>SMALL MACHINES.</span><span class="bl-outline-text">BACKED BY</span><span class="bl-blue-text">BOB-LIFT.</span></h1>
      </div>
      <div class="bl-about-hero-side">
        <p>We are the compact equipment line of Xuzhou BOB-LIFT Construction Machinery Co., Ltd.: mini skid steer loaders and mini dumpers, each one configured to its order.</p>
        <a class="bl-text-link" href="#how-we-fit">How we fit into BOB-LIFT <?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <div class="bl-about-hero-stage">
  <figure class="bl-about-hero-photo">
    <img srcset="<?= bl_escape(bl_asset('images/about/hall-1200.webp')) ?> 1200w, <?= bl_escape(bl_asset('images/about/hall-2560.webp')) ?> 2560w" sizes="100vw" src="<?= bl_escape(bl_asset('images/about/hall-2560.webp')) ?>" alt="Rows of finished mini skid steer loaders in red, blue, yellow and green, each painted to its customer’s order, at the BOB-LIFT factory in Xuzhou" width="2560" height="1707" fetchpriority="high">
    <figcaption>Finished loaders at our Xuzhou factory, each in the colour its customer ordered</figcaption>
  </figure>
  <dl class="bl-about-plate bl-shell" aria-label="BOB-LIFT at a glance">
    <?php foreach ($companyFacts as $fact): ?><div><dt><?= bl_escape($fact['label']) ?></dt><dd><?= bl_escape($fact['value']) ?><?php if ($fact['unit']): ?><small><?= bl_escape($fact['unit']) ?></small><?php endif; ?></dd></div><?php endforeach; ?>
  </dl>
  </div>
</section>

<section class="bl-about-group" id="how-we-fit" aria-labelledby="how-we-fit-title">
  <div class="bl-shell">
    <div class="bl-about-group-head">
      <div class="bl-reveal"><p class="bl-eyebrow">01 / HOW WE FIT</p><h2 id="how-we-fit-title">BOB-LIFT IS THE COMPANY.<br><span>THIS IS ITS COMPACT SIDE.</span></h2></div>
      <div class="bl-about-group-copy bl-reveal"><?php $customCompany = bl_is_wordpress() ? bl_page_editor_content() : ''; if ($customCompany): ?><?= wp_kses_post($customCompany) ?><?php else: ?><p class="bl-lead">BOB-LIFT is one company with two lines of machines. Truck-mounted cranes are its core business; compact equipment is its second line.</p><p>This website covers the compact line on its own: mini skid steer loaders and mini dumpers, their specifications, and a direct line to the team that builds them.</p><?php endif; ?></div>
    </div>
    <ul class="bl-about-tree bl-reveal">
      <li class="bl-tree-root">
        <div class="bl-tree-root-card">
          <div class="bl-tree-root-photo"><img src="<?= bl_escape(bl_asset('images/company/boblift-workshop-aerial-mobile.webp')) ?>" alt="Aerial view of the BOB-LIFT crane assembly workshop in Xuzhou" width="1280" height="720" loading="lazy"></div>
          <div class="bl-tree-root-name"><span>THE COMPANY</span><h3>BOB-LIFT</h3><p>Xuzhou BOB-LIFT Construction Machinery Co., Ltd.</p></div>
          <a class="bl-tree-link" href="https://www.boblift.com/" target="_blank" rel="noopener"><span>Company website</span>boblift.com <?= bl_icon('arrow-up-right') ?></a>
        </div>
        <ul class="bl-tree-branches">
          <li class="bl-tree-branch"><span>LIFTING EQUIPMENT</span><h3>CRANES</h3><p>Truck-mounted, marine and pickup cranes.</p></li>
          <li class="bl-tree-branch bl-tree-here" aria-current="page"><span>COMPACT EQUIPMENT <small>YOU ARE HERE</small></span><h3>LOADERS &amp; DUMPERS</h3><p>The line this website is for: small machines, published specifications and a direct line to the team.</p><div class="bl-tree-here-links"><?php foreach ($families as $family): ?><a href="<?= bl_escape($family['url']) ?>"><b><?= $family['count'] ?></b> <?= bl_escape($family['label']) ?> <?= bl_icon('arrow-up-right') ?></a><?php endforeach; ?></div></li>
        </ul>
      </li>
    </ul>
    <ol class="bl-about-promises">
      <li class="bl-reveal"><span>01</span><h3>One company, start to finish.</h3><p>You deal directly with Xuzhou BOB-LIFT Construction Machinery Co., Ltd., the company that builds the machines, from the first quote to delivery.</p></li>
      <li class="bl-reveal"><span>02</span><h3>Built to your order.</h3><p>Engine, running gear and operator position are set per order, and loaders are painted in the colour you specify.</p></li>
      <li class="bl-reveal"><span>03</span><h3>One team to talk to.</h3><p><a href="mailto:info@boblift.com">info@boblift.com</a> reaches the same team for loaders, dumpers, <a href="<?= bl_escape(bl_url('/support/') . '#after-delivery') ?>">parts and service</a> questions.</p></li>
    </ol>
  </div>
</section>

<section class="bl-about-factory bl-shell" aria-labelledby="factory-title">
  <div class="bl-about-factory-copy bl-reveal">
    <p class="bl-eyebrow">02 / OUR FACTORY</p>
    <h2 id="factory-title">BUILT IN<br><span class="bl-blue-text">XUZHOU.</span></h2>
    <p>Our loaders are welded, painted and assembled at our factory in Xuzhou, Jiangsu, and each one leaves in the colour its customer ordered.</p>
    <a class="bl-text-link" href="<?= bl_escape(bl_url('/products/skid-steer-loaders/#how-they-are-made')) ?>">See how our loaders are made <?= bl_icon('arrow-up-right') ?></a>
  </div>
  <div class="bl-about-factory-photos bl-reveal">
    <img srcset="<?= bl_escape(bl_asset('images/about/assembly-900.webp')) ?> 900w, <?= bl_escape(bl_asset('images/about/assembly-1600.webp')) ?> 1600w" sizes="(max-width: 1000px) 100vw, 40vw" src="<?= bl_escape(bl_asset('images/about/assembly-1600.webp')) ?>" alt="Technicians assembling a tracked loader frame at the BOB-LIFT factory in Xuzhou" width="1600" height="1067" loading="lazy">
    <img src="<?= bl_escape(bl_asset('images/about/weld-900.webp')) ?>" alt="Welder at work on a loader frame, sparks flying from the torch" width="900" height="600" loading="lazy">
    <img src="<?= bl_escape(bl_asset('images/about/paint-900.webp')) ?>" alt="Loader frames painted blue for a customer order, waiting for assembly" width="900" height="600" loading="lazy">
  </div>
</section>

<section class="bl-about-certs bl-shell" id="certificates" aria-labelledby="certificates-title">
  <div class="bl-about-certs-head bl-reveal"><p class="bl-eyebrow">03 / PAPERWORK</p><h2 id="certificates-title">CERTIFIED<br>TO EXPORT.</h2><p>Certificates held by Xuzhou BOB-LIFT Construction Machinery Co., Ltd. Ask which documents cover your model, and request current copies with your enquiry.</p></div>
  <ul class="bl-about-cert-list"><?php foreach ($certificates as $cert): ?><li class="bl-reveal"><img src="<?= bl_escape(bl_asset('images/company/' . $cert['file'] . '.webp')) ?>" alt="<?= bl_escape($cert['label']) ?> certificate" width="452" height="640" loading="lazy"><span><?= bl_escape($cert['label']) ?></span></li><?php endforeach; ?></ul>
</section>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
