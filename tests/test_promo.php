<?php
/** php tests/test_promo.php — the year-end offers: dates, the enquiry mark, the sitemap and the page's words. */
declare(strict_types=1);
$_SERVER['REQUEST_URI'] = '/';
require __DIR__ . '/../inc/bootstrap.php';
$fail = static function (string $what): void { fwrite(STDERR, $what . "\n"); exit(1); };

// Live from the first to the last day (China time), ended from the day after.
foreach (['2026-11-19' => [false, false], '2026-11-20' => [true, false], '2026-12-31' => [true, false], '2027-01-01' => [false, true]] as $day => [$live, $ended]) {
    if (bl_promo_live($day) !== $live || bl_promo_ended($day) !== $ended) $fail('dates on ' . $day);
}
// The enquiry mark: only the page's own value, only while the offers run.
if (bl_promo_enquiry_mark(BL_PROMO_CODE, '2026-12-01') !== BL_PROMO_LABEL) $fail('mark while live');
if (bl_promo_enquiry_mark(BL_PROMO_CODE, '2027-01-01') !== '' || bl_promo_enquiry_mark('other', '2026-12-01') !== '') $fail('mark after the end or with another value');
// Every coupon and deposit row has its amounts; the page states no price list and no special-price model.
if (count(bl_promo_rows('coupons', ['save', 'over'])) !== 3 || count(bl_promo_rows('deposit', ['order', 'pay', 'worth'])) !== 3) $fail('offer rows');
$copy = serialize(bl_content_defaults('promo'));
if (preg_match('/\bprice list\b|\bFOB\b|\bEXW\b|\bfrom US\$\d[\d,]* per\b/i', $copy)) $fail('the copy states a price');
// Every line the page shows has its Spanish.
$es = bl_i18n_dictionary('es');
foreach (['Claim an offer', 'Order value', 'You pay', 'Counts as', 'See the offers', 'Up to US$2,500 off your order.'] as $line) if (bl_i18n_get($es, bl_i18n_key($line)) === null) $fail('Spanish missing: ' . $line);
if (!bl_has_language(BL_PROMO_PATH, 'es')) $fail('page not listed in Spanish');
$route = bl_resolve_route(BL_PROMO_PATH);
if ($route['type'] !== 'promo' || $route['status'] !== 200) $fail('route');
// The cache never keeps a copy past a change of the offers: cut before the first day and before the last, never stored on
// the last day, left alone once the offers have ended and on other pages (times China time).
$at = static fn(string $time): int => (new DateTimeImmutable($time, new DateTimeZone('Asia/Shanghai')))->getTimestamp();
$home = ['type' => 'home'];
foreach (['2026-11-19 23:59:00' => 'max-age=60', '2026-12-01 10:00:00' => 'max-age=300', '2026-12-30 23:58:00' => 'max-age=120', '2026-12-31 00:00:00' => 'no-store', '2026-12-31 23:59:59' => 'no-store', '2027-01-01 00:00:00' => ''] as $time => $rule) {
    if (bl_promo_cache_rules($home, $at($time)) !== $rule) $fail('cache rule at ' . $time);
}
if (bl_promo_cache_rules(['type' => 'contact'], $at('2026-12-31 12:00:00')) !== '') $fail('cache rule on another page');
echo "promo: ok\n";
