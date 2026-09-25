<?php
declare(strict_types=1);

/** Shared presentation content; WordPress can replace this boundary with managed fields. */
$equipment = [
    'loader' => [
        'index' => '01',
        'label' => 'Skid steer loaders',
        'title' => 'SMALL FOOTPRINT.<br>BIG POTENTIAL.',
        'image' => '/assets/images/range-loader-studio.webp',
        'alt' => 'Studio view of a BOB-LIFT tracked mini skid steer loader, based on BL-T1000-H product photography',
        'description' => 'For the jobs that call for more than one kind of capability. Explore a compact approach to loading, ground preparation and everyday site work.',
        'traits' => ['Loading & ground preparation', 'Attachment options to discuss', 'Configuration matched to your work'],
        'detail' => 'Tell us about your working space, materials and the tasks you need to handle. Our team can help you explore the available loader configurations and confirm the right specifications.',
    ],
    'dumper' => [
        'index' => '02',
        'label' => 'Mini dumpers',
        'title' => 'LESS BACK & FORTH.<br>MORE FORWARD.',
        'image' => '/assets/images/range-dumper-studio.webp',
        'alt' => 'Studio view of a yellow BOB-LIFT site mini dumper, based on FY-30 product photography',
        'description' => 'Move soil, gravel and site materials where they need to go. Discover a dedicated range of mini dumpers for the everyday work between A and B.',
        'traits' => ['Soil, gravel & material transport', 'A range of load capacities', 'Wheeled site dumper configurations'],
        'detail' => 'The BOB-LIFT mini dumper range includes multiple load capacities. Tell us about your material, typical load and site conditions so we can confirm a suitable model and its specifications.',
    ],
];

/**
 * The three applications. 'focus' names the published figure that matters most for the job, which also orders its machines;
 * 'checks' are what to measure or describe before an enquiry.
 */
$applications = [
    ['title' => 'Landscaping', 'subtitle' => 'MAKE ROOM FOR BETTER IDEAS.', 'description' => 'From shaping ground to moving soil. Bring compact equipment into the spaces where your landscape takes shape.', 'image' => '/assets/images/app-landscaping.webp', 'alt' => 'Illustrative landscaping scene featuring the BOB-LIFT BL-T1000-H mini loader', 'product' => 'loader', 'focus' => 'width',
        'checks' => [
            ['title' => 'Access & working space', 'text' => 'Gardens are reached through gates, side passages and paths. The narrowest point on the way in decides which loader you can use.', 'measure' => 'The narrowest gate, path or passage, in millimetres'],
            ['title' => 'Materials & attachments', 'text' => 'Tell us whether you will move soil, gravel, turf or stone, and which tasks need an attachment beyond the bucket.', 'measure' => 'The materials, and the attachments you have in mind'],
            ['title' => 'Ground you will cross', 'text' => 'Lawns, soft beds and slopes favour tracks; paving and drives suit tires. Tell us what the machine will drive over.', 'measure' => 'Ground type, and the steepest slope on site'],
        ]],
    ['title' => 'Site preparation', 'subtitle' => 'A STRONG START, FROM THE GROUND UP.', 'description' => 'Prepare the ground, handle loose material and keep your next project moving. Start with the job; find the equipment to match.', 'image' => '/assets/images/app-site-prep.webp', 'alt' => 'Illustrative site preparation scene featuring a BOB-LIFT mini loader spreading crushed stone', 'product' => 'loader', 'focus' => 'load',
        'checks' => [
            ['title' => 'The work ahead', 'text' => 'Levelling, spreading crushed stone, backfilling or clearing: tell us the tasks and the material, so we can match bucket and rated load.', 'measure' => 'Tasks, material and a typical bucket load'],
            ['title' => 'Ground & access', 'text' => 'Describe the site surface and the entrance. Loose or wet ground favours tracks; an entrance narrower than the bucket rules a model out.', 'measure' => 'Site surface, and the entrance width in millimetres'],
            ['title' => 'Project requirements', 'text' => 'Share your delivery country, schedule and the documents your project needs, such as CE or EAC paperwork.', 'measure' => 'Delivery country, dates and required certificates'],
        ]],
    ['title' => 'Material transport', 'subtitle' => 'KEEP THE WORK MOVING.', 'description' => 'Give soil, sand and gravel a clear route across your site. Explore mini dumpers for the journeys that make the difference.', 'image' => '/assets/images/app-transport.webp', 'alt' => 'Illustrative material transport scene based on BOB-LIFT FY-30 product photography', 'product' => 'dumper', 'focus' => 'payload',
        'checks' => [
            ['title' => 'The material', 'text' => 'Soil, sand, gravel, concrete or site waste. Weight and volume both matter: a full hopper of wet soil weighs far more than one of mulch.', 'measure' => 'What you carry, and roughly how much per trip'],
            ['title' => 'The route', 'text' => 'Wheeled site dumpers are 1.5 to 1.8 metres wide; the tracked models are under 0.9 metres. The narrowest point on the route decides between them.', 'measure' => 'Route length, narrowest width and ground conditions'],
            ['title' => 'Loading & tipping', 'text' => 'Tell us how the dumper will be loaded, by hand, loader or its own bucket, and where it tips.', 'measure' => 'How it is loaded, and where it unloads'],
        ]],
];

foreach ($equipment as &$item) $item['image'] = bl_asset(substr($item['image'], strlen('/assets/')));
unset($item);
foreach ($applications as &$item) $item['image'] = bl_asset(substr($item['image'], strlen('/assets/')));
unset($item);
