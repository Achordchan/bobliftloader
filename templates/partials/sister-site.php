<?php
/**
 * Our crane website, at the foot of a family page after its guides: a quiet card for buyers who came for a crane, not
 * a banner between the machines. The button and the pictures open through the leave-site notice (assets/js/leave-site.js),
 * as the header's site switch does. Words in WordPress (全站设置 → 起重机站推荐); an empty address leaves it out.
 */
$sister = bl_sister_site();
if (!$sister) return;
$link = 'href="' . bl_escape($sister['url']) . '" data-leave-site data-name="' . bl_escape($sister['name']) . '" data-domain="' . bl_escape($sister['domain']) . '" data-mark="' . bl_escape($sister['mark']) . '"';
?>
<section class="bl-sister bl-shell" aria-labelledby="sister-title">
  <div class="bl-sister-card bl-reveal">
    <div class="bl-sister-copy">
      <p class="bl-eyebrow"><?= bl_escape(bl_content_value('global', 'sister.eyebrow')) ?></p>
      <h2 id="sister-title"><?= bl_escape(bl_content_value('global', 'sister.title')) ?></h2>
      <p><?= bl_escape(bl_content_value('global', 'sister.text')) ?></p>
      <a class="bl-button bl-sister-button" <?= $link ?>><?= bl_escape(bl_content_value('global', 'sister.button')) ?> <?= bl_icon('arrow-up-right') ?></a>
    </div>
    <a class="bl-sister-media" <?= $link ?> tabindex="-1" aria-hidden="true">
      <img src="<?= bl_escape(bl_asset('images/sister/truck-mounted-crane.webp')) ?>" alt="" width="581" height="434" loading="lazy">
      <img src="<?= bl_escape(bl_asset('images/sister/marine-crane.webp')) ?>" alt="" width="560" height="507" loading="lazy">
    </a>
  </div>
</section>
