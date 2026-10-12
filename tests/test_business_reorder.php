<?php
/** Stock wording in a list the editor reordered still adopts the shared purchasing figures. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
$defaults = bl_content_defaults('support');
$variables = bl_business_variables(bl_support_defaults()['terms']);
// Find a list whose rows use a purchasing variable, expand it to the old literal wording, then reverse its order.
$found = null;
$scan = static function (array $node, array $path) use (&$scan, &$found): void {
    foreach ($node as $key => $value) {
        if ($found || !is_array($value)) continue;
        if (array_is_list($value) && count($value) > 1 && str_contains(serialize($value), '{')) { $found = [...$path, $key]; return; }
        $scan($value, [...$path, $key]);
    }
};
$scan($defaults, []);
if (!$found) { fwrite(STDERR, "no list with purchasing variables in the Support defaults\n"); exit(1); }
$list = $defaults; foreach ($found as $key) $list = $list[$key];
$expand = static function ($row) use (&$expand, $variables) { return is_array($row) ? array_map($expand, $row) : (is_string($row) ? strtr($row, $variables) : $row); };
$saved = []; $ref = &$saved; foreach ($found as $key) { $ref[$key] = []; $ref = &$ref[$key]; } $ref = array_reverse(array_map($expand, $list)); unset($ref);
$migrated = bl_business_migrate('support', $saved);
$out = $migrated; foreach ($found as $key) $out = $out[$key];
if ($out !== array_reverse($list)) { fwrite(STDERR, "reordered stock rows were not migrated to variables\n"); exit(1); }
// A saved copy of the former people-section step keeps following the warranty months.
$former = strtr(bl_home_former_steps()[0]['text'], $variables);
$legacyHome = bl_business_migrate('home', ['approach' => ['steps' => [['title' => 'Keep the conversation going.', 'text' => $former]]]]);
if (($legacyHome['approach']['steps'][0]['text'] ?? '') !== bl_home_former_steps()[0]['text']) { fwrite(STDERR, "saved former home warranty step lost its variable\n"); exit(1); }
echo "business reorder ok (" . implode('.', $found) . ")\n";
