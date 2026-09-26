<?php
declare(strict_types=1);

/**
 * Buying, after-delivery and attachment content. Each fact lives here once: the Support page, the product pages and the
 * loader family page all read it from these functions. Terms confirmed by BOB-LIFT on 2026-09-25.
 */

/** The four terms a buyer asks about first, shown as the plate on the Support page and in short on product pages. */
function bl_support_terms(): array
{
    return [
        'moq' => ['label' => 'Minimum order', 'value' => '1', 'unit' => 'unit'],
        'payment' => ['label' => 'Payment', 'value' => 'T/T', 'unit' => ''],
        'lead' => ['label' => 'Ready to ship', 'value' => '8–30', 'unit' => 'working days'],
        'warranty' => ['label' => 'Whole-machine warranty', 'value' => '12', 'unit' => 'months'],
    ];
}

/** From the first message to the machine leaving Xuzhou. */
function bl_buying_steps(): array
{
    return [
        ['title' => 'Tell us the job', 'text' => 'The narrowest opening on your site, the heaviest load and the ground you will work on. A few photos of the route help.', 'link' => [bl_url('/applications/') . '#before-you-enquire', 'What to send us']],
        ['title' => 'Quote & configuration', 'text' => 'We suggest a model and confirm its engine, running gear, colour and attachments, then quote on the trade terms you need.'],
        ['title' => 'Place the order', 'text' => 'One machine is enough to order. Payment is by T/T bank transfer.', 'fact' => 'MOQ 1 unit · T/T'],
        ['title' => 'Built to order', 'text' => 'Your machine is built at our Xuzhou factory, typically in 8 to 30 working days. Quantity and configuration decide the date, which we confirm with your order.', 'fact' => '8–30 working days'],
        ['title' => 'Shipping', 'text' => 'We quote EXW, FOB, CFR, CIF and the other common Incoterms, and ship by container.', 'link' => ['#containers', 'Machines per container']],
    ];
}

/** After delivery: what to send when something goes wrong, and what happens next. */
function bl_service_steps(): array
{
    return [
        ['title' => 'Send us the details', 'text' => 'The model, the serial number and a photo or short video of the problem.'],
        ['title' => 'Remote guidance', 'text' => 'Our team talks you through the diagnosis and the fix over WhatsApp, video or email.'],
        ['title' => 'Parts on the way', 'text' => 'We ship the replacement parts to you.'],
    ];
}

/**
 * Approximate machines per container, open-seat versions with loader buckets taken off and packed alongside. Estimated
 * from each model's published dimensions against standard 20GP and 40HQ interiors; the loading plan is confirmed per order.
 */
function bl_container_loading(): array
{
    return [
        'bl-t360-h-mini-skid-steer-loader' => [6, 14], 'bl-t460-h-mini-skid-steer-loader' => [6, 14],
        'bl-s390-c-mini-skid-steer-loader' => [6, 12], 'bl-t390-c-mini-skid-steer-loader' => [6, 12],
        'bl-t1000-h-mini-skid-steer-loader' => [5, 11], 'bl-t750-h-mini-skid-steer-loader' => [4, 10],
        'bl-s490-c-mini-skid-steer-loader' => [4, 10], 'bl-t490-c-mini-skid-steer-loader' => [4, 10],
        '500kg-tracked-mini-dumper' => [6, 12], 'tracked-mini-mixer-truck' => [6, 12],
        '1-ton-mini-dumper' => [2, 4], '1-5-ton-mini-dumper' => [2, 4], '2-ton-mini-dumper-with-bucket' => [1, 3],
        '2-ton-mini-dumper' => [1, 2], '3-ton-mini-dumper' => [1, 2], '3-ton-mini-dumper-with-bucket' => [1, 2],
        '4-ton-mini-dumper' => [1, 2], '5-ton-mini-dumper' => [1, 2],
    ];
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

/** Questions the sections above don't already answer. Answers may carry one link: [text, url]. */
function bl_support_faq(): array
{
    $loaders = bl_url('/products/skid-steer-loaders/');
    return [
        'Choosing a machine' => [
            ['q' => 'Which loader fits through my gate or doorway?', 'a' => 'Set your narrowest opening on the width chart of the loaders page. It shows which models pass through, with and without the bucket fitted.', 'link' => ['Check your opening', $loaders . '#about-the-range']],
            ['q' => 'Should I choose tracks or tires?', 'a' => 'Rubber tracks suit soft ground, lawns and slopes. Rubber tires suit concrete, asphalt and paving.'],
            ['q' => 'Can I choose the engine?', 'a' => 'Several loaders offer more than one engine brand, and some have an electric version. Each model lists its choices under Engine options on its specification sheet.'],
            ['q' => 'Can the loader be painted in our colour?', 'a' => 'Yes. Loaders are painted to order, so tell us the colour when you ask for a quote.'],
            ['q' => 'Which attachments can I use?', 'a' => 'Our loaders take a range of attachments, from pallet forks and augers to sweepers and breakers. We confirm which suit your model when we quote the machine.', 'link' => ['See the attachments', $loaders . '#attachments']],
        ],
        'Shipping & paperwork' => [
            ['q' => 'Which certificates do you hold?', 'a' => 'Xuzhou BOB-LIFT Construction Machinery Co., Ltd. holds ISO 9001, CE and EAC certificates. Ask which documents cover your model and market.', 'link' => ['View the certificates', bl_url('/about/') . '#certificates']],
            ['q' => 'Can we visit the factory before ordering?', 'a' => 'Yes. Tell us your dates and we will arrange a visit to our factory in Xuzhou, Jiangsu.', 'link' => ['Plan a visit', bl_url('/contact/') . '#enquiry']],
        ],
        'Parts & service' => [
            ['q' => 'Can I order parts for a machine I already have?', 'a' => 'Yes. Send the model and serial number with a photo of the part, and we will confirm it before shipping.', 'link' => ['Ask about parts', bl_url('/contact/') . '?topic=service#enquiry']],
        ],
    ];
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
