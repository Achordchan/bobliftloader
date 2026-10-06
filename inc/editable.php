<?php
declare(strict_types=1);

/**
 * The words and pictures of the home and Support pages. The defaults below are the site as written; in WordPress the
 * "网站内容" screens save only what an editor changed (options bl_content_home / bl_content_support), and those changes are
 * laid over the defaults here. The standalone preview has no database and always shows the defaults.
 *
 * Text conventions shared with the admin screens:
 *  - headings: a new line is a line break, *words* in stars take the section's accent (blue or outline);
 *  - rich text: a new line is a line break, and simple links / bold / italics are allowed;
 *  - an empty image means the theme's own photograph.
 */

function bl_home_defaults(): array
{
    return [
        'seo' => [
            'title' => 'Small size. Serious capability.',
            'description' => 'Explore BOB-LIFT skid steer loaders and mini dumpers. A dedicated loader equipment business website of Xuzhou BOB-LIFT Construction Machinery Co., Ltd.',
            'image' => '',
        ],
        'hero' => [
            'eyebrow' => 'LOADER EQUIPMENT. OPEN POSSIBILITIES.',
            'title' => "SMALL SIZE.\nSERIOUS\nCAPABILITY.",
            'button' => 'Explore the range',
            'link' => 'Talk to our team',
            'image' => '',
            'image_mobile' => '',
            'alt' => 'Illustrative stone-quarry scene at dusk based on BOB-LIFT BL-T1000-H product photography',
        ],
        // The second hero slide; the switch above the picture names both slides after the family cards below.
        'hero_dumper' => [
            'eyebrow' => 'MINI DUMPERS. ON TRACKS OR WHEELS.',
            'title' => "MORE LOAD.\nFEWER\nTRIPS.",
            'button' => 'Explore mini dumpers',
            'link' => 'Talk to our team',
            'image' => '',
            'image_mobile' => '',
            'alt' => 'Illustrative stone-quarry scene at dusk based on BOB-LIFT FY30 mini dumper product photography',
        ],
        'families' => [
            'loader' => [
                'label' => 'Skid steer loaders',
                'title' => "SMALL FOOTPRINT.\nBIG POTENTIAL.",
                'description' => 'For the jobs that call for more than one kind of capability. Explore a compact approach to loading, ground preparation and everyday site work.',
                'image' => '',
                'alt' => 'Studio view of a BOB-LIFT tracked mini skid steer loader, based on BL-T1000-H product photography',
            ],
            'dumper' => [
                'label' => 'Mini dumpers',
                'title' => "LESS BACK & FORTH.\nMORE FORWARD.",
                'description' => 'Move soil, gravel and site materials where they need to go. Discover a dedicated range of mini dumpers for the everyday work between A and B.',
                'image' => '',
                'alt' => 'Studio view of a yellow BOB-LIFT site mini dumper, based on FY-30 product photography',
            ],
        ],
        'range' => [
            'eyebrow' => '01 / THE EQUIPMENT',
            'title' => 'THE RANGE.',
            'description' => "Two families. {models} models.\nBuilt for the space you actually have.",
            'assist' => 'Different jobs. Different requirements.',
            'assist_link' => 'Let’s find your fit',
        ],
        'applications' => [
            'eyebrow' => '02 / IN YOUR ELEMENT',
            'title' => "WORK WITHOUT\n*LIMITS.*",
            'description' => "Every site has its own challenges. \nStart with yours.",
        ],
        'approach' => [
            'eyebrow' => '03 / PEOPLE BEHIND THE MACHINE',
            'title' => "BUILT BY PEOPLE\nYOU CAN *TALK TO.*",
            'text' => 'Our loaders are welded, painted and assembled by our own team at our factory in Xuzhou. Tell us about your work and talk it through with the people behind the machines.',
            'link' => 'Meet our company',
            'image' => '',
            'alt' => 'A BOB-LIFT technician assembling a tracked loader at the factory in Xuzhou',
            'caption' => 'Loader assembly · BOB-LIFT factory, Xuzhou',
            'facts' => [
                ['value' => '37,000', 'unit' => 'm²', 'label' => 'Own factory in Xuzhou, China'],
                ['value' => '200+', 'unit' => '', 'label' => 'Countries and regions served'],
            ],
            'steps' => [
                ['title' => 'Start with the right fit.', 'text' => 'Your site, your materials, your priorities. Tell us what you need to do and we’ll discuss the equipment options with you.'],
                ['title' => 'Know what you’re choosing.', 'text' => 'Ask for model specifications, configuration options and product documentation before you decide, and confirm the details that matter to your work.'],
                ['title' => 'Keep the conversation going.', 'text' => 'Contact the same team for equipment questions, <a href="' . bl_escape(bl_url('/support/') . '#after-delivery') . '">parts and service</a>, with a 12-month warranty on the whole machine. Have a machine already? Include its model and serial number.'],
            ],
        ],
        'journal' => [
            'eyebrow' => 'FROM THE COMPANY',
            'title' => 'INSIDE BOB-LIFT.',
            'link' => 'All company stories',
        ],
        'contact' => [
            'eyebrow' => 'YOUR NEXT PROJECT STARTS HERE',
            'title' => "LET’S GET\nTO *WORK.*",
            'text' => "A question. A project. A new partnership.\nTell us what you have in mind.",
            'signoff' => 'REAL PEOPLE. PRACTICAL CONVERSATIONS.',
        ],
    ];
}

