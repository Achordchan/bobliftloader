<?php
/** Keep the independent-form JavaScript regression in the database-free PHP test entry points. */
declare(strict_types=1);
passthru('node ' . escapeshellarg(__DIR__ . '/fixtures/check-content-editor.js'), $status);
exit($status);
