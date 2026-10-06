<?php
/** Edited plain text keeping a default's &amp; shows a single ampersand; About's sentence keeps its spaces after edits. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
$GLOBALS['posts'] = [];
$GLOBALS['options']['bl_content_global'] = ['footer' => ['support_label' => 'Buying &amp; after-sales']];
$fail = [];
if (bl_content_text('global', 'footer.support_label') !== 'Buying &amp; after-sales') $fail[] = 'kept &amp; was double-encoded';
// Text fields are trimmed on save, so the template supplies the spaces around the two links.
$about = file_get_contents(__DIR__ . '/../templates/pages/about.php');
if (!str_contains($about, "</a> <?= bl_content_text('about', 'promises.team_text') ?> <a ") || !str_contains($about, "</a> <?= bl_content_text('about', 'promises.team_suffix') ?>")) $fail[] = 'About team sentence lost its spaces';
if ($fail) { fwrite(STDERR, implode("\n", $fail) . "\n"); exit(1); }
echo "content text ok\n";
