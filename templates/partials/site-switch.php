<?php /* Which BOB-LIFT website this is, and a switch to the other two (same tab: it is a change of site, not a reference). Used by the header (opens down) and the footer (opens up). */ ?>
<details class="bl-site-switch">
  <summary aria-label="Loader website: switch to another BOB-LIFT website"><span><?= bl_escape(bl_content_value('global', 'switcher.label')) ?></span><i aria-hidden="true"></i></summary>
  <div class="bl-site-panel">
    <p><?= bl_escape(bl_content_value('global', 'switcher.title')) ?></p>
    <ul>
      <?php foreach (bl_site_switch_items() as $site): ['url' => $url, 'domain' => $domain, 'name' => $name, 'text' => $text, 'mark' => $mark] = $site; ?>
      <li><?php if ($url): ?><a href="<?= bl_escape(bl_application_link($url)) ?>"><?php else: ?><span class="bl-site-current" aria-current="true"><?php endif; ?>
        <i aria-hidden="true"><?= bl_escape($mark) ?></i>
        <span><small><?= bl_escape($domain) ?></small><b><?= bl_escape($name) ?></b><span><?= bl_escape($text) ?></span></span>
        <?= $url ? bl_icon('arrow-up-right') : '<em>' . bl_escape(bl_content_value('global', 'switcher.current')) . '</em>' ?>
      <?= $url ? '</a>' : '</span>' ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</details>
