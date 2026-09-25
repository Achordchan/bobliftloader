<?php
/**
 * Support: a working page for buyers and owners. The head names the four things most visitors come to do; below it every
 * section has the same shape (a narrow head column, the content beside it): how buying works with container loading,
 * warranty and service, downloads, and the questions the sections don't already answer. All facts come from
 * inc/support.php, which the product and family pages read too.
 */
$terms = bl_support_terms();
$loading = bl_container_loading();
$catalogue = bl_catalogue();
$serviceUrl = bl_url('/contact/') . '?topic=service#enquiry';
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
// What most visitors come to do, one step away from the top of the page.
$tasks = [
    ['label' => 'Service', 'title' => 'Parts & service request', 'text' => 'Report a fault or order parts, with your model and serial number.', 'url' => $serviceUrl, 'icon' => 'arrow-up-right'],
    ['label' => 'Download', 'title' => 'Product catalogue', 'text' => 'The full range in one PDF' . ($catalogue['size'] ? ', ' . $catalogue['size'] : '') . '.', 'url' => $catalogue['url'], 'icon' => 'download-simple', 'download' => true],
    ['label' => 'Warranty', 'title' => $terms['warranty']['value'] . ' ' . $terms['warranty']['unit'] . ', whole machine', 'text' => 'What it covers and what happens when something goes wrong.', 'url' => '#after-delivery', 'icon' => 'arrow-down'],
    ['label' => 'Buying', 'title' => 'How an order works', 'text' => 'From the first message to the container, with machines per container.', 'url' => '#buying', 'icon' => 'arrow-down'],
];
$fits = static fn(array $rows): string => implode('', array_map(static function (array $row): string {
    $p = $row['product'];
    $key = bl_key_specs($p, true);
    return '<tr><th scope="row"><a href="' . bl_escape(bl_product_url($p)) . '">' . bl_escape($p['model']) . '</a></th><td>' . ($key ? bl_escape(array_key_first($key) . ' ' . reset($key)) : '') . '</td><td>≈ ' . $row['fit'][0] . '</td><td>≈ ' . $row['fit'][1] . '</td></tr>';
}, $rows));
$tableHead = '<thead><tr><th scope="col">Model</th><th scope="col">Key figure</th><th scope="col"><abbr title="20-foot standard container">20GP</abbr></th><th scope="col"><abbr title="40-foot high-cube container">40HQ</abbr></th></tr></thead>';
?>
<section class="bl-sup-top bl-shell" aria-labelledby="support-title">
  <?php bl_breadcrumbs([['label' => 'Support']]); ?>
  <div class="bl-sup-top-row">
    <h1 id="support-title">Buying &amp; support</h1>
    <p>How an order works, what the warranty covers, and where to find parts, documents and answers. The team that builds the machines handles all of it.</p>
  </div>
  <ul class="bl-sup-tasks">
    <?php foreach ($tasks as $task): ?>
    <li><a href="<?= bl_escape($task['url']) ?>"<?= !empty($task['download']) ? ' download' : '' ?>>
      <small><?= bl_escape($task['label']) ?></small>
      <b><?= bl_escape($task['title']) ?></b>
      <span><?= bl_escape($task['text']) ?></span>
      <?= bl_icon($task['icon']) ?>
    </a></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="bl-sup-section bl-shell" id="buying" aria-labelledby="buying-title">
  <header class="bl-sup-section-head">
    <h2 id="buying-title">How buying works</h2>
    <p>You deal directly with the company that builds the machine, from the first message to the container door.</p>
  </header>
  <div class="bl-sup-section-body">
    <dl class="bl-sup-terms" aria-label="Buying terms">
      <?php foreach ($terms as $term): ?><div><dt><?= bl_escape($term['label']) ?></dt><dd><?= bl_escape($term['value']) ?><?php if ($term['unit']): ?> <small><?= bl_escape($term['unit']) ?></small><?php endif; ?></dd></div><?php endforeach; ?>
    </dl>
    <ol class="bl-sup-steps">
      <?php foreach (bl_buying_steps() as $step): ?>
      <li>
        <h3><?= bl_escape($step['title']) ?></h3>
        <p><?= bl_escape($step['text']) ?></p>
        <?php if (!empty($step['link'])): ?><a class="bl-text-link" href="<?= bl_escape($step['link'][0]) ?>"><?= bl_escape($step['link'][1]) ?> <?= bl_icon(str_starts_with($step['link'][0], '#') ? 'arrow-down' : 'arrow-up-right') ?></a><?php endif; ?>
      </li>
      <?php endforeach; ?>
    </ol>

    <section class="bl-sup-sub" id="containers" aria-labelledby="containers-title">
      <h3 id="containers-title">Machines per container</h3>
      <p>Approximate figures, worked out from each model’s published dimensions for open-seat versions, with loader buckets taken off and packed alongside. We confirm the loading plan with your order.</p>
      <table class="bl-sup-table">
        <caption>Mini skid steer loaders</caption>
        <?= $tableHead ?>
        <tbody><?= $fits($containerGroups['skid-steer-loaders']) ?></tbody>
      </table>
      <?php if ($containerGroups['mini-dumpers']): ?>
      <details class="bl-sup-more">
        <summary>Mini dumpers <small><?= count($containerGroups['mini-dumpers']) ?> models</small><?= bl_icon('plus') ?></summary>
        <table class="bl-sup-table">
          <caption class="bl-visually-hidden">Mini dumpers</caption>
          <?= $tableHead ?>
          <tbody><?= $fits($containerGroups['mini-dumpers']) ?></tbody>
        </table>
        <p class="bl-sup-note">With a roof or cab, the FY-20 to FY-50 and FJZ-30 dumpers stand 2.65–2.75 m tall, above a container door. Ask how they ship.</p>
      </details>
      <?php endif; ?>
    </section>
  </div>
