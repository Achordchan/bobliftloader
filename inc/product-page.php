<?php
declare(strict_types=1);

/**
 * What a product page shows below its specification, gathered from data the site already keeps: the attachments and jobs
 * on the family pages, the applications and cases that name the model, its container loading, and the family's shared
 * block (网站内容 → 滑移装载机 / 迷你翻斗车 → 产品页通用模块). A product's own fields (photos, highlights, questions, chosen
 * attachments; 设备产品 → 编辑 → 产品页模块) come first. Every part is empty when there is nothing true to show, and the
 * template leaves its section out.
 */

/** Kinds of photograph a product can carry below its specification; a drawing sits beside the specification instead. */
const BL_PHOTO_GROUPS = ['at-work' => 'At work', 'detail' => 'Details', 'factory' => 'In the factory', 'delivery' => 'Packing & delivery', 'drawing' => 'Dimension drawing'];

function bl_product_family_key(array $product): string
{
    return $product['category'] === 'mini-dumpers' ? 'dumpers' : 'loaders';
}

/** The shared block of the product's family. */
function bl_product_shared(array $product): array
{
    return bl_editable(bl_product_family_key($product))['product'] ?? [];
}

/** A stored image path (theme path images/… or a full address) as the page loads it. */
function bl_product_media(string $path): string
{
    if ($path === '') return '';
    return str_starts_with($path, 'images/') || str_starts_with($path, 'videos/') ? bl_asset($path) : $path;
}

/** The search description: the one written for search results, else the card summary. */
function bl_product_description(array $product): string
{
    $written = trim((string) ($product['seo_description'] ?? ''));
    return $written !== '' ? $written : (string) $product['excerpt'];
}

/**
 * The photographs beside the main image, each with its alternative text: the text written for it, else one that names
 * the model and the picture's place in the set, so no product photograph goes without a description.
 */
function bl_product_gallery(array $product): array
{
    $paths = array_slice(($product['gallery'] ?? []) ?: array_filter([$product['image']]), 0, BL_CONTENT_LIMITS['gallery']);
    $written = is_array($product['gallery_alt'] ?? null) ? $product['gallery_alt'] : [];
    $count = count($paths);
    $images = [];
    foreach (array_values($paths) as $i => $src) {
        $raw = (string) ($product['gallery_paths'][$i] ?? $src);
        $alt = trim((string) ($written[$raw] ?? ''));
        $images[] = ['src' => $src, 'alt' => $alt !== '' ? $alt : $product['name'] . ($count > 1 ? ', photograph ' . ($i + 1) . ' of ' . $count : '')];
    }
    // The main image (右侧“产品主图”, else 图片 → 主图) opens the page. When the gallery leaves it out it goes first, so it
    // stays reachable from the thumbnails and the lightbox.
    if ($product['image'] && bl_product_main_index($product, $images) === null) array_unshift($images, ['src' => $product['image'], 'alt' => $product['name']]);
    return $images;
}

/** Where the main image sits in the gallery, matched by path (WordPress serves sized copies such as name-1024x768.webp beside the file). */
function bl_product_main_index(array $product, array $images): ?int
{
    $file = static fn(string $url): string => (string) preg_replace('/-\d+x\d+(?=\.\w+$)/', '', (string) parse_url($url, PHP_URL_PATH));
    $main = $file((string) $product['image']);
    foreach ($images as $i => $image) if ($file($image['src']) === $main) return $i;
    return null;
}

/** The product's own photographs of one kind or several: [src, alt, caption, group]. */
function bl_product_photos(array $product, array $groups): array
{
    $photos = [];
    foreach (array_slice((array) ($product['photos'] ?? []), 0, BL_CONTENT_LIMITS['photos']) as $photo) {
        if (!is_array($photo) || empty($photo['src']) || !in_array($photo['group'] ?? '', $groups, true)) continue;
        $photos[] = ['src' => bl_product_media((string) $photo['src']), 'alt' => (string) ($photo['alt'] ?? '') ?: $product['model'] . ' ' . strtolower(BL_PHOTO_GROUPS[$photo['group']]), 'caption' => (string) ($photo['caption'] ?? ''), 'group' => $photo['group']];
    }
    return array_slice($photos, 0, $groups === ['drawing'] ? BL_CONTENT_LIMITS['drawing'] : BL_CONTENT_LIMITS['photos']);
}

/** Up to three short points that set this model apart, written per product. */
function bl_product_highlights(array $product): array
{
    $items = [];
    foreach ((array) ($product['highlights'] ?? []) as $item) {
        if (is_array($item) && trim((string) ($item['title'] ?? '')) !== '') $items[] = ['title' => (string) $item['title'], 'text' => (string) ($item['text'] ?? '')];
    }
    return array_slice($items, 0, BL_CONTENT_LIMITS['highlights']);
}

/**
 * The attachments a loader page shows: the ones ticked on the product, else the family's default list, matched by name
 * to the attachments on the loaders page (so each keeps its picture and use). Dumpers take no attachments.
 */