function bl_support_defaults(): array
{
    return [
        'seo' => [
            'title' => 'Buying, Warranty & Support',
            'description' => 'How buying from BOB-LIFT works: one-unit orders, T/T payment, 8–30 working days to ship, a 12-month whole-machine warranty, spare parts, downloads and answers to common questions.',
            'image' => '',
        ],
        'hero' => [
            'eyebrow' => 'SUPPORT / BUYING & AFTER DELIVERY',
            'title' => "FROM ORDER\nTO DELIVERY.\n*AND AFTER.*",
            'text' => 'How an order works, what the warranty covers, and where to find parts, documents and answers. One team handles all of it, from the first message to the last spare part.',
            'link' => 'How buying works',
            'image' => '',
            'alt' => 'A BOB-LIFT technician fitting the hydraulics on a tracked loader at the factory in Xuzhou',
            'caption' => 'The team that builds the machines also answers for them after delivery',
        ],
        // The keys are fixed: product pages quote these four terms by name. Terms confirmed by BOB-LIFT on 2026-09-25.
        'terms' => [
            'moq' => ['label' => 'Minimum order', 'value' => '1', 'unit' => 'unit'],
            'payment' => ['label' => 'Payment', 'value' => 'T/T', 'unit' => ''],
            'lead' => ['label' => 'Ready to ship', 'value' => '8–30', 'unit' => 'working days'],
            'warranty' => ['label' => 'Whole-machine warranty', 'value' => '12', 'unit' => 'months'],
        ],
        'buying' => [
            'eyebrow' => '01 / HOW BUYING WORKS',
            'title' => "FIVE STEPS\n*TO YOUR SITE.*",
            'intro' => 'You deal directly with the company that builds the machine, from the first message to the container door.',
            'steps' => [
                ['title' => 'Tell us the job', 'text' => 'The narrowest opening on your site, the heaviest load and the ground you will work on. A few photos of the route help.', 'link_label' => 'What to send us', 'link_url' => bl_url('/applications/') . '#before-you-enquire'],
                ['title' => 'Quote & configuration', 'text' => 'We suggest a model and confirm its engine, running gear, colour and attachments, then quote on the trade terms you need.', 'link_label' => '', 'link_url' => ''],
                ['title' => 'Place the order', 'text' => 'One machine is enough to order. Payment is by T/T bank transfer.', 'link_label' => '', 'link_url' => ''],
                ['title' => 'Built to order', 'text' => 'Your machine is built at our Xuzhou factory, typically in 8 to 30 working days. Quantity and configuration decide the date, which we confirm with your order.', 'link_label' => '', 'link_url' => ''],
                ['title' => 'Shipping', 'text' => 'We quote EXW, FOB, CFR, CIF and the other common Incoterms, and ship by container.', 'link_label' => 'Machines per container', 'link_url' => '#containers'],
            ],
        ],
        'containers' => [
            'title' => 'MACHINES PER CONTAINER.',
            'text' => 'Approximate figures, worked out from each model’s published dimensions for open-seat versions, with loader buckets taken off and packed alongside. We confirm the loading plan with your order.',
            // Machines per 20GP and 40HQ container, estimated from each model's published dimensions against standard interiors.
            'loading' => [
                'bl-t360-h-mini-skid-steer-loader' => [6, 14], 'bl-t460-h-mini-skid-steer-loader' => [6, 14],
                'bl-s390-c-mini-skid-steer-loader' => [6, 12], 'bl-t390-c-mini-skid-steer-loader' => [6, 12],
                'bl-t1000-h-mini-skid-steer-loader' => [5, 11], 'bl-t750-h-mini-skid-steer-loader' => [4, 10],
                'bl-s490-c-mini-skid-steer-loader' => [4, 10], 'bl-t490-c-mini-skid-steer-loader' => [4, 10],
                '500kg-tracked-mini-dumper' => [6, 12], 'tracked-mini-mixer-truck' => [6, 12],
                '1-ton-mini-dumper' => [2, 4], '1-5-ton-mini-dumper' => [2, 4], '2-ton-mini-dumper-with-bucket' => [1, 3],
                '2-ton-mini-dumper' => [1, 2], '3-ton-mini-dumper' => [1, 2], '3-ton-mini-dumper-with-bucket' => [1, 2],
                '4-ton-mini-dumper' => [1, 2], '5-ton-mini-dumper' => [1, 2],
            ],
            'note' => 'With a roof or cab, the FY20-4 to FY50 and FJZ30 dumpers stand 2.65–2.75 m tall, above a container door. Ask how they ship.',
        ],
        'after' => [
            'eyebrow' => '02 / AFTER DELIVERY',
            'title' => "COVERED FOR\n*TWELVE MONTHS.*",
            'intro' => 'The team that built your machine stays on the other end of the line, for questions, faults and parts.',
            'warranty_eyebrow' => 'WARRANTY',
            'warranty_title' => 'The whole machine.',
            'warranty_text' => 'Every BOB-LIFT loader and dumper carries a 12-month warranty that covers the whole machine.',
            'service_eyebrow' => 'IF SOMETHING GOES WRONG',
            'service_steps' => [
                ['title' => 'Send us the details', 'text' => 'The model, the serial number and a photo or short video of the problem.'],
                ['title' => 'Remote guidance', 'text' => 'Our team talks you through the diagnosis and the fix over WhatsApp, video or email.'],
                ['title' => 'Parts on the way', 'text' => 'We ship the replacement parts to you.'],
            ],
            'parts_image' => '',
            'parts_alt' => 'Hydraulic hose fittings at a loader’s lift arm pivot',
            'parts_eyebrow' => 'SPARE PARTS',
            'parts_title' => 'Original wear parts.',
            'parts_text' => 'Each machine ships with a set of original wear parts. Order more from the same team at any time, with your model and serial number.',
            'button' => 'Parts & service request',
            'whatsapp_label' => 'Send us a video on WhatsApp',
            'whatsapp_number' => '8615852499415',
            'whatsapp_message' => 'Hello BOB-LIFT, I need help with my machine. Model: , serial number: .',
        ],
        'downloads' => [
            'eyebrow' => '03 / DOWNLOADS',
            'title' => "KEEP IT\n*ON FILE.*",
            'intro' => 'The documents buyers ask for most, in one place. Ask the team for anything that isn’t here.',
            'catalogue_title' => 'Product catalogue',
            'catalogue_text' => 'The range in print, with specification tables and the attachments.',
            'sheets_title' => 'Specification sheets',
            'sheets_text' => 'Open any model and save its specification sheet as a PDF, straight from the page.',
            'videos_title' => 'Machine videos',
        ],
        'faq' => [
            'eyebrow' => '04 / QUESTIONS',
            'title' => "ASKED &\n*ANSWERED.*",
            'intro' => 'Not answered here? Ask the team that builds the machines.',
            'link' => 'Ask a question',
            // Questions the sections above don't already answer, in page order; consecutive items share a group heading.
            'items' => bl_support_default_faq(),
        ],
    ];
}

