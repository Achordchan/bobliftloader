<?php
/**
 * The cookie banner. It exists only when a tracking code is saved (inc/consent.php); assets/js/consent.js shows it to a
 * visitor who has not chosen yet, or on request from the footer, and loads each category's code once it is agreed to.
 */
$categories = bl_consent_categories();
if (!$categories) return;
$text = static fn(string $key): string => bl_content_text('global', 'consent.' . $key);
?>
<section class="bl-consent" role="dialog" aria-modal="false" aria-labelledby="bl-consent-title" data-bl-consent-banner data-categories="<?= bl_escape(implode(',', $categories)) ?>" hidden>
  <button type="button" class="bl-consent-close" data-bl-consent="close" aria-label="<?= $text('close') ?>" hidden><?= bl_icon('x') ?></button>
  <h2 id="bl-consent-title" tabindex="-1"><?= $text('title') ?></h2>
  <p class="bl-consent-text"><?= $text('text') ?> <a href="<?= bl_escape(bl_application_link(bl_content_value('global', 'footer.privacy_url'))) ?>"><?= $text('privacy') ?></a></p>
  <div class="bl-consent-choices" hidden>
    <?php foreach ($categories as $category): ?>
    <label class="bl-consent-option"><input type="checkbox" name="<?= bl_escape($category) ?>"><span><strong><?= $text($category . '_title') ?></strong><small><?= $text($category . '_text') ?></small></span></label>
    <?php endforeach; ?>
    <p class="bl-consent-note"><?= $text('note') ?></p>
  </div>
  <div class="bl-consent-actions">
    <button type="button" class="bl-consent-button" data-bl-consent="reject"><?= $text('reject') ?></button>
    <button type="button" class="bl-consent-button" data-bl-consent="accept"><?= $text('accept') ?></button>
    <button type="button" class="bl-consent-button" data-bl-consent="save" hidden><?= $text('save') ?></button>
    <?php if (count($categories) > 1): ?><button type="button" class="bl-consent-choose" data-bl-consent="choose" aria-expanded="false"><?= $text('choose') ?></button><?php endif; ?>
  </div>
</section>
