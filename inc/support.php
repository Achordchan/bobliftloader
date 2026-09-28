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
    return array_filter(bl_editable('support')['containers']['loading'], static fn($fit): bool => is_array($fit) && count($fit) === 2);
}

/** The catalogue on the Downloads list. WordPress can point it at an uploaded file (Settings → BOB-LIFT 资料下载). */
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

/** Questions the sections above don't already answer, by group. Answers may carry one link: [text, url]. */
function bl_support_faq(): array
{
    $groups = [];
    foreach (bl_editable('support')['faq']['items'] as $item) {
        $row = ['q' => $item['q'], 'a' => $item['a']];
        if ($item['link_label'] !== '' && $item['link_url'] !== '') $row['link'] = [$item['link_label'], $item['link_url']];
        $groups[$item['group']][] = $row;
    }
    return $groups;
}

/**
 * Attachments for the loaders, grouped by the work they do. They are quoted with a machine, not sold on their own.
 * Images cut from BOB-LIFT's supplier attachment sheet; replace with photography when it is supplied.
 */
function bl_attachments(): array
{
    return [
        'Dig & trench' => [
            ['slug' => 'four-in-one-bucket', 'name' => '4-in-1 bucket', 'use' => 'Dig, grab, level and doze'],
            ['slug' => 'direct-excavation', 'name' => 'Backhoe', 'use' => 'Small excavations and pits'],
            ['slug' => 'auger', 'name' => 'Auger', 'use' => 'Holes for posts and planting'],
            ['slug' => 'trencher', 'name' => 'Trencher', 'use' => 'Narrow trenches for cables and pipes'],
            ['slug' => 'trench-filler', 'name' => 'Trench filler', 'use' => 'Backfill trenches after the pipe is in'],
        ],
        'Lift & carry' => [
            ['slug' => 'pallet-fork', 'name' => 'Pallet forks', 'use' => 'Pallets, bagged cement, bales'],
            ['slug' => 'hydraulic-grapple', 'name' => 'Hydraulic grapple', 'use' => 'Straw, branches and loose debris'],
            ['slug' => 'crab-bucket', 'name' => 'Grapple bucket', 'use' => 'Rubble, logs and awkward loads'],
            ['slug' => 'lifting-device', 'name' => 'Lifting jib', 'use' => 'Lift and place with a chain or sling'],
            ['slug' => 'pulling-device', 'name' => 'Tow hitch', 'use' => 'Move trailers around the site'],
        ],
        'Break & clear' => [
            ['slug' => 'breaker', 'name' => 'Hydraulic breaker', 'use' => 'Concrete, paving and frozen ground'],
            ['slug' => 'ripper', 'name' => 'Ripper', 'use' => 'Loosen compacted soil and roots'],
            ['slug' => 'mulcher', 'name' => 'Mulcher', 'use' => 'Clear scrub and small trees'],
            ['slug' => 'brush-cutter', 'name' => 'Brush cutter', 'use' => 'Cut back overgrown ground'],
            ['slug' => 'log-splitter', 'name' => 'Log splitter', 'use' => 'Split firewood on the spot'],
        ],
        'Ground & lawn' => [
            ['slug' => 'leveler', 'name' => 'Leveller', 'use' => 'Grade and spread loose material'],
            ['slug' => 'rotary-tiller', 'name' => 'Rotary tiller', 'use' => 'Turn soil for beds and planting'],
            ['slug' => 'soil-conditioner', 'name' => 'Soil conditioner', 'use' => 'Prepare a seed bed in one pass'],
            ['slug' => 'lawn-aerator', 'name' => 'Lawn aerator', 'use' => 'Open up compacted turf'],
            ['slug' => 'lawn-mower', 'name' => 'Lawn mower', 'use' => 'Mow large areas of grass'],
        ],
        'Sweep, snow & mix' => [
            ['slug' => 'sweeper', 'name' => 'Sweeper', 'use' => 'Yards, roads and light snow'],
            ['slug' => 'snow-blower', 'name' => 'Snow blower', 'use' => 'Clear paths and drives'],
            ['slug' => 'mixer', 'name' => 'Concrete mixer', 'use' => 'Mix small batches on site'],
            ['slug' => 'mixing-bucket', 'name' => 'Mixing bucket', 'use' => 'Mix and pour in one attachment'],
        ],
    ];
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
    return $videos;
}
