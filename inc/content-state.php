<?php
declare(strict_types=1);
require_once __DIR__ . '/downloads.php';

/** Dynamic maps are declared by page and path; all other groups retain their fixed schema. */
function bl_content_dynamic_paths(string $page): array
{
    return $page === 'support' ? ['containers.loading'] : [];
}

/** Version 1 preserves legacy empty-field fallback; subsequent explicit empty edits are authoritative. */
function bl_content_migrate(string $page, array $saved): array
{
    if ($page === 'home' && ($saved['_bl_schema_version'] ?? 0) < 1) $saved = bl_migrate_home_facts($saved);
    if ($page === 'applications' && ($saved['_bl_schema_version'] ?? 0) < 1) {
        foreach ($saved['jobs'] ?? [] as $slug => $job) {
            if (!is_array($job)) continue;
            foreach (['subtitle', 'description', 'checks'] as $key) {
                if (isset($job[$key]) && ($job[$key] === '' || $job[$key] === [])) unset($saved['jobs'][$slug][$key]);
            }
        }
    }
    $saved = bl_business_migrate($page, $saved);
    $saved['_bl_schema_version'] = 1;
    return $saved;
}

function bl_content_resolve(string $page, array $saved): array
{
    if ($page === 'downloads') return bl_downloads_content($saved);
    $content = bl_merge_content(bl_content_defaults($page), bl_content_migrate($page, $saved), bl_content_dynamic_paths($page));
    return $content;
}

/** A hash includes the stored revision, detecting edits even when a reset returns to identical content. */
function bl_content_revision(array $saved, string $page = ''): string
{
    return hash('sha256', serialize($saved));
}

/** Application links keep external destinations and same-page anchors intact. */
function bl_application_link(string $url): string
{
    if (preg_match('~^(?:https?://|mailto:|tel:|#|\?)~i', $url)) return $url;
    return bl_url($url);
}

/** Restore the complete snapshot while retaining row history from the value read under the page lock. */
function bl_content_restore_values(string $page, array $snapshot, array $previous = []): array
{
    if ($page === 'downloads') { unset($snapshot['_bl_history']); return $snapshot; }
    $restored = bl_content_migrate($page, $snapshot);
    if ($page === 'loaders') {
        // Snapshots hold overrides only: compare the rows as they will show (defaults filled in) on both sides, and keep
        // the merged rows even when the snapshot had none of its own, so their alias history is not lost.
        $rows = bl_content_resolve($page, $snapshot)['attachments']['items'] ?? [];
        $before = bl_content_resolve($page, $previous)['attachments']['items'] ?? [];
        $merged = bl_attachment_restore_aliases($rows, $before);
        if ($merged !== $rows || isset($restored['attachments']['items'])) $restored['attachments']['items'] = $merged;
    }
    return $restored;
}
