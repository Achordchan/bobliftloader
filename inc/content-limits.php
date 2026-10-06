<?php
declare(strict_types=1);

/** Display limits only: editors and renderers share these; persistence retains overflow. */
const BL_CONTENT_LIMITS = [
    'faq' => 5, 'drawing' => 1, 'specs' => 40, 'factory' => 3,
    'highlights' => 3, 'attachments' => 8, 'photos' => 24, 'gallery' => 12, 'videos' => 8,
];
