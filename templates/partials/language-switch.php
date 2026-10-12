<?php /* The language switch in the header (inc/languages.php). English is the site itself; other languages appear as their folders go live. */
$barSwitch = !empty($barSwitch); // the copy in the phone bar (the one in the navigation steps aside from 600px down)
$languages = bl_languages(); $current = $languages[0]; $available = count(array_filter($languages, static fn($language) => $language['url'] !== '')); ?>
<details class="bl-lang-switch<?= $available < 2 ? ' bl-lang-switch-single' : '' ?><?= $barSwitch ? ' bl-lang-switch-bar' : '' ?>">
  <summary aria-label="Language: <?= bl_escape($current['name']) ?>. Choose another language"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9" fill="none" stroke="currentColor" stroke-width="1.5"/><path fill="none" stroke="currentColor" stroke-width="1.5" d="M3 12h18M12 3c2.5 2.6 3.8 5.6 3.8 9s-1.3 6.4-3.8 9c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3Z"/></svg><span><?= bl_escape(strtoupper($current['code'])) ?></span><i aria-hidden="true"></i></summary>
  <div class="bl-lang-panel">
    <p>Language</p>
    <ul>
      <?php foreach ($languages as $i => $language): $code = strtoupper($language['code']); ?>
      <li lang="<?= bl_escape($language['code']) ?>"><?php if ($i === 0): ?><span class="bl-lang-current" aria-current="true"><b><?= bl_escape($language['name']) ?></b><small><?= bl_escape($code) ?></small></span><?php elseif ($language['url'] !== ''): ?><a href="<?= bl_escape(str_starts_with($language['url'], '/') ? bl_url($language['url']) : $language['url']) ?>" hreflang="<?= bl_escape($language['code']) ?>"><b><?= bl_escape($language['name']) ?></b><small><?= bl_escape($code) ?></small></a><?php else: ?><span class="bl-lang-soon"><b><?= bl_escape($language['name']) ?></b><small>Soon</small></span><?php endif; ?></li>
      <?php endforeach; ?>
    </ul>
    <?php if ($available < 2): ?><p class="bl-lang-note">More languages are on the way.</p><?php endif; ?>
  </div>
</details>