function bl_support_default_faq(): array
{
    $loaders = bl_url('/products/skid-steer-loaders/');
    $items = [];
    $groups = [
        'Choosing a machine' => [
            ['What is a mini skid steer loader?', 'A compact loader that steers by running its left and right tracks or wheels at different speeds, so it turns on the spot. A quick-attach plate lets the same machine dig, lift, break, sweep or clear snow.', 'See the jobs it does', $loaders . '#loader-jobs'],
            ['Which loader fits through my gate or doorway?', 'Set your narrowest opening on the width chart of the loaders page. It shows which models pass through, with and without the bucket fitted.', 'Check your opening', $loaders . '#about-the-range'],
            ['Should I choose tracks or tires?', 'Rubber tracks suit soft ground, lawns and slopes. Rubber tires suit concrete, asphalt and paving.'],
            ['Can I choose the engine?', 'Several loaders offer more than one engine brand, and some have an electric version. Each model lists its choices under Engine options on its specification sheet.'],
            ['Can the loader be painted in our colour?', 'Yes. Loaders are painted to order, so tell us the colour when you ask for a quote.'],
            ['Which attachments can I use?', 'Our loaders take a range of attachments, from pallet forks and augers to sweepers and breakers. We confirm which suit your model when we quote the machine.', 'See the attachments', $loaders . '#attachments'],
        ],
        'Shipping & paperwork' => [
            ['Can we visit the factory before ordering?', 'Yes. Tell us your dates and we will arrange a visit to our factory in Xuzhou, Jiangsu.', 'Plan a visit', bl_url('/contact/') . '#enquiry'],
        ],
        'Parts & service' => [
            ['Can I order parts for a machine I already have?', 'Yes. Send the model and serial number with a photo of the part, and we will confirm it before shipping.', 'Ask about parts', bl_url('/contact/') . '?topic=service#enquiry'],
        ],
    ];
    foreach ($groups as $group => $questions) foreach ($questions as $q) $items[] = ['group' => $group, 'q' => $q[0], 'a' => $q[1], 'link_label' => $q[2] ?? '', 'link_url' => $q[3] ?? ''];
    return $items;
}

