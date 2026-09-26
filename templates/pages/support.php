<?php
/**
 * Support: everything between choosing a machine and running it. How an order works (with container loading), what
 * happens after delivery, the downloads, and the questions the sections don't already answer. All facts come from
 * inc/support.php, which the product and family pages read too.
 */
$terms = bl_support_terms();
$loading = bl_container_loading();
$catalogue = bl_catalogue();
$serviceUrl = bl_url('/contact/') . '?topic=service#enquiry';
$sectionLinks = ['buying' => 'How buying works', 'after-delivery' => 'After delivery', 'downloads' => 'Downloads', 'faq' => 'Questions'];
// Container rows per family, the most per container first.
$containerGroups = ['skid-steer-loaders' => [], 'mini-dumpers' => []];
foreach (bl_products() as $p) if (isset($loading[$p['slug']], $containerGroups[$p['category']])) $containerGroups[$p['category']][] = ['product' => $p, 'fit' => $loading[$p['slug']]];
foreach ($containerGroups as &$rows) usort($rows, static fn($a, $b) => [$b['fit'][0], $b['fit'][1], $a['product']['model']] <=> [$a['fit'][0], $a['fit'][1], $b['product']['model']]);
unset($rows);
$filmed = array_values(array_filter(bl_products(), static fn($p) => bl_product_videos($p)));
$filmedModels = array_column($filmed, 'model');
$filmedModels = count($filmedModels) > 1 ? implode(', ', array_slice($filmedModels, 0, -1)) . ' and ' . end($filmedModels) : implode('', $filmedModels);
$downloads = [
    ['kind' => 'PDF' . ($catalogue['size'] ? ' · ' . $catalogue['size'] : ''), 'title' => 'Product catalogue', 'text' => 'The range in print, with specification tables and the attachments.', 'url' => $catalogue['url'], 'download' => true],
    ['kind' => count(bl_products()) . ' models', 'title' => 'Specification sheets', 'text' => 'Open any model and save its specification sheet as a PDF, straight from the page.', 'url' => bl_url('/products/skid-steer-loaders/')],
    ['kind' => 'ISO 9001 · CE · EAC', 'title' => 'Certificates', 'text' => 'The quality and export certificates held by Xuzhou BOB-LIFT Construction Machinery Co., Ltd.', 'url' => bl_url('/about/') . '#certificates'],
];
if ($filmed) $downloads[] = ['kind' => 'Video', 'title' => 'Machine videos', 'text' => 'Watch the ' . $filmedModels . ' at work, in ' . (count($filmed) > 1 ? 'their product galleries.' : 'its product gallery.'), 'url' => bl_product_url($filmed[0])];
?>
<section class="bl-sup-hero" aria-labelledby="support-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => 'Support']]); ?>
    <div class="bl-sup-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> SUPPORT / BUYING &amp; AFTER DELIVERY</p>
        <h1 id="support-title"><span>FROM ORDER</span><span>TO DELIVERY.</span><span class="bl-blue-text">AND AFTER.</span></h1>
      </div>
      <div class="bl-sup-hero-side">
        <p>How an order works, what the warranty covers, and where to find parts, documents and answers. One team handles all of it, from the first message to the last spare part.</p>
        <a class="bl-text-link" href="#buying">How buying works <?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <div class="bl-sup-stage">
    <figure class="bl-sup-photo">
      <img srcset="<?= bl_escape(bl_asset('images/about/people-900.webp')) ?> 900w, <?= bl_escape(bl_asset('images/about/people-1600.webp')) ?> 1600w, <?= bl_escape(bl_asset('images/about/people-2560.webp')) ?> 2560w" sizes="100vw" src="<?= bl_escape(bl_asset('images/about/people-1600.webp')) ?>" alt="A BOB-LIFT technician fitting the hydraulics on a tracked loader at the factory in Xuzhou" width="2560" height="1707" fetchpriority="high">
      <figcaption>The team that builds the machines also answers for them after delivery</figcaption>
    </figure>
    <dl class="bl-sup-plate bl-shell" aria-label="Buying terms at a glance" data-section-nav-after>
      <?php foreach ($terms as $term): ?><div><dt><?= bl_escape($term['label']) ?></dt><dd><?= bl_escape($term['value']) ?><?php if ($term['unit']): ?><small><?= bl_escape($term['unit']) ?></small><?php endif; ?></dd></div><?php endforeach; ?>
    </dl>
  </div>
