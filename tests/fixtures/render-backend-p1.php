<?php
/** Isolated request renderer for the database-free default HTML regression. */
declare(strict_types=1);
$_SERVER['REQUEST_URI'] = $argv[1] ?? '/';
putenv('BL_SITE_URL=https://bobliftloader.com');
putenv('BL_ENV=');
require dirname(__DIR__, 2) . '/index.php';
