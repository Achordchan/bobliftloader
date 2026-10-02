<?php
declare(strict_types=1);

/**
 * The applications. 'focus' names the published figure that matters most for the job, which also orders its machines;
 * 'checks' are what to measure or describe before an enquiry. 'models', when given, are the machines the job page shows
 * (in that order) instead of the family's first three by the focus figure; 'slug' fixes the URL whatever the title says.
 */
$applications = [
    ['title' => 'Landscaping', 'subtitle' => 'MAKE ROOM FOR BETTER IDEAS.', 'description' => 'From shaping ground to moving soil. Bring compact equipment into the spaces where your landscape takes shape.', 'image' => '/assets/images/app-landscaping.webp', 'alt' => 'Illustrative landscaping scene featuring the BOB-LIFT BL-T1000-H mini loader', 'product' => 'loader', 'focus' => 'width',
        'checks' => [
            ['title' => 'Access & working space', 'text' => 'Gardens are reached through gates, side passages and paths. The narrowest point on the way in decides which loader you can use.', 'measure' => 'The narrowest gate, path or passage, in millimetres'],
            ['title' => 'Materials & attachments', 'text' => 'Tell us whether you will move soil, gravel, turf or stone, and which tasks need an attachment beyond the bucket.', 'measure' => 'The materials, and the attachments you have in mind', 'link' => ['/products/skid-steer-loaders/#attachments', 'See the attachments']],
            ['title' => 'Ground you will cross', 'text' => 'Lawns, soft beds and slopes favour tracks; paving and drives suit tires. Tell us what the machine will drive over.', 'measure' => 'Ground type, and the steepest slope on site'],
        ]],
    ['title' => 'Site preparation', 'subtitle' => 'A STRONG START, FROM THE GROUND UP.', 'description' => 'Prepare the ground, handle loose material and keep your next project moving. Start with the job; find the equipment to match.', 'image' => '/assets/images/app-site-prep.webp', 'alt' => 'Illustrative site preparation scene featuring a BOB-LIFT mini loader spreading crushed stone', 'product' => 'loader', 'focus' => 'load',
        'checks' => [
            ['title' => 'The work ahead', 'text' => 'Levelling, spreading crushed stone, backfilling or clearing: tell us the tasks and the material, so we can match bucket and rated load.', 'measure' => 'Tasks, material and a typical bucket load'],
            ['title' => 'Ground & access', 'text' => 'Describe the site surface and the entrance. Loose or wet ground favours tracks; an entrance narrower than the bucket rules a model out.', 'measure' => 'Site surface, and the entrance width in millimetres'],
            ['title' => 'Project requirements', 'text' => 'Share your delivery country, schedule and the documents your project needs, such as CE or EAC paperwork.', 'measure' => 'Delivery country, dates and required certificates'],
        ]],
    ['title' => 'Material transport', 'subtitle' => 'KEEP THE WORK MOVING.', 'description' => 'Give soil, sand and gravel a clear route across your site. Explore mini dumpers for the journeys that make the difference.', 'image' => '/assets/images/app-transport.webp', 'alt' => 'Illustrative material transport scene based on BOB-LIFT FY30 product photography', 'product' => 'dumper', 'focus' => 'payload',
        'checks' => [
            ['title' => 'The material', 'text' => 'Soil, sand, gravel, concrete or site waste. Weight and volume both matter: a full hopper of wet soil weighs far more than one of mulch.', 'measure' => 'What you carry, and roughly how much per trip'],
            ['title' => 'The route', 'text' => 'Wheeled site dumpers are 1.5 to 1.8 metres wide; the tracked models are under 0.9 metres. The narrowest point on the route decides between them.', 'measure' => 'Route length, narrowest width and ground conditions'],
            ['title' => 'Loading & tipping', 'text' => 'Tell us how the dumper will be loaded, by hand, loader or its own bucket, and where it tips.', 'measure' => 'How it is loaded, and where it unloads'],
        ]],
    ['title' => 'Gardens and homesteads', 'slug' => 'gardens-and-homesteads', 'subtitle' => 'THROUGH THE GATE, ROUND THE BACK.', 'description' => 'Soil, gravel, mulch and firewood carried along garden paths and side passages. Start with the way in; it decides the dumper.', 'image' => '/assets/images/app-dumper-gardens.webp', 'alt' => 'Illustrative garden scene featuring the BOB-LIFT BL-T500-HD tracked mini dumper', 'product' => 'dumper', 'focus' => 'width', 'models' => ['BL-T500-HD', 'FC10', 'FY15'],
        'checks' => [
            ['title' => 'The way in', 'text' => 'Gates, side passages and paths decide the machine. The tracked dumper is under 0.9 metres wide; the wheeled dumpers start at about 1.5 metres.', 'measure' => 'The narrowest gate, path or passage, in millimetres', 'link' => ['/products/mini-dumpers/#about-the-range', 'Check every dumper’s width']],
            ['title' => 'The load', 'text' => 'Soil, gravel, mulch or firewood. Tell us what you carry and roughly how much each trip, so we can match the hopper and rated load.', 'measure' => 'What you carry, and roughly how much per trip'],
            ['title' => 'The ground', 'text' => 'Lawns, soft beds and slopes favour tracks; drives and firm paths suit wheels. Tell us what the dumper will drive over.', 'measure' => 'Ground type, and the steepest slope on the route'],
        ]],
    ['title' => 'Farms and orchards', 'slug' => 'farms-and-orchards', 'subtitle' => 'FROM THE SHED TO THE FIELD.', 'description' => 'Feed, manure and bagged fertiliser moved between sheds, yards and fields, on farm tracks and between the rows.', 'image' => '/assets/images/app-dumper-farms.webp', 'alt' => 'Illustrative orchard scene featuring the BOB-LIFT FY15 mini dumper with roof', 'product' => 'dumper', 'focus' => 'payload', 'models' => ['FC10', 'FY15', 'FY20-2'],
        'checks' => [
            ['title' => 'The material', 'text' => 'Feed, manure, bagged fertiliser or harvest crates. Weight and volume both matter: a hopper of wet manure weighs far more than one of feed.', 'measure' => 'What you carry, and roughly how much per trip'],
            ['title' => 'Tracks and rows', 'text' => 'Tell us the narrowest point the dumper must pass, a shed door, gateway or the gap between rows, and whether the tracks get wet or muddy.', 'measure' => 'Narrowest width on the route, and the ground in wet weather'],
            ['title' => 'Loading', 'text' => 'Loaded by hand, by a loader or by itself: the FY20-2 carries a front bucket, so it can load its own hopper.', 'measure' => 'How it is loaded, and where it unloads', 'link' => ['/products/mini-dumpers/#dumper-jobs', 'See where our dumpers work']],
        ]],
    ['title' => 'Building sites', 'slug' => 'building-sites', 'subtitle' => 'WHERE THE TRUCK CAN’T FOLLOW.', 'description' => 'Sand, gravel, bricks and spoil from the stockpile to the work face, on building, road and drainage jobs.', 'image' => '/assets/images/app-dumper-sites.webp', 'alt' => 'Illustrative drainage works scene featuring a BOB-LIFT front-tip site dumper with enclosed cab', 'product' => 'dumper', 'focus' => 'load', 'models' => ['FY50', 'FY40', 'FY30'],
        'checks' => [
            ['title' => 'Load per trip', 'text' => 'Tell us the material and how much each trip should carry. Our wheeled site dumpers are rated from one to five tonnes.', 'measure' => 'Material, and the load per trip'],
            ['title' => 'Site & slope', 'text' => 'Loose ground and ramps call for four-wheel drive: the FY20-4, FY30, FY40, FY50 and FJZ30 drive all four wheels.', 'measure' => 'Site surface, and the steepest ramp the dumper will climb'],
            ['title' => 'Project requirements', 'text' => 'Share your delivery country, schedule and the documents your project needs, such as CE or EAC paperwork.', 'measure' => 'Delivery country, dates and required certificates'],
        ]],
];

// The theme's own records, kept as written: they are the defaults of the 应用场景 content screen (inc/editable.php).
$GLOBALS['bl_application_base'] = $applications; // set through $GLOBALS: WP-CLI and some loaders include the theme inside a function
foreach ($applications as &$item) $item['image'] = bl_asset(substr($item['image'], strlen('/assets/')));
unset($item);