/**
 * The skid steer loader family page: the jobs a mini loader is bought for and the attachments it takes. A job names its
 * tools by their names in the attachments list, separated by commas.
 */
function bl_loaders_defaults(): array
{
    $tool = static fn(string $slug, string $name, string $use, string $group): array => ['group' => $group, 'name' => $name, 'use' => $use, 'image' => bl_asset('images/attachments/' . $slug . '.webp')];
    return [
        'jobs' => [
            'eyebrow' => 'WHERE IT WORKS',
            'items' => [
                ['title' => 'Renovation and indoor work', 'text' => 'Inside buildings, basements and car parks: break out floors, clear rubble and move materials where a full-size loader can’t go.', 'tools' => 'Hydraulic breaker, 4-in-1 bucket, Grapple bucket'],
                ['title' => 'Gardens and landscaping', 'text' => 'Rubber tracks spread the weight on lawns and soft soil. Dig planting holes, level beds, turn soil and gather cuttings.', 'tools' => 'Auger, Leveller, Rotary tiller, Hydraulic grapple'],
                ['title' => 'Farms, greenhouses and barns', 'text' => 'Narrow enough for greenhouse aisles and livestock sheds: clean out, move feed and carry bales.', 'tools' => '4-in-1 bucket, Pallet forks, Hydraulic grapple'],
                ['title' => 'Streets and public spaces', 'text' => 'Works on footpaths, lanes and housing estates without closing the road: patch repairs, drain clearing and snow.', 'tools' => 'Hydraulic breaker, Sweeper, Snow blower'],
                ['title' => 'Yards and warehouses', 'text' => 'Moves pallets and clears scrap and offcuts in narrow aisles and busy yards.', 'tools' => 'Pallet forks, Grapple bucket, Sweeper'],
                ['title' => 'Alongside bigger machines', 'text' => 'Takes the corners, indoor and finishing work on small sites: footings, trenches, backfill and clean-up.', 'tools' => 'Backhoe, Trencher, Trench filler'],
            ],
        ],
        'attachments' => [
            'title' => "ONE LOADER.\n*MANY JOBS.*",
            'text' => 'Our loaders take their tools through a quick-attach plate and auxiliary hydraulic lines, so the bucket comes off and a fork, auger or sweeper goes on in minutes. Attachments are quoted with your loader and matched to its hydraulics.',
            'link' => 'Ask which suit your model',
            'note' => 'Hydraulic flow differs from model to model, so not every attachment suits every loader. We confirm the match when we quote your machine.',
            // Images cut from BOB-LIFT's supplier attachment sheet; replace with photography when it is supplied.
            'items' => [
                $tool('four-in-one-bucket', '4-in-1 bucket', 'Dig, grab, level and doze', 'Dig & trench'),
                $tool('direct-excavation', 'Backhoe', 'Small excavations and pits', 'Dig & trench'),
                $tool('auger', 'Auger', 'Holes for posts and planting', 'Dig & trench'),
                $tool('trencher', 'Trencher', 'Narrow trenches for cables and pipes', 'Dig & trench'),
                $tool('trench-filler', 'Trench filler', 'Backfill trenches after the pipe is in', 'Dig & trench'),
                $tool('pallet-fork', 'Pallet forks', 'Pallets, bagged cement, bales', 'Lift & carry'),
                $tool('hydraulic-grapple', 'Hydraulic grapple', 'Straw, branches and loose debris', 'Lift & carry'),
                $tool('crab-bucket', 'Grapple bucket', 'Rubble, logs and awkward loads', 'Lift & carry'),
                $tool('lifting-device', 'Lifting jib', 'Lift and place with a chain or sling', 'Lift & carry'),
                $tool('pulling-device', 'Tow hitch', 'Move trailers around the site', 'Lift & carry'),
                $tool('breaker', 'Hydraulic breaker', 'Concrete, paving and frozen ground', 'Break & clear'),
                $tool('ripper', 'Ripper', 'Loosen compacted soil and roots', 'Break & clear'),
                $tool('mulcher', 'Mulcher', 'Clear scrub and small trees', 'Break & clear'),
                $tool('brush-cutter', 'Brush cutter', 'Cut back overgrown ground', 'Break & clear'),
                $tool('log-splitter', 'Log splitter', 'Split firewood on the spot', 'Break & clear'),
                $tool('leveler', 'Leveller', 'Grade and spread loose material', 'Ground & lawn'),
                $tool('rotary-tiller', 'Rotary tiller', 'Turn soil for beds and planting', 'Ground & lawn'),
                $tool('soil-conditioner', 'Soil conditioner', 'Prepare a seed bed in one pass', 'Ground & lawn'),
                $tool('lawn-aerator', 'Lawn aerator', 'Open up compacted turf', 'Ground & lawn'),
                $tool('lawn-mower', 'Lawn mower', 'Mow large areas of grass', 'Ground & lawn'),
                $tool('sweeper', 'Sweeper', 'Yards, roads and light snow', 'Sweep, snow & mix'),
                $tool('snow-blower', 'Snow blower', 'Clear paths and drives', 'Sweep, snow & mix'),
                $tool('mixer', 'Concrete mixer', 'Mix small batches on site', 'Sweep, snow & mix'),
                $tool('mixing-bucket', 'Mixing bucket', 'Mix and pour in one attachment', 'Sweep, snow & mix'),
            ],
        ],
    ];
}

