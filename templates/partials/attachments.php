<?php
/**
 * Attachments on the skid steer loader family page, grouped by the work they do. They belong to the machine: quoted with
 * a loader, matched to its hydraulics, not sold as a catalogue of their own. Words and rows are edited in WordPress
 * (网站内容 → 滑移装载机, defaults in inc/editable.php).
 */
$attachmentContent = bl_editable('loaders')['attachments'];
$attachmentGroups = bl_attachments();
$attachmentCount = array_sum(array_map('count', $attachmentGroups));
$loaderJobs = bl_loader_jobs();
?>
<section class="bl-attachments bl-shell" id="attachments" aria-labelledby="attachments-title">
  <div class="bl-attachments-head bl-reveal">
    <div><p class="bl-eyebrow"><span class="bl-status-dot"></span> ATTACHMENTS / <?= $attachmentCount ?> TOOLS</p><h2 id="attachments-title"><?= bl_heading($attachmentContent['title']) ?></h2></div>
    <div>
      <p><?= bl_escape($attachmentContent['text']) ?></p>
      <?php if ($attachmentContent['link'] !== ''): ?><a class="bl-text-link" href="<?= bl_escape(bl_url('/contact/') . '?equipment=loader#enquiry') ?>"><?= bl_escape($attachmentContent['link']) ?> <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
    </div>
  </div>
  <?php if ($loaderJobs): ?>
  <div class="bl-attachments-jobs" id="loader-jobs">
    <h3 class="bl-eyebrow"><?= bl_escape(bl_editable('loaders')['jobs']['eyebrow']) ?></h3>
    <ul>
      <?php foreach ($loaderJobs as $job): ?>
      <li class="bl-reveal"><strong><?= bl_escape($job['title']) ?></strong><p><?= bl_escape($job['text']) ?></p><?php if ($job['tools']): ?><span><?= bl_escape(implode(' · ', $job['tools'])) ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>
  <div class="bl-attachments-groups">
    <?php foreach ($attachmentGroups as $group => $items): ?>
    <div class="bl-attachments-group bl-reveal">
      <h3><?= bl_escape($group) ?> <small><?= count($items) ?></small></h3>
      <ul>
        <?php foreach ($items as $item): ?>
        <li><?php if ($item['image'] !== ''): ?><img src="<?= bl_escape($item['image']) ?>" alt="" width="480" height="360" loading="lazy"><?php endif; ?><strong><?= bl_escape($item['name']) ?></strong><span><?= bl_escape($item['use']) ?></span></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <?php endforeach; ?>
  </div>
  <?php if ($attachmentContent['note'] !== ''): ?><p class="bl-attachments-note"><?= bl_escape($attachmentContent['note']) ?></p><?php endif; ?>
</section>
