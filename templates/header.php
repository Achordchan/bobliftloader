<?php $activeType = $route['type']; ?>
<header class="bl-header" id="top">
  <div class="bl-brand-group">
    <a class="bl-brand" href="<?= bl_escape(bl_url('/')) ?>" aria-label="BOB-LIFT home"><img src="<?= bl_escape(bl_brand_logo()) ?>" alt="BOB-LIFT" width="224" height="91"></a>
    <?php require __DIR__ . '/partials/site-switch.php'; ?>
  </div>
  <nav class="bl-nav" aria-label="Main navigation" id="main-navigation">
    <?php $labels = bl_editable('global')['header']; foreach ([['equipment', bl_has_language('/products/skid-steer-loaders/', bl_lang()) || !bl_has_language('/products/mini-dumpers/', bl_lang()) ? '/products/skid-steer-loaders/' : '/products/mini-dumpers/' /* a language that has only the dumper range opens on it */], ['applications', '/applications/'], ['support', '/support/'], ['about', '/about/'], ['news', '/news/']] as $i => [$id, $path]): ?>
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
    <div class="bl-nav-direct"><a href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg><?= bl_content_text('global', 'side.whatsapp') ?></a><a href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M3 6.5h18v11H3z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="m3 7 9 6 9-6"/></svg><?= bl_content_text('global', 'side.email') ?></a><?php foreach (bl_social_links() as $key => $link): ?><a href="<?= bl_escape($link['url']) ?>" target="_blank" rel="noopener noreferrer"><?= bl_social_icon($key) ?><?= bl_escape($link['name']) ?></a><?php endforeach; ?></div>
  </nav>
  <?php /* Phones keep the language switch and search in the bar itself, beside the menu button; both search buttons appear once the script runs. */ $barSwitch = true; require __DIR__ . '/partials/language-switch.php'; $barSwitch = false; ?>
  <button class="bl-search-toggle bl-search-toggle-bar" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" hidden><?= bl_icon('magnifying-glass') ?></button>
  <button class="bl-menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="main-navigation"><?= bl_icon('list', 'bl-menu-open') ?><?= bl_icon('x', 'bl-menu-close') ?></button>
</header>
