<?php
declare(strict_types=1);
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/languages.php';
require_once __DIR__ . '/content.php';
require_once __DIR__ . '/editable.php';
require_once __DIR__ . '/consent.php';
$applications = bl_apply_application_content($applications);
require_once __DIR__ . '/support.php';
require_once __DIR__ . '/product-page.php';
require_once __DIR__ . '/routes.php';
require_once __DIR__ . '/seo.php';
require_once __DIR__ . '/llms.php';
