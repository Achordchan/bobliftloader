<?php $activeType = $route['type']; ?>
<header class="bl-header" id="top">
  <a class="bl-brand" href="<?= bl_escape(bl_url('/')) ?>" aria-label="BOB-LIFT home"><img src="<?= bl_escape(bl_asset('images/logo.png')) ?>" alt="BOB-LIFT" width="224" height="91"><span>COMPACT<br>EQUIPMENT</span></a>
  <nav class="bl-nav" aria-label="Main navigation" id="main-navigation">
    <?php foreach ([['/products/', 'Equipment', ['products', 'product']], ['/applications/', 'Applications', ['applications', 'application']], ['/support/', 'Support', ['support']], ['/about/', 'Our company', ['about']], ['/news/', 'News', ['news', 'article']]] as $i => $nav): ?>
    <a href="<?= bl_escape(bl_url($nav[0])) ?>" <?= in_array($activeType, $nav[2], true) ? 'aria-current="page"' : '' ?>><?= $nav[1] ?><span class="bl-nav-index">0<?= $i + 1 ?></span></a>
    <?php endforeach; ?>
    <button class="bl-search-toggle" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" title="Search (/)" hidden><?= bl_icon('magnifying-glass') ?></button>
    <a class="bl-nav-contact" href="<?= bl_escape(bl_url('/contact/')) ?>" <?= $activeType === 'contact' ? 'aria-current="page"' : '' ?>>Let’s talk <?= bl_icon('arrow-up-right') ?></a>
  </nav>
  <?php /* Phones keep search in the bar itself, beside the menu button; both search buttons appear once the script runs. */ ?>
  <button class="bl-search-toggle bl-search-toggle-bar" type="button" data-open-search aria-haspopup="dialog" aria-controls="site-search" aria-label="Search the site" hidden><?= bl_icon('magnifying-glass') ?></button>
  <button class="bl-menu-toggle" aria-label="Open navigation" aria-expanded="false" aria-controls="main-navigation"><?= bl_icon('list', 'bl-menu-open') ?><?= bl_icon('x', 'bl-menu-close') ?></button>
</header>
