<?php
/**
 * Support: everything between choosing a machine and running it. How an order works (with container loading), what
 * happens after delivery, the downloads, and the questions the sections don't already answer. All facts come from
 * inc/support.php, which the product and family pages read too.
 */
$content = bl_editable('support');
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
$files = $content['downloads'];
$downloads = [
    ['kind' => 'PDF' . ($catalogue['size'] ? ' · ' . $catalogue['size'] : ''), 'title' => $files['catalogue_title'], 'text' => $files['catalogue_text'], 'url' => $catalogue['url'], 'download' => true],
    ['kind' => count(bl_products()) . ' models', 'title' => $files['sheets_title'], 'text' => $files['sheets_text'], 'url' => bl_url('/products/skid-steer-loaders/')],
];
if ($filmed) $downloads[] = ['kind' => 'Video', 'title' => $files['videos_title'], 'text' => 'Watch the ' . $filmedModels . ' at work, in ' . (count($filmed) > 1 ? 'their product galleries.' : 'its product gallery.'), 'url' => bl_product_url($filmed[0])];
$hero = $content['hero'];
$heroImage = bl_content_image($hero['image'], ['src' => bl_asset('images/about/people-1600.webp'), 'srcset' => bl_asset('images/about/people-900.webp') . ' 900w, ' . bl_asset('images/about/people-1600.webp') . ' 1600w, ' . bl_asset('images/about/people-2560.webp') . ' 2560w', 'width' => 2560, 'height' => 1707]);
$after = $content['after'];
$partsImage = bl_content_image($after['parts_image'], ['src' => bl_asset('images/about/hydraulics-900.webp'), 'width' => 900, 'height' => 600]);
?>
<section class="bl-sup-hero" aria-labelledby="support-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => 'Support']]); ?>
    <div class="bl-sup-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> <?= bl_escape($hero['eyebrow']) ?></p>
        <h1 id="support-title"><?= bl_heading_lines($hero['title']) ?></h1>
      </div>
      <div class="bl-sup-hero-side">
        <p><?= bl_escape($hero['text']) ?></p>
        <a class="bl-text-link" href="#buying"><?= bl_escape($hero['link']) ?> <?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <div class="bl-sup-stage">
    <figure class="bl-sup-photo">
      <img<?= bl_image_attributes($heroImage, '100vw') ?> alt="<?= bl_escape($hero['alt']) ?>" fetchpriority="high">
      <?php if ($hero['caption'] !== ''): ?><figcaption><?= bl_escape($hero['caption']) ?></figcaption><?php endif; ?>
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
    <div><p class="bl-eyebrow"><?= bl_escape($content['buying']['eyebrow']) ?></p><h2 id="buying-title"><?= bl_heading($content['buying']['title']) ?></h2></div>
    <p><?= bl_escape($content['buying']['intro']) ?></p>
  </div>
  <ol class="bl-sup-steps">
    <?php foreach (bl_buying_steps() as $i => $step): ?>
    <li class="bl-reveal">
      <span class="bl-sup-step-index"><?= sprintf('%02d', $i + 1) ?></span>
      <h3><?= bl_escape($step['title']) ?></h3>
      <p><?= bl_escape($step['text']) ?></p>
      <?php if (!empty($step['link'])): ?><a class="bl-text-link" href="<?= bl_escape($step['link'][0]) ?>"><?= bl_escape($step['link'][1]) ?> <?= bl_icon(str_starts_with($step['link'][0], '#') ? 'arrow-down' : 'arrow-up-right') ?></a><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>

  <section class="bl-sup-containers" id="containers" aria-labelledby="containers-title">
    <div class="bl-sup-containers-head bl-reveal">
      <h3 id="containers-title"><?= bl_escape($content['containers']['title']) ?></h3>
      <p><?= bl_escape($content['containers']['text']) ?></p>
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
    <?php if ($content['containers']['note'] !== ''): ?><p class="bl-sup-note"><?= bl_escape($content['containers']['note']) ?></p><?php endif; ?>
  </section>
</section>

<section class="bl-sup-after" id="after-delivery" aria-labelledby="after-title">
  <div class="bl-shell">
    <div class="bl-sup-head bl-reveal">
      <div><p class="bl-eyebrow"><?= bl_escape($after['eyebrow']) ?></p><h2 id="after-title"><?= bl_heading($after['title'], '') ?></h2></div>
      <p><?= bl_escape($after['intro']) ?></p>
    </div>
    <div class="bl-sup-after-grid">
      <div class="bl-sup-warranty bl-reveal">
        <p class="bl-eyebrow"><?= bl_escape($after['warranty_eyebrow']) ?></p>
        <h3><?= bl_escape($after['warranty_title']) ?></h3>
        <p><?= bl_escape($after['warranty_text']) ?></p>
      </div>
      <div class="bl-sup-service bl-reveal">
        <p class="bl-eyebrow"><?= bl_escape($after['service_eyebrow']) ?></p>
        <ol><?php foreach (bl_service_steps() as $step): ?><li><div><h3><?= bl_escape($step['title']) ?></h3><p><?= bl_escape($step['text']) ?></p></div></li><?php endforeach; ?></ol>
      </div>
      <figure class="bl-sup-parts bl-reveal">
        <img<?= bl_image_attributes($partsImage, '(max-width: 800px) 100vw, 50vw') ?> alt="<?= bl_escape($after['parts_alt']) ?>" loading="lazy">
        <figcaption><p class="bl-eyebrow"><?= bl_escape($after['parts_eyebrow']) ?></p><h3><?= bl_escape($after['parts_title']) ?></h3><p><?= bl_escape($after['parts_text']) ?></p></figcaption>
      </figure>
    </div>
    <div class="bl-sup-after-actions bl-reveal">
      <a class="bl-button bl-button-blue" href="<?= bl_escape($serviceUrl) ?>"><?= bl_escape($after['button']) ?> <?= bl_icon('arrow-up-right') ?></a>
      <?php if (bl_company('service_whatsapp') !== ''): ?><a class="bl-text-link" href="https://wa.me/<?= bl_escape(bl_company('service_whatsapp')) ?>?text=<?= rawurlencode($after['whatsapp_message']) ?>" target="_blank" rel="noopener"><?= bl_escape($after['whatsapp_label']) ?> <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
    </div>
  </div>
</section>

<section class="bl-sup-downloads bl-shell" id="downloads" aria-labelledby="downloads-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($files['eyebrow']) ?></p><h2 id="downloads-title"><?= bl_heading($files['title']) ?></h2></div>
    <p><?= bl_escape($files['intro']) ?></p>
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
    <p class="bl-eyebrow"><?= bl_escape($content['faq']['eyebrow']) ?></p>
    <h2 id="faq-title"><?= bl_heading($content['faq']['title']) ?></h2>
    <p><?= bl_escape($content['faq']['intro']) ?></p>
    <a class="bl-text-link" href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>"><?= bl_escape($content['faq']['link']) ?> <?= bl_icon('arrow-up-right') ?></a>
  </div>
  <div class="bl-sup-faq-groups">
    <?php foreach (bl_support_faq() as $group): ?>
    <div class="bl-sup-faq-group bl-reveal">
      <h3><?= bl_escape($group['title']) ?></h3>
      <?php foreach ($group['items'] as $item): ?>
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