</section>

<nav class="bl-section-nav" aria-label="On this page" data-section-nav>
  <div class="bl-section-nav-inner bl-shell">
    <strong class="bl-section-nav-title">Support</strong>
    <ol><?php $n = 0; foreach ($sectionLinks as $id => $label): $n++; ?><li><a href="#<?= $id ?>" data-section-link><span>0<?= $n ?></span><?= $label ?></a></li><?php endforeach; ?></ol>
    <a class="bl-section-nav-cta" href="<?= bl_escape($serviceUrl) ?>">Parts &amp; service <?= bl_icon('arrow-up-right') ?></a>
  </div>
  <i class="bl-section-nav-progress" aria-hidden="true"></i>
</nav>

<section class="bl-sup-buying bl-shell" id="buying" aria-labelledby="buying-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow">01 / HOW BUYING WORKS</p><h2 id="buying-title">FIVE STEPS<br><span class="bl-blue-text">TO YOUR SITE.</span></h2></div>
    <p>You deal directly with the company that builds the machine, from the first message to the container door.</p>
  </div>
  <ol class="bl-sup-steps">
    <?php foreach (bl_buying_steps() as $i => $step): ?>
    <li class="bl-reveal">
      <span class="bl-sup-step-index">0<?= $i + 1 ?></span>
      <h3><?= bl_escape($step['title']) ?></h3>
      <p><?= bl_escape($step['text']) ?></p>
      <?php if (!empty($step['link'])): ?><a class="bl-text-link" href="<?= bl_escape($step['link'][0]) ?>"><?= bl_escape($step['link'][1]) ?> <?= bl_icon(str_starts_with($step['link'][0], '#') ? 'arrow-down' : 'arrow-up-right') ?></a><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>

  <section class="bl-sup-containers" id="containers" aria-labelledby="containers-title">
    <div class="bl-sup-containers-head bl-reveal">
      <h3 id="containers-title">MACHINES PER CONTAINER.</h3>
      <p>Approximate figures, worked out from each model’s published dimensions for open-seat versions, with loader buckets taken off and packed alongside. We confirm the loading plan with your order.</p>
    </div>
    <div class="bl-sup-containers-tables">
      <?php foreach ($containerGroups as $category => $rows): if (!$rows) continue; ?>
      <table class="bl-sup-table bl-reveal">
        <caption><?= $category === 'mini-dumpers' ? 'Mini dumpers' : 'Mini skid steer loaders' ?></caption>
        <thead><tr><th scope="col">Model</th><th scope="col"><abbr title="20-foot standard container">20GP</abbr></th><th scope="col"><abbr title="40-foot high-cube container">40HQ</abbr></th></tr></thead>
        <tbody>
          <?php foreach ($rows as $row): $p = $row['product']; $key = bl_key_specs($p, true); ?>
          <tr><th scope="row"><a href="<?= bl_escape(bl_product_url($p)) ?>"><?= bl_escape($p['model']) ?></a><?php if ($key): ?><small><?= bl_escape(array_key_first($key) . ' ' . reset($key)) ?></small><?php endif; ?></th><td>≈ <?= $row['fit'][0] ?></td><td>≈ <?= $row['fit'][1] ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endforeach; ?>
    </div>
    <p class="bl-sup-note">With a roof or cab, the FY-20 to FY-50 and FJZ-30 dumpers stand 2.65–2.75 m tall, above a container door. Ask how they ship.</p>
  </section>
</section>

