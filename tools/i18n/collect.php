<?php
declare(strict_types=1);
/**
 * Lists the text on pages that a language's dictionary does not have yet, as JSON (key => ""), ready to translate.
 *
 *   php tools/i18n/collect.php es page1.html page2.html … > missing.json
 *
 * The pages are saved English pages (curl https://bobliftloader.com/products/mini-dumpers/ > page.html) or pages under
 * the language's folder: the keys are the same. Text that needs no translation (figures, model codes, addresses, names)
 * is listed in tools/i18n/keep.txt, one key per line, and left out.
 */
require __DIR__ . '/../../inc/helpers.php';
require __DIR__ . '/../../inc/i18n.php';

[$script, $lang] = [$argv[0], $argv[1] ?? ''];
if (!bl_i18n_language($lang) || count($argv) < 3) {
    fwrite(STDERR, "Usage: php tools/i18n/collect.php <lang> <page.html>…\n");
    exit(1);
}
$dictionary = bl_i18n_dictionary($lang);
$keep = is_file(__DIR__ . '/keep.txt') ? array_flip(array_map('bl_i18n_key', file(__DIR__ . '/keep.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES))) : [];
$missing = [];
foreach (array_slice($argv, 2) as $file) {
    foreach (bl_i18n_translate_html((string) file_get_contents($file), $lang, true) as $key) {
        $key = (string) $key;
        if (isset($dictionary[$key]) || isset($keep[$key]) || isset($missing[$key])) continue;
        // Nothing to translate: no word of two letters or more (figures, units, codes), an address, a URL.
        if (!preg_match('/\p{Ll}{2}|\p{Lu}\p{Ll}|\p{Lu}{3,}/u', $key) || preg_match('~^(https?://\S+|[\w.+-]+@[\w-]+(\.[\w-]+)+)$~', $key)) continue;
        $missing[$key] = '';
    }
}
echo json_encode($missing, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), "\n";
