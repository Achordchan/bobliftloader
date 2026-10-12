<?php $activeType = $route['type']; ?>
<header class="bl-header" id="top">
  <div class="bl-brand-group">
    <a class="bl-brand" href="<?= bl_escape(bl_url('/')) ?>" aria-label="BOB-LIFT home"><img src="<?= bl_escape(bl_brand_logo()) ?>" alt="BOB-LIFT" width="224" height="91"></a>
    <?php require __DIR__ . '/partials/site-switch.php'; ?>
  </div>
  <nav class="bl-nav" aria-label="Main navigation" id="main-navigation">
    <?php $labels = bl_editable('global')['header']; foreach ([['equipment', '/products/skid-steer-loaders/'], ['applications', '/applications/'], ['support', '/support/'], ['about', '/about/'], ['news', '/news/']] as $i => [$id, $path]): ?>
    <a href="<?= bl_escape(bl_url($path)) ?>" <?= bl_header_active($id, $activeType) ? 'aria-current="page"' : '' ?>><?= bl_escape($labels['labels'][$id]) ?><span class="bl-nav-index">0<?= $i + 1 ?></span></a>
    <?php if ($id === 'equipment'): /* Equipment opens on the loaders; this panel names both families (hover or keyboard focus; a sub-list in the phone menu). */ ?>
    <div class="bl-nav-panel">
      <?php foreach (['skid-steer-loaders' => 'loader', 'mini-dumpers' => 'dumper'] as $key => $family): $label = $labels['products'][$family]; $ranges = bl_family_ranges($family); ?>
      <a href="<?= bl_escape(bl_url('/products/' . $key . '/')) ?>"><img<?= ($panelSrcset = bl_image_srcset(bl_family_image($family), [160, 240])) !== '' ? ' data-srcset="' . bl_escape($panelSrcset) . '" sizes="76px"' : '' ?> data-src="<?= bl_escape(bl_family_image($family)) ?>" alt="" width="1374" height="1145" decoding="async"><span><b><?= bl_escape($label) ?></b><small><?= $ranges['count'] ?> models · rated <?= bl_escape(bl_load_range($ranges['load'])) ?></small></span></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <button class="bl-search-toggle" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" title="Search (/)" hidden><?= bl_icon('magnifying-glass') ?></button>
    <?php require __DIR__ . '/partials/language-switch.php'; ?>
    <a class="bl-nav-contact" href="<?= bl_escape(bl_application_link(bl_content_value('global', 'header.contact_url'))) ?>" <?= $activeType === 'contact' ? 'aria-current="page"' : '' ?>><?= bl_escape(bl_content_value('global', 'header.contact_label')) ?> <?= bl_icon('arrow-up-right') ?></a>
    <?php /* Phone menu only: the contact bar's WhatsApp and Email, under Let's talk (the bar steps aside while the menu is open). */ ?>
    <div class="bl-nav-direct"><a href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.5 4 5.2 5.2 0 0 0 3.2.7 2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg><?= bl_content_text('global', 'side.whatsapp') ?></a><a href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M3 6.5h18v11H3z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="m3 7 9 6 9-6"/></svg><?= bl_content_text('global', 'side.email') ?></a></div>
  </nav>
  <?php /* Phones keep search in the bar itself, beside the menu button; both search buttons appear once the script runs. */ ?>
  <button class="bl-search-toggle bl-search-toggle-bar" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" hidden><?= bl_icon('magnifying-glass') ?></button>
  <button class="bl-menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="main-navigation"><?= bl_icon('list', 'bl-menu-open') ?><?= bl_icon('x', 'bl-menu-close') ?></button>
</header>
