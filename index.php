<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/bootstrap.php';
if (!bl_is_wordpress()) bl_serve_discovery();
$route = bl_resolve_route();
// The range opens on the loaders, the main line. /products/ itself stays only for header searches (?q=), which span both families.
if ($route['type'] === 'products' && $route['category'] === '' && bl_query('q') === '') {
    header('Location: ' . bl_url('/products/skid-steer-loaders/'), true, 301);
    exit;
}
if (!bl_is_wordpress() && $route['status'] === 200) {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($requested !== $route['path'] && !str_contains($requested, '%')) {
        header('Location: ' . $route['path'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 301);
        exit;
    }
}
// WordPress has already sent its own status (200 for a post it found, even one this theme will not show); status_header() replaces it.
if (bl_is_wordpress()) status_header($route['status']); else http_response_code($route['status']);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="theme-color" content="#081c2b">
  <?php bl_render_seo($route); ?>
  <link rel="icon" href="<?= bl_escape(bl_asset('images/logo.png')) ?>" type="image/png">
  <link rel="preload" href="<?= bl_escape(bl_asset('fonts/barlow-condensed-700.ttf')) ?>" as="font" type="font/ttf" crossorigin>
  <?php if ($route['type'] === 'home'): $heroImages = bl_home_hero_images(); ?><link rel="preload" href="<?= bl_escape($heroImages['wide']) ?>" as="image" media="(min-width: 801px)" fetchpriority="high"><link rel="preload" href="<?= bl_escape($heroImages['narrow']) ?>" as="image" media="(max-width: 800px)" fetchpriority="high"><?php endif; ?>
  <?php if ($route['type'] === 'about'): ?><link rel="preload" as="image" imagesrcset="<?= bl_escape(bl_asset('images/about/hall-1200.webp')) ?> 1200w, <?= bl_escape(bl_asset('images/about/hall-2560.webp')) ?> 2560w" imagesizes="100vw" fetchpriority="high"><?php endif; ?>
  <?php foreach (['base', 'home', 'components', 'responsive', 'pages', 'page-responsive', ...($route['type'] === 'about' ? ['about'] : []), ...(in_array($route['type'], ['applications', 'application', 'application_case'], true) ? ['applications'] : []), ...(in_array($route['type'], ['news', 'article', 'application_case'], true) ? ['news'] : [])] as $stylesheet): ?><link rel="stylesheet" href="<?= bl_escape(bl_asset_versioned('css/' . $stylesheet . '.css')) ?>"><?php endforeach; ?>
  <script type="importmap"><?= bl_script_import_map() ?></script>
  <script type="module" src="<?= bl_escape(bl_asset_versioned('js/main.js')) ?>"></script>
  <?php if (bl_is_wordpress()) wp_head(); ?>
  <?php /* Support page, and the parts of it that product and family pages share (attachments, videos, spec-sheet print). */ if (in_array($route['type'], ['support', 'products', 'product'], true)): ?><link rel="stylesheet" href="<?= bl_escape(bl_asset_versioned('css/support.css')) ?>"><?php endif; ?>
</head>
<body class="bl-site bl-page-<?= bl_escape($route['type']) ?><?= bl_is_wordpress() && is_admin_bar_showing() ? ' bl-wp-admin-bar' : '' ?>">
  <?php if (bl_is_wordpress()) wp_body_open(); ?>
  <a class="bl-skip" href="#main">Skip to content</a>
  <?php require __DIR__ . '/templates/header.php'; ?>
  <main id="main"><?php require __DIR__ . '/templates/' . ($route['type'] === 'home' ? 'home' : 'pages/' . $route['type']) . '.php'; ?></main>
  <?php require __DIR__ . '/templates/footer.php'; ?>
  <?php require __DIR__ . '/templates/partials/side-contact.php'; ?>
  <?php require __DIR__ . '/templates/dialogs.php'; ?>
  <?php if (bl_is_wordpress()) wp_footer(); ?>
</body>
</html>
