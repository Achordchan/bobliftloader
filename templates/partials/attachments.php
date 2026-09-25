<?php
/**
 * Attachments on the skid steer loader family page, grouped by the work they do. They belong to the machine: quoted with
 * a loader, matched to its hydraulics, not sold as a catalogue of their own. Data in inc/support.php.
 */
$attachmentGroups = bl_attachments();
$attachmentCount = array_sum(array_map('count', $attachmentGroups));
?>
<section class="bl-attachments bl-shell" id="attachments" aria-labelledby="attachments-title">
  <div class="bl-attachments-head bl-reveal">
    <div><p class="bl-eyebrow"><span class="bl-status-dot"></span> ATTACHMENTS / <?= $attachmentCount ?> TOOLS</p><h2 id="attachments-title">ONE LOADER.<br><span class="bl-blue-text">MANY JOBS.</span></h2></div>
    <div>
      <p>Our loaders take their tools through a quick-attach plate and auxiliary hydraulic lines, so the bucket comes off and a fork, auger or sweeper goes on in minutes. Attachments are quoted with your loader and matched to its hydraulics.</p>
      <a class="bl-text-link" href="<?= bl_escape(bl_url('/contact/') . '?equipment=loader#enquiry') ?>">Ask which suit your model <?= bl_icon('arrow-up-right') ?></a>
    </div>
  </div>
  <div class="bl-attachments-groups">
    <?php foreach ($attachmentGroups as $group => $items): ?>
    <div class="bl-attachments-group bl-reveal">
      <h3><?= bl_escape($group) ?> <small><?= count($items) ?></small></h3>
      <ul>
        <?php foreach ($items as $item): ?>
        <li><img src="<?= bl_escape(bl_asset('images/attachments/' . $item['slug'] . '.webp')) ?>" alt="" width="480" height="360" loading="lazy"><strong><?= bl_escape($item['name']) ?></strong><span><?= bl_escape($item['use']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>
  </div>
  <p class="bl-attachments-note">Hydraulic flow differs from model to model, so not every attachment suits every loader. We confirm the match when we quote your machine.</p>
</section>