/**
 * The mini dumper family page: the range introduction and its configuration options, the jobs the dumpers are bought for
 * (each naming the models that suit it, by model name), and the note under the comparison table.
 */
function bl_dumpers_defaults(): array
{
    return [
        'about' => [
            'eyebrow' => 'ABOUT THE DUMPERS',
            'title' => "FROM THE GARDEN\nPATH TO THE SITE.",
            'intro' => 'Mini dumpers carry soil, gravel, concrete and site waste over ground a truck can’t reach. The range runs from compact tracked dumpers that fit narrow paths to wheeled site dumpers for heavier loads.',
            'options' => [
                ['big' => 'WHEELS / TRACKS', 'title' => 'Running gear.', 'text' => 'Wheeled site dumpers for firm ground and heavier loads; tracked models for soft ground and narrow access.'],
                ['big' => '4×2 / 4×4', 'title' => 'Drive.', 'text' => 'The FC10, FY15 and FY20-2 drive two wheels; the FY20-4, FY30, FY40, FY50 and FJZ30 drive all four. Each spec sheet lists its drive.'],
                ['big' => 'OPEN / ROOF / CAB', 'title' => 'Operator cover.', 'text' => 'Wheeled models come open or with a roof or cab. Each spec sheet lists the height with roof / cab.'],
                ['big' => 'TIP / LOAD / MIX', 'title' => 'Hopper, bucket or drum.', 'text' => 'Every model tips hydraulically. The FY20-2 and FJZ30 add a front bucket; the tracked mixer carries a drum to mix concrete on site.'],
            ],
        ],
        'jobs' => [
            'eyebrow' => 'WHERE THEY WORK',
            'title' => "ONE LOAD AT A TIME.\n*ALL DAY LONG.*",
            'text' => 'Homeowners, farmers, builders and contractors buy our dumpers for the same reason: material has to get from where it is delivered to where it is needed. Start with the job, then check the models that suit it.',
            'items' => [
                ['title' => 'Gardens and homesteads', 'text' => 'Soil, gravel, mulch and firewood along garden paths and round the back of the house. The tracked dumper is under 0.9 m wide.', 'models' => 'BL-T500-HD, FC10'],
                ['title' => 'Farms and orchards', 'text' => 'Feed, manure and bagged fertiliser between sheds and fields, on farm tracks and yards.', 'models' => 'FC10, FY15, FY20-2'],
                ['title' => 'Building sites', 'text' => 'Sand, gravel, bricks and spoil between the stockpile and the work face, where a truck can’t follow.', 'models' => 'FY20-4, FY30, FY40'],
                ['title' => 'Concrete and small pours', 'text' => 'Mix a small batch in the tracked mixer’s 200 L drum and drive it to the pour, or carry it in a tipping hopper.', 'models' => 'BL-T200-HJ, BL-T500-HD'],
                ['title' => 'Loading without a loader', 'text' => 'With a front bucket on the machine, loading doesn’t wait for a second machine on site.', 'models' => 'FY20-2, FJZ30'],
                ['title' => 'Contractors and heavier loads', 'text' => 'Road, drainage and landscaping contractors moving four to five tonnes a trip on 4×4 site dumpers.', 'models' => 'FY40, FY50'],
            ],
        ],
        'compare' => [
            'note' => 'Wheeled dumper figures from the BOB-LIFT mini dumper brochure; tracked models from the BOB-LIFT technical sheets. Confirm the final configuration with our team before ordering.',
        ],
    ];
}

