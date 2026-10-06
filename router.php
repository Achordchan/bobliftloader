<?php
declare(strict_types=1);
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
// The local preview exposes public assets and routed pages, never source or QA files.
if (str_starts_with($path, '/assets/')) {
    $file = realpath(__DIR__ . $path);
    $allowed = ['css', 'js', 'png', 'webp', 'jpg', 'jpeg', 'svg', 'ttf', 'woff2', 'mp4', 'pdf', 'ico'];
    if ($file && str_starts_with($file, __DIR__ . '/assets/') && is_file($file) && in_array(pathinfo($file, PATHINFO_EXTENSION), $allowed, true)) return false;
    http_response_code(404);
    exit;
}
require __DIR__ . '/index.php';
