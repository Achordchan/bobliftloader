<?php
declare(strict_types=1);
// php tests/test_content_migration.php — saved content from before the home facts move and the certificate withdrawal.
require __DIR__ . '/../inc/bootstrap.php';

$failures = [];
$check = static function (bool $ok, string $message) use (&$failures): void { if (!$ok) $failures[] = $message; };

$facts = [
    ['value' => '40,000', 'unit' => 'm²', 'label' => 'Own factory'],
    ['value' => 'ISO 9001', 'unit' => '', 'label' => 'Certified quality management'],
    ['value' => '150+', 'unit' => '', 'label' => 'Countries and regions served'],
];
// Edited figures carry over; the certification one does not.
$moved = bl_migrate_home_facts(['intro' => ['facts' => $facts]]);
$check(array_column($moved['approach']['facts'], 'value') === ['40,000', '150+'], 'saved figures not carried over, or the ISO one was');
// A list already saved in the new place is never overwritten.
$saved = ['intro' => ['facts' => $facts], 'approach' => ['facts' => [['value' => '1', 'unit' => '', 'label' => 'x']]]];
$check(bl_migrate_home_facts($saved) === $saved, 'saved approach facts were overwritten');
// Nothing to carry over: left alone, so the defaults show.
$check(bl_migrate_home_facts(['hero' => ['title' => 'x']]) === ['hero' => ['title' => 'x']], 'unrelated content changed');
$check(!isset(bl_migrate_home_facts(['intro' => ['facts' => [$facts[1]]]])['approach']['facts']), 'an all-certificate list became an empty list');

// The withdrawn FAQ goes whether it came from the old stock list or a custom link; other questions stay.
$check(bl_support_faq_retired(['q' => 'Which certificates do you hold?', 'a' => '…']), 'stock certificate question kept');
$check(bl_support_faq_retired(['q' => 'Do you have papers?', 'a' => '…', 'link_url' => '/about/#certificates']), 'item linking to the removed section kept');
$check(!bl_support_faq_retired(['q' => 'Can we visit the factory before ordering?', 'a' => '…', 'link_url' => '/contact/#enquiry']), 'an unrelated question was dropped');

if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo "content migration ok\n";
