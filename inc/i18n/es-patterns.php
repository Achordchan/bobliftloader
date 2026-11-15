<?php
// Spanish (Latin America) text that follows one shape with different figures: [regular expression on the English key,
// translation with $1…, or a function of the match]. Used by bl_i18n_get() (inc/i18n.php) only for a key the dictionary
// (es.php) does not have, so an entry there still wins. Wording from DeepL (ES-419), edited for "usted"; figures stay as
// the page writes them (1,500 kg), as everywhere else on the Spanish pages.
return [
    // News and guide dates: 14 Nov 2026 → 14 nov 2026.
    ['~^(\d{1,2}) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (\d{4})$~', static fn(array $m): string => $m[1] . ' ' . ['Jan' => 'ene', 'Feb' => 'feb', 'Mar' => 'mar', 'Apr' => 'abr', 'May' => 'may', 'Jun' => 'jun', 'Jul' => 'jul', 'Aug' => 'ago', 'Sep' => 'sept', 'Oct' => 'oct', 'Nov' => 'nov', 'Dec' => 'dic'][$m[2]] . ' ' . $m[3]],
    // Galleries.
    ['~^Show photograph (\d+) of (\d+)$~', 'Mostrar la fotografía $1 de $2'],
    ['~^View larger · (\d+) photos$~', 'Ver más grande · $1 fotos'],
    ['~^(\d+) photographs\. Select one to view it larger\.$~', '$1 fotografías. Seleccione una para verla más grande.'],
    ['~^\+(\d+) more$~', '+$1 más'],
    // Counts.
    ['~^(\d+) <t0>machines</t0>$~', '$1 <t0>máquinas</t0>'],
    ['~^Watch it work <t0>(\d+) videos</t0>$~', 'Mire cómo funciona <t0>$1 videos</t0>'],
    // News tabs and machine chips with their story count: Guides <t0>2</t0> → Guías <t0>2</t0>.
    ['~^(All|Product news|Exhibition news|Customer stories|Guides|Company news|Skid steer loaders|Mini dumpers) <t0>(\d+)</t0>$~', static fn(array $m): string => ['All' => 'Todas', 'Product news' => 'Novedades de productos', 'Exhibition news' => 'Noticias de ferias', 'Customer stories' => 'Historias de clientes', 'Guides' => 'Guías', 'Company news' => 'Noticias de la empresa', 'Skid steer loaders' => 'Minicargadoras', 'Mini dumpers' => 'Minidumpers'][$m[1]] . ' <t0>' . $m[2] . '</t0>'],
    // Figures with a word.
    ['~^Rated load ([\d,.]+) kg$~', 'Carga nominal de $1 kg'],
    ['~^([\d,.]+) kg payload$~', '$1 kg de carga útil'],
    ['~^([\d,.]+) kg load$~', 'Carga de $1 kg'],
    ['~^<t0>(\d+)</t0>([\d,.]+) kg load$~', '<t0>$1</t0>Carga de $2 kg'],
    ['~^([\d,.]+) kg lifting capacity$~', 'Capacidad de elevación de $1 kg'],
    ['~^([\d,.]+) kg machine$~', 'Máquina de $1 kg'],
    ['~^([\d,.]+) mm working height$~', '$1 mm de altura de trabajo'],
    ['~^Bucket ([\d,.]+) mm$~', 'Cucharón de $1 mm'],
];
