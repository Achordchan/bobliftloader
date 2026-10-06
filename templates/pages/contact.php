<?php
$selectedRecord = bl_find_record(bl_products(), bl_query('product'));
$selectedFamily = $selectedRecord && $selectedRecord['category'] === 'mini-dumpers' ? 'dumper' : 'loader';
$requestedApplication = bl_query('application');
// ?application= may carry the application's current name, the theme's original name (links shared before an editor renamed it) or its URL slug.
$application = null;
if ($requestedApplication !== '') foreach ($applications as $index => $candidate) {
    $original = $GLOBALS['bl_application_base'][$index]['title'] ?? $candidate['title'];
    if (in_array($requestedApplication, [$candidate['title'], $original, bl_application_slug($candidate)], true)) { $application = $candidate; break; }
}
$applicationName = $application['title'] ?? '';
if (!$selectedRecord && ($application['product'] ?? '') === 'dumper') $selectedFamily = 'dumper';
// Family pages link here with ?equipment=loader|dumper so the right equipment is already chosen.
if (!$selectedRecord && bl_query('equipment') === 'dumper') $selectedFamily = 'dumper';
// The Support page and its FAQ link here with ?topic=service: an owner asking about parts or a fault.
if (!$selectedRecord && bl_query('topic') === 'service') $selectedFamily = 'service';
$prefillMessage = $selectedRecord ? 'I would like information and a quotation for the ' . $selectedRecord['name'] . '.' : '';
if ($applicationName) $prefillMessage .= ($prefillMessage ? ' ' : '') . 'My application is ' . strtolower($applicationName) . '.';
// The family range charts send the visitor's opening or load along; only plausible values are written in.
$fitWidth = (int) bl_query('width');
$fitLoad = (int) bl_query('load');
$siteNotes = [];
if ($fitWidth >= 500 && $fitWidth <= 5000) $siteNotes[] = 'The narrowest opening on my site is ' . number_format($fitWidth) . ' mm.';
if ($fitLoad > 0 && $fitLoad <= 10000) $siteNotes[] = 'My heaviest load is about ' . number_format($fitLoad) . ' kg.';
if (bl_query('ground') === 'soft') $siteNotes[] = 'The ground is soft or sloped.';
if ($siteNotes) $prefillMessage .= ($prefillMessage ? ' ' : '') . implode(' ', $siteNotes);
if ($selectedFamily === 'service') $prefillMessage = "Model:\nSerial number:\nWhat I need (part, fault or question):\n";
?>
<?php $prepare = bl_editable('applications')['send']['items']; ?>
<section class="bl-contact-main bl-shell" id="enquiry">
  <?php bl_breadcrumbs([['label' => bl_content_value('contact', 'hero.breadcrumb')]]); ?>
  <div class="bl-contact-grid">
    <div class="bl-contact-intro">
      <p class="bl-eyebrow"><?= bl_content_text('contact', 'hero.eyebrow') ?></p>
      <h1><?= bl_content_text('contact', 'hero.line1') ?><br><span class="bl-blue-text"><?= bl_content_text('contact', 'hero.line2') ?></span></h1>
      <p class="bl-contact-lede"><?= bl_content_text('contact', 'hero.text') ?></p>
      <ul class="bl-contact-channels" aria-label="Ways to reach us">
        <li><a href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.5 4 5.2 5.2 0 0 0 3.2.7 2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg><span><small><?= bl_content_text('contact', 'channels.whatsapp') ?></small><b><?= bl_escape(bl_company('sales_whatsapp_label')) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li>
        <li><a href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M3 6.5h18v11H3z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="m3 7 9 6 9-6"/></svg><span><small><?= bl_content_text('contact', 'channels.email') ?></small><b><?= bl_escape(bl_company('email')) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li>
        <li><a href="<?= bl_escape(bl_company('phone_url')) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M6.6 3.5h2.7l1.4 4-1.9 1.3a11 11 0 0 0 5.4 5.4l1.3-1.9 4 1.4v2.7a2 2 0 0 1-2.2 2A15.5 15.5 0 0 1 4.6 5.7 2 2 0 0 1 6.6 3.5Z"/></svg><span><small><?= bl_escape('Phone · ' . bl_company_phone_hours()) ?></small><b><?= bl_escape(bl_company('phone')) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li>
      </ul>
    </div>
    <div class="bl-contact-prepare">
      <p class="bl-eyebrow"><?= bl_content_text('contact', 'form.prepare_title') ?></p>
      <ol>
        <?php foreach ($prepare as $i => $item): ?><li><span>0<?= $i + 1 ?></span><div><h2><?= bl_escape($item['title']) ?></h2><p><?= bl_rich($item['text']) ?></p></div></li><?php endforeach; ?>
      </ol>
    </div>
    <div class="bl-contact-form-panel">
      <p class="bl-eyebrow"><?= bl_content_text('contact', 'form.eyebrow') ?></p>
      <h2><?= bl_content_text('contact', 'form.line1') ?><br><?= bl_content_text('contact', 'form.line2') ?></h2>
      <?php require __DIR__ . '/../partials/inquiry-form.php'; ?>
    </div>
  </div>
