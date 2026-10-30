<?php
/**
 * The BL-T360-H / BL-T460-H 3D showcase (inc/showcase.php): a full document of its own, with the site's SEO tags,
 * tracking, cookie banner and enquiry attribution. The markup in templates/bl360/ is generated: change the page in
 * loader-experience/ and run node loader-experience/scripts/build_wp.mjs.
 */
$products = bl_showcase_products();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
  <meta name="theme-color" content="#121314">
  <?php bl_render_seo($route); ?>
  <link rel="icon" href="<?= bl_escape(bl_brand_icon('images/icons/favicon.ico', 48)) ?>" sizes="48x48">
  <link rel="icon" href="<?= bl_escape(bl_brand_icon('images/icons/icon-32.png', 32)) ?>" type="image/png" sizes="32x32">
  <link rel="apple-touch-icon" href="<?= bl_escape(bl_brand_icon('images/icons/apple-touch-icon.png', 180)) ?>">
  <script>window.BL360_BASE = <?= json_encode(bl_asset('bl360/'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>; window.BL360_VIDEOS = <?= json_encode(bl_asset('videos/'), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) ?>;</script>
  <?= bl_showcase_markup('head.html') ?>
  <link rel="stylesheet" href="<?= bl_escape(bl_asset('bl360/site.css')) ?>">
  <?php if (bl_is_wordpress()) wp_head(); ?>
</head>
<body class="experience bl-site">
  <?php if (bl_is_wordpress()) wp_body_open(); ?>
  <?php require __DIR__ . '/partials/consent.php'; ?>
  <?= bl_showcase_markup('body.html', $products) ?>
  <script type="module">
    import { initializeAttribution } from '<?= bl_escape(bl_asset('js/attribution.js')) ?>';
    import { initializeConsent } from '<?= bl_escape(bl_asset('js/consent.js')) ?>';
    initializeAttribution();
    initializeConsent();
  </script>
  <?php if (bl_is_wordpress()) wp_footer(); ?>
</body>
</html>
