<?php
$thanks = bl_editable('thanks');
$catalogue = bl_catalogue();
$more = array_filter([
    ['https://wa.me/8615852499415', $thanks['more']['whatsapp'], 'WhatsApp', '+86 158 5249 9415', '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.5l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.5 4 5.2 5.2 0 0 0 3.2.7 2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>'],
    [$catalogue['url'], $thanks['more']['catalogue'], 'PDF' . ($catalogue['size'] ? ' · ' . $catalogue['size'] : ''), 'Catalogue', '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M6 3h9l4 4v14H6z"/><path fill="none" stroke="currentColor" stroke-width="1.6" d="M14 3v5h5M12.5 11v6m-3-3 3 3 3-3"/></svg>'],
    [bl_url('/products/'), $thanks['more']['range'], 'Loaders and dumpers', 'All models', '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.6" d="M3.5 3.5h7v7h-7zm10 0h7v7h-7zm-10 10h7v7h-7zm10 0h7v7h-7z"/></svg>'],
], static fn($link) => trim($link[1]) !== '' && $link[0] !== '');
?>
<section class="bl-thanks bl-shell">
  <div class="bl-thanks-grid">
    <div class="bl-thanks-intro">
      <p class="bl-eyebrow"><?= bl_escape($thanks['hero']['eyebrow']) ?></p>
      <h1><?= str_replace('</span><span', '</span> <span', bl_heading_lines($thanks['hero']['title'])) /* a space between the lines for screen readers and copying */ ?></h1>
      <p class="bl-thanks-lede"><?= bl_escape($thanks['hero']['text']) ?></p>
      <?php if ($more): ?><p class="bl-eyebrow bl-thanks-more"><?= bl_escape($thanks['more']['eyebrow']) ?></p>
      <ul class="bl-contact-channels" aria-label="<?= bl_escape(ucfirst(strtolower($thanks['more']['eyebrow']))) ?>">
        <?php foreach ($more as [$href, $label, $small, $big, $svg]): $external = str_starts_with($href, 'https://wa.me/'); ?><li><a href="<?= bl_escape($href) ?>"<?= $external ? ' target="_blank" rel="noopener"' : '' ?>><?= $svg ?><span><small><?= bl_escape($label) ?></small><b><?= bl_escape($big) ?></b></span><?= bl_icon(str_ends_with(strtolower((string) strtok($href, '?#')), '.pdf') ? 'download-simple' : 'arrow-up-right') ?></a></li>
        <?php endforeach; ?>
      </ul><?php endif; ?>
    </div>
    <div class="bl-thanks-next">
      <p class="bl-eyebrow"><?= bl_escape($thanks['next']['eyebrow']) ?></p>
      <h2><?= bl_heading($thanks['next']['title']) ?></h2>
      <ol>
        <?php foreach ($thanks['next']['items'] as $i => $item): ?><li><span><?= sprintf('%02d', $i + 1) ?></span><div><h3><?= bl_escape($item['title']) ?></h3><p><?= bl_escape($item['text']) ?></p></div></li><?php endforeach; ?>
      </ol>
    </div>
  </div>
</section>
