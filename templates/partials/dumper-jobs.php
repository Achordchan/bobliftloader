<?php
/**
 * Where the mini dumpers work, on the dumper family page between the range introduction and the comparison. Each job
 * links to the models that suit it. Words and rows are edited in WordPress (网站内容 → 迷你翻斗车, defaults in
 * inc/editable.php); with no rows the section is left out.
 */
$dumperJobs = bl_dumper_jobs();
if (!$dumperJobs) return;
$jobsContent = bl_editable('dumpers')['jobs'];
?>
<section class="bl-attachments bl-dumper-jobs bl-shell" id="dumper-jobs" aria-labelledby="dumper-jobs-title">
  <div class="bl-attachments-head bl-reveal">
    <div><p class="bl-eyebrow"><span class="bl-status-dot"></span> <?= bl_escape($jobsContent['eyebrow']) ?></p><h2 id="dumper-jobs-title"><?= bl_heading($jobsContent['title']) ?></h2></div>
    <div>
      <?php if ($jobsContent['text'] !== ''): ?><p><?= bl_escape($jobsContent['text']) ?></p><?php endif; ?>
      <a class="bl-text-link" href="<?= bl_escape(bl_url('/contact/') . '?equipment=dumper#enquiry') ?>">Ask which suits your job <?= bl_icon('arrow-up-right') ?></a>
    </div>
  </div>
  <div class="bl-attachments-jobs">
    <ul>
      <?php foreach ($dumperJobs as $job): ?>
      <li class="bl-reveal"><strong><?= bl_escape($job['title']) ?></strong><p><?= bl_escape($job['text']) ?></p><?php if ($job['models']): ?><span><?php foreach ($job['models'] as $i => $model): ?><?= $i ? ' · ' : '' ?><a href="<?= bl_escape(bl_product_url($model)) ?>"><?= bl_escape($model['model']) ?></a><?php endforeach; ?></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</section>
