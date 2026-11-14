<?php
/**
 * Dealers: how a company that sells or rents compact equipment buys from the factory, what it can put its own name on, how
 * the machines are supported, and the application form. Words in 网站内容 → 经销商 (inc/content-dealers.php); the terms,
 * lead time, warranty and load ranges come from the same data as the Support and product pages.
 */
$content = bl_editable('dealers');
$terms = bl_support_terms();
$hero = $content['hero'];
$heroImage = bl_content_image($hero['image'], ['src' => bl_asset('images/about/ready-1600.webp'), 'srcset' => bl_asset('images/about/ready-900.webp') . ' 900w, ' . bl_asset('images/about/ready-1600.webp') . ' 1600w', 'width' => 1600, 'height' => 1067]);
$brandImage = bl_content_image($content['brand']['image'], ['src' => bl_asset('images/about/paint-900.webp'), 'srcset' => bl_asset('images/about/paint-900.webp') . ' 900w, ' . bl_asset('images/about/paint-1600.webp') . ' 1600w', 'width' => 1600, 'height' => 1067]);
$after = $content['after'];
$partsImage = bl_content_image($after['parts_image'], ['src' => bl_asset('images/about/hydraulics-900.webp'), 'width' => 900, 'height' => 600]);
$range = $content['range'];
$tokens = bl_dealer_tokens();
$rangeRows = [
    ['kind' => $tokens['{loader_count}'] . ' models', 'title' => $range['loaders_title'], 'text' => $range['loaders_text'], 'url' => bl_url('/products/skid-steer-loaders/')],
    ['kind' => $tokens['{dumper_count}'] . ' models', 'title' => $range['dumpers_title'], 'text' => $range['dumpers_text'], 'url' => bl_url('/products/mini-dumpers/')],
    ['kind' => array_sum(array_map('count', bl_attachments())) . ' attachments', 'title' => $range['attachments_title'], 'text' => $range['attachments_text'], 'url' => bl_url('/products/skid-steer-loaders/') . '#attachments'],
];
$rangeRows = array_values(array_filter($rangeRows, static fn($row) => trim($row['title']) !== ''));
$steps = array_values(array_filter($content['steps']['items'], static fn($s) => is_array($s) && trim((string) ($s['title'] ?? '')) !== ''));
$brandItems = array_values(array_filter($content['brand']['items'], static fn($s) => is_array($s) && trim((string) ($s['title'] ?? '')) !== ''));
$colours = array_values(array_filter($content['brand']['colours'], static fn($s) => is_array($s) && trim((string) ($s['range'] ?? '')) !== '' && trim((string) ($s['rule'] ?? '')) !== ''));
$service = array_values(array_filter($after['service'], static fn($s) => is_array($s) && trim((string) ($s['title'] ?? '')) !== ''));
$faq = bl_dealer_faq();
$points = array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $content['apply']['points']))));
$sectionLinks = array_filter(['buying' => 'How dealers buy', 'range' => 'The range', 'brand' => 'Your brand', 'after-sale' => 'After the sale', 'faq' => $faq ? 'Questions' : null]);
?>
<section class="bl-sup-hero" aria-labelledby="dealers-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => $hero['breadcrumb']]]); ?>
    <div class="bl-sup-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> <?= bl_escape($hero['eyebrow']) ?></p>
        <h1 id="dealers-title"><?= bl_heading_lines($hero['title']) ?></h1>
      </div>
      <div class="bl-sup-hero-side">
        <p><?= bl_dealer_text($hero['text']) ?></p>
        <a class="bl-button bl-button-blue bl-sup-hero-cta" href="#apply"><?= bl_escape($hero['button']) ?><?= bl_icon('arrow-down') ?></a>
        <a class="bl-text-link" href="#buying"><?= bl_escape($hero['link']) ?> <?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <div class="bl-sup-stage">
    <figure class="bl-sup-photo">
      <img<?= bl_image_attributes($heroImage, '100vw') ?> alt="<?= bl_escape($hero['alt']) ?>" fetchpriority="high">
      <?php if ($hero['caption'] !== ''): ?><figcaption><?= bl_escape($hero['caption']) ?></figcaption><?php endif; ?>
    </figure>
    <dl class="bl-sup-plate bl-shell" aria-label="Dealer terms at a glance" data-section-nav-after>
      <?php foreach ($terms as $term): ?><div><dt><?= bl_escape($term['label']) ?></dt><dd><?= bl_escape($term['value']) ?><?php if ($term['unit']): ?><small><?= bl_escape($term['unit']) ?></small><?php endif; ?></dd></div><?php endforeach; ?>
    </dl>
  </div>