/**
 * The Applications list page and the three application pages. The three jobs start from the records in inc/content.php
 * (also read by the home page tiles, the contact form and site search); each job's URL is fixed, its words are not.
 */
function bl_applications_defaults(): array
{
    global $bl_application_base;
    $jobs = [];
    foreach ($bl_application_base as $app) {
        $checks = [];
        foreach ($app['checks'] as $check) $checks[] = ['title' => $check['title'], 'text' => $check['text'], 'measure' => $check['measure'], 'link_label' => $check['link'][1] ?? '', 'link_url' => $check['link'][0] ?? ''];
        $jobs[bl_application_slug($app)] = ['title' => $app['title'], 'subtitle' => $app['subtitle'], 'description' => $app['description'], 'image' => '', 'alt' => $app['alt'], 'models' => implode(', ', $app['models'] ?? []), 'checks' => $checks];
    }
    return [
        'seo' => [
            'title' => 'Compact Equipment Applications',
            'description' => 'Find BOB-LIFT equipment for landscaping, site preparation and material transport. Explore application requirements and relevant product families.',
            'image' => '',
        ],
        'hero' => [
            'line1' => 'YOUR WORK.',
            'line2' => 'OUR STARTING',
            'line3' => 'POINT.',
            'text' => 'Every job has one number that decides the machine. Start with the job, and we will point you to the models that suit it.',
            'link' => 'Before you enquire',
        ],
        'send' => [
            'eyebrow' => 'BEFORE YOU ENQUIRE',
            'title' => "TELL US ABOUT\n*THE SITE.*",
            'text' => 'With these four answers, our team can suggest a model and configuration in its first reply instead of starting with questions.',
            'items' => [
                ['title' => 'The narrowest opening', 'text' => 'Gate, side passage, doorway or ramp: the tightest point between the road and the work, in millimetres.'],
                ['title' => 'The heaviest load', 'text' => 'What you will move and roughly how much at a time, per bucket or per trip.'],
                ['title' => 'The ground', 'text' => 'Lawn, soil, gravel, paving or concrete, and the steepest slope the machine will climb.'],
                ['title' => 'Photos of the route', 'text' => 'A few pictures of the way in and the work area answer most of our questions before we ask them.'],
            ],
        ],
        'jobs' => $jobs,
    ];
}

