<?php
/** The side enquiry button opens the quick panel on phones only while it points at the default enquiry form. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
$GLOBALS['options']['bl_content_global'] = ['side' => ['enquiry_url' => '/support/#parts']];
ob_start(); require __DIR__ . '/../templates/partials/side-contact.php'; $html = ob_get_clean();
if (str_contains($html, 'data-quick') || !str_contains($html, '/support/#parts')) { fwrite(STDERR, "custom side enquiry link still opens the quick panel\n"); exit(1); }
echo "side enquiry ok\n";