</section>

<nav class="bl-section-nav" aria-label="On this page" data-section-nav>
  <div class="bl-section-nav-inner bl-shell">
    <strong class="bl-section-nav-title">Dealers</strong>
    <ol><?php $n = 0; foreach ($sectionLinks as $id => $label): $n++; ?><li><a href="#<?= $id ?>" data-section-link><span>0<?= $n ?></span><?= $label ?></a></li><?php endforeach; ?></ol>
    <a class="bl-section-nav-cta" href="#apply"><?= bl_escape($hero['button']) ?> <?= bl_icon('arrow-down') ?></a>
  </div>
  <i class="bl-section-nav-progress" aria-hidden="true"></i>
</nav>

<section class="bl-sup-buying bl-shell" id="buying" aria-labelledby="buying-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($content['steps']['eyebrow']) ?></p><h2 id="buying-title"><?= bl_heading($content['steps']['title']) ?></h2></div>
    <p><?= bl_dealer_text($content['steps']['intro']) ?></p>
  </div>
  <ol class="bl-sup-steps bl-dealer-steps">
    <?php foreach ($steps as $i => $step): ?>
    <li class="bl-reveal">
      <span class="bl-sup-step-index"><?= sprintf('%02d', $i + 1) ?></span>
      <h3><?= bl_escape($step['title']) ?></h3>
      <p><?= bl_dealer_text((string) ($step['text'] ?? '')) ?></p>
      <?php if ($i === count($steps) - 1 && trim($content['steps']['link']) !== ''): ?><a class="bl-text-link" href="<?= bl_escape(bl_url('/support/') . '#containers') ?>"><?= bl_escape($content['steps']['link']) ?> <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
    </li>
    <?php endforeach; ?>
  </ol>
</section>

<section class="bl-sup-downloads bl-dealer-range bl-shell" id="range" aria-labelledby="range-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($range['eyebrow']) ?></p><h2 id="range-title"><?= bl_heading($range['title']) ?></h2></div>
    <p><?= bl_dealer_text($range['intro']) ?></p>
  </div>
  <ul class="bl-sup-files">
    <?php foreach ($rangeRows as $row): ?>
    <li class="bl-reveal"><a href="<?= bl_escape($row['url']) ?>">
      <span class="bl-sup-file-body"><span class="bl-sup-file-kind"><?= bl_escape($row['kind']) ?></span><span class="bl-sup-file-title"><?= bl_escape($row['title']) ?></span><span class="bl-sup-file-text"><?= bl_dealer_text($row['text']) ?></span></span>
      <span class="bl-circle-button"><?= bl_icon('arrow-up-right') ?></span>
    </a></li>
    <?php endforeach; ?>
  </ul>
</section>

