<?php
/**
 * One story. Head: breadcrumbs, section link, title, lede. Main column: lead image and text, with any stand artwork set
 * into the text. Side column, always in this order and only when there is something to show: who wrote it, event
 * details, related products.
 */
$article = $route['record'];
$category = bl_news_category($article);
$event = $article['event'] ?? [];
$status = !empty($event['start']) ? bl_event_status($event) : null;
$poster = !empty($article['poster']['image']) ? $article['poster'] : null;
$posterAfter = $poster ? (int) ($poster['after'] ?? 0) : 0;
$storyProducts = array_values(array_filter(array_map(static fn($slug) => bl_find_record(bl_products(), $slug), $article['products'] ?? [])));
// Stories imported from the JSON data have no WordPress author: this site's are signed by the team, group stories by BOB-LIFT.
$author = !empty($article['author']['name']) ? $article['author'] : ['name' => 'BOB-LIFT', 'title' => $category ? 'Compact equipment team' : '', 'avatar' => ''];
$all = bl_articles();
$index = (int) array_search($article['slug'], array_column($all, 'slug'), true);
$newer = $all[$index - 1] ?? null;
$older = $all[$index + 1] ?? null;
$posterFigure = static fn(array $poster): string => '<figure class="bl-story-poster"><img src="' . bl_escape($poster['image']) . '" alt="' . bl_escape($poster['alt']) . '" width="889" height="2000" loading="lazy"><figcaption>' . bl_escape($poster['caption']) . '</figcaption></figure>';
?>
<article class="bl-story bl-shell" aria-labelledby="story-title">
  <?php bl_breadcrumbs([['label' => 'News', 'url' => bl_url('/news/')], ['label' => $article['title']]]); ?>
  <header class="bl-story-head">
    <?php if ($category): ?><a class="bl-story-category" href="<?= bl_escape(bl_url('/news/') . '?category=' . $category) ?>"><?= bl_escape(BL_NEWS_CATEGORIES[$category]) ?></a><?php else: ?><span class="bl-story-category bl-story-category-group"><?= bl_escape(bl_news_label($article)) ?></span><?php endif; ?>
    <h1 id="story-title"><?= bl_escape($article['title']) ?></h1>
    <p class="bl-story-lede"><?= bl_escape($article['excerpt']) ?></p>
  </header>

  <div class="bl-story-layout">
    <div class="bl-story-main">
      <figure class="bl-story-figure"><img src="<?= bl_escape($article['image']) ?>" alt="<?= bl_escape($article['caption']) ?>" width="1500" height="900" fetchpriority="high"><figcaption><?= bl_escape($article['caption']) ?></figcaption></figure>
      <div class="bl-story-text">
        <?php if (!empty($article['content_html'])): ?><?= $poster ? $posterFigure($poster) : '' ?><div class="bl-wp-article-content"><?= wp_kses_post($article['content_html']) ?></div>
        <?php else: foreach ($article['sections'] as $i => $section): ?><?= $poster && $i + 1 === $posterAfter ? $posterFigure($poster) : '' ?><h2><?= bl_escape($section['heading']) ?></h2><p><?= bl_escape($section['text']) ?></p><?php endforeach; ?><?= $poster && ($posterAfter < 1 || $posterAfter > count($article['sections'])) ? $posterFigure($poster) : '' ?><?php endif; ?>
        <?php if ($status && $status['state'] !== 'past'): ?><p class="bl-story-visit">Planning to visit? <a href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>">Tell us your dates</a> or <a href="https://wa.me/8615852499415" target="_blank" rel="noopener">message us on WhatsApp</a>, so the compact equipment team can expect you.</p><?php endif; ?>
      </div>
    </div>

    <aside class="bl-story-aside" aria-label="About this story">
      <section class="bl-story-box bl-story-author">
        <h2>Written by</h2>
        <div class="bl-story-author-name">
          <?php if ($author['avatar']): ?><img src="<?= bl_escape($author['avatar']) ?>" alt="" width="64" height="64"><?php else: ?><span class="bl-story-avatar" aria-hidden="true"><?= bl_escape(mb_substr($author['name'], 0, 1)) ?></span><?php endif; ?>
          <span><b><?= bl_escape($author['name']) ?></b><?php if ($author['title']): ?><small><?= bl_escape($author['title']) ?></small><?php endif; ?></span>
        </div>
        <dl>
          <div><dt>Published</dt><dd><time datetime="<?= bl_escape($article['date']) ?>"><?= date('j F Y', strtotime($article['date'])) ?></time></dd></div>
          <?php if (!empty($article['updated'])): ?><div><dt>Updated</dt><dd><time datetime="<?= bl_escape($article['updated']) ?>"><?= date('j F Y', strtotime($article['updated'])) ?></time></dd></div><?php endif; ?>
        </dl>
        <?php if ($article['source']): ?><a class="bl-story-box-link" href="<?= bl_escape($article['source']) ?>" target="_blank" rel="noopener">Original announcement on boblift.com <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
      </section>

      <?php if ($status): ?>
      <section class="bl-story-box">
        <h2>Event details</h2>
        <dl>
          <div><dt>Event</dt><dd><?= bl_escape($event['name']) ?></dd></div>
          <div><dt>Dates</dt><dd><?= bl_escape(bl_event_dates($event)) ?> <span class="bl-news-when bl-news-when-<?= $status['state'] ?>"><?= bl_escape($status['label']) ?></span></dd></div>
          <?php if (!empty($event['venue'])): ?><div><dt>Venue</dt><dd><?= bl_escape($event['venue']) ?><?php if (!empty($event['address'])): ?><small><?= bl_escape($event['address']) ?></small><?php endif; ?></dd></div><?php endif; ?>
          <?php if (!empty($event['booth'])): ?><div><dt>Booth</dt><dd><strong><?= bl_escape($event['booth']) ?></strong><?php if (!empty($event['booth_note'])): ?><small><?= bl_escape($event['booth_note']) ?></small><?php endif; ?></dd></div><?php endif; ?>
        </dl>
        <?php if (!empty($event['source'])): ?><a class="bl-story-box-link" href="<?= bl_escape($event['source']) ?>" target="_blank" rel="noopener"><?= bl_escape($event['source_label'] ?: 'Event source') ?> <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
      </section>
      <?php endif; ?>

      <?php if ($storyProducts): ?>
      <section class="bl-story-box">
        <h2>Related products</h2>
        <ul class="bl-story-product-list">
          <?php foreach ($storyProducts as $product): $key = bl_key_specs($product); $first = $key ? array_key_first($key) : ''; ?>
          <li><a href="<?= bl_escape(bl_product_url($product)) ?>">
            <span class="bl-story-product-image"><?php if ($product['image']): ?><img src="<?= bl_escape($product['image']) ?>" alt="" width="800" height="650" loading="lazy"><?php endif; ?></span>
            <span><b><?= bl_escape($product['model']) ?></b><small><?= bl_escape(trim(preg_replace('/^' . preg_quote($product['model'], '/') . '\s*/', '', $product['name']))) ?></small><?php if ($first): ?><small><?= bl_escape($first) ?> <?= bl_escape($key[$first]) ?></small><?php endif; ?></span>
          </a></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>
    </aside>
  </div>
</article>

<nav class="bl-story-pager bl-shell" aria-label="More stories">
  <?php if ($newer): ?><a href="<?= bl_escape(bl_article_url($newer)) ?>"><span><?= bl_icon('arrow-left') ?> Newer</span><b><?= bl_escape($newer['title']) ?></b></a><?php else: ?><a href="<?= bl_escape(bl_url('/news/')) ?>"><span><?= bl_icon('arrow-left') ?> All news</span><b>Back to the list</b></a><?php endif; ?>
  <?php if ($older): ?><a class="bl-story-pager-older" href="<?= bl_escape(bl_article_url($older)) ?>"><span>Older <?= bl_icon('arrow-up-right') ?></span><b><?= bl_escape($older['title']) ?></b></a><?php endif; ?>
</nav>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
