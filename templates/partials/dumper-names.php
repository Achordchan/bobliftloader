<?php
/**
 * How the mini dumper model names are built, after the comparison on the dumper family page (#model-names; product
 * pages link here). The words are edited in WordPress (网站内容 → 迷你翻斗车 → 型号命名, defaults in inc/editable.php);
 * a group with no parts is left out, and with neither group the whole section is.
 */
$names = bl_editable('dumpers')['names'];
$nameGroups = bl_dumper_name_groups();
if (!$nameGroups) return;
// A slash between alternatives takes the accent colour, as on the loader key.
$nameCode = static fn(string $code): string => str_replace(' / ', '<i>/</i>', bl_escape($code));
?>
<section class="bl-model-key bl-model-key-dumpers bl-shell" id="model-names" aria-labelledby="model-names-title">
  <div class="bl-model-key-head"><p class="bl-eyebrow"><?= bl_escape($names['eyebrow']) ?></p><h2 id="model-names-title"><?= bl_heading($names['title']) ?></h2><?php if ($names['intro'] !== ''): ?><p class="bl-model-key-intro"><?= bl_escape($names['intro']) ?></p><?php endif; ?></div>
  <div class="bl-model-key-groups">
    <?php foreach ($nameGroups as $group): ?>
    <div class="bl-model-key-group">
      <?php if ($group['label'] !== ''): ?><h3><?= bl_escape($group['label']) ?></h3><?php endif; ?>
      <dl class="bl-model-key-parts">
        <?php foreach ($group['parts'] as $part): ?><div><dt><?= $nameCode($part['code']) ?></dt><dd><?= bl_escape($part['text']) ?></dd></div><?php endforeach; ?>
      </dl>
    </div>
    <?php endforeach; ?>
  </div>
</section>
