<?php
/** php tests/test_promo_upgrade.php — a site with its own saved list of Spanish pages gets the offers page added once. */
declare(strict_types=1);
$GLOBALS['options'] = [];
function get_option(string $name, $default = false) { return $GLOBALS['options'][$name] ?? $default; }
function update_option(string $name, $value, $autoload = null): bool { $GLOBALS['options'][$name] = $value; return true; }
require __DIR__ . '/../inc/promo.php';
$fail = static function (string $what): void { fwrite(STDERR, $what . "\n"); exit(1); };

// A saved list keeps every page it had and gains the offers page.
$GLOBALS['options'] = ['bl_i18n_es_pages' => ['/', '/about/']];
if (!bl_promo_add_spanish_page() || $GLOBALS['options']['bl_i18n_es_pages'] !== ['/', '/about/', BL_PROMO_PATH]) $fail('saved list not extended');
// Once done, taking the page out again sticks.
$GLOBALS['options']['bl_i18n_es_pages'] = ['/', '/about/'];
if (bl_promo_add_spanish_page() || in_array(BL_PROMO_PATH, $GLOBALS['options']['bl_i18n_es_pages'], true)) $fail('added twice');
// No saved list: left alone (the defaults have the page); a list that has it already is not doubled.
$GLOBALS['options'] = [];
if (!bl_promo_add_spanish_page() || isset($GLOBALS['options']['bl_i18n_es_pages'])) $fail('defaults touched');
$GLOBALS['options'] = ['bl_i18n_es_pages' => ['/', BL_PROMO_PATH]];
bl_promo_add_spanish_page();
if ($GLOBALS['options']['bl_i18n_es_pages'] !== ['/', BL_PROMO_PATH]) $fail('page listed twice');
echo "promo upgrade: ok\n";
