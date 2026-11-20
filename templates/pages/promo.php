<?php
/**
 * Year-end offers: the order coupons, the deposit offer, the terms and a quote form whose enquiries carry the offer mark.
 * Words in 网站内容 → 年终活动 (inc/content-promo.php). No price and no special-price model is ever shown here (company
 * policy). After the last day the page keeps its address and says the offers have ended (inc/promo.php).
 */
$content = bl_editable('promo');
$hero = $content['hero'];
$ended = bl_promo_ended();
$heroImage = bl_content_image((string) $hero['image'], ['src' => bl_asset('images/hero-real-loader.webp'), 'width' => 1536, 'height' => 1024]);
$coupons = bl_promo_rows('coupons', ['save', 'over']);
$deposits = bl_promo_rows('deposit', ['order', 'pay', 'worth']);
$points = array_values(array_filter((array) $content['deposit']['points'], static fn($p) => is_array($p) && trim((string) ($p['title'] ?? '')) !== ''));
$terms = bl_promo_terms();
$endsOn = ($ends = DateTimeImmutable::createFromFormat('!Y-m-d', bl_promo_dates()['ends'])) ? $ends->format('j M Y') : '';
$whatsapp = bl_company('sales_whatsapp_url') . '?text=' . rawurlencode(bl_t('Hello BOB-LIFT, I would like to use a year-end offer.'));
?>
<section class="bl-sup-hero bl-promo-hero" aria-labelledby="promo-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => $hero['breadcrumb']]]); ?>
    <div class="bl-sup-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> <?= bl_escape($hero['eyebrow']) ?></p>
        <h1 id="promo-title"><?= bl_heading_lines($hero['title']) ?></h1>
      </div>
      <div class="bl-sup-hero-side">
        <?php if ($ended): ?>
        <p class="bl-promo-ended"><?= bl_escape($hero['ended']) ?></p>
        <a class="bl-button bl-button-blue bl-promo-ended-cta" href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>"><?= bl_escape($hero['ended_button']) ?> <?= bl_icon('arrow-up-right') ?></a>
        <?php else: ?>
        <p><?= bl_escape($hero['text']) ?></p>
        <a class="bl-button bl-button-blue bl-sup-hero-cta" href="#claim"><?= bl_escape($hero['button']) ?> <?= bl_icon('arrow-down') ?></a>
        <a class="bl-text-link" href="#terms"><?= bl_escape($hero['link']) ?> <?= bl_icon('arrow-down') ?></a>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="bl-sup-stage">
    <figure class="bl-sup-photo"><img<?= bl_image_attributes($heroImage, '100vw') ?> alt="<?= bl_escape($hero['alt']) ?>" fetchpriority="high"></figure>
    <?php if (!$ended && $coupons): ?>
    <dl class="bl-sup-plate bl-promo-plate bl-shell" aria-label="Order coupons at a glance">
      <?php foreach (array_slice($coupons, 0, 3) as $row): ?><div><dt>Orders from US$<?= bl_escape($row['over']) ?></dt><dd><small>US$</small><?= bl_escape($row['save']) ?><small>off</small></dd></div><?php endforeach; ?>
      <?php if ($endsOn !== ''): ?><div><dt>Offers end</dt><dd><?= bl_escape($endsOn) ?></dd></div><?php endif; ?>
    </dl>
    <?php endif; ?>
  </div>
</section>

<?php if (!$ended): ?>
<?php if ($coupons): ?>
<section class="bl-promo-coupons bl-shell" id="coupons" aria-labelledby="coupons-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($content['coupons']['eyebrow']) ?></p><h2 id="coupons-title"><?= bl_heading($content['coupons']['title']) ?></h2></div>
    <p><?= bl_escape($content['coupons']['intro']) ?></p>
  </div>
  <ol class="bl-promo-tiers">
    <?php foreach ($coupons as $i => $row): ?>
    <li class="bl-reveal"><span class="bl-promo-tier-index"><?= sprintf('%02d', $i + 1) ?></span><p class="bl-promo-tier-save"><small>US$</small><?= bl_escape($row['save']) ?> <span>off</span></p><p class="bl-promo-tier-over">on orders of US$<?= bl_escape($row['over']) ?> or more</p></li>
    <?php endforeach; ?>
  </ol>
