<?php
/** The banner lists the regions it asks in only while the Google tag's regional defaults are confirmed and no code is pasted into the slots. */
declare(strict_types=1);
require __DIR__ . '/fixtures/wp-memory.php';
require __DIR__ . '/../inc/bootstrap.php';
require __DIR__ . '/../inc/wp-admin.php';
require __DIR__ . '/../inc/wp-support.php';
require __DIR__ . '/../inc/wp-content-editor.php';
require __DIR__ . '/../inc/wp-tracking.php';
$checks = 0;
$check = static function (bool $ok, string $message) use (&$checks): void { $checks++; if (!$ok) throw new RuntimeException($message); };
$banner = static function (): string { ob_start(); require __DIR__ . '/../templates/partials/consent.php'; return ob_get_clean(); };
$save = static function (array $codes): void { $GLOBALS['options']['bl_content_tracking'] = $codes + ['grouped' => '1']; };
$regions = 'data-regions="' . implode(',', BL_CONSENT_DENIED_REGIONS) . '"';

$save(['google_tag' => 'G-ABC1234, AW-123456789', 'google_regional' => '1']);
$check(bl_consent_regional_banner() && str_contains($banner(), $regions), 'confirmed Google tags alone: the banner does not list its regions');
$check(str_contains($banner(), 'data-regions="AT,') && str_contains($banner(), ',GB,CH"'), 'the region list is not the Consent Mode list');

$save(['google_tag' => 'G-ABC1234, AW-123456789', 'google_regional' => '']);
$check(!bl_consent_regional_banner() && !str_contains($banner(), 'data-regions'), 'unconfirmed defaults: the banner still lists regions (nothing would run elsewhere)');

foreach (['head', 'body_open', 'footer', 'ads_head', 'ads_body_open', 'ads_footer'] as $slot) {
    $save(['google_tag' => 'G-ABC1234', 'google_regional' => '1', $slot => '<script>tool()</script>']);
    $check(!bl_consent_regional_banner() && !str_contains($banner(), 'data-regions'), "code pasted into $slot: the banner still lists regions");
}

$save(['ads_footer' => '<script>tool()</script>', 'google_regional' => '1']);
$check(!bl_consent_regional_banner() && !str_contains($banner(), 'data-regions'), 'no Google tag: the banner lists regions');
echo "Consent regions: $checks checks passed\n";