<section class="bl-dealer-brand bl-shell" id="brand" aria-labelledby="brand-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($content['brand']['eyebrow']) ?></p><h2 id="brand-title"><?= bl_heading($content['brand']['title']) ?></h2></div>
    <p><?= bl_dealer_text($content['brand']['intro']) ?></p>
  </div>
  <div class="bl-dealer-brand-grid">
    <figure class="bl-dealer-brand-photo bl-reveal"><img<?= bl_image_attributes($brandImage, '(max-width: 800px) 100vw, 45vw') ?> alt="<?= bl_escape($content['brand']['alt']) ?>" loading="lazy"></figure>
    <div class="bl-dealer-brand-body">
      <?php if ($brandItems): ?><ul class="bl-dealer-points bl-reveal"><?php foreach ($brandItems as $item): ?><li><h3><?= bl_escape($item['title']) ?></h3><p><?= bl_dealer_text((string) ($item['text'] ?? '')) ?></p></li><?php endforeach; ?></ul><?php endif; ?>
      <?php if ($colours): ?>
      <div class="bl-dealer-colours bl-reveal">
        <h3><?= bl_escape($content['brand']['colour_title']) ?></h3>
        <dl><?php foreach ($colours as $row): ?><div><dt><?= bl_escape($row['range']) ?></dt><dd><?= bl_dealer_text($row['rule']) ?></dd></div><?php endforeach; ?></dl>
        <?php if (trim($content['brand']['colour_note']) !== ''): ?><p><?= bl_dealer_text($content['brand']['colour_note']) ?></p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="bl-sup-after" id="after-sale" aria-labelledby="after-title">
  <div class="bl-shell">
    <div class="bl-sup-head bl-reveal">
      <div><p class="bl-eyebrow"><?= bl_escape($after['eyebrow']) ?></p><h2 id="after-title"><?= bl_heading($after['title'], '') ?></h2></div>
      <p><?= bl_dealer_text($after['intro']) ?></p>
    </div>
    <div class="bl-sup-after-grid">
      <div class="bl-sup-warranty bl-reveal">
        <p class="bl-eyebrow"><?= bl_escape($after['warranty_eyebrow']) ?></p>
        <h3><?= bl_escape($after['warranty_title']) ?></h3>
        <p><?= bl_dealer_text($after['warranty_text']) ?></p>
        <?php if (trim($after['checks_title']) !== ''): ?><h3 class="bl-dealer-checks"><?= bl_escape($after['checks_title']) ?></h3><p><?= bl_dealer_text($after['checks_text']) ?></p><?php endif; ?>
      </div>
      <div class="bl-sup-service bl-reveal">
        <p class="bl-eyebrow"><?= bl_escape($after['service_eyebrow']) ?></p>
        <ol><?php foreach ($service as $step): ?><li><div><h3><?= bl_escape($step['title']) ?></h3><p><?= bl_dealer_text((string) ($step['text'] ?? '')) ?></p></div></li><?php endforeach; ?></ol>
      </div>
      <figure class="bl-sup-parts bl-reveal">
        <img<?= bl_image_attributes($partsImage, '(max-width: 800px) 100vw, 50vw') ?> alt="<?= bl_escape($after['parts_alt']) ?>" loading="lazy">
        <figcaption><p class="bl-eyebrow"><?= bl_escape($after['parts_eyebrow']) ?></p><h3><?= bl_escape($after['parts_title']) ?></h3><p><?= bl_dealer_text($after['parts_text']) ?></p></figcaption>
      </figure>
    </div>
  </div>
</section>

<?php if ($faq): ?>
<section class="bl-sup-faq bl-shell" id="faq" aria-labelledby="faq-title">
  <div class="bl-sup-faq-aside bl-reveal">
    <p class="bl-eyebrow"><?= bl_escape($content['faq']['eyebrow']) ?></p>
    <h2 id="faq-title"><?= bl_heading($content['faq']['title']) ?></h2>
    <p><?= bl_dealer_text($content['faq']['intro']) ?></p>
    <a class="bl-text-link" href="#apply"><?= bl_escape($hero['button']) ?> <?= bl_icon('arrow-down') ?></a>
  </div>
  <div class="bl-sup-faq-groups">
    <div class="bl-sup-faq-group bl-reveal">
      <?php foreach ($faq as $item): ?>
      <details class="bl-sup-faq-item">
        <summary><?= bl_escape($item['q']) ?><?= bl_icon('plus') ?></summary>
        <div><p><?= bl_escape($item['a']) ?></p></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="bl-dealer-apply" id="apply" aria-labelledby="apply-title">
  <div class="bl-shell bl-dealer-apply-grid">
    <div class="bl-dealer-apply-intro">
      <p class="bl-eyebrow"><?= bl_escape($content['apply']['eyebrow']) ?></p>
      <h2 id="apply-title"><?= bl_heading($content['apply']['title']) ?></h2>
      <p><?= bl_dealer_text($content['apply']['text']) ?></p>
      <?php if ($points): ?><p class="bl-eyebrow bl-dealer-apply-points-title"><?= bl_escape($content['apply']['points_title']) ?></p><ul class="bl-checklist"><?php foreach ($points as $point): ?><li><?= bl_escape($point) ?></li><?php endforeach; ?></ul><?php endif; ?>
      <p class="bl-dealer-apply-direct">Or write to <a href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>?subject=<?= rawurlencode('Dealer application') ?>"><?= bl_escape(bl_company('email')) ?></a> or <a href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>?text=<?= rawurlencode('Hello BOB-LIFT, I would like to become a dealer.') ?>" target="_blank" rel="noopener">WhatsApp <?= bl_escape(bl_company('sales_whatsapp_label')) ?></a>.</p>
    </div>
    <div class="bl-contact-form-panel bl-dealer-apply-panel">
      <div class="bl-inquiry-live" data-thanks="<?= bl_escape(bl_url('/thank-you/')) ?>" data-model="" data-model-family="" data-form="dealer"><?= bl_dealer_form() ?></div>
    </div>
  </div>
</section>
