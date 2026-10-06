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
      <div><p class="bl-eyebrow">QUICK ENQUIRY</p><h2 id="quick-enquiry-title">Tell us what you need.</h2><p>Our team in Xuzhou replies by email from <?= bl_escape(bl_company('email')) ?>.</p></div>
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
      <a class="bl-text-link bl-quick-wa" href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.5 4 5.2 5.2 0 0 0 3.2.7 2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg><span>WhatsApp <span><?= bl_escape(bl_company('sales_whatsapp_label')) ?></span></span> <?= bl_icon('arrow-up-right') ?></a>
    </div>
    <?php endif; ?>
  </section>
</aside>
