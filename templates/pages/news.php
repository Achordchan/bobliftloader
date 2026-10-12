<?php
/**
 * News: one list, pinned stories first and then newest first, with a tab per section and a filter per product family.
 * A section tab appears once it has a story; stories from the wider BOB-LIFT group belong to no section and are listed
 * under All only. Each story is filed under the product families of the products it names (else company news). The
 * layout switch (list or grid) works in the browser (assets/js/news-view.js) and is remembered there.
 */
$allArticles = bl_news_order(bl_articles());
$currentProduct = isset(BL_NEWS_PRODUCT_FILTERS[bl_query('product')]) ? bl_query('product') : '';
$inFamily = static fn(array $list, string $family): array => $family === '' ? $list : array_values(array_filter($list, static fn($a) => in_array($family, bl_news_families($a), true)));
$pool = $inFamily($allArticles, $currentProduct);
$counts = array_count_values(array_filter(array_map('bl_news_category', $pool)));
$current = isset($counts[bl_query('category')]) ? bl_query('category') : '';
$articles = $current ? array_values(array_filter($pool, static fn($a) => bl_news_category($a) === $current)) : $pool;
// The product chips count the stories in the chosen section; a chip with none is left out, and the row is not shown when fewer than two chips have stories.
$sectionStories = $current ? array_values(array_filter($allArticles, static fn($a) => bl_news_category($a) === $current)) : $allArticles;
$familyCounts = [];
foreach (array_keys(BL_NEWS_PRODUCT_FILTERS) as $family) $familyCounts[$family] = count($inFamily($sectionStories, $family));
$showFamilies = count(array_filter($familyCounts)) >= 2 || $currentProduct !== '';
$newsUrl = static fn(string $category, string $product): string => bl_url('/news/') . ($category !== '' || $product !== '' ? '?' . http_build_query(array_filter(['category' => $category, 'product' => $product])) : '');
?>
<section class="bl-news-head bl-shell" aria-labelledby="news-title">
  <?php bl_breadcrumbs($current ? [['label' => 'News', 'url' => bl_url('/news/')], ['label' => BL_NEWS_CATEGORIES[$current]]] : [['label' => 'News']]); ?>
  <div class="bl-news-head-row">
    <h1 id="news-title"><?= $current ? bl_escape(BL_NEWS_CATEGORIES[$current]) : bl_content_text('news', 'hero.title') ?></h1>
    <p><?= bl_content_text('news', 'hero.intro') ?></p>
  </div>
  <nav class="bl-category-nav bl-news-tabs" aria-label="News sections">
    <a href="<?= bl_escape($newsUrl('', $currentProduct)) ?>"<?= $current ? '' : ' aria-current="page"' ?>>All <small><?= count($pool) ?></small></a>
    <?php foreach (BL_NEWS_CATEGORIES as $key => $label): if (empty($counts[$key])) continue; ?><a href="<?= bl_escape($newsUrl($key, $currentProduct)) ?>"<?= $current === $key ? ' aria-current="page"' : '' ?>><?= bl_escape($label) ?> <small><?= $counts[$key] ?></small></a><?php endforeach; ?>
  </nav>
  <div class="bl-news-tools">
    <?php if ($showFamilies): ?>
    <nav class="bl-news-products" aria-label="Filter by product">
      <span class="bl-news-tools-label">Product</span>
      <a href="<?= bl_escape($newsUrl($current, '')) ?>"<?= $currentProduct === '' ? ' aria-current="page"' : '' ?>>All</a>
      <?php foreach (BL_NEWS_PRODUCT_FILTERS as $family => $label): if (empty($familyCounts[$family]) && $currentProduct !== $family) continue; ?><a href="<?= bl_escape($newsUrl($current, $family)) ?>"<?= $currentProduct === $family ? ' aria-current="page"' : '' ?>><?= bl_escape($label) ?> <small><?= $familyCounts[$family] ?></small></a><?php endforeach; ?>
    </nav>
    <?php endif; ?>
    <div class="bl-news-view" role="group" aria-label="Layout" data-bl-news-view hidden>
      <button type="button" data-view="list" aria-pressed="true"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 5h4v4H3zm6 1h12v2H9zM3 10h4v4H3zm6 1h12v2H9zM3 15h4v4H3zm6 1h12v2H9z"/></svg><span>List</span></button>
      <button type="button" data-view="grid" aria-pressed="false"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true" focusable="false"><path fill="currentColor" d="M3 3h8v8H3zm10 0h8v8h-8zM3 13h8v8H3zm10 0h8v8h-8z"/></svg><span>Grid</span></button>
    </div>
  </div>
</section>

<section class="bl-news-list-section bl-shell" aria-label="<?= $current ? bl_escape(BL_NEWS_CATEGORIES[$current]) : 'All stories' ?>">
  <?php if ($articles): ?>
  <ol class="bl-news-list" data-view="list">
    <?php foreach ($articles as $i => $article): $event = $article['event'] ?? []; $status = !empty($event['start']) ? bl_event_status($event) : null; ?>
    <li><a class="bl-news-item" href="<?= bl_escape(bl_article_url($article)) ?>">
      <?php // On phones a picture near 3:2 fills a frame of its own shape, so nothing is cropped; one far from it (a square
      // poster, wide artwork) or of unknown size sits whole in the 3:2 frame.
      $ratio = bl_news_image_ratio($article); $whole = !$ratio || $ratio < 1.3 || $ratio > 1.8; ?>
      <span class="bl-news-item-image<?= $whole ? ' bl-news-item-image-whole' : '' ?>"<?= !$whole && abs($ratio - 1.5) > .02 ? ' style="--bl-news-ratio:' . round($ratio, 3) . '"' : '' ?>><img src="<?= bl_escape($article['image']) ?>" alt="" width="900" height="600"<?= $i ? ' loading="lazy"' : '' ?>></span>
      <span class="bl-news-item-body">
        <span class="bl-news-item-meta"><?php if (!empty($article['pinned'])): ?><span class="bl-news-pin">Pinned</span><?php endif; ?><span class="bl-news-tag<?= bl_news_category($article) ? '' : ' bl-news-tag-group' ?>"><?= bl_escape(bl_news_label($article)) ?></span><time datetime="<?= bl_escape($article['date']) ?>"><?= date('j M Y', strtotime($article['date'])) ?></time></span>
        <span class="bl-news-item-title"><?= bl_title_text($article['title']) ?></span>
        <span class="bl-news-item-excerpt"><?= bl_escape($article['excerpt']) ?></span>
        <?php if ($status): ?><span class="bl-news-item-event"><span><?= bl_escape(bl_event_dates($event)) ?></span><?php if (!empty($event['booth'])): ?><span>Booth <?= bl_escape($event['booth']) ?></span><?php endif; ?><span class="bl-news-when bl-news-when-<?= $status['state'] ?>"><?= bl_escape($status['label']) ?></span></span><?php endif; ?>
      </span>
    </a></li>
    <?php endforeach; ?>
  </ol>
  <?php else: ?>
  <div class="bl-empty"><h2><?= bl_content_text('news', 'hero.empty_title') ?></h2><p><?= bl_content_text('news', 'hero.empty_text') ?></p></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
