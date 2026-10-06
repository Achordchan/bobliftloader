<?php
/*
 * The quick enquiry in the bottom-right corner: a button that opens a short panel holding the same enquiry form (the same
 * mail, the same copy in the admin, the same thank-you page), with only the equipment, name, email and a line about the
 * job. Product pages and family pages arrive with their equipment chosen. Left off the contact and thank-you pages, which
 * are about the enquiry already.
 */
if (in_array($route['type'], ['contact', 'thanks'], true)) return;
$quickRecord = $route['type'] === 'product' ? $route['record'] : null;
$quickFamily = $quickRecord ? ($quickRecord['category'] === 'mini-dumpers' ? 'dumper' : 'loader') : (($route['category'] ?? '') === 'mini-dumpers' ? 'dumper' : (($route['category'] ?? '') === 'skid-steer-loaders' ? 'loader' : 'help'));
$quickForm = bl_inquiry_form_id() ? bl_wp_inquiry_form(['product' => $quickFamily, 'model' => $quickRecord['model'] ?? '', 'message' => '']) : '';
?>
<aside class="bl-quick" aria-label="Quick enquiry">
  <button class="bl-quick-toggle" type="button" aria-expanded="false" aria-controls="quick-enquiry"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M4 5h16v11H9l-5 4z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="M8 9.5h8M8 12.5h5"/></svg><span>Quote or question?</span></button>
  <section class="bl-quick-panel" id="quick-enquiry" aria-labelledby="quick-enquiry-title" hidden>
    <div class="bl-quick-head">
      <div><p class="bl-eyebrow">QUICK ENQUIRY</p><h2 id="quick-enquiry-title">Tell us what you need.</h2><p>Our team in Xuzhou replies by email from info@boblift.com.</p></div>
      <button class="bl-quick-close" type="button" aria-label="Close quick enquiry"><?= bl_icon('x') ?></button>
    </div>
    <?php if ($quickForm): ?>
    <div class="bl-inquiry-cf7 bl-quick-form" data-thanks="<?= bl_escape(bl_url('/thank-you/')) ?>" data-model="<?= bl_escape($quickRecord['model'] ?? '') ?>" data-model-family="<?= bl_escape($quickFamily) ?>">
      <?php if ($quickRecord): ?><p class="bl-selected-model">Enquiring about: <strong><?= bl_escape($quickRecord['name']) ?></strong></p><?php endif; ?>
      <?= $quickForm ?>
    </div>
    <?php else: ?>
    <div class="bl-quick-fallback">
      <a class="bl-button bl-button-blue" href="<?= bl_escape(bl_url('/contact/')) ?><?= $quickRecord ? '?product=' . rawurlencode($quickRecord['slug']) : '' ?>#enquiry">Write your enquiry <?= bl_icon('arrow-up-right') ?></a>
      <a class="bl-text-link" href="https://wa.me/8615852499415" target="_blank" rel="noopener">WhatsApp +86 158 5249 9415 <?= bl_icon('arrow-up-right') ?></a>
    </div>
    <?php endif; ?>
  </section>
</aside>
