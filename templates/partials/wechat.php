<?php /* The WeChat QR code, opened by any [data-wechat] link (assets/js/wechat.js); without the script the link opens the image itself. */
if (bl_company('wechat_id') === '') return; ?>
<dialog class="bl-dialog bl-wechat-dialog" id="wechat-dialog" aria-labelledby="wechat-title" aria-describedby="wechat-text">
  <button class="bl-dialog-close bl-circle-button" type="button" data-close-dialog aria-label="Close"><?= bl_icon('x') ?></button>
  <span class="bl-wechat-mark"><?= bl_wechat_icon() ?></span>
  <h2 id="wechat-title"><?= bl_content_text('global', 'side.wechat_title') ?></h2>
  <p id="wechat-text" class="bl-wechat-text"><?= bl_content_text('global', 'side.wechat_text') ?></p>
  <img class="bl-wechat-qr" src="<?= bl_escape(bl_wechat_qr()) ?>" alt="BOB-LIFT WeChat QR code" width="543" height="542" loading="lazy" decoding="async">
  <p class="bl-wechat-id"><small>WeChat ID</small><b><?= bl_escape(bl_company('wechat_id')) ?></b></p>
</dialog>
