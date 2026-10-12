<?php /* The notice before one of our other websites opens (assets/js/leave-site.js fills in the site and shows it). */
$leave = bl_editable('global')['switcher']['leave']; ?>
<dialog class="bl-dialog bl-leave-dialog" id="leave-dialog" aria-labelledby="leave-title" aria-describedby="leave-text">
  <button class="bl-dialog-close bl-circle-button" type="button" data-close-dialog aria-label="Close"><?= bl_icon('x') ?></button>
  <span class="bl-leave-check" aria-hidden="true"><svg viewBox="0 0 24 24"><path fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" d="m5.5 12.8 4.2 4.2 8.8-9.4"/></svg></span>
  <p class="bl-eyebrow"><?= bl_escape($leave['eyebrow']) ?></p>
  <h2 id="leave-title" data-leave-name></h2>
  <p class="bl-leave-verified"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10" fill="currentColor"/><path fill="none" stroke="#fff" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" d="m7.6 12.4 3 3 5.8-6.2"/></svg><span><b data-leave-domain></b> · <?= bl_escape($leave['verified']) ?></span></p>
  <p id="leave-text" class="bl-leave-text"><?= bl_escape($leave['text']) ?></p>
  <div class="bl-leave-actions">
    <a class="bl-leave-go" href="#" target="_blank" rel="noopener" data-leave-go><span><?= bl_escape($leave['go']) ?> <b data-leave-go-domain></b></span><?= bl_icon('arrow-up-right') ?></a>
    <button class="bl-leave-stay" type="button" data-close-dialog><?= bl_escape($leave['stay']) ?></button>
  </div>
</dialog>
