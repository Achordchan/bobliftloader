<?php
/**
 * The BL-T360-H / BL-T460-H 3D showcase (inc/showcase.php): a full document of its own, with the site's SEO tags,
 * header, tracking, cookie banner and enquiry attribution (assets/js/main.js). The markup in templates/bl360/ is generated: change the page in
 * loader-experience/ and run node loader-experience/scripts/build_wp.mjs.
 */
$products = bl_showcase_products();
// The site header (with its search and the cookie banner) inside its own .bl-site box: the site's styles are scoped to
// that class, so they reach these parts and leave the page's own styles alone.
ob_start(); ?>
<div class="bl-site bl-showcase-site">
  <?php require __DIR__ . '/partials/consent.php'; ?>
  <?php require __DIR__ . '/header.php'; ?>
  <?php require __DIR__ . '/partials/site-search.php'; ?>
</div>
<?php $siteHeader = (string) ob_get_clean();
?>
<!doctype html>
<html lang="<?= bl_escape(BL_I18N_LANGUAGES[bl_lang()]['hreflang']) ?>">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#121314">
  <?php bl_render_seo($route); ?>
  <?= bl_i18n_script_dictionary() ?>
  <?php if ($scriptCorrections = bl_showcase_script_corrections()): ?><script>window.BL360_I18N_SAVED = <?= json_encode($scriptCorrections, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;</script><?php endif; ?>
  <link rel="icon" href="<?= bl_escape(bl_brand_icon('images/icons/favicon.ico', 48)) ?>" sizes="48x48">
  <link rel="icon" href="<?= bl_escape(bl_brand_icon('images/icons/icon-32.png', 32)) ?>" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= bl_escape(bl_brand_icon('images/icons/apple-touch-icon.png', 180)) ?>">
  <script>window.BL360_BASE = <?= json_encode(bl_asset('bl360/'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>; window.BL360_VIDEOS = <?= json_encode(bl_asset('videos/'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
  <?= bl_showcase_markup('head.html') ?>
  <link rel="preload" href="<?= bl_escape(bl_asset('fonts/barlow-condensed-700.woff2')) ?>" as="font" type="font/woff2" crossorigin>
  <?php foreach (['base', 'components', 'responsive', 'showcase'] as $stylesheet): ?><link rel="stylesheet" href="<?= bl_escape(bl_asset_versioned('css/' . $stylesheet . '.css')) ?>"><?php endforeach; ?>
  <?php if (bl_is_wordpress()) wp_head(); ?>
</head>
<body class="experience">
  <?php if (bl_is_wordpress()) wp_body_open(); ?>
  <?= bl_showcase_markup('body.html', $products, $siteHeader) ?>
  <script type="module" src="<?= bl_escape(bl_asset_versioned('js/main.js')) ?>"></script>
  <?php if (bl_is_wordpress()) wp_footer(); ?>
</body>
</html>