</section>

<section class="bl-sup-section bl-shell" id="after-delivery" aria-labelledby="after-title">
  <header class="bl-sup-section-head">
    <h2 id="after-title">Warranty &amp; service</h2>
    <p>The team that built your machine stays on the other end of the line, for questions, faults and parts.</p>
  </header>
  <div class="bl-sup-section-body">
    <p class="bl-sup-lead">Every BOB-LIFT loader and dumper carries a <strong><?= bl_escape($terms['warranty']['value'] . '-' . rtrim($terms['warranty']['unit'], 's')) ?> warranty that covers the whole machine</strong>.</p>
    <div class="bl-sup-sub">
      <h3>If something goes wrong</h3>
      <ol class="bl-sup-steps"><?php foreach (bl_service_steps() as $step): ?><li><h4><?= bl_escape($step['title']) ?></h4><p><?= bl_escape($step['text']) ?></p></li><?php endforeach; ?></ol>
    </div>
    <div class="bl-sup-sub">
      <h3>Spare parts</h3>
      <p>Each machine ships with a set of original wear parts. Order more from the same team at any time, with your model and serial number.</p>
    </div>
    <div class="bl-sup-actions">
      <a class="bl-button bl-button-blue" href="<?= bl_escape($serviceUrl) ?>">Parts &amp; service request <?= bl_icon('arrow-up-right') ?></a>
      <a class="bl-text-link" href="https://wa.me/8615852499415?text=<?= rawurlencode('Hello BOB-LIFT, I need help with my machine. Model: , serial number: .') ?>" target="_blank" rel="noopener">Send us a video on WhatsApp <?= bl_icon('arrow-up-right') ?></a>
    </div>
  </div>
</section>

<section class="bl-sup-section bl-shell" id="downloads" aria-labelledby="downloads-title">
  <header class="bl-sup-section-head">
    <h2 id="downloads-title">Downloads</h2>
    <p>The documents buyers ask for most. Ask the team for anything that isn’t here.</p>
  </header>
  <div class="bl-sup-section-body">
    <ul class="bl-sup-files">
      <?php foreach ($downloads as $file): ?>
      <li><a href="<?= bl_escape($file['url']) ?>"<?= !empty($file['download']) ? ' download' : '' ?>>
        <span><b><?= bl_escape($file['title']) ?></b><small><?= bl_escape($file['kind']) ?></small></span>
        <span><?= bl_escape($file['text']) ?></span>
        <?= bl_icon(!empty($file['download']) ? 'download-simple' : 'arrow-up-right') ?>
      </a></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>

<section class="bl-sup-section bl-shell" id="faq" aria-labelledby="faq-title">
  <header class="bl-sup-section-head">
    <h2 id="faq-title">Questions</h2>
    <p>Not answered here? Ask the team that builds the machines.</p>
    <a class="bl-text-link" href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>">Ask a question <?= bl_icon('arrow-up-right') ?></a>
  </header>
  <div class="bl-sup-section-body">
    <?php foreach (bl_support_faq() as $group => $items): ?>
    <div class="bl-sup-faq-group">
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