</section>
<?php endif; ?>

<?php if ($deposits): ?>
<section class="bl-promo-deposit" id="deposit" aria-labelledby="deposit-title">
  <div class="bl-shell">
    <div class="bl-sup-head bl-reveal">
      <div><p class="bl-eyebrow"><?= bl_escape($content['deposit']['eyebrow']) ?></p><h2 id="deposit-title"><?= bl_heading($content['deposit']['title'], '') ?></h2></div>
      <p><?= bl_escape($content['deposit']['intro']) ?></p>
    </div>
    <div class="bl-promo-deposit-grid">
      <table class="bl-promo-table bl-reveal">
        <caption class="bl-visually-hidden">Deposit offer by order value</caption>
        <thead><tr><th scope="col">Order value</th><th scope="col">You pay</th><th scope="col">Counts as</th></tr></thead>
        <tbody><?php foreach ($deposits as $row): ?><tr><th scope="row"><?= bl_escape($row['order']) ?></th><td>US$<?= bl_escape($row['pay']) ?></td><td>US$<?= bl_escape($row['worth']) ?></td></tr><?php endforeach; ?></tbody>
      </table>
      <?php if ($points): ?><ul class="bl-promo-points bl-reveal"><?php foreach ($points as $point): ?><li><h3><?= bl_escape($point['title']) ?></h3><p><?= bl_escape((string) ($point['text'] ?? '')) ?></p></li><?php endforeach; ?></ul><?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($terms): ?>
<section class="bl-promo-terms bl-shell" id="terms" aria-labelledby="terms-title">
  <div class="bl-sup-head bl-reveal">
    <div><p class="bl-eyebrow"><?= bl_escape($content['terms']['eyebrow']) ?></p><h2 id="terms-title"><?= bl_heading($content['terms']['title']) ?></h2></div>
  </div>
  <ol class="bl-promo-rules bl-reveal"><?php foreach ($terms as $i => $term): ?><li><span><?= sprintf('%02d', $i + 1) ?></span><p><?= bl_escape($term) ?></p></li><?php endforeach; ?></ol>
</section>
<?php endif; ?>

<section class="bl-dealer-apply bl-promo-claim" id="claim" aria-labelledby="claim-title">
  <div class="bl-shell bl-dealer-apply-grid">
    <div class="bl-dealer-apply-intro">
      <p class="bl-eyebrow"><?= bl_escape($content['claim']['eyebrow']) ?></p>
      <h2 id="claim-title"><?= bl_heading($content['claim']['title']) ?></h2>
      <p><?= bl_escape($content['claim']['text']) ?></p>
      <div class="bl-promo-claim-links">
        <a class="bl-button bl-button-outline" href="<?= bl_escape($whatsapp) ?>" target="_blank" rel="noopener"><?= bl_escape($content['claim']['whatsapp']) ?> <?= bl_icon('arrow-up-right') ?></a>
        <a class="bl-text-link" href="<?= bl_escape(bl_url('/products/skid-steer-loaders/')) ?>">Mini skid steer loaders <?= bl_icon('arrow-up-right') ?></a>
        <a class="bl-text-link" href="<?= bl_escape(bl_url('/products/mini-dumpers/')) ?>">Mini dumpers <?= bl_icon('arrow-up-right') ?></a>
      </div>
    </div>
    <div class="bl-contact-form-panel bl-dealer-apply-panel">
      <?php if (bl_inquiry_live()): ?>
      <div class="bl-inquiry-live" data-thanks="<?= bl_escape(bl_url('/thank-you/')) ?>" data-model="" data-model-family="loader"><?= bl_wp_inquiry_form(['product' => 'loader', 'model' => '', 'message' => (string) $content['claim']['message'], 'source' => 'Year-end offers', 'promo' => BL_PROMO_CODE]) ?></div>
      <?php else: $prefillMessage = (string) $content['claim']['message']; require __DIR__ . '/../partials/inquiry-form.php'; endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>
