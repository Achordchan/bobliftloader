<?php
declare(strict_types=1);
require __DIR__ . '/../inc/helpers.php';

$folder = 'https://example.com/wp-content/plugins/p/build/';
$cases = [
    'a{background:url(img/x.png)}' => 'a{background:url(' . $folder . 'img/x.png)}',
    "a{background:url('img/x.png')}" => "a{background:url('" . $folder . "img/x.png')}",
    'a{background:url("../i.svg")}' => 'a{background:url("' . $folder . '../i.svg")}',
    'a{background:url(data:image/png;base64,AA)}' => 'a{background:url(data:image/png;base64,AA)}',
    'a{background:url("data:image/svg+xml,%3Csvg%3E")}' => 'a{background:url("data:image/svg+xml,%3Csvg%3E")}',
    "a{background:url('https://cdn.example/x.png')}" => "a{background:url('https://cdn.example/x.png')}",
    'a{background:url("//cdn.example/x.png")}' => 'a{background:url("//cdn.example/x.png")}',
    'a{background:url("/abs.png")}' => 'a{background:url("/abs.png")}',
    'a{mask:url("#m")}' => 'a{mask:url("#m")}',
    'a{background:url( "https://cdn.example/x.png")}' => 'a{background:url( "https://cdn.example/x.png")}',
    'a{background:url(  https://cdn.example/x.png)}' => 'a{background:url(  https://cdn.example/x.png)}',
    "a{background:url( '/abs.png')}" => "a{background:url( '/abs.png')}",
    'a{background:url( data:image/png;base64,AA)}' => 'a{background:url( data:image/png;base64,AA)}',
    'a{background:url( "img/x.png")}' => 'a{background:url("' . $folder . 'img/x.png")}',
];
foreach ($cases as $in => $want) {
    $got = bl_css_relative_urls($in, $folder);
    if ($got !== $want) { fwrite(STDERR, "FAIL\n  in:   $in\n  got:  $got\n  want: $want\n"); exit(1); }
}
echo "ok\n";