</section>
<section class="bl-contact-visit bl-shell" id="visit" aria-labelledby="visit-title">
  <div class="bl-contact-visit-heading"><p class="bl-eyebrow"><?= bl_content_text('contact', 'visit.eyebrow') ?></p><h2 id="visit-title"><?= bl_content_text('contact', 'visit.line1') ?><br><span class="bl-blue-text"><?= bl_content_text('contact', 'visit.line2') ?></span></h2></div>
  <div class="bl-contact-visit-grid">
    <figure class="bl-contact-visit-photo">
      <img src="<?= bl_escape(bl_content_asset('contact', 'visit.image_small', 'images/about/hall-1200.webp')) ?>" srcset="<?= bl_escape(bl_content_asset('contact', 'visit.image_small', 'images/about/hall-1200.webp')) ?> 1200w, <?= bl_escape(bl_content_asset('contact', 'visit.image', 'images/about/hall-2560.webp')) ?> 2560w" sizes="(max-width: 900px) 100vw, 55vw" width="2560" height="1707" loading="lazy" alt="<?= bl_escape(bl_content_value('contact', 'hero.image_alt')) ?>">
      <figcaption><?= bl_content_text('contact', 'visit.caption') ?><span><?= bl_content_text('contact', 'visit.caption_detail') ?></span></figcaption>
    </figure>
    <div class="bl-contact-visit-info">
      <p class="bl-eyebrow"><?= bl_content_text('contact', 'visit.plan_title') ?></p>
      <h3><?= bl_content_text('contact', 'visit.plan_line1') ?><br><?= bl_content_text('contact', 'visit.plan_line2') ?></h3>
      <p class="bl-contact-visit-lede"><?= bl_content_text('contact', 'visit.text') ?></p>
      <dl class="bl-contact-visit-details">
        <div><dt><?= bl_content_text('contact', 'visit.address_label') ?></dt><dd><strong><?= bl_escape(bl_company('name')) ?></strong><address><?= bl_company_address_part(0) ?><br><?= bl_company_address_part(1) ?></address></dd></div>
        <div><dt><?= bl_content_text('contact', 'visit.hours_label') ?></dt><dd><?= bl_escape(bl_company('hours')) ?><span><?= bl_escape(bl_company('timezone')) ?></span></dd></div>
      </dl>
      <div class="bl-contact-visit-actions">
        <a class="bl-button bl-button-blue" href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>?subject=<?= rawurlencode(bl_content_value('contact', 'mail.subject')) ?>&amp;body=<?= rawurlencode(bl_content_value('contact', 'mail.body')) ?>"><?= bl_content_text('contact', 'visit.button') ?><?= bl_icon('arrow-up-right') ?></a>
        <a class="bl-text-link" href="https://www.google.com/maps/search/?api=1&amp;query=<?= rawurlencode(bl_company('map_address')) ?>" target="_blank" rel="noopener"><?= bl_content_text('contact', 'visit.map_label') ?><?= bl_icon('arrow-up-right') ?></a>
      </div>
    </div>
  </div>
  <div class="bl-contact-visit-business"><div><p class="bl-eyebrow"><?= bl_content_text('contact', 'business.title') ?></p><p><?= bl_content_text('contact', 'business.text') ?></p></div><a class="bl-text-link" href="<?= bl_escape(bl_mailto(bl_company('business_email'))) ?>"><?= bl_escape(bl_company('business_email')) ?> <?= bl_icon('arrow-up-right') ?></a></div>
</section>
