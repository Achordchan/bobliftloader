<?php
declare(strict_types=1);

const BL_DOWNLOAD_TYPES = ['catalogue' => '产品画册', 'manual' => '用户手册', 'parts' => '配件目录', 'specification' => '规格表', 'other' => '其它'];
const BL_DOWNLOAD_SCOPES = ['all' => '全部', 'skid-steer-loaders' => '滑移装载机', 'mini-dumpers' => '迷你翻斗车', 'models' => '指定型号'];

/** An absent list inherits legacy values at read time; no migration writes other options. */
function bl_downloads_content(array $saved): array
{
    return ['items' => $saved['items'] ?? [[
        'id' => 'catalogue', 'url' => bl_is_wordpress() ? (string) get_option('bl_catalogue_url', '') : '',
        'title' => '', 'description' => '', 'type' => 'catalogue', 'scope' => 'all', 'models' => '',
    ]]];
}

function bl_downloads_saved(): array
{
    $saved = bl_is_wordpress() ? get_option('bl_downloads', []) : [];
    return is_array($saved) ? $saved : [];
}

/** Resolve local sizes without requesting external URLs. Legacy sizes remain valid only for the same legacy URL. */
function bl_download_file(string $url): array
{
    if ($url === '') {
        $local = 'downloads/boblift-compact-equipment-catalogue.pdf';
        return ['url' => bl_asset($local), 'size' => is_file(dirname(__DIR__) . '/assets/' . $local) ? (int) filesize(dirname(__DIR__) . '/assets/' . $local) : 0];
    }
    $size = 0;
    if (bl_is_wordpress()) {
        if ($url === (string) get_option('bl_catalogue_url', '')) $size = (int) get_option('bl_catalogue_size', 0);
        // Preserve an existing legacy display exactly; new media links read their actual local file size.
        elseif (function_exists('attachment_url_to_postid')) {
            $id = attachment_url_to_postid($url);
            $file = $id ? get_attached_file($id) : '';
            if ($file && is_file($file)) $size = (int) filesize($file);
        }
    }
    return ['url' => $url, 'size' => $size];
}

function bl_download_size(int $bytes): string
{
    return $bytes ? number_format($bytes / 1048576, 1) . ' MB' : '';
}

/** The stable primary row supplies all existing catalogue-only consumers, regardless of list order. */
function bl_downloads_catalogue_row(?array $saved = null): array
{
    foreach (bl_content_resolve('downloads', $saved ?? bl_downloads_saved())['items'] as $row) if ($row['id'] === 'catalogue') return $row;
    return bl_downloads_content([])['items'][0];
}

function bl_downloads_extended(): bool
{
    $items = bl_content_resolve('downloads', bl_downloads_saved())['items'];
    if (count($items) > 1) return true;
    $row = $items[0] ?? [];
    return ($row['title'] ?? '') !== '' || ($row['description'] ?? '') !== '' || ($row['scope'] ?? 'all') !== 'all';
}

/** Resolve model aliases on reads, leaving saved references and product records untouched. */
function bl_downloads_for(?array $product = null): array
{
    $result = [];
    $index = $product ? bl_named_record_index(bl_products(), 'model', 'model_aliases') : [];
    foreach (bl_content_resolve('downloads', bl_downloads_saved())['items'] as $row) {
        if ($product && $row['scope'] !== 'all' && $row['scope'] !== ($product['category'] ?? '')) {
            if ($row['scope'] !== 'models') continue;
            $matches = false;
            foreach (explode(',', $row['models']) as $model) {
                $candidate = $index[strtolower(trim($model))] ?? null;
                if ($candidate && $candidate['slug'] === $product['slug']) $matches = true;
            }
            if (!$matches) continue;
        }
        $file = bl_download_file($row['url']);
        $extension = strtoupper(pathinfo((string) parse_url($file['url'], PHP_URL_PATH), PATHINFO_EXTENSION));
        $kind = preg_match('/^[A-Z0-9]{1,10}$/', $extension) ? $extension : 'File';
        $size = bl_download_size($file['size']);
        $copy = bl_editable('support')['downloads'];
        $result[] = array_merge($row, ['url' => $file['url'], 'size' => $file['size'], 'kind' => $kind . ($size ? ' · ' . $size : ''),
            'title' => $row['title'] !== '' ? $row['title'] : $copy['catalogue_title'],
            'text' => $row['description'] !== '' ? $row['description'] : ($row['id'] === 'catalogue' ? $copy['catalogue_text'] : ''), 'download' => true]);
    }
    return $result;
}
