<?php
declare(strict_types=1);

/** Request-local index of loaded records. Exact slugs reserve their keys before historical slugs are added. */
function bl_record_slug_index(array $records): array
{
    static $indexes = [];
    $key = hash('sha256', serialize($records));
    if (isset($indexes[$key])) return $indexes[$key];
    $index = [];
    foreach ($records as $record) $index[$record['slug']] ??= $record;
    foreach ($records as $record) foreach ((array) ($record['_wp_old_slug'] ?? []) as $slug) {
        if (is_string($slug) && $slug !== '') $index[$slug] ??= $record;
    }
    return $indexes[$key] = $index;
}

/** Current names always win; case folding matches the existing model and attachment readers. */
function bl_named_record_index(array $records, string $field, string $aliases): array
{
    $index = [];
    foreach ($records as $record) $index[strtolower((string) $record[$field])] = $record;
    foreach ($records as $record) foreach ((array) ($record[$aliases] ?? []) as $name) {
        if (is_string($name) && $name !== '') $index[strtolower($name)] ??= $record;
    }
    return $index;
}

/** Merge stored history in the same product record; callers exclude submitted alias fields. */
function bl_product_model_aliases(array $record, array $previous): array
{
    $old = (string) ($previous['model'] ?? '');
    $aliases = [...(array) ($record['model_aliases'] ?? []), ...(array) ($previous['model_aliases'] ?? []), $old];
    $aliases = array_values(array_unique(array_filter($aliases, static fn($name) => is_string($name) && $name !== '' && strcasecmp($name, (string) ($record['model'] ?? '')) !== 0)));
    if ($aliases || array_key_exists('model_aliases', $record)) $record['model_aliases'] = $aliases;
    return $record;
}

/**
 * Restoring the loaders page matches rows the same way a save does (same name, or same position with the same picture,
 * group and use), so the restored row keeps the names it had since, in either direction.
 */
function bl_attachment_restore_aliases(array $rows, array $previous): array
{
    // A current row that lists the restored name among its aliases is that same attachment, wherever it now sits.
    $byAlias = [];
    foreach ($previous as $i => $row) foreach ((array) ($row['aliases'] ?? []) as $alias) {
        if (is_string($alias) && $alias !== '') $byAlias[strtolower($alias)][] = $i;
    }
    $currentNames = array_map(static fn($row) => strtolower((string) ($row['name'] ?? '')), $previous);
    $rows = bl_attachment_row_aliases($rows, $previous);
    foreach ($rows as &$row) {
        $name = strtolower((string) ($row['name'] ?? ''));
        if ($name === '' || in_array($name, $currentNames, true) || count(array_unique($byAlias[$name] ?? [])) !== 1) continue;
        $old = $previous[$byAlias[$name][0]];
        $aliases = [...(array) ($row['aliases'] ?? []), ...(array) ($old['aliases'] ?? []), (string) ($old['name'] ?? '')];
        $row['aliases'] = array_values(array_unique(array_filter($aliases, static fn($alias) => is_string($alias) && $alias !== '' && strcasecmp($alias, (string) $row['name']) !== 0)));
    }
    unset($row);
    return $rows;
}

/**
 * Resolve loading keys in memory. An explicit current key (even empty) wins; otherwise the most recent old address that
 * has figures does (WordPress keeps old slugs oldest first), so a further rename never falls back to older figures.
 */
function bl_loading_aliases(array $loading, array $products): array
{
    $index = bl_record_slug_index($products);
    foreach ($products as $product) {
        $current = (string) ($product['slug'] ?? '');
        if ($current === '' || array_key_exists($current, $loading)) continue;
        foreach (array_reverse((array) ($product['_wp_old_slug'] ?? [])) as $old) {
            if (is_string($old) && array_key_exists($old, $loading) && ($index[$old]['slug'] ?? '') === $current) { $loading[$current] = $loading[$old]; break; }
        }
    }
    return $loading;
}

/** Preserve history across reordering; infer a rename only at the same position with unique, unchanged row evidence. */
function bl_attachment_row_aliases(array $rows, array $previous): array
{
    $names = array_count_values(array_column($previous, 'name'));
    $images = array_count_values(array_column($previous, 'image'));
    $newImages = array_count_values(array_column($rows, 'image'));
    $newNames = array_count_values(array_column($rows, 'name'));
    foreach ($rows as $i => &$row) {
        $old = null;
        foreach ($previous as $candidate) {
            if ($candidate['name'] === $row['name'] && ($names[$row['name']] ?? 0) === 1 && ($newNames[$row['name']] ?? 0) === 1) { $old = $candidate; break; }
        }
        if ($old === null && count($rows) === count($previous) && isset($previous[$i])) {
            $candidate = $previous[$i];
            $image = (string) ($row['image'] ?? '');
            if ($image !== '' && ($images[$image] ?? 0) === 1 && ($newImages[$image] ?? 0) === 1
                && !isset($newNames[$candidate['name']]) && !isset($names[$row['name']])
                && $image === ($candidate['image'] ?? '') && ($row['group'] ?? '') === ($candidate['group'] ?? '') && ($row['use'] ?? '') === ($candidate['use'] ?? '')) $old = $candidate;
        }
        if ($old === null) continue;
        // Aliases only grow: the row's own, the matched row's, and the matched row's name if it differs.
        $aliases = [...(array) ($row['aliases'] ?? []), ...(array) ($old['aliases'] ?? []), (string) ($old['name'] ?? '')];
        $aliases = array_values(array_unique(array_filter($aliases, static fn($name) => is_string($name) && $name !== '' && strcasecmp($name, (string) ($row['name'] ?? '')) !== 0)));
        if ($aliases) $row['aliases'] = $aliases;
    }
    unset($row);
    return $rows;
}
