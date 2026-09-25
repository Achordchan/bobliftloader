<?php
$sendUs = [
    ['title' => 'The narrowest opening', 'text' => 'Gate, side passage, doorway or ramp: the tightest point between the road and the work, in millimetres.'],
    ['title' => 'The heaviest load', 'text' => 'What you will move and roughly how much at a time, per bucket or per trip.'],
    ['title' => 'The ground', 'text' => 'Lawn, soil, gravel, paving or concrete, and the steepest slope the machine will climb.'],
    ['title' => 'Photos of the route', 'text' => 'A few pictures of the way in and the work area answer most of our questions before we ask them.'],
];
?>
<section class="bl-apps-hero" aria-labelledby="applications-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => 'Applications']]); ?>
    <div class="bl-apps-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span> APPLICATIONS / <?= count($applications) ?> JOBS</p>
        <h1 id="applications-title"><span>YOUR WORK.</span><span class="bl-outline-text">OUR STARTING</span><span class="bl-blue-text">POINT.</span></h1>
      </div>
      <div class="bl-apps-hero-side">
        <p>Every job has one number that decides the machine. Start with the job, and we will point you to the models that suit it.</p>
        <a class="bl-text-link" href="#before-you-enquire">Before you enquire <?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <ol class="bl-apps-triptych">
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

<section class="bl-apps-send bl-shell" id="before-you-enquire" aria-labelledby="send-title">
  <div class="bl-apps-send-head bl-reveal">
    <p class="bl-eyebrow">BEFORE YOU ENQUIRE</p>
    <h2 id="send-title">TELL US ABOUT<br><span class="bl-blue-text">THE SITE.</span></h2>
    <p>With these four answers, our team can suggest a model and configuration in its first reply instead of starting with questions.</p>
    <div class="bl-apps-send-actions">
      <a class="bl-button bl-button-blue" href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>">Start an enquiry <?= bl_icon('arrow-up-right') ?></a>
      <a class="bl-text-link" href="https://wa.me/8615852499415" target="_blank" rel="noopener">Send photos on WhatsApp <?= bl_icon('arrow-up-right') ?></a>
    </div>
  </div>
  <ol class="bl-apps-send-list">
    <?php foreach ($sendUs as $i => $item): ?><li class="bl-reveal"><span>0<?= $i + 1 ?></span><h3><?= bl_escape($item['title']) ?></h3><p><?= bl_escape($item['text']) ?></p></li><?php endforeach; ?>
  </ol>
</section>
