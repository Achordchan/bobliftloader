<?php
/**
 * News: one list, newest first, with a tab per section. A section tab appears once it has a story; stories from the
 * wider BOB-LIFT group belong to no section and are listed under All only.
 */
$allArticles = bl_articles();
$counts = array_count_values(array_filter(array_map('bl_news_category', $allArticles)));
$current = isset($counts[bl_query('category')]) ? bl_query('category') : '';
$articles = $current ? array_values(array_filter($allArticles, static fn($a) => bl_news_category($a) === $current)) : $allArticles;
?>
<section class="bl-news-head bl-shell" aria-labelledby="news-title">
  <?php bl_breadcrumbs($current ? [['label' => 'News', 'url' => bl_url('/news/')], ['label' => BL_NEWS_CATEGORIES[$current]]] : [['label' => 'News']]); ?>
  <div class="bl-news-head-row">
    <h1 id="news-title"><?= $current ? bl_escape(BL_NEWS_CATEGORIES[$current]) : 'News' ?></h1>
    <p>Product news, customer stories and industry news from the BOB-LIFT Loader team.</p>
  </div>
  <nav class="bl-category-nav bl-news-tabs" aria-label="News sections">
    <a href="<?= bl_escape(bl_url('/news/')) ?>"<?= $current ? '' : ' aria-current="page"' ?>>All <small><?= count($allArticles) ?></small></a>
    <?php foreach (BL_NEWS_CATEGORIES as $key => $label): if (empty($counts[$key])) continue; ?><a href="<?= bl_escape(bl_url('/news/') . '?category=' . $key) ?>"<?= $current === $key ? ' aria-current="page"' : '' ?>><?= bl_escape($label) ?> <small><?= $counts[$key] ?></small></a><?php endforeach; ?>
  </nav>
</section>

<section class="bl-news-list-section bl-shell" aria-label="<?= $current ? bl_escape(BL_NEWS_CATEGORIES[$current]) : 'All stories' ?>">
  <?php if ($articles): ?>
  <ol class="bl-news-list">
    <?php foreach ($articles as $i => $article): $event = $article['event'] ?? []; $status = !empty($event['start']) ? bl_event_status($event) : null; ?>
    <li><a class="bl-news-item" href="<?= bl_escape(bl_article_url($article)) ?>">
      <?php // On phones a picture near 3:2 fills a frame of its own shape, so nothing is cropped; one far from it (a square
      // poster, wide artwork) or of unknown size sits whole in the 3:2 frame.
      $ratio = bl_news_image_ratio($article); $whole = !$ratio || $ratio < 1.3 || $ratio > 1.8; ?>
      <span class="bl-news-item-image<?= $whole ? ' bl-news-item-image-whole' : '' ?>"<?= !$whole && abs($ratio - 1.5) > .02 ? ' style="--bl-news-ratio:' . round($ratio, 3) . '"' : '' ?>><img src="<?= bl_escape($article['image']) ?>" alt="" width="900" height="600"<?= $i ? ' loading="lazy"' : '' ?>></span>
      <span class="bl-news-item-body">
        <span class="bl-news-item-meta"><span class="bl-news-tag<?= bl_news_category($article) ? '' : ' bl-news-tag-group' ?>"><?= bl_escape(bl_news_label($article)) ?></span><time datetime="<?= bl_escape($article['date']) ?>"><?= date('j M Y', strtotime($article['date'])) ?></time></span>
        <span class="bl-news-item-title"><?= bl_title_text($article['title']) ?></span>
        <span class="bl-news-item-excerpt"><?= bl_escape($article['excerpt']) ?></span>
        <?php if ($status): ?><span class="bl-news-item-event"><span><?= bl_escape(bl_event_dates($event)) ?></span><?php if (!empty($event['booth'])): ?><span>Booth <?= bl_escape($event['booth']) ?></span><?php endif; ?><span class="bl-news-when bl-news-when-<?= $status['state'] ?>"><?= bl_escape($status['label']) ?></span></span><?php endif; ?>
      </span>
    </a></li>
    <?php endforeach; ?>
  </ol>
  <?php else: ?>
  <div class="bl-empty"><h2>No stories yet.</h2><p>News from the BOB-LIFT Loader team will appear here.</p></div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
