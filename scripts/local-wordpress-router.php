<?php
declare(strict_types=1);
// Router for `php -S` over .wordpress/: serves real files, sends everything else through WordPress (pretty permalinks).
$uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
$root = dirname(__DIR__) . '/.wordpress';
// Every check below uses the decoded path, the same one the file lookup and PHP's static server use (%2e is ".").
$path = rawurldecode((string) (parse_url($uri, PHP_URL_PATH) ?? '/'));

// The theme folder is this repository; never serve its hidden files (.git, .env), parent-directory
// segments or the login note, however the request spells them.
if (str_contains($path, "\0") || preg_match('~(^|/)\.~', $path) || strcasecmp(basename($path), 'admin-login.txt') === 0) {
    http_response_code(404);
    exit;
}

if ($path === '/wp-admin') {
    $query = (string) (parse_url($uri, PHP_URL_QUERY) ?? '');
    header('Location: /wp-admin/' . ($query !== '' ? '?' . $query : ''), true, 301);
    exit;
}

$file = $root . $path;
if (is_file($file) || is_dir($file)) return false;

$_SERVER['SCRIPT_NAME'] = '/index.php';
require $root . '/index.php';
