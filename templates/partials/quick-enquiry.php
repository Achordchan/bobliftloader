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
// On a range page the visitor has not picked a model yet: the form lists that range's models to choose from (optional).
$quickModels = !$quickRecord && $route['type'] === 'products' && in_array($route['category'] ?? '', ['mini-dumpers', 'skid-steer-loaders'], true) ? array_values(array_filter(bl_products(), static fn(array $p): bool => $p['category'] === $route['category'])) : [];
$quickForm = bl_inquiry_live() ? bl_wp_inquiry_form(['product' => $quickFamily, 'model' => $quickRecord['model'] ?? '', 'models' => $quickModels, 'message' => '', 'source' => 'Quick enquiry']) : '';
?>
<aside class="bl-quick" aria-label="Quick enquiry">
  <button class="bl-quick-toggle" type="button" aria-expanded="false" aria-controls="quick-enquiry"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M4 5h16v11H9l-5 4z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="M8 9.5h8M8 12.5h5"/></svg><span>Quote or question?</span></button>
  <section class="bl-quick-panel" id="quick-enquiry" aria-labelledby="quick-enquiry-title" hidden>
    <div class="bl-quick-head">
      <div><p class="bl-eyebrow">QUICK ENQUIRY</p><h2 id="quick-enquiry-title">Tell us what you need.</h2><p>Our team in Xuzhou replies by email from <?= bl_escape(bl_company('email')) ?>.</p></div>
      <button class="bl-quick-close" type="button" aria-label="Close quick enquiry"><?= bl_icon('x') ?></button>
    </div>
    <?php if ($quickForm): ?>
    <div class="bl-inquiry-live bl-quick-form" data-thanks="<?= bl_escape(bl_url('/thank-you/')) ?>" data-model="<?= bl_escape($quickRecord['model'] ?? '') ?>" data-model-family="<?= bl_escape($quickFamily) ?>">
      <?php if ($quickRecord): ?><p class="bl-selected-model">Enquiring about: <strong><?= bl_escape($quickRecord['name']) ?></strong></p><?php endif; ?>
      <?= $quickForm ?>
    </div>
    <?php else: ?>
    <div class="bl-quick-fallback">
      <a class="bl-button bl-button-blue" href="<?= bl_escape(bl_url('/contact/')) ?><?= $quickRecord ? '?product=' . rawurlencode($quickRecord['slug']) : ($quickFamily !== 'help' ? '?equipment=' . $quickFamily : '') ?>#enquiry">Write your enquiry <?= bl_icon('arrow-up-right') ?></a>
      <a class="bl-text-link bl-quick-wa" href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg><span>WhatsApp <span><?= bl_escape(bl_company('sales_whatsapp_label')) ?></span></span> <?= bl_icon('arrow-up-right') ?></a>
    </div>
    <?php endif; ?>
  </section>
</aside>
