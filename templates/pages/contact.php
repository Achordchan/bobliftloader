<?php
$selectedRecord = bl_find_record(bl_products(), bl_query('product'));
$selectedFamily = $selectedRecord && $selectedRecord['category'] === 'mini-dumpers' ? 'dumper' : 'loader';
$requestedApplication = bl_query('application');
$validApplications = array_column($applications, 'title');
$applicationName = in_array($requestedApplication, $validApplications, true) ? $requestedApplication : '';
if (!$selectedRecord && $applicationName === 'Material transport') $selectedFamily = 'dumper';
// Family pages link here with ?equipment=loader|dumper so the right equipment is already chosen.
if (!$selectedRecord && bl_query('equipment') === 'dumper') $selectedFamily = 'dumper';
$prefillMessage = $selectedRecord ? 'I would like information and a quotation for the ' . $selectedRecord['name'] . '.' : '';
if ($applicationName) $prefillMessage .= ($prefillMessage ? ' ' : '') . 'My application is ' . strtolower($applicationName) . '.';
// The family range charts send the visitor's opening or load along; only plausible values are written in.
$fitWidth = (int) bl_query('width');
$fitLoad = (int) bl_query('load');
$siteNotes = [];
if ($fitWidth >= 500 && $fitWidth <= 5000) $siteNotes[] = 'The narrowest opening on my site is ' . number_format($fitWidth) . ' mm.';
if ($fitLoad > 0 && $fitLoad <= 10000) $siteNotes[] = 'My heaviest load is about ' . number_format($fitLoad) . ' kg.';
if (bl_query('ground') === 'soft') $siteNotes[] = 'The ground is soft or sloped.';
if ($siteNotes) $prefillMessage .= ($prefillMessage ? ' ' : '') . implode(' ', $siteNotes);
?>
<section class="bl-page-hero bl-shell"><?php bl_breadcrumbs([['label' => 'Contact']]); ?><div class="bl-page-heading"><div><p class="bl-eyebrow">A DIRECT LINE TO BOB-LIFT</p><h1>LET’S BUILD<br><span class="bl-blue-text">WHAT’S NEXT.</span></h1></div><p>Equipment questions. Project requirements. Business partnerships. Start a conversation with our team.</p></div></section>
<section class="bl-contact-page-grid bl-shell" id="enquiry"><aside class="bl-contact-details"><div><p class="bl-eyebrow">EQUIPMENT ENQUIRIES</p><a href="mailto:info@boblift.com">info@boblift.com <?= bl_icon('arrow-up-right') ?></a><a href="tel:+8615852499415">+86 158 5249 9415</a><a class="bl-text-link" href="https://wa.me/8615852499415" target="_blank" rel="noopener">Talk on WhatsApp <?= bl_icon('arrow-up-right') ?></a></div><div><p class="bl-eyebrow">BUSINESS COOPERATION</p><a href="mailto:business@boblift.com">business@boblift.com <?= bl_icon('arrow-up-right') ?></a></div><div><p class="bl-eyebrow">OUR COMPANY</p><h2>Xuzhou BOB-LIFT<br>Construction Machinery Co., Ltd.</h2><address>No. 79, Zone B, Jingluotuo Industrial Park,<br>Yunlong District, Xuzhou, Jiangsu, China</address><p>Monday–Friday · 08:30–18:00<br>China Standard Time (UTC+8)</p><a class="bl-text-link" href="https://www.boblift.com/contactus.html" target="_blank" rel="noopener">Main company contact page <?= bl_icon('arrow-up-right') ?></a></div></aside><div class="bl-contact-form-panel"><p class="bl-eyebrow">TELL US ABOUT YOUR PROJECT</p><h2>THE RIGHT CONVERSATION<br>STARTS WITH YOU.</h2><?php require __DIR__ . '/../partials/inquiry-form.php'; ?></div></section>
<section class="bl-contact-parent bl-shell"><span class="bl-eyebrow">ONE COMPANY. A DIRECT CONNECTION.</span><p>This compact equipment website is operated for <strong>Xuzhou BOB-LIFT Construction Machinery Co., Ltd.</strong> Explore the wider company at <a href="https://www.boblift.com/" target="_blank" rel="noopener">boblift.com</a>.</p></section>
