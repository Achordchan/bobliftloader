<?php
/**
 * php tests/test_showcase_i18n.php — corrections saved in the settings (bl_i18n_es_strings) reach the text the 3D
 * showcase's scripts write, not only the markup the theme translates (templates/bl360.php, bl_showcase_script_corrections).
 */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';

$failures = 0;
$check = static function (bool $ok, string $what) use (&$failures): void {
    if (!$ok) { fwrite(STDERR, "FAIL: $what\n"); $failures++; }
};

$english = json_decode((string) file_get_contents(__DIR__ . '/../templates/bl360/script-strings.json'), true);
$check(is_array($english) && in_array('Bucket', $english, true) && in_array('Bucket tilt', $english, true), 'the build lists the English of the scripts\' rows');

$GLOBALS['options']['bl_i18n_es_strings'] = ['Bucket' => 'Cazo', 'Skid steer loader' => 'Minicargadora compacta', 'Bucket tilt' => ' '];
$GLOBALS['bl_lang'] = 'en';
$check(bl_showcase_script_corrections() === [], 'nothing on the English page');
$GLOBALS['bl_lang'] = 'es';
$corrections = bl_showcase_script_corrections();
$check($corrections === ['Bucket' => 'Cazo'], 'only saved corrections of the scripts\' own text, empty ones left out: ' . json_encode($corrections, JSON_UNESCAPED_UNICODE));

// The progress caption written before the translation script loads is in the page's language too.
$head = bl_showcase_markup('head.html');
$check(!str_contains($head, '{{LOADING}}') && str_contains($head, ': "Cargando el modelo 3D" + \' \' + n + \'%\''), 'the early loading caption is in Spanish');
$GLOBALS['bl_lang'] = 'en';
$check(str_contains(bl_showcase_markup('head.html'), ': "Loading the 3D model" + \' \' + n + \'%\''), 'and in English on the English page');
$GLOBALS['bl_lang'] = 'es';

// The page's translator leaves the script that carries them alone.
$island = '<script>window.BL360_I18N_SAVED = ' . json_encode($corrections, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) . ';</script>';
$check(str_contains(bl_i18n_translate_html($island . '<p>Bucket</p>', 'es'), $island . '<p>Cazo</p>'), 'the corrections reach the page as written');

// The built script uses them over the Spanish it was built with, and keeps the built Spanish for the rest.
$probe = <<<'JS'
const fs = require('fs'), vm = require('vm');
const window = { BL360_I18N_SAVED: JSON.parse(process.argv[1]) };
const document = { title: '', documentElement: { lang: 'es', style: { setProperty() {} } }, getElementById: () => null };
const context = vm.createContext({ window, document, location: { search: '', href: 'https://example.test/es/bl-t360-t460/' }, navigator: { languages: ['es'] }, localStorage: { getItem: () => null, setItem() {} }, URLSearchParams, URL, Map, RegExp, JSON, Object, String, NodeFilter: {} });
for (const file of ['strings.js', 'i18n.js']) vm.runInContext(fs.readFileSync('assets/bl360/i18n/' + file, 'utf8'), context);
process.stdout.write(JSON.stringify([window.I18N.t('铲斗'), window.I18N.t('铲斗翻转'), window.I18N.lang]));
JS;
$out = shell_exec('cd ' . escapeshellarg(dirname(__DIR__)) . ' && node -e ' . escapeshellarg($probe) . ' ' . escapeshellarg(json_encode($corrections, JSON_UNESCAPED_UNICODE)) . ' 2>&1');
$check($out === json_encode(['Cazo', bl_t('Bucket tilt', 'es'), 'es'], JSON_UNESCAPED_UNICODE), 'the showcase script uses the saved correction: ' . $out);

if ($failures) exit(1);
echo "showcase i18n: ok\n";
