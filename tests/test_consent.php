<?php
/** The cookie banner: tracking code is saved per consent category, always printed inside an inert <template>, and the banner and footer link exist only when some code is saved. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-support.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-tracking.php';
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$capture = static function (callable $render): string { ob_start(); $render(); return ob_get_clean(); };
$banner = static fn(): string => $capture(static fn() => require __DIR__ . '/../templates/partials/consent.php');
$footer = static fn(): string => $capture(static fn() => require __DIR__ . '/../templates/footer.php');
$save = static function (array $codes): void { $GLOBALS['options']['bl_content_tracking'] = $codes + ['grouped' => '1']; };

// The WordPress entrypoint (functions.php) never loads bootstrap.php, so wp-tracking.php must bring the slot list itself.
$alone = shell_exec('php -r ' . escapeshellarg("require " . var_export(__DIR__ . '/fixtures/wp-memory.php', true) . "; require " . var_export(__DIR__ . '/../inc/wp-tracking.php', true) . "; echo count(BL_TRACKING_SLOTS);") . ' 2>&1');
$check(trim((string) $alone) === '2', 'wp-tracking.php does not load the consent slots on its own: ' . $alone);

// Nothing saved: no banner, no footer link, no template.
$check(bl_consent_categories() === [], 'categories without any code');
$check($banner() === '', 'banner printed with no tracking code');
$check(!str_contains($footer(), 'data-bl-consent-open'), 'footer link printed with no tracking code');
foreach (['wp_head', 'wp_body_open', 'wp_footer'] as $hook) $check($capture(static fn() => run_hook($hook)) === '', "empty $hook printed markup");

// Code saved before the groups existed is held back, not printed, until the tracking screen has been saved once.
$GLOBALS['options']['bl_content_tracking'] = ['head' => '<script>legacy()</script>', 'ads_footer' => '<script>x()</script>'];
$check(bl_consent_categories() === ['advertising'], 'legacy code counted as a category before it was checked');
$check($capture(static fn() => run_hook('wp_head')) === '', 'unchecked legacy code was printed');
$GLOBALS['options']['bl_content_tracking'] = ['head' => '<script>legacy()</script>'];
$check(bl_consent_categories() === [] && $banner() === '', 'unchecked legacy code produced a banner');

// Analytics only (the three original slots).
$save(['head' => '<script>window.a = 1;</script>', 'body_open' => '', 'footer' => "  <script async src=\"https://example.test/a.js\"></script>\n"]);
$check(bl_consent_categories() === ['analytics'], 'analytics not the only category');
$html = $banner();
$check(str_contains($html, 'data-bl-consent-banner') && str_contains($html, 'data-categories="analytics"'), 'banner missing for analytics');
$check(str_contains($html, 'name="analytics"') && !str_contains($html, 'name="advertising"'), 'wrong category checkboxes');
$check(!str_contains($html, 'data-bl-consent="choose"'), 'a choose button for one category');
$check($capture(static fn() => run_hook('wp_head')) === bl_tracking_markup('analytics', '<script>window.a = 1;</script>'), 'head code not wrapped');
$check($capture(static fn() => run_hook('wp_body_open')) === '', 'empty body slot printed');
$check($capture(static fn() => run_hook('wp_footer')) === bl_tracking_markup('analytics', '<script async src="https://example.test/a.js"></script>'), 'footer code not trimmed and wrapped');
$check(str_contains($footer(), 'data-bl-consent-open'), 'footer link missing');

// Both categories: advertising has its own three slots and is printed after analytics.
$save(['head' => '<script>a()</script>', 'ads_head' => '<script>fbq("init")</script>', 'ads_body_open' => '<noscript><img src="https://example.test/p.gif"></noscript>', 'ads_footer' => '']);
$check(bl_consent_categories() === ['analytics', 'advertising'], 'two categories not found');
$html = $banner();
$check(str_contains($html, 'data-categories="analytics,advertising"') && str_contains($html, 'data-bl-consent="choose"'), 'banner missing the choice');
$check($capture(static fn() => run_hook('wp_head')) === bl_tracking_markup('analytics', '<script>a()</script>') . bl_tracking_markup('advertising', '<script>fbq("init")</script>'), 'head order or wrapping wrong');
$check(str_contains($capture(static fn() => run_hook('wp_body_open')), '<template data-bl-consent="advertising"><noscript>'), 'advertising body code missing');

// The markup is inert (a template) followed by the gate: it writes the code only for a cookie that agrees to this category.
$markup = bl_tracking_markup('advertising', '<script>x()</script>');
$check(str_starts_with($markup, '<template data-bl-consent="advertising"><script>x()</script></template><script>'), 'code is not held in a template');
$check(str_contains($markup, 'bl_consent=') && str_contains($markup, 'v.v===1') && str_contains($markup, 'v[c]===true') && str_contains($markup, 'document.write(t.innerHTML)') && str_contains($markup, 'window.blConsentRan') && str_ends_with($markup, ',"advertising")</script>'), 'consent gate changed');
$check($capture(static fn() => run_hook('wp_footer')) === '', 'empty advertising footer printed');

// Advertising only still gets a banner.
$save(['ads_footer' => '<script>x()</script>']);
$check(bl_consent_categories() === ['advertising'], 'advertising alone not found');
$check(str_contains($banner(), 'name="advertising"'), 'banner missing for advertising only');

// Hook positions are unchanged, so code still lands where the head, body and footer expect it.
$check(isset($GLOBALS['hooks']['wp_head'][1], $GLOBALS['hooks']['wp_body_open'][1], $GLOBALS['hooks']['wp_footer'][99]), 'tracking hook priorities changed');

// The editor knows all six raw fields and keeps them verbatim; the admin screen shows two groups.
$GLOBALS['caps'] = [];
$keys = ['head', 'body_open', 'footer', 'ads_head', 'ads_body_open', 'ads_footer'];
$defaults = bl_content_defaults('tracking');
foreach ($keys as $key) $check(array_key_exists($key, $defaults), "tracking default missing: $key");
$screen = $capture('bl_tracking_screen');
foreach ($keys as $key) $check(str_contains($screen, 'name="bl_content[' . $key . ']"'), "admin screen missing textarea: $key");
$check(str_contains($screen, 'name="bl_content[grouped]" value="1"'), 'admin screen does not carry the grouped mark');

// Code saved before the groups existed asks for a check once; saving the six-field screen settles it, even with the advertising fields empty.
$GLOBALS['caps'] = ['manage_options' => true, 'unfiltered_html' => true];
$GLOBALS['options']['bl_content_tracking'] = ['head' => '<script>old()</script>'];
$check(str_contains($capture('bl_tracking_screen'), '原有代码已暂停加载'), 'legacy code was not flagged on the tracking screen');
$result = bl_content_locked('tracking', static fn($previous) => bl_content_prepare('tracking', $previous, ['head' => '<script>old()</script>', 'body_open' => '', 'footer' => '', 'ads_head' => '', 'ads_body_open' => '', 'ads_footer' => '', 'grouped' => '1'], true), bl_content_revision(['head' => '<script>old()</script>']));
$check($result === true, 'saving the grouped screen failed');
$check(!str_contains($capture('bl_tracking_screen'), '原有代码已暂停加载'), 'the legacy notice stayed after saving');
// An external script with neither async nor defer is flagged on the screen (Chrome may skip it when written into the page on a slow connection); async, defer, modules and inline code are not.
foreach (['<script src="https://t.example/a.js"></script>' => true, '<script async src="https://t.example/a.js"></script>' => false, '<script defer src=x></script>' => false, '<script type="module" src="/m.js"></script>' => false, '<script type="text/plain" src="/n.js"></script>' => false, '<script type="text/javascript" src="/n.js"></script>' => true, '<script>inline()</script>' => false, '<SCRIPT SRC="/a.js" data-async="1"></SCRIPT>' => true, '<script nomodule src="/old.js"></script>' => false, '<script>document.write("<script src=\\"/w.js\\"></scr"+"ipt>")</script>' => false, '<script>a()</script><script src="/b.js"></script>' => true, '<!-- <script src="/old.js"></script> --><script async src="/new.js"></script>' => false] as $code => $flagged) {
    $check(bl_tracking_has_blocking_script($code) === $flagged, 'blocking-script check wrong for ' . $code);
}
$GLOBALS['options']['bl_content_tracking'] = ['head' => '<script src="https://t.example/a.js"></script>', 'grouped' => '1'];
$check(str_contains($capture('bl_tracking_screen'), '有外部脚本没有写 async 或 defer'), 'a blocking external script was not flagged on the tracking screen');
$check(bl_tracking_code('head') === '<script src="https://t.example/a.js"></script>' && bl_consent_categories() === ['analytics'], 'code with a plain external script was changed or held back');
$GLOBALS['options']['bl_content_tracking'] = ['head' => '<script async src="https://t.example/a.js"></script>', 'grouped' => '1'];
$check(bl_tracking_code('head') !== '' && bl_consent_categories() === ['analytics'], 'async code was held back');
$check(!str_contains($capture('bl_tracking_screen'), '有外部脚本没有写 async 或 defer'), 'an async script was flagged');
$GLOBALS['caps'] = [];

// The copy is editable and the privacy default no longer promises that the tools load without asking.
foreach (['title', 'text', 'privacy', 'reject', 'accept', 'choose', 'save', 'close', 'analytics_title', 'analytics_text', 'advertising_title', 'advertising_text', 'note', 'settings'] as $key) {
    $check(bl_content_value('global', 'consent.' . $key) !== null && bl_content_value('global', 'consent.' . $key) !== '', "consent copy missing: $key");
}
$check(str_contains(bl_content_value('privacy', 'body.ending'), 'only after you agree'), 'privacy page does not mention consent');

// The script keeps the choice in the cookie the gate reads, and reloads when the agreed categories change; it never runs tracking code itself.
$js = file_get_contents(__DIR__ . '/../assets/js/consent.js');
$check(str_contains($js, "const COOKIE = 'bl_consent'") && str_contains($js, 'const VERSION = 1;'), 'cookie name or version no longer match the gate');
$check(str_contains($js, 'clearToolCookies(declined)') && str_contains($js, 'location.reload()'), 'consent script lost its cleanup or reload');
$check(str_contains($js, 'BroadcastChannel') && str_contains($js, 'visibilitychange') && str_contains($js, 'readSaved()'), 'consent script no longer syncs other tabs');
$check(!str_contains($js, 'createElement(\'script\')') && !str_contains($js, 'document.write'), 'consent script runs tracking code itself');

// Google tag IDs: the theme loads the Google tag with Consent Mode v2 (denied by default in the EEA, UK and Switzerland, granted
// elsewhere, then the cookie's choice), and the categories it serves get a banner even with no pasted code.
$save([]);
$check(bl_google_tag_markup() === '' && bl_google_tag_ids() === [], 'Google tag printed with no ID');
$save(['google_tag' => " g-ABC1234XYZ, aw-123456789\nnot-an-id, AW-123456789", 'google_conversion' => 'AW-123456789/AbC-dEf_9', 'google_regional' => '1']);
$check(bl_google_tag_ids() === ['G-ABC1234XYZ', 'AW-123456789'], 'Google tag IDs not parsed, upper-cased and de-duplicated');
$check(bl_consent_categories() === ['analytics', 'advertising'], 'Google tag IDs do not create the banner categories');
$markup = $capture(static fn() => run_hook('wp_head'));
$check(str_starts_with($markup, '<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag("consent","default",{"ad_storage":"denied"'), 'consent defaults are not first');
$check(str_contains($markup, '"region":["AT",') && str_contains($markup, '"GB","CH"]') && str_contains($markup, '"wait_for_update":500'), 'denied region default missing');
$check(strpos($markup, '"region"') < strpos($markup, '{"ad_storage":"granted"'), 'granted default before the regional one');
$check(str_contains($markup, 'bl_consent=') && str_contains($markup, 'v.v!==1') && str_contains($markup, 'gtag("consent","update"') && str_contains($markup, '["analytics","advertising"]'), 'cookie choice not applied');
$check(str_contains($markup, 'gtag("config","G-ABC1234XYZ");gtag("config","AW-123456789");') && str_contains($markup, 'window.blGoogleConversion="AW-123456789/AbC-dEf_9"'), 'config or conversion missing');
$check(str_ends_with($markup, '<script async src="https://www.googletagmanager.com/gtag/js?id=G-ABC1234XYZ"></script>'), 'gtag.js not loaded async');
// Until an administrator confirms the texts describe the regional defaults, the whole Google tag is gated like pasted code:
// nothing runs before the cookie agrees to a category it serves.
$confirmed = bl_google_tag_markup();
$save(['google_tag' => 'G-ABC1234XYZ, AW-123456789']);
$gated = bl_google_tag_markup();
$check(str_starts_with($gated, '<template data-bl-consent="google"><script>window.dataLayer') && str_contains($gated, 'c.some(function(k){return v[k]===true})') && str_contains($gated, 'document.write(t.innerHTML)') && str_ends_with($gated, ',["analytics","advertising"])</script>'), 'unconfirmed Google tag is not gated');
$check(!str_starts_with($confirmed, '<template'), 'confirmed Google tag was gated');
$check(str_contains($gated, 'if((window.blConsentRan||{})["analytics"])gtag("config","G-ABC1234XYZ");if((window.blConsentRan||{})["advertising"])gtag("config","AW-123456789");') && str_contains($confirmed, ';gtag("config","G-ABC1234XYZ");gtag("config","AW-123456789");'), 'unconfirmed tags not configured per agreed category');
// Run the gated script with a cookie that agrees to analytics only: GA4 is configured, Google Ads is not.
preg_match('~<script>(window\.dataLayer.*?)</script>~', $gated, $inline);
$run = static fn(string $cookie): string => (string) shell_exec('node -e ' . escapeshellarg('global.window=global;global.document={cookie:' . json_encode($cookie) . '};eval(' . json_encode($inline[1]) . ');console.log(JSON.stringify(dataLayer.filter(a=>a[0]==="config").map(a=>a[1])))'));
$check(trim($run('bl_consent=' . rawurlencode('{"v":1,"analytics":true,"advertising":false}'))) === '["G-ABC1234XYZ"]', 'declined advertising tag was configured');
$check(trim($run('bl_consent=' . rawurlencode('{"v":1,"analytics":true,"advertising":true}'))) === '["G-ABC1234XYZ","AW-123456789"]', 'agreed tags were not configured');
$GLOBALS['caps'] = ['manage_options' => true, 'unfiltered_html' => true];
$check(str_contains($capture('bl_tracking_screen'), 'Google 标签目前仍是“同意后才加载”') && str_contains($capture('bl_tracking_screen'), 'name="bl_content[google_regional]" value="1">'), 'unconfirmed state not shown on the tracking screen');
$save(['google_tag' => 'G-ABC1234XYZ', 'google_regional' => '1']);
$check(!str_contains($capture('bl_tracking_screen'), 'Google 标签目前仍是') && str_contains($capture('bl_tracking_screen'), 'value="1" checked>'), 'confirmed state not shown on the tracking screen');
$GLOBALS['caps'] = [];
$save(['google_tag' => 'AW-123456789', 'google_conversion' => 'AW-1/x"onload', 'google_regional' => '1']);
$check(bl_consent_categories() === ['advertising'] && !str_contains(bl_google_tag_markup(), 'blGoogleConversion'), 'malformed conversion printed or wrong category');
$check(str_contains(bl_google_tag_markup(), 'window.blGoogleTag=true;'), 'theme Google tag not marked');
$save(['google_tag' => 'GT-ABCDEFG, DC-1234567']);
$check(bl_google_tag_ids() === [] && bl_consent_categories() === [], 'ambiguous GT-/DC- IDs accepted');
$save(['google_tag' => '<script>x()</script>']);
$check(bl_google_tag_markup() === '' && bl_consent_categories() === [], 'invalid tag ID printed');
$GLOBALS['caps'] = ['manage_options' => true, 'unfiltered_html' => true];
$screen = $capture('bl_tracking_screen');
$check(str_contains($screen, 'name="bl_content[google_tag]"') && str_contains($screen, 'name="bl_content[google_conversion]"'), 'Google fields missing from the tracking screen');
$GLOBALS['caps'] = [];
$check(array_key_exists('google_tag', bl_content_defaults('tracking')) && array_key_exists('google_conversion', bl_content_defaults('tracking')), 'Google fields missing from defaults');
$check(str_contains($js, "window.gtag('consent', 'update'"), 'banner choice does not update Google consent at once');
$inquiry = file_get_contents(__DIR__ . '/../assets/js/inquiry.js');
$check(str_contains($inquiry, "if (!window.blGoogleTag || typeof window.gtag !== 'function') return;") && str_contains($inquiry, "window.gtag('event', 'generate_lead'") && str_contains($inquiry, "send_to: window.blGoogleConversion"), 'enquiry not reported to the Google tag');

echo "ok ($checks checks)\n";
