<?php
declare(strict_types=1);

/** Business values remain in the existing Support terms; templates never persist expanded prose. */
function bl_business_variables(array $terms): array
{
    $warranty = (string) $terms['warranty']['value'];
    $lead = (string) $terms['lead']['value'];
    $moq = (string) $terms['moq']['value'];
    $words = ['0' => 'zero', '1' => 'one', '2' => 'two', '3' => 'three', '4' => 'four', '5' => 'five', '6' => 'six', '7' => 'seven', '8' => 'eight', '9' => 'nine', '10' => 'ten', '11' => 'eleven', '12' => 'twelve'];
    return [
        '{warranty_months}' => $warranty,
        '{warranty_months_upper}' => strtoupper($words[$warranty] ?? $warranty),
        '{lead_time}' => $lead,
        '{lead_time_words}' => str_replace(['–', '—'], ' to ', $lead),
        '{minimum_order}' => $moq,
        '{minimum_order_noun}' => $moq === '1' ? 'machine' : 'machines',
        '{minimum_order_verb}' => $moq === '1' ? 'is' : 'are',
        '{minimum_order_words}' => $words[$moq] ?? $moq,
        '{minimum_order_sentence}' => ucfirst($words[$moq] ?? $moq),
    ];
}

/** Expand only home and Support prose, leaving all data keys, terms and saved overrides intact. */
function bl_business_render(string $page, array $content): array
{
    if (!in_array($page, ['home', 'support'], true)) return $content;
    $terms = $page === 'support' ? $content['terms'] : bl_editable('support')['terms'];
    $variables = bl_business_variables($terms);
    $walk = static function ($value) use (&$walk, $variables) {
        if (is_array($value)) return array_map($walk, $value);
        return is_string($value) ? strtr($value, $variables) : $value;
    };
    foreach ($content as $key => $value) if ($key !== 'terms') $content[$key] = $walk($value);
    return $content;
}

/** Only exact former stock copy adopts variables; authored text remains verbatim for editorial review. */
function bl_business_migrate(string $page, array $saved): array
{
    if (!in_array($page, ['home', 'support'], true)) return $saved;
    $defaults = bl_content_defaults($page);
    $variables = bl_business_variables(bl_support_defaults()['terms']);
    $walk = static function (array $value, array $defaults) use (&$walk, $variables): array {
        foreach ($value as $key => $item) {
            $default = $defaults[$key] ?? null;
            // A reordered list is matched row by row against every stock row, field by field, not by position.
            if (is_array($item) && is_array($default) && array_is_list($item) && array_is_list($default)) $value[$key] = bl_business_migrate_list($item, $default, $variables);
            elseif (is_array($item) && is_array($default)) $value[$key] = $walk($item, $default);
            elseif (is_string($item) && is_string($default) && $item === strtr($default, $variables)) $value[$key] = $default;
        }
        return $value;
    };
    return $walk($saved, $defaults);
}

/** Rows of a saved list whose fields still read as a stock row's former wording take that row's variable form. */
function bl_business_migrate_list(array $rows, array $defaults, array $variables): array
{
    $stock = [];
    foreach ($defaults as $row) {
        if (is_string($row)) $stock[''][strtr($row, $variables)] = $row;
        elseif (is_array($row)) foreach ($row as $field => $text) if (is_string($text)) $stock[$field][strtr($text, $variables)] = $text;
    }
    foreach ($rows as $i => $row) {
        if (is_string($row) && isset($stock[''][$row])) $rows[$i] = $stock[''][$row];
        elseif (is_array($row)) foreach ($row as $field => $text) if (is_string($text) && isset($stock[$field][$text])) $rows[$i][$field] = $stock[$field][$text];
    }
    return $rows;
}
