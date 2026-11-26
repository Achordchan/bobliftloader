<?php
// Spanish (Latin America) text that follows one shape with different figures: [regular expression on the English key,
// translation with $1…, or a function of the match]. Used by bl_i18n_get() (inc/i18n.php) only for a key the dictionary
// (es.php) does not have, so an entry there still wins. Wording from DeepL (ES-419), edited for "usted"; figures stay as
// the page writes them (1,500 kg), as everywhere else on the Spanish pages.
return [
    // Story bylines and the Published / Updated dates: 26 November 2026 → 26 de noviembre de 2026. May is left to the
    // short form below (3 may 2026), because a byline in May reads the same as a news card's date.
    ['~^(\d{1,2}) (January|February|March|April|June|July|August|September|October|November|December) (\d{4})$~', static fn(array $m): string => $m[1] . ' de ' . ['January' => 'enero', 'February' => 'febrero', 'March' => 'marzo', 'April' => 'abril', 'June' => 'junio', 'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre', 'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'][$m[2]] . ' de ' . $m[3]],
    // The contents box of a story: "In this article · 10 sections".
    ['~^(\d+) sections$~', '$1 secciones'],
    // News and guide dates: 14 Nov 2026 → 14 nov 2026.
    ['~^(\d{1,2}) (Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec) (\d{4})$~', static fn(array $m): string => $m[1] . ' ' . ['Jan' => 'ene', 'Feb' => 'feb', 'Mar' => 'mar', 'Apr' => 'abr', 'May' => 'may', 'Jun' => 'jun', 'Jul' => 'jul', 'Aug' => 'ago', 'Sep' => 'sept', 'Oct' => 'oct', 'Nov' => 'nov', 'Dec' => 'dic'][$m[2]] . ' ' . $m[3]],
    // Galleries.
    ['~^Show photograph (\d+) of (\d+)$~', 'Mostrar la fotografía $1 de $2'],
    ['~^View larger · (\d+) photos$~', 'Ver más grande · $1 fotos'],
    ['~^(\d+) photographs\. Select one to view it larger\.$~', '$1 fotografías. Seleccione una para verla más grande.'],
    ['~^\+(\d+) more$~', '+$1 más'],
    // The Photos section of a product page names its kinds of photograph: "7 photographs: other views, details. …".
    ['~^(\d+) photographs: ([a-z &,]+)\. Select one to view it larger\.$~', static function (array $m): ?string {
        $names = ['other views' => 'otras vistas', 'at work' => 'en el trabajo', 'details' => 'detalles', 'in the factory' => 'en la fábrica', 'packing & delivery' => 'empaque y entrega'];
        $groups = [];
        foreach (explode(', ', $m[2]) as $group) {
            if (!isset($names[$group])) return null;
            $groups[] = $names[$group];
        }
        $last = array_pop($groups);
        return $m[1] . ' fotografías: ' . ($groups ? implode(', ', $groups) . ' y ' : '') . $last . '. Seleccione una para verla más grande.';
    }],
    ['~^Dimension drawing, (.+)$~', 'Plano de dimensiones del $1'],
    // The tile after the product thumbnails, should it ever be read as one run ("+7 photos").
    ['~^(<t0>)?\+(\d+)(</t0>)? (<t1>)?photos(</t1>)?$~', '$1+$2$3 $4fotos$5'],
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
