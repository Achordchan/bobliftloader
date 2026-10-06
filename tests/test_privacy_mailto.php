<?php
/** A configured address with URL syntax in it stays one recipient inside the privacy text's mailto: link. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
$GLOBALS['options']['bl_content_global'] = ['company' => ['email' => 'sales?quotes@example.com']];
$privacy = bl_privacy_body('ending');
if (!str_contains($privacy, 'href="mailto:sales%3Fquotes@example.com"') || !str_contains($privacy, '>sales?quotes@example.com<')) {
    fwrite(STDERR, "privacy mailto placeholder was not URL-escaped\n");
    exit(1);
}
echo "privacy mailto ok\n";
