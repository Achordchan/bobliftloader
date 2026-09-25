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

$applications = [
    ['title' => 'Landscaping', 'subtitle' => 'MAKE ROOM FOR BETTER IDEAS.', 'description' => 'From shaping ground to moving soil. Bring compact equipment into the spaces where your landscape takes shape.', 'image' => '/assets/images/app-landscaping.webp', 'alt' => 'Illustrative landscaping scene featuring the BOB-LIFT BL-T1000-H mini loader', 'product' => 'loader'],
    ['title' => 'Site preparation', 'subtitle' => 'A STRONG START, FROM THE GROUND UP.', 'description' => 'Prepare the ground, handle loose material and keep your next project moving. Start with the job; find the equipment to match.', 'image' => '/assets/images/app-site-prep.webp', 'alt' => 'Illustrative site preparation scene featuring a BOB-LIFT mini loader spreading crushed stone', 'product' => 'loader'],
    ['title' => 'Material transport', 'subtitle' => 'KEEP THE WORK MOVING.', 'description' => 'Give soil, sand and gravel a clear route across your site. Explore mini dumpers for the journeys that make the difference.', 'image' => '/assets/images/app-transport.webp', 'alt' => 'Illustrative material transport scene based on BOB-LIFT FY-30 product photography', 'product' => 'dumper'],
];

foreach ($equipment as &$item) $item['image'] = bl_asset(substr($item['image'], strlen('/assets/')));
unset($item);
foreach ($applications as &$item) $item['image'] = bl_asset(substr($item['image'], strlen('/assets/')));
unset($item);
