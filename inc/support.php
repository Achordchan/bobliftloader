<?php
declare(strict_types=1);

/**
 * Buying, after-delivery and attachment content. Each fact lives here once: the Support page, the product pages and the
 * loader family page all read it from these functions. The words themselves live in inc/editable.php, editable in WordPress.
 */

/** The four terms a buyer asks about first, shown as the plate on the Support page and in short on product pages. */
function bl_support_terms(): array
{
    return bl_editable('support')['terms'];
}

/** From the first message to the machine leaving Xuzhou. A step's link is [url, label]. */
function bl_buying_steps(): array
{
    $steps = [];
    foreach (bl_editable('support')['buying']['steps'] as $step) {
        $row = ['title' => $step['title'], 'text' => $step['text']];
        if ($step['link_url'] !== '' && $step['link_label'] !== '') $row['link'] = [$step['link_url'], $step['link_label']];
        $steps[] = $row;
    }
    return $steps;
}

/** After delivery: what to send when something goes wrong, and what happens next. */
function bl_service_steps(): array
{
    return bl_editable('support')['after']['service_steps'];
}

/**
 * Approximate machines per container [20GP, 40HQ], open-seat versions with loader buckets taken off and packed alongside.
 * Estimated from each model's published dimensions; the loading plan is confirmed per order. A model without figures is left out.
 */
function bl_container_loading(): array
{
    return array_filter(bl_loading_aliases(bl_editable('support')['containers']['loading'], bl_products()), static fn($fit): bool => is_array($fit) && count($fit) === 2);
}

/** The catalogue on the Downloads list. WordPress can point it at an uploaded file (网站内容 → 资料下载). */
function bl_catalogue(): array
{
    $local = 'downloads/boblift-compact-equipment-catalogue.pdf';
    $url = bl_asset($local);
    $size = is_file(dirname(__DIR__) . '/assets/' . $local) ? (int) filesize(dirname(__DIR__) . '/assets/' . $local) : 0;
    if (bl_is_wordpress() && ($custom = (string) get_option('bl_catalogue_url'))) {
        $url = $custom;
        $size = (int) get_option('bl_catalogue_size');
    }
    return ['url' => $url, 'size' => $size ? number_format($size / 1048576, 1) . ' MB' : ''];
}

/**
 * Questions the sections above don't already answer, in page order: each run of consecutive questions with the same group
 * name becomes one group ['title', 'items'], so a group name may appear more than once. Answers may carry one link: [text, url].
 */
/** The certificate question was withdrawn (the certificates on file cover cranes only); an installation that saved the old stock list still has it. */
function bl_support_faq_retired(array $item): bool
{
    return stripos((string) ($item['q'] ?? ''), 'Which certificates do you hold') === 0 || str_contains((string) ($item['link_url'] ?? ''), '#certificates');
}

function bl_support_faq(): array
{
    $groups = [];
    foreach (bl_editable('support')['faq']['items'] as $item) {
        if (bl_support_faq_retired($item)) continue;
        $row = ['q' => $item['q'], 'a' => $item['a']];
        if ($item['link_label'] !== '' && $item['link_url'] !== '') $row['link'] = [$item['link_label'], $item['link_url']];
        if (!$groups || end($groups)['title'] !== $item['group']) $groups[] = ['title' => $item['group'], 'items' => []];
        $groups[array_key_last($groups)]['items'][] = $row;
    }
    return $groups;
}

/**
 * Attachments for the loaders, grouped by the work they do, groups in the order they first appear. They are quoted with a machine, not sold on their own. The rows are edited in WordPress (网站内容 → 滑移装载机).
 */
function bl_attachments(): array
{
    $groups = [];
    foreach (bl_editable('loaders')['attachments']['items'] as $item) {
        $row = ['name' => $item['name'], 'use' => $item['use'], 'image' => $item['image']];
        if (!empty($item['aliases'])) $row['aliases'] = $item['aliases'];
        $groups[$item['group']][] = $row;
    }
    return $groups;
}

/** Jobs a mini skid steer is bought for, shown above the attachments; each lists the tools it uses, by their names below. */
function bl_loader_jobs(): array
{
    $jobs = [];
    foreach (bl_editable('loaders')['jobs']['items'] as $job) {
        $tools = array_values(array_filter(array_map('trim', explode(',', $job['tools'])), 'strlen'));
        $jobs[] = ['title' => $job['title'], 'text' => $job['text'], 'tools' => $tools];
    }
    return $jobs;
}

/**
 * Jobs the mini dumpers are bought for, on the dumper family page. Each names its models by model name, separated by
 * commas; a name that matches a published mini dumper links to it, any other name is left out, so a job never points
 * at a model the site does not show.
 */
function bl_dumper_jobs(): array
{
    $byName = bl_dumper_model_index(bl_products());
    $jobs = [];
    foreach (bl_editable('dumpers')['jobs']['items'] as $job) {
        $models = [];
        foreach (array_filter(array_map('trim', explode(',', $job['models'])), 'strlen') as $name) {
            $product = $byName[strtolower($name)] ?? null;
            if ($product && !in_array($product, $models, true)) $models[] = $product;
        }
        $jobs[] = ['title' => $job['title'], 'text' => $job['text'], 'models' => $models];
    }
    return $jobs;
}

/**
 * Published mini dumpers by lower-case model name. A model renamed since (FY-30 is now FY30) is found under either name,
 * whichever one its record and the editor use; a model's own name always wins over another model's former name.
 */
function bl_dumper_model_index(array $products): array
{
    $index = bl_named_record_index(array_values(array_filter($products, static fn($p) => $p['category'] === 'mini-dumpers')), 'model', 'model_aliases');
    $aliases = [];
    foreach (BL_FORMER_MODELS as $model => $former) foreach ($former as $name) {
        // Current name written, record still on the former name; and former name written, record already renamed.
        if (isset($index[strtolower($name)])) $aliases[strtolower($model)] ??= $index[strtolower($name)];
        if (isset($index[strtolower($model)])) $aliases[strtolower($name)] ??= $index[strtolower($model)];
    }
    return $index + $aliases;
}

/** A product's videos: each has a source, a poster and a caption. Theme paths (videos/…) resolve to the theme's assets. */
function bl_product_videos(array $product): array
{
    $videos = [];
    foreach ((array) ($product['videos'] ?? []) as $video) {
        if (empty($video['src'])) continue;
        $resolve = static fn(string $path): string => preg_match('~^(videos|images)/~', $path) ? bl_asset($path) : $path;
        $videos[] = ['src' => $resolve($video['src']), 'poster' => !empty($video['poster']) ? $resolve($video['poster']) : '', 'caption' => (string) ($video['caption'] ?? '')];
    }
    return array_slice($videos, 0, BL_CONTENT_LIMITS['videos']);
}