function bl_product_attachments(array $product): array
{
    if ($product['category'] !== 'skid-steer-loaders') return [];
    $names = array_values(array_filter(array_map('strval', (array) ($product['attachments'] ?? []))));
    if (!$names) $names = array_map('trim', explode(',', (string) (bl_product_shared($product)['attachments'] ?? '')));
    $byName = [];
    foreach (bl_attachments() as $items) foreach ($items as $item) $byName[strtolower($item['name'])] = $item;
    $chosen = [];
    foreach ($names as $name) if (isset($byName[strtolower($name)]) && !in_array($byName[strtolower($name)], $chosen, true)) $chosen[] = $byName[strtolower($name)];
    return array_slice($chosen, 0, BL_CONTENT_LIMITS['attachments']);
}

/** The dumper jobs on the family page that name this model. */
function bl_product_dumper_jobs(array $product): array
{
    if ($product['category'] !== 'mini-dumpers') return [];
    return array_values(array_filter(bl_dumper_jobs(), static fn(array $job): bool => in_array($product['slug'], array_column($job['models'], 'slug'), true)));
}

/** The applications that recommend this model by name (网站内容 → 应用场景 → 推荐机型). */
function bl_product_applications(array $product): array
{
    $found = [];
    foreach ($GLOBALS['applications'] ?? [] as $application) {
        if (in_array($product['slug'], array_column(bl_application_models($application), 'slug'), true)) $found[] = $application;
    }
    return $found;
}

/** Application cases that list this model among the machines used. */
function bl_product_cases(array $product): array
{
    return array_slice(array_values(array_filter(bl_application_cases(), static fn(array $case): bool => in_array($product['slug'], (array) ($case['products'] ?? []), true))), 0, 3);
}

/** Machines per 20GP and 40HQ container for this model, or null when no figure is published. */
function bl_product_loading(array $product): ?array
{
    return bl_container_loading()[$product['slug']] ?? null;
}

/** The family's factory and inspection block, with at most three photographs; empty when it holds neither words nor checks. */
function bl_product_factory(array $product): array
{
    $factory = bl_product_shared($product)['factory'] ?? [];
    if (!is_array($factory)) return [];
    $factory['images'] = array_slice(array_values(array_filter((array) ($factory['images'] ?? []), static fn($image): bool => is_array($image) && ($image['image'] ?? '') !== '')), 0, BL_CONTENT_LIMITS['factory']);
    $factory['checks'] = array_values(array_filter((array) ($factory['checks'] ?? []), static fn($check): bool => is_array($check) && ($check['title'] ?? '') !== ''));
    return ($factory['text'] ?? '') === '' && !$factory['checks'] ? [] : $factory;
}

/** The questions on the page: the product's own first, then the family's, five at most, no question twice. */
function bl_product_faq(array $product): array
{
    $items = [];
    $seen = [];
    foreach ([(array) ($product['faq'] ?? []), (array) (bl_product_shared($product)['faq'] ?? [])] as $list) {
        foreach ($list as $item) {
            $q = is_array($item) ? trim((string) ($item['q'] ?? '')) : '';
            if ($q === '' || trim((string) ($item['a'] ?? '')) === '' || isset($seen[strtolower($q)])) continue;
            $seen[strtolower($q)] = true;
            $row = ['q' => $q, 'a' => (string) $item['a']];
            if (($item['link_label'] ?? '') !== '' && ($item['link_url'] ?? '') !== '') $row['link'] = [(string) $item['link_label'], (string) $item['link_url']];
            $items[] = $row;
        }
    }
    return array_slice($items, 0, BL_CONTENT_LIMITS['faq']);
}

/**
 * The model with the next smaller and next larger rated load in its family, for the comparison under the page; models
 * without a rated load sort by name. Returns the models in order of load, this one included.
 */
function bl_product_neighbours(array $product): array
{
    $family = array_values(array_filter(bl_products(), static fn(array $p): bool => $p['category'] === $product['category']));
    usort($family, static fn(array $a, array $b): int => [(int) $a['load'], $a['model']] <=> [(int) $b['load'], $b['model']]);
    $index = array_search($product['slug'], array_column($family, 'slug'), true);
    if ($index === false || count($family) < 2) return [];
    $start = max(0, min($index - 1, count($family) - 3));
    return array_slice($family, $start, 3);
}

/** The rows the comparison shows: the figures buyers set side by side, in that order, where this model publishes them. */
function bl_product_compare_rows(array $product, array $models): array
{
    $keys = ['Rated load', 'Bucket capacity', 'Drum capacity', 'Engine power', 'Machine weight', 'Machine width', 'Standard dimensions', 'Working height', 'Max travel speed', 'Travel speed', 'Drive', 'Running gear', 'Operator station'];
    $rows = [];
    foreach ($keys as $key) {
        if (empty($product['specs'][$key])) continue;
        $rows[$key] = array_map(static fn(array $m): string => (string) ($m['specs'][$key] ?? '—'), $models);
        if (count($rows) === 6) break;
    }
    return $rows;
}
