<?php
declare(strict_types=1);
// Router for `php -S` over .wordpress/: serves real files, sends everything else through WordPress (pretty permalinks).
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$path = (string) (parse_url($uri, PHP_URL_PATH) ?? '/');
$root = dirname(__DIR__) . '/.wordpress';

// The theme folder is this repository; never serve its hidden files (.git, .env) or the login note.
if (preg_match('~/\.~', $path) || $path === '/admin-login.txt') {
    http_response_code(404);
    exit;
}

if ($path === '/wp-admin') {
    $query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
    header('Location: /wp-admin/' . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

$file = $root . rawurldecode($path);
if (is_file($file) || is_dir($file)) return false;

$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
