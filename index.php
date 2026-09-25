<?php
declare(strict_types=1);
require_once __DIR__ . '/inc/bootstrap.php';
if (!bl_is_wordpress()) bl_serve_discovery();
$route = bl_resolve_route();
if (!bl_is_wordpress() && $route['status'] === 200) {
    $requested = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    if ($requested !== $route['path'] && !str_contains($requested, '%')) {
        header('Location: ' . $route['path'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''), true, 301);
        exit;
    }
}
http_response_code($route['status']);
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
  <?php if ($route['type'] === 'home'): ?><link rel="preload" href="<?= bl_escape(bl_asset('images/hero-banner-2k.webp')) ?>" as="image" media="(min-width: 801px)" fetchpriority="high"><link rel="preload" href="<?= bl_escape(bl_asset('images/hero-real-loader.webp')) ?>" as="image" media="(max-width: 800px)" fetchpriority="high"><?php endif; ?>
  <?php if ($route['type'] === 'about'): ?><link rel="preload" as="image" imagesrcset="<?= bl_escape(bl_asset('images/about/hall-1200.webp')) ?> 1200w, <?= bl_escape(bl_asset('images/about/hall-2560.webp')) ?> 2560w" imagesizes="100vw" fetchpriority="high"><?php endif; ?>
  <?php foreach (['base', 'home', 'components', 'responsive', 'pages', 'page-responsive', ...($route['type'] === 'about' ? ['about'] : [])] as $stylesheet): ?><link rel="stylesheet" href="<?= bl_escape(bl_asset('css/' . $stylesheet . '.css')) ?>"><?php endforeach; ?>
  <script type="module" src="<?= bl_escape(bl_asset('js/main.js')) ?>"></script>
  <?php if (bl_is_wordpress()) wp_head(); ?>
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
