<?php
$selectedRecord = bl_find_record(bl_products(), bl_query('product'));
$selectedFamily = $selectedRecord && $selectedRecord['category'] === 'mini-dumpers' ? 'dumper' : 'loader';
$requestedApplication = bl_query('application');
// ?application= may carry the application's current name, the theme's original name (links shared before an editor renamed it) or its URL slug.
$application = null;
if ($requestedApplication !== '') foreach ($applications as $index => $candidate) {
    $original = $candidate['title'];
    foreach ($GLOBALS['bl_application_base'] as $base) if (bl_application_slug($base) === bl_application_slug($candidate)) $original = $base['title'];
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
        <li><a href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg><span><small><?= bl_content_text('contact', 'channels.whatsapp') ?></small><b><?= bl_escape(bl_company('sales_whatsapp_label')) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li>
        <li><a href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M3 6.5h18v11H3z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="m3 7 9 6 9-6"/></svg><span><small><?= bl_content_text('contact', 'channels.email') ?></small><b><?= bl_escape(bl_company('email')) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li>
        <?php if (bl_company('wechat_id') !== ''): ?><li><a href="<?= bl_escape(bl_wechat_qr()) ?>" data-wechat aria-haspopup="dialog"><?= bl_wechat_icon() ?><span><small><?= bl_content_text('contact', 'channels.wechat') ?></small><b><?= bl_escape(bl_company('wechat_id')) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li><?php endif; ?>
        <li><a href="<?= bl_escape(bl_company('phone_url')) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M6.6 3.5h2.7l1.4 4-1.9 1.3a11 11 0 0 0 5.4 5.4l1.3-1.9 4 1.4v2.7a2 2 0 0 1-2.2 2A15.5 15.5 0 0 1 4.6 5.7 2 2 0 0 1 6.6 3.5Z"/></svg><span><small><?= bl_escape('Phone · ' . bl_company_phone_hours()) ?></small><b><?= bl_escape(bl_company('phone')) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li>
        <?php foreach (bl_social_links() as $key => $link): ?><li><a href="<?= bl_escape($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= bl_social_icon($key) ?><span><small><?= bl_content_text('contact', 'channels.' . $key) ?></small><b><?= bl_escape($link['name']) ?></b></span><?= bl_icon('arrow-up-right') ?></a></li><?php endforeach; ?>
      </ul>
    </div>
    <div class="bl-contact-prepare">
      <p class="bl-eyebrow" id="contact-prepare-title"><?= bl_content_text('contact', 'form.prepare_title') ?></p>
      <ol aria-labelledby="contact-prepare-title" data-swipe-row>
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
        <div><dt><?= bl_content_text('contact', 'visit.address_label') ?></dt><dd><strong><?= bl_escape(bl_company('name')) ?></strong><?php if (bl_company('name_zh') !== ''): ?><span class="bl-company-zh" lang="zh-CN"><?= bl_escape(bl_company('name_zh')) ?></span><?php endif; ?><?php foreach (['street' => 'registered_label', 'factory_street' => 'factory_label', 'office_street' => 'office_label'] as $addressKey => $labelKey): if (bl_company($addressKey) === '') continue; ?><address><em><?= bl_content_text('contact', 'visit.' . $labelKey) ?></em><?= bl_company_address_part(0, $addressKey) ?><br><?= bl_company_address_part(1, $addressKey) ?></address><?php endforeach; ?></dd></div>
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
