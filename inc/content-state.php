<?php
declare(strict_types=1);

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
    $saved['_bl_schema_version'] = 1;
    return $saved;
}

function bl_content_resolve(string $page, array $saved): array
{
    return bl_merge_content(bl_content_defaults($page), bl_content_migrate($page, $saved), bl_content_dynamic_paths($page));
}

/**
 * A hash includes the stored revision, detecting edits even when a reset returns to identical content. Global settings
 * also show the service WhatsApp number inherited from Support, so a change there makes an open settings form stale too.
 */
function bl_content_revision(array $saved, string $page = ''): string
{
    if ($page === 'global') {
        // Read Support past the options cache, as the lock reads the page itself (another request may have just saved it).
        $support = function_exists('bl_content_saved_now') ? bl_content_saved_now('support') : [];
        $saved = [$saved, bl_content_resolve('support', $support)['after']['whatsapp_number']];
    }
    return hash('sha256', serialize($saved));
}

/** Application links keep external destinations and same-page anchors intact. */
function bl_application_link(string $url): string
{
    if (preg_match('~^(?:https?://|mailto:|tel:|#|\?)~i', $url)) return $url;
    return bl_url($url);
}
