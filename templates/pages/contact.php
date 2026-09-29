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
  <?php bl_breadcrumbs([['label' => 'Contact']]); ?>
  <div class="bl-contact-grid">
    <div class="bl-contact-intro">
      <p class="bl-eyebrow">A DIRECT LINE TO BOB-LIFT</p>
      <h1>LET’S BUILD<br><span class="bl-blue-text">WHAT’S NEXT.</span></h1>
      <p class="bl-contact-lede">Equipment questions. Project requirements. Business partnerships. Start a conversation with our team.</p>
      <ul class="bl-contact-channels" aria-label="Ways to reach us">
        <li><a href="https://wa.me/8615852499415" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.5 4 5.2 5.2 0 0 0 3.2.7 2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg><span><small>WhatsApp · photos and quick questions</small><b>+86 158 5249 9415</b></span><?= bl_icon('arrow-up-right') ?></a></li>
        <li><a href="mailto:info@boblift.com"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M3 6.5h18v11H3z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="m3 7 9 6 9-6"/></svg><span><small>Email · specifications and quotations</small><b>info@boblift.com</b></span><?= bl_icon('arrow-up-right') ?></a></li>
        <li><a href="tel:+8615852499415"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" d="M6.6 3.5h2.7l1.4 4-1.9 1.3a11 11 0 0 0 5.4 5.4l1.3-1.9 4 1.4v2.7a2 2 0 0 1-2.2 2A15.5 15.5 0 0 1 4.6 5.7 2 2 0 0 1 6.6 3.5Z"/></svg><span><small>Phone · Monday–Friday, 08:30–18:00 (UTC+8)</small><b>+86 158 5249 9415</b></span><?= bl_icon('arrow-up-right') ?></a></li>
      </ul>
    </div>
    <div class="bl-contact-prepare">
      <p class="bl-eyebrow">WHAT HELPS US REPLY WELL</p>
      <ol>
        <?php foreach ($prepare as $i => $item): ?><li><span>0<?= $i + 1 ?></span><div><h2><?= bl_escape($item['title']) ?></h2><p><?= bl_escape($item['text']) ?></p></div></li><?php endforeach; ?>
      </ol>
    </div>
    <div class="bl-contact-form-panel">
      <p class="bl-eyebrow">TELL US ABOUT YOUR PROJECT</p>
      <h2>THE RIGHT CONVERSATION<br>STARTS WITH YOU.</h2>
      <?php require __DIR__ . '/../partials/inquiry-form.php'; ?>
    </div>
  </div>
</section>
<section class="bl-contact-company" aria-label="Company">
  <div class="bl-shell">
    <div class="bl-contact-company-grid">
      <div><p class="bl-eyebrow">OUR COMPANY</p><h2>Xuzhou BOB-LIFT<br>Construction Machinery Co., Ltd.</h2><address>No. 79, Zone B, Jingluotuo Industrial Park,<br>Yunlong District, Xuzhou, Jiangsu, China</address></div>
      <div><p class="bl-eyebrow">OFFICE HOURS</p><p>Monday–Friday<br>08:30–18:00<br>China Standard Time (UTC+8)</p></div>
      <div><p class="bl-eyebrow">BUSINESS COOPERATION</p><a class="bl-contact-company-link" href="mailto:business@boblift.com">business@boblift.com <?= bl_icon('arrow-up-right') ?></a><a class="bl-contact-company-link" href="https://www.boblift.com/contactus.html" target="_blank" rel="noopener">Main company contact page <?= bl_icon('arrow-up-right') ?></a></div>
    </div>
    <p class="bl-contact-company-note">This compact equipment website is operated for <strong>Xuzhou BOB-LIFT Construction Machinery Co., Ltd.</strong> Explore the wider company at <a href="https://www.boblift.com/" target="_blank" rel="noopener">boblift.com</a>.</p>
  </div>
</section>
