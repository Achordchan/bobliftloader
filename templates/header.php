<?php $activeType = $route['type']; ?>
<header class="bl-header" id="top">
  <div class="bl-brand-group">
    <a class="bl-brand" href="<?= bl_escape(bl_url('/')) ?>" aria-label="BOB-LIFT home"><img src="<?= bl_escape(bl_asset('images/logo.png')) ?>" alt="BOB-LIFT" width="224" height="91"></a>
    <?php /* Which BOB-LIFT website this is, and a switch to the other two (same tab: it is a change of site, not a reference). */ ?>
    <details class="bl-site-switch">
      <summary aria-label="Compact equipment website: switch to another BOB-LIFT website"><span>COMPACT<br>EQUIPMENT</span><i aria-hidden="true"></i></summary>
      <div class="bl-site-panel">
        <p>BOB-LIFT websites</p>
        <ul>
          <?php foreach ([['https://www.boblift.com/', 'boblift.com', 'BOB-LIFT', 'The company, its factory and the full range', 'B'], ['https://www.bobliftcrane.com/', 'bobliftcrane.com', 'Cranes', 'Truck-mounted, marine and pickup cranes', 'C'], ['', 'bobliftloader.com', 'Compact equipment', 'Mini skid steer loaders and mini dumpers', 'E']] as [$url, $domain, $name, $text, $mark]): ?>
          <li><?php if ($url): ?><a href="<?= $url ?>"><?php else: ?><span class="bl-site-current" aria-current="true"><?php endif; ?>
            <i aria-hidden="true"><?= $mark ?></i>
            <span><small><?= $domain ?></small><b><?= $name ?></b><span><?= $text ?></span></span>
            <?= $url ? bl_icon('arrow-up-right') : '<em>You are here</em>' ?>
          <?= $url ? '</a>' : '</span>' ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    </details>
  </div>
  <nav class="bl-nav" aria-label="Main navigation" id="main-navigation">
    <?php foreach ([['/products/skid-steer-loaders/', 'Equipment', ['products', 'product']], ['/applications/', 'Applications', ['applications', 'application']], ['/support/', 'Support', ['support']], ['/about/', 'Our company', ['about']], ['/news/', 'News', ['news', 'article']]] as $i => $nav): ?>
    <a href="<?= bl_escape(bl_url($nav[0])) ?>" <?= in_array($activeType, $nav[2], true) ? 'aria-current="page"' : '' ?>><?= $nav[1] ?><span class="bl-nav-index">0<?= $i + 1 ?></span></a>
    <?php if ($i === 0): /* Equipment opens on the loaders; this panel names both families (hover or keyboard focus; a sub-list in the phone menu). */ ?>
    <div class="bl-nav-panel">
      <?php foreach (['skid-steer-loaders' => ['Mini skid steer loaders', 'range-loader-studio.webp', 'loader'], 'mini-dumpers' => ['Mini dumpers', 'range-dumper-studio.webp', 'dumper']] as $key => [$label, $image, $family]): $ranges = bl_family_ranges($family); ?>
      <a href="<?= bl_escape(bl_url('/products/' . $key . '/')) ?>"><img src="<?= bl_escape(bl_asset('images/' . $image)) ?>" alt="" width="1374" height="1145" loading="lazy"><span><b><?= $label ?></b><small><?= $ranges['count'] ?> models · rated <?= bl_escape(bl_load_range($ranges['load'])) ?></small></span></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
    <button class="bl-search-toggle" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" title="Search (/)" hidden><?= bl_icon('magnifying-glass') ?></button>
    <a class="bl-nav-contact" href="<?= bl_escape(bl_url('/contact/')) ?>" <?= $activeType === 'contact' ? 'aria-current="page"' : '' ?>>Let’s talk <?= bl_icon('arrow-up-right') ?></a>
  </nav>
  <?php /* Phones keep search in the bar itself, beside the menu button; both search buttons appear once the script runs. */ ?>
  <button class="bl-search-toggle bl-search-toggle-bar" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" hidden><?= bl_icon('magnifying-glass') ?></button>
  <button class="bl-menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="main-navigation"><?= bl_icon('list', 'bl-menu-open') ?><?= bl_icon('x', 'bl-menu-close') ?></button>
</header>
