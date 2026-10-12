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
$save(['head' => '<script>window.a = 1;</script>', 'body_open' => '', 'footer' => "  <script src=\"https://example.test/a.js\"></script>\n"]);
$check(bl_consent_categories() === ['analytics'], 'analytics not the only category');
$html = $banner();
$check(str_contains($html, 'data-bl-consent-banner') && str_contains($html, 'data-categories="analytics"'), 'banner missing for analytics');
$check(str_contains($html, 'name="analytics"') && !str_contains($html, 'name="advertising"'), 'wrong category checkboxes');
$check(!str_contains($html, 'data-bl-consent="choose"'), 'a choose button for one category');
$check($capture(static fn() => run_hook('wp_head')) === '<template data-bl-consent="analytics"><script>window.a = 1;</script></template>', 'head code not wrapped');
$check($capture(static fn() => run_hook('wp_body_open')) === '', 'empty body slot printed');
$check($capture(static fn() => run_hook('wp_footer')) === '<template data-bl-consent="analytics"><script src="https://example.test/a.js"></script></template>', 'footer code not trimmed and wrapped');
$check(str_contains($footer(), 'data-bl-consent-open'), 'footer link missing');

// Both categories: advertising has its own three slots and is printed after analytics.
$save(['head' => '<script>a()</script>', 'ads_head' => '<script>fbq("init")</script>', 'ads_body_open' => '<noscript><img src="https://example.test/p.gif"></noscript>', 'ads_footer' => '']);
$check(bl_consent_categories() === ['analytics', 'advertising'], 'two categories not found');
$html = $banner();
$check(str_contains($html, 'data-categories="analytics,advertising"') && str_contains($html, 'data-bl-consent="choose"'), 'banner missing the choice');
$check($capture(static fn() => run_hook('wp_head')) === '<template data-bl-consent="analytics"><script>a()</script></template><template data-bl-consent="advertising"><script>fbq("init")</script></template>', 'head order or wrapping wrong');
$check(str_contains($capture(static fn() => run_hook('wp_body_open')), '<template data-bl-consent="advertising"><noscript>'), 'advertising body code missing');
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
$GLOBALS['caps'] = [];

// The copy is editable and the privacy default no longer promises that the tools load without asking.
foreach (['title', 'text', 'privacy', 'reject', 'accept', 'choose', 'save', 'close', 'analytics_title', 'analytics_text', 'advertising_title', 'advertising_text', 'note', 'settings'] as $key) {
    $check(bl_content_value('global', 'consent.' . $key) !== null && bl_content_value('global', 'consent.' . $key) !== '', "consent copy missing: $key");
}
$check(str_contains(bl_content_value('privacy', 'body.ending'), 'only after you agree'), 'privacy page does not mention consent');

// The script never starts a category the visitor has not agreed to, and clears the tools' cookies on withdrawal.
$js = file_get_contents(__DIR__ . '/../assets/js/consent.js');
$check(str_contains($js, 'clearToolCookies(declined)') && !str_contains($js, 'SCRIPT_WAIT') && str_contains($js, 'blStarted'), 'consent script lost its guards');
$check(str_contains($js, 'lateAllowances') && str_contains($js, 'pending.deferred') && str_contains($js, "'writeln'") && str_contains($js, 'readSaved()') && str_contains($js, "'onload'") && str_contains($js, "hasAttribute('async')") && str_contains($js, 'nomodule') && str_contains($js, 'writeSlot') && str_contains($js, 'BroadcastChannel') && str_contains($js, 'visibilitychange'), 'consent script no longer syncs other tabs');
$check(str_contains($js, "addEventListener('load'") && str_contains($js, 'jobs.shift()'), 'consent script no longer waits for external scripts');

echo "ok ($checks checks)\n";
