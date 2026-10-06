<?php
$content = bl_editable('applications');
$hero = $content['hero'];
$send = $content['send'];
?>
<section class="bl-apps-hero" aria-labelledby="applications-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => 'Applications']]); ?>
    <div class="bl-apps-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> APPLICATIONS / <?= count($applications) ?> JOBS</p>
        <h1 id="applications-title"><span><?= bl_escape($hero['line1']) ?></span><span class="bl-outline-text"><?= bl_escape($hero['line2']) ?></span><span class="bl-blue-text"><?= bl_escape($hero['line3']) ?></span></h1>
      </div>
      <div class="bl-apps-hero-side">
        <p><?= bl_escape($hero['text']) ?></p>
        <a class="bl-text-link" href="#before-you-enquire"><?= bl_escape($hero['link']) ?> <?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <ol class="bl-apps-triptych<?= count($applications) > 3 ? ' bl-apps-triptych-rows' : '' ?>">
    <?php foreach ($applications as $i => $application): $focus = bl_application_focus($application); ?>
    <li>
      <a href="<?= bl_escape(bl_url('/applications/' . bl_application_slug($application) . '/')) ?>">
        <img src="<?= bl_escape($application['image']) ?>" alt="<?= bl_escape($application['alt']) ?>" width="1586" height="992"<?= $i ? ' loading="lazy"' : ' fetchpriority="high"' ?>>
        <span class="bl-apps-panel-index">0<?= $i + 1 ?></span>
        <span class="bl-apps-panel-body">
          <span class="bl-apps-panel-family"><?= bl_escape($application['product'] === 'dumper' ? 'Mini dumpers' : 'Mini skid steer loaders') ?></span>
          <span class="bl-apps-panel-title"><?= bl_escape($application['title']) ?></span>
          <span class="bl-apps-panel-focus"><b><?= bl_escape($focus['value']) ?><small><?= bl_escape($focus['unit']) ?></small></b><span><?= bl_escape($focus['label']) ?></span></span>
          <span class="bl-apps-panel-more">Explore <?= bl_icon('arrow-up-right') ?></span>
        </span>
      </a>
    </li>
    <?php endforeach; ?>
  </ol>
  <p class="bl-apps-credit bl-shell">Scenes illustrated from BOB-LIFT product photography. Figures from each model’s published specifications.</p>
</section>

<?php $recent = array_slice(bl_application_cases(), 0, 6); if ($recent): ?>
<section class="bl-app-cases bl-shell" id="in-practice" aria-labelledby="cases-title">
  <div class="bl-app-machines-head bl-reveal">
    <div><p class="bl-eyebrow">IN PRACTICE</p><h2 id="cases-title">REAL JOBS.<br><span class="bl-blue-text">REAL SITES.</span></h2></div>
    <div><p>Specific applications, written up: what the site looked like, which machine did the work and how it went.</p></div>
  </div>
  <div class="bl-news-grid"><?php foreach ($recent as $case) bl_case_card($case); ?></div>
</section>
<?php endif; ?>
<section class="bl-apps-send bl-shell" id="before-you-enquire" aria-labelledby="send-title">
  <div class="bl-apps-send-head bl-reveal">
    <p class="bl-eyebrow"><?= bl_escape($send['eyebrow']) ?></p>
    <h2 id="send-title"><?= bl_heading($send['title']) ?></h2>
    <p><?= bl_escape($send['text']) ?></p>
    <div class="bl-apps-send-actions">
      <a class="bl-button bl-button-blue" href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>">Start an enquiry <?= bl_icon('arrow-up-right') ?></a>
      <a class="bl-text-link" href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener">Send photos on WhatsApp <?= bl_icon('arrow-up-right') ?></a>
    </div>
  </div>
  <ol class="bl-apps-send-list">
    <?php foreach ($send['items'] as $i => $item): ?><li class="bl-reveal"><span>0<?= $i + 1 ?></span><h3><?= bl_escape($item['title']) ?></h3><p><?= bl_rich($item['text']) ?></p></li><?php endforeach; ?>
  </ol>
</section>
