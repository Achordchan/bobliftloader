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
      <a href="<?= bl_escape(bl_url('/products/' . $key . '/')) ?>"><img src="<?= bl_escape(bl_family_image($family)) ?>" alt="" width="1374" height="1145" loading="lazy"><span><b><?= bl_escape($label) ?></b><small><?= $ranges['count'] ?> models · rated <?= bl_escape(bl_load_range($ranges['load'])) ?></small></span></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <button class="bl-search-toggle" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" title="Search (/)" hidden><?= bl_icon('magnifying-glass') ?></button>
    <?php require __DIR__ . '/partials/language-switch.php'; ?>
    <a class="bl-nav-contact" href="<?= bl_escape(bl_application_link(bl_content_value('global', 'header.contact_url'))) ?>" <?= $activeType === 'contact' ? 'aria-current="page"' : '' ?>><?= bl_escape(bl_content_value('global', 'header.contact_label')) ?> <?= bl_icon('arrow-up-right') ?></a>
  </nav>
  <?php /* Phones keep search in the bar itself, beside the menu button; both search buttons appear once the script runs. */ ?>
  <button class="bl-search-toggle bl-search-toggle-bar" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" hidden><?= bl_icon('magnifying-glass') ?></button>
  <button class="bl-menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="main-navigation"><?= bl_icon('list', 'bl-menu-open') ?><?= bl_icon('x', 'bl-menu-close') ?></button>
</header>
