<?php
/* The home page's year-end offers band under the hero (网站内容 → 年终活动 → 首页横幅): only while the offers run (inc/promo.php). */
if (!bl_promo_live()) return;
$promoBanner = bl_editable('promo')['banner'];
if (trim((string) $promoBanner['title']) === '') return;
?>
<aside class="bl-promo-banner" aria-label="<?= bl_escape(bl_editable('promo')['hero']['breadcrumb']) ?>">
  <a class="bl-promo-banner-inner bl-shell" href="<?= bl_escape(bl_url(BL_PROMO_PATH)) ?>">
    <span class="bl-promo-banner-copy">
      <?php if (trim((string) $promoBanner['eyebrow']) !== ''): ?><span class="bl-eyebrow"><?= bl_escape($promoBanner['eyebrow']) ?></span><?php endif; ?>
      <strong><?= bl_escape($promoBanner['title']) ?></strong>
      <?php if (trim((string) $promoBanner['text']) !== ''): ?><span class="bl-promo-banner-text"><?= bl_escape($promoBanner['text']) ?></span><?php endif; ?>
    </span>
    <span class="bl-button bl-button-blue"><?= bl_escape($promoBanner['button']) ?> <?= bl_icon('arrow-up-right') ?></span>
  </a>
</aside>
