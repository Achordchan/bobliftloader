<?php
declare(strict_types=1);
/** Baseline hashes come from the P0 commit, not from the modified templates. No database or web server is needed. */
$baseline = json_decode(file_get_contents(__DIR__ . '/fixtures/backend-p1-default-html.json'), true, 512, JSON_THROW_ON_ERROR);
$failures = [];
foreach ($baseline['sha256'] as $path => $expected) {
    $process = proc_open([PHP_BINARY, __DIR__ . '/fixtures/render-backend-p1.php', $path], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Cannot start isolated PHP renderer.');
    $html = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    $status = proc_close($process);
    // Deployment timestamps and the automatic copyright year are the only variable tokens.
    $html = preg_replace('~(/assets/(?:js|css)/[^"?]+\?ver=)\d+~', '${1}DEPLOY_TIMESTAMP', $html);
    $html = preg_replace('/© \d{4}/u', '© COPYRIGHT_YEAR', $html);
    if ($status !== 0 || $errors !== '' || hash('sha256', $html) !== $expected) $failures[] = $path . ($errors !== '' ? ': ' . $errors : ': default HTML differs from P0');
}
if ($failures) { fwrite(STDERR, implode("\n", $failures) . "\n"); exit(1); }
echo 'backend P1 default HTML: ' . count($baseline['sha256']) . " pages match P0 (asset mtimes/year normalized)\n";
