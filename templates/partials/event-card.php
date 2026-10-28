<?php
/* The home page's dated promotion above the company stories (网站内容 → 首页 → 展会卡片): only with a title and inside its dates. */
$event = $content['event'] ?? [];
if (trim((string) ($event['title'] ?? '')) === '' || !bl_notice_live((string) ($event['starts'] ?? ''), (string) ($event['ends'] ?? ''))) return;
$eventFacts = array_slice(array_values(array_filter((array) ($event['facts'] ?? []), static fn($fact) => is_array($fact) && trim((string) ($fact['value'] ?? '')) !== '')), 0, 3);
$eventImage = trim((string) ($event['image'] ?? '')) !== '' ? bl_content_image((string) $event['image'], []) : null;
?>
<article class="bl-event-card<?= $eventImage ? '' : ' bl-event-card-plain' ?>" aria-labelledby="event-card-title">
  <?php if ($eventImage): ?><div class="bl-event-card-media"><img src="<?= bl_escape($eventImage['src']) ?>"<?= $eventImage['srcset'] !== '' ? ' srcset="' . bl_escape($eventImage['srcset']) . '" sizes="(max-width: 800px) 100vw, 50vw"' : '' ?> alt="<?= bl_escape((string) ($event['alt'] ?? '')) ?>"<?= $eventImage['width'] ? ' width="' . $eventImage['width'] . '" height="' . $eventImage['height'] . '"' : '' ?> loading="lazy" decoding="async"></div><?php endif; ?>
  <div class="bl-event-card-body">
    <?php if (trim((string) ($event['eyebrow'] ?? '')) !== ''): ?><p class="bl-eyebrow"><?= bl_notice_text($event['eyebrow'] ?? '') ?></p><?php endif; ?>
    <h3 id="event-card-title"><?= bl_notice_text($event['title'] ?? '') ?></h3>
    <?php if (trim((string) ($event['text'] ?? '')) !== ''): ?><p class="bl-event-card-text"><?= bl_notice_text($event['text'] ?? '') ?></p><?php endif; ?>
    <?php if ($eventFacts): ?><dl class="bl-event-card-facts"><?php foreach ($eventFacts as $fact): ?><div><dt><?= bl_notice_text($fact['label'] ?? '') ?></dt><dd><?= bl_notice_text($fact['value']) ?></dd></div><?php endforeach; ?></dl><?php endif; ?>
    <?php if (trim((string) ($event['button'] ?? '')) !== '' && trim((string) ($event['url'] ?? '')) !== ''): ?><a class="bl-button bl-button-blue" href="<?= bl_escape(bl_application_link((string) $event['url'])) ?>"><?= bl_notice_text($event['button'] ?? '') ?> <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
  </div>
</article>