/** $applications with the words and pictures an editor saved laid over the theme's own; a cleared field falls back to the theme's. */
function bl_apply_application_content(array $applications): array
{
    $jobs = bl_editable('applications')['jobs'];
    foreach ($applications as &$app) {
        $slug = bl_application_slug($app);
        $app['slug'] = $slug;
        $job = $jobs[$slug] ?? null;
        if (!is_array($job)) continue;
        foreach (['title', 'subtitle', 'description', 'alt', 'image'] as $key) if (($job[$key] ?? '') !== '') $app[$key] = $job[$key];
        // Named machines: a cleared field means none, so the page falls back to the family's first three.
        if (array_key_exists('models', $job)) $app['models'] = array_values(array_filter(array_map('trim', explode(',', (string) $job['models']))));
        $checks = [];
        foreach ((array) ($job['checks'] ?? []) as $check) {
            $item = ['title' => (string) ($check['title'] ?? ''), 'text' => (string) ($check['text'] ?? ''), 'measure' => (string) ($check['measure'] ?? '')];
            if (($check['link_url'] ?? '') !== '' && ($check['link_label'] ?? '') !== '') $item['link'] = [$check['link_url'], $check['link_label']];
            $checks[] = $item;
        }
        if ($checks) $app['checks'] = $checks;
    }
    return $applications;
}

function bl_content_defaults(string $page): array
{
    return match ($page) {
        'support' => bl_support_defaults(),
        'applications' => bl_applications_defaults(),
        'loaders' => bl_loaders_defaults(),
        'dumpers' => bl_dumpers_defaults(),
        default => bl_home_defaults(),
    };
}

/** A page's content: the defaults with the editor's saved changes laid over them. */
function bl_editable(string $page): array
{
    static $cache = [];
    if (isset($cache[$page])) return $cache[$page];
    $content = bl_content_defaults($page);
    $saved = bl_is_wordpress() ? get_option('bl_content_' . $page) : [];
    if (is_array($saved) && $saved) {
        $content = bl_merge_content($content, $saved);
        // Loading figures are keyed by product, so models added in WordPress after these defaults are kept as well.
        if ($page === 'support' && is_array($saved['containers']['loading'] ?? null)) {
            foreach ($saved['containers']['loading'] as $slug => $fit) if (is_array($fit)) $content['containers']['loading'][$slug] = $fit;
        }
    }
    return $cache[$page] = $content;
}

/** Saved values replace defaults of the same kind; groups merge key by key, lists are replaced whole. */
function bl_merge_content(array $defaults, array $saved): array
{
    foreach ($saved as $key => $value) {
        if (!array_key_exists($key, $defaults)) continue;
        $default = $defaults[$key];
        if (is_array($default) && !array_is_list($default) && is_array($value)) $defaults[$key] = bl_merge_content($default, $value);
        elseif (is_array($default) === is_array($value)) $defaults[$key] = $value;
    }
    return $defaults;
}

