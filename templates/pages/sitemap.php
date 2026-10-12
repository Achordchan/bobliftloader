<?php
/** The sitemap for people: every public page in one place. The lists come from the same records as /sitemap.xml, so they never fall behind. */
$text = static fn(string $key): string => bl_content_text('global', 'sitemap.' . $key);
$products = bl_products();
$byFamily = static fn(string $family): array => array_values(array_filter($products, static fn($p) => ($p['category'] ?? '') === $family));
$pages = [
    ['label' => bl_content_value('global', 'sitemap.home_label'), 'url' => '/'],
    ['label' => bl_content_value('global', 'footer.loaders_label'), 'url' => '/products/skid-steer-loaders/'],
    ['label' => bl_content_value('global', 'footer.dumpers_label'), 'url' => '/products/mini-dumpers/'],
    ['label' => bl_content_value('global', 'footer.applications_label'), 'url' => '/applications/'],
    ['label' => bl_content_value('global', 'footer.support_label'), 'url' => '/support/'],
    ['label' => bl_content_value('global', 'footer.about_label'), 'url' => '/about/'],
    ['label' => bl_content_value('global', 'footer.news_label'), 'url' => '/news/'],
    ['label' => bl_content_value('global', 'footer.contact_label'), 'url' => '/contact/'],
    ['label' => bl_content_value('global', 'footer.privacy_label'), 'url' => '/privacy/'],
];
$groups = [
    ['title' => $text('loaders_title'), 'items' => array_map(static fn($p) => ['label' => $p['name'], 'url' => bl_url('/product/' . $p['slug'] . '/')], $byFamily('skid-steer-loaders'))],
    ['title' => $text('dumpers_title'), 'items' => array_map(static fn($p) => ['label' => $p['name'], 'url' => bl_url('/product/' . $p['slug'] . '/')], $byFamily('mini-dumpers'))],
    ['title' => $text('applications_title'), 'items' => array_map(static fn($a) => ['label' => $a['title'], 'url' => bl_url('/applications/' . bl_application_slug($a) . '/')], array_values($applications ?? []))],
    ['title' => $text('cases_title'), 'items' => array_map(static fn($c) => ['label' => $c['title'], 'url' => bl_case_url($c)], bl_application_cases())],
    ['title' => $text('news_title'), 'items' => array_map(static fn($a) => ['label' => $a['title'], 'url' => bl_url('/news/' . $a['slug'] . '/')], bl_news_order(bl_articles()))],
];
?>
<section class="bl-page-hero bl-shell"><?php bl_breadcrumbs([['label' => bl_content_value('global', 'sitemap.breadcrumb')]]); ?><div class="bl-page-heading"><div><p class="bl-eyebrow"><?= $text('eyebrow') ?></p><h1><?= $text('title') ?></h1></div></div></section>
<div class="bl-sitemap bl-shell">
  <p class="bl-sitemap-intro"><?= $text('intro') ?></p>
  <div class="bl-sitemap-grid">
    <nav class="bl-sitemap-group" aria-labelledby="sitemap-pages">
      <h2 id="sitemap-pages"><?= $text('pages_title') ?></h2>
      <ul>
        <?php foreach ($pages as $page): ?><li><a href="<?= bl_escape(bl_application_link($page['url'])) ?>"><?= bl_escape(html_entity_decode($page['label'], ENT_QUOTES)) ?></a></li><?php endforeach; ?>
        <?php if (bl_indexable()): ?><li><a href="<?= bl_escape(bl_url('/sitemap.xml')) ?>"><?= $text('xml_label') ?></a></li><?php endif; ?>
      </ul>
    </nav>
    <?php foreach ($groups as $i => $group): if (!$group['items']) continue; ?>
    <nav class="bl-sitemap-group" aria-labelledby="sitemap-group-<?= $i ?>">
      <h2 id="sitemap-group-<?= $i ?>"><?= $group['title'] ?></h2>
      <ul>
        <?php foreach ($group['items'] as $item): ?><li><a href="<?= bl_escape($item['url']) ?>"><?= bl_escape(html_entity_decode((string) $item['label'], ENT_QUOTES)) ?></a></li><?php endforeach; ?>
      </ul>
    </nav>
    <?php endforeach; ?>
  </div>
</div>