<section class="bl-sup-after" id="after-delivery" aria-labelledby="after-title">
  <div class="bl-shell">
    <div class="bl-sup-head bl-reveal">
      <div><p class="bl-eyebrow">02 / AFTER DELIVERY</p><h2 id="after-title">COVERED FOR<br><span>TWELVE MONTHS.</span></h2></div>
      <p>The team that built your machine stays on the other end of the line, for questions, faults and parts.</p>
    </div>
    <div class="bl-sup-after-grid">
      <div class="bl-sup-warranty bl-reveal">
        <p class="bl-eyebrow">WARRANTY</p>
        <h3>The whole machine.</h3>
        <p>Every BOB-LIFT loader and dumper carries a 12-month warranty that covers the whole machine.</p>
      </div>
      <div class="bl-sup-service bl-reveal">
        <p class="bl-eyebrow">IF SOMETHING GOES WRONG</p>
        <ol><?php foreach (bl_service_steps() as $step): ?><li><div><h3><?= bl_escape($step['title']) ?></h3><p><?= bl_escape($step['text']) ?></p></div></li><?php endforeach; ?></ol>
      </div>
      <figure class="bl-sup-parts bl-reveal">
        <img src="<?= bl_escape(bl_asset('images/about/hydraulics-900.webp')) ?>" alt="Hydraulic hose fittings at a loader’s lift arm pivot" width="900" height="600" loading="lazy">
        <figcaption><p class="bl-eyebrow">SPARE PARTS</p><h3>Original wear parts.</h3><p>Each machine ships with a set of original wear parts. Order more from the same team at any time, with your model and serial number.</p></figcaption>
      </figure>
    </div>
    <div class="bl-sup-after-actions bl-reveal">
      <a class="bl-button bl-button-blue" href="<?= bl_escape($serviceUrl) ?>">Parts &amp; service request <?= bl_icon('arrow-up-right') ?></a>
      <a class="bl-text-link" href="https://wa.me/8615852499415?text=<?= rawurlencode('Hello BOB-LIFT, I need help with my machine. Model: , serial number: .') ?>" target="_blank" rel="noopener">Send us a video on WhatsApp <?= bl_icon('arrow-up-right') ?></a>
    </div>
  </div>
</section>

<section class="bl-sup-downloads bl-shell" id="downloads" aria-labelledby="downloads-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow">03 / DOWNLOADS</p><h2 id="downloads-title">KEEP IT<br><span class="bl-blue-text">ON FILE.</span></h2></div>
    <p>The documents buyers ask for most, in one place. Ask the team for anything that isn’t here.</p>
  </div>
  <ul class="bl-sup-files">
    <?php foreach ($downloads as $file): ?>
    <li class="bl-reveal"><a href="<?= bl_escape($file['url']) ?>"<?= !empty($file['download']) ? ' download' : '' ?>>
      <span class="bl-sup-file-body"><span class="bl-sup-file-kind"><?= bl_escape($file['kind']) ?></span><span class="bl-sup-file-title"><?= bl_escape($file['title']) ?></span><span class="bl-sup-file-text"><?= bl_escape($file['text']) ?></span></span>
      <span class="bl-circle-button"><?= bl_icon(!empty($file['download']) ? 'download-simple' : 'arrow-up-right') ?></span>
    </a></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="bl-sup-faq bl-shell" id="faq" aria-labelledby="faq-title">
  <div class="bl-sup-faq-aside bl-reveal">
    <p class="bl-eyebrow">04 / QUESTIONS</p>
    <h2 id="faq-title">ASKED &amp;<br><span class="bl-blue-text">ANSWERED.</span></h2>
    <p>Not answered here? Ask the team that builds the machines.</p>
    <a class="bl-text-link" href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>">Ask a question <?= bl_icon('arrow-up-right') ?></a>
  </div>
  <div class="bl-sup-faq-groups">
    <?php foreach (bl_support_faq() as $group => $items): ?>
    <div class="bl-sup-faq-group bl-reveal">
      <h3><?= bl_escape($group) ?></h3>
      <?php foreach ($items as $item): ?>
      <details class="bl-sup-faq-item">
        <summary><?= bl_escape($item['q']) ?><?= bl_icon('plus') ?></summary>
        <div><p><?= bl_escape($item['a']) ?></p><?php if (!empty($item['link'])): ?><a class="bl-text-link" href="<?= bl_escape($item['link'][1]) ?>"><?= bl_escape($item['link'][0]) ?> <?= bl_icon(str_starts_with($item['link'][1], '#') ? 'arrow-down' : 'arrow-up-right') ?></a><?php endif; ?></div>
      </details>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
