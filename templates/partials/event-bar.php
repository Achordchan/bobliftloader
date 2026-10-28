<?php
/* The dated notice above the header (全站设置 → 顶部公告条): only with words in it and inside its dates. It scrolls away with the page; the header below stays. */
$eventBar = bl_editable('global')['event_bar'] ?? [];
if (trim((string) ($eventBar['text'] ?? '')) === '' || !bl_notice_live((string) ($eventBar['starts'] ?? ''), (string) ($eventBar['ends'] ?? ''))) return;
$eventShort = trim((string) ($eventBar['short'] ?? ''));
?>
<div class="bl-event-bar" role="region" aria-label="<?= bl_escape(trim((string) ($eventBar['badge'] ?? '')) ?: 'Announcement') ?>">
  <?php if (trim((string) ($eventBar['badge'] ?? '')) !== ''): ?><span class="bl-event-bar-badge"><?= bl_notice_text($eventBar['badge'] ?? '') ?></span><?php endif; ?>
  <span class="bl-event-bar-text<?= $eventShort !== '' ? ' bl-event-bar-long' : '' ?>"><?= bl_notice_text($eventBar['text'] ?? '') ?></span>
  <?php if ($eventShort !== ''): ?><span class="bl-event-bar-text bl-event-bar-short"><?= bl_notice_text($eventBar['short'] ?? '') ?></span><?php endif; ?>
  <?php if (trim((string) ($eventBar['link'] ?? '')) !== '' && trim((string) ($eventBar['url'] ?? '')) !== ''): ?><a class="bl-event-bar-link" href="<?= bl_escape(bl_application_link((string) $eventBar['url'])) ?>"><?= bl_notice_text($eventBar['link'] ?? '') ?></a><?php endif; ?>
</div>
