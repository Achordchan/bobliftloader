<?php
// Spanish (Latin America) text that follows one shape with different figures: [regular expression on the English key,
// translation with $1…, or a function of the match]. Used by bl_i18n_get() (inc/i18n.php) only for a key the dictionary
// (es.php) does not have, so an entry there still wins. Wording from DeepL (ES-419), edited for "usted"; figures stay as
// the page writes them (1,500 kg), as everywhere else on the Spanish pages.
$bl_es_months = ['January' => 'enero', 'February' => 'febrero', 'March' => 'marzo', 'April' => 'abril', 'May' => 'mayo', 'June' => 'junio', 'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre', 'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'];
// Event dates as bl_event_dates() writes them: "24–27 November 2026" or "30 October – 2 November 2026".
$bl_es_range = static function (string $range) use ($bl_es_months): ?string {
    if (preg_match('~^(\d{1,2})–(\d{1,2}) ([A-Z][a-z]+) (\d{4})$~', $range, $m) && isset($bl_es_months[$m[3]])) return $m[1] . '–' . $m[2] . ' de ' . $bl_es_months[$m[3]] . ' de ' . $m[4];
    if (preg_match('~^(\d{1,2}) ([A-Z][a-z]+) – (\d{1,2}) ([A-Z][a-z]+) (\d{4})$~', $range, $m) && isset($bl_es_months[$m[2]], $bl_es_months[$m[4]])) return $m[1] . ' de ' . $bl_es_months[$m[2]] . ' – ' . $m[3] . ' de ' . $bl_es_months[$m[4]] . ' de ' . $m[5];
    return null;
};
// The event status label from bl_event_status().
$bl_es_status = static function (string $label) use ($bl_es_range): ?string {
    if ($label === 'On now') return 'En curso';
    if ($label === 'Opens tomorrow') return 'Abre mañana';
    if (preg_match('~^Opens in (\d+) days$~', $label, $m)) return 'Abre en ' . $m[1] . ' días';
    if (str_starts_with($label, 'Held ') && ($range = $bl_es_range(substr($label, 5))) !== null) return 'Celebrada: ' . $range;
    return null;
};
return [
    // Event dates, the event status label, and the two together in the Event details box.
    ['~^\d{1,2}(?: [A-Z][a-z]+)? ?– ?\d{1,2} [A-Z][a-z]+ \d{4}$~', static fn(array $m): ?string => $bl_es_range($m[0])],
    ['~^(?:On now|Opens tomorrow|Opens in \d+ days|Held .+)$~', static fn(array $m): ?string => $bl_es_status($m[0])],
    ['~^(.+?) <t0>(On now|Opens tomorrow|Opens in \d+ days|Held .+)</t0>$~', static function (array $m) use ($bl_es_range, $bl_es_status): ?string {
        $range = $bl_es_range($m[1]);
        $status = $bl_es_status($m[2]);
        return $range !== null && $status !== null ? $range . ' <t0>' . $status . '</t0>' : null;
    }],
    // The booth on a news card: "Booth H.03".
    ['~^Booth ([A-Z0-9][A-Z0-9.\-]*)$~', 'Stand $1'],
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
