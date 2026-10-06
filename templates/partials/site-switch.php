<?php /* Which BOB-LIFT website this is, and a switch to the other two (same tab: it is a change of site, not a reference). Used by the header (opens down) and the footer (opens up). */ ?>
<details class="bl-site-switch">
  <summary aria-label="Loader website: switch to another BOB-LIFT website"><span>LOADER</span><i aria-hidden="true"></i></summary>
  <div class="bl-site-panel">
    <p>BOB-LIFT websites</p>
    <ul>
      <?php foreach ([['https://www.boblift.com/', 'boblift.com', 'BOB-LIFT', 'The company, its factory and the full range', 'B'], ['https://www.bobliftcrane.com/', 'bobliftcrane.com', 'Cranes', 'Truck-mounted, marine and pickup cranes', 'C'], ['', 'bobliftloader.com', 'Loader', 'Mini skid steer loaders and mini dumpers', 'L']] as [$url, $domain, $name, $text, $mark]): ?>
      <li><?php if ($url): ?><a href="<?= $url ?>"><?php else: ?><span class="bl-site-current" aria-current="true"><?php endif; ?>
        <i aria-hidden="true"><?= $mark ?></i>
        <span><small><?= $domain ?></small><b><?= $name ?></b><span><?= $text ?></span></span>
        <?= $url ? bl_icon('arrow-up-right') : '<em>You are here</em>' ?>
      <?= $url ? '</a>' : '</span>' ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
</details>