/** A heading: new lines break, *words* take the accent class (an empty class still wraps them in a plain span). */
function bl_heading(string $text, string $accent = 'bl-blue-text', bool $period = false): string
{
    $html = bl_escape($text);
    if ($period && str_ends_with($html, '.')) $html = substr($html, 0, -1) . '<span class="bl-period">.</span>';
    $html = preg_replace('/\*(.+?)\*/s', $accent === '' ? '<span>$1</span>' : '<span class="' . bl_escape($accent) . '">$1</span>', $html);
    return str_replace(["\r\n", "\n"], '<br>', $html);
}

/** A display title set line by line: each line its own span, a line in *stars* takes the accent. */
function bl_heading_lines(string $text, bool $period = false): string
{
    $lines = array_values(array_filter(array_map('trim', preg_split('/\R/', $text)), 'strlen'));
    $html = '';
    foreach ($lines as $i => $line) {
        $accent = preg_match('/^\*(.+)\*$/s', $line, $match) === 1;
        $inner = bl_escape($accent ? $match[1] : $line);
        if ($period && $i === count($lines) - 1 && str_ends_with($inner, '.')) $inner = substr($inner, 0, -1) . '<span class="bl-period">.</span>';
        $html .= $accent ? '<span class="bl-blue-text">' . $inner . '</span>' : '<span>' . $inner . '</span>';
    }
    return $html;
}

/** Rich text: already filtered when saved (links, bold, italics), so only its line breaks are turned into <br>. */
function bl_rich(string $text, array $replace = []): string
{
    return str_replace(["\r\n", "\n"], '<br>', strtr($text, $replace));
}

/**
 * An image slot: the theme's own photograph (with its responsive sizes) unless the editor chose another. A chosen image from
 * the media library brings its own sizes and dimensions.
 */
function bl_content_image(string $custom, array $default): array
{
    if ($custom === '') return $default + ['srcset' => '', 'width' => 0, 'height' => 0];
    $image = ['src' => $custom, 'srcset' => '', 'width' => 0, 'height' => 0];
    // Saved links to this site are root-relative paths; the media lookup needs the full address, so only the origin is added.
    if (bl_is_wordpress() && ($id = attachment_url_to_postid(str_starts_with($custom, '/') ? bl_site_origin() . $custom : $custom))) {
        $meta = wp_get_attachment_metadata($id);
        $image['srcset'] = (string) wp_get_attachment_image_srcset($id, 'full');
        $image['width'] = (int) ($meta['width'] ?? 0);
        $image['height'] = (int) ($meta['height'] ?? 0);
    }
    return $image;
}

/** scheme://host[:port] of this WordPress site, without any installation subdirectory. */
function bl_site_origin(): string
{
    $home = wp_parse_url(home_url());
    return ($home['scheme'] ?? 'http') . '://' . ($home['host'] ?? '') . (isset($home['port']) ? ':' . $home['port'] : '');
}

/** src / srcset / sizes / width / height attributes for an image slot. */
function bl_image_attributes(array $image, string $sizes = ''): string
{
    $html = ($image['srcset'] ? ' srcset="' . bl_escape($image['srcset']) . '"' . ($sizes ? ' sizes="' . bl_escape($sizes) . '"' : '') : '') . ' src="' . bl_escape($image['src']) . '"';
    if ($image['width'] && $image['height']) $html .= ' width="' . (int) $image['width'] . '" height="' . (int) $image['height'] . '"';
    return $html;
}

/**
 * A home hero slide's pictures, wide screens and phones, each falling back to the theme's own. Empty when the slide has
 * no picture at all, so it is left out.
 */
function bl_home_hero_images(string $group = 'hero'): array
{
    $hero = bl_editable('home')[$group];
    [$wideFile, $narrowFile] = $group === 'hero_dumper' ? ['images/hero-dumper-2k.webp', 'images/hero-dumper-mobile.webp'] : ['images/hero-banner-2k.webp', 'images/hero-real-loader.webp'];
    $theme = static fn(string $file): string => is_file(dirname(__DIR__) . '/assets/' . $file) ? bl_asset($file) : '';
    $wide = $hero['image'] ?: $theme($wideFile);
    $narrow = $hero['image_mobile'] ?: ($hero['image'] ?: ($theme($narrowFile) ?: $wide));
    return $wide && $narrow ? ['wide' => $wide, 'narrow' => $narrow] : [];
}
