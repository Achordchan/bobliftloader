<?php
/**
 * The buying guide on a family page, above the related guides: what to check before choosing a model, then the questions
 * buyers ask about the range. Laid out like the support page's questions (support.css). Words and rows are edited in
 * WordPress (网站内容 → 滑移装载机 / 迷你翻斗车); with no points and no questions the section is left out. Expects $category.
 */
$buying = bl_family_buying($category === 'mini-dumpers' ? 'dumpers' : 'loaders');
if (!$buying['points'] && !$buying['faq']) return;
?>
<section class="bl-sup-faq bl-range-guide bl-shell" id="buying-guide" aria-labelledby="buying-guide-title">
  <div class="bl-sup-faq-aside bl-reveal">
    <p class="bl-eyebrow"><?= bl_escape($buying['eyebrow']) ?></p>
    <h2 id="buying-guide-title"><?= bl_heading($buying['title']) ?></h2>
    <?php if ($buying['intro'] !== ''): ?><p><?= bl_escape($buying['intro']) ?></p><?php endif; ?>
    <?php if ($buying['link'] !== ''): ?><a class="bl-text-link" href="<?= bl_escape(bl_url('/contact/') . '?equipment=' . ($category === 'mini-dumpers' ? 'dumper' : 'loader') . '#enquiry') ?>"><?= bl_escape($buying['link']) ?> <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
  </div>
  <div class="bl-sup-faq-groups">
    <?php if ($buying['points']): ?>
    <div class="bl-sup-faq-group bl-range-guide-points bl-reveal">
      <h3><?= bl_escape($buying['points_label']) ?></h3>
      <ol><?php foreach ($buying['points'] as $i => $point): ?><li><span><?= sprintf('%02d', $i + 1) ?></span><div><h4><?= bl_escape($point['title']) ?></h4><p><?= bl_rich($point['text']) ?></p></div></li><?php endforeach; ?></ol>
    </div>
    <?php endif; ?>
    <?php if ($buying['faq']): ?>
    <div class="bl-sup-faq-group bl-reveal">
      <h3><?= bl_escape($buying['faq_label']) ?></h3>
      <?php foreach ($buying['faq'] as $item): $link = ($item['link_label'] ?? '') !== '' && ($item['link_url'] ?? '') !== ''; ?>
      <details class="bl-sup-faq-item">
        <summary><?= bl_escape($item['q']) ?><?= bl_icon('plus') ?></summary>
        <div><p><?= bl_escape($item['a']) ?></p><?php if ($link): ?><a class="bl-text-link" href="<?= bl_escape(bl_application_link($item['link_url'])) ?>"><?= bl_escape($item['link_label']) ?> <?= bl_icon(str_starts_with($item['link_url'], '#') ? 'arrow-down' : 'arrow-up-right') ?></a><?php endif; ?></div>
      </details>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
