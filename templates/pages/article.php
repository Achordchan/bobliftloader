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
$author = (!empty($article['author']['name']) ? $article['author'] : ['name' => 'BOB-LIFT', 'title' => $category ? 'BOB-LIFT Loader team' : '', 'avatar' => '']) + ['bio' => ''];
$all = bl_articles();
$index = (int) array_search($article['slug'], array_column($all, 'slug'), true);
$newer = $all[$index - 1] ?? null;
$older = $all[$index + 1] ?? null;
// An editor can mark a cover as cut from the story's stand artwork ('hide_cover'): the story then leads with the artwork
// alone, since showing both repeats the picture. A featured image chosen in WordPress is a picture of its own and still leads.
$leadFigure = !$poster || empty($article['hide_cover']) || !empty($article['image_id']);
// The lead picture and the stand artwork open larger in the lightbox (assets/js/lightbox.js), lead picture first. The
// button over each picture stays hidden until the script runs, so without it the pictures are plain images.
$zoom = static fn(int $index, string $src, string $alt, string $caption): string => '<button class="bl-story-zoom" type="button" data-lightbox-open="' . $index . '" hidden>' . bl_icon('magnifying-glass') . '<span class="bl-visually-hidden">View larger</span></button><span hidden data-lightbox-item="' . $index . '" data-src="' . bl_escape($src) . '" data-alt="' . bl_escape($alt) . '" data-caption="' . bl_escape($caption) . '"></span>';
$posterIndex = $leadFigure ? 1 : 0;
$posterFigure = static fn(array $poster): string => '<figure class="bl-story-poster"><span class="bl-story-zoom-frame"><img src="' . bl_escape($poster['image']) . '" alt="' . bl_escape($poster['alt']) . '" width="889" height="2000" loading="lazy">' . $zoom($posterIndex, $poster['image'], $poster['alt'], $poster['caption']) . '</span><figcaption>' . bl_escape($poster['caption']) . '</figcaption></figure>';
// Text written in WordPress places the artwork as imported sections do: at the head of section 'after' (floated beside it
// on wide screens), or after the text when that section does not exist. Sections start at each <h2>.
$contentHtml = !empty($article['content_html']) ? wp_kses_post($article['content_html']) : '';
if ($contentHtml !== '' && $poster) {
    $parts = preg_split('/(?=<h2[\s>])/i', $contentHtml); // [text before the first heading, section 1, section 2, …]
    $contentHtml = $posterAfter >= 1 && $posterAfter < count($parts)
        ? implode('', array_slice($parts, 0, $posterAfter)) . $posterFigure($poster) . implode('', array_slice($parts, $posterAfter))
        : $contentHtml . $posterFigure($poster);
}
?>
<article class="bl-story bl-shell" aria-labelledby="story-title">
  <?php bl_breadcrumbs([['label' => 'News', 'url' => bl_url('/news/')], ['label' => $article['title']]]); ?>

  <?php /* The headline sits in the text column, so the side column starts level with it instead of leaving the top right empty. */ ?>
  <div class="bl-story-layout bl-story-layout-top">
    <div class="bl-story-main">
      <header class="bl-story-head">
        <a class="bl-story-category" href="<?= bl_escape(bl_url('/news/') . '?category=' . bl_news_section($article)) ?>"><?= bl_escape(bl_news_label($article)) ?></a>
        <h1 id="story-title"><?= bl_title_text($article['title']) ?></h1>
        <p class="bl-story-lede"><?= bl_escape($article['excerpt']) ?></p>
        <?php /* Phones only (news.css): who wrote it and when, and the event's dates and booth, which the side column shows
           only after the whole story there. */ ?>
        <p class="bl-story-byline"><span><?= bl_escape($author['name']) ?></span><time datetime="<?= bl_escape($article['date']) ?>"><?= date('j F Y', strtotime($article['date'])) ?></time></p>
        <?php if ($status): ?><dl class="bl-story-when">
          <div><dt>Dates</dt><dd><?php if ($status['state'] !== 'past'): ?><?= bl_escape(bl_event_dates($event)) ?> <?php endif; ?><span class="bl-news-when bl-news-when-<?= $status['state'] ?>"><?= bl_escape($status['label']) ?></span></dd></div>
          <?php if (!empty($event['booth'])): ?><div><dt>Booth</dt><dd><strong><?= bl_escape($event['booth']) ?></strong></dd></div><?php endif; ?>
        </dl><?php endif; ?>
      </header>
      <div class="bl-story-body">
        <?php if ($leadFigure): ?><figure class="bl-story-figure"><span class="bl-story-zoom-frame"><img src="<?= bl_escape($article['image']) ?>" alt="<?= bl_escape($article['caption']) ?>" width="1500" height="900" fetchpriority="high"><?= $zoom(0, $article['image'], $article['caption'], $article['caption']) ?></span><figcaption><?= bl_escape($article['caption']) ?></figcaption></figure><?php endif; ?>
        <div class="bl-story-text">
          <?php if ($contentHtml !== '' || !empty($article['_bl_body_managed'])): ?><?= $contentHtml === '' && $poster ? $posterFigure($poster) : '' ?><div class="bl-wp-article-content"><?= $contentHtml ?></div>
          <?php else: foreach ($article['sections'] as $i => $section): ?><?= $poster && $i + 1 === $posterAfter ? $posterFigure($poster) : '' ?><h2><?= bl_escape($section['heading']) ?></h2><p><?= bl_escape($section['text']) ?></p><?php endforeach; ?><?= $poster && ($posterAfter < 1 || $posterAfter > count($article['sections'])) ? $posterFigure($poster) : '' ?><?php endif; ?>
          <?php if ($status && $status['state'] !== 'past'): ?><p class="bl-story-visit">Planning to visit? <a href="<?= bl_escape(bl_url('/contact/') . '#enquiry') ?>">Tell us your dates</a> or <a href="<?= bl_escape(bl_company('sales_whatsapp_url')) ?>" target="_blank" rel="noopener">message us on WhatsApp</a>, so the compact equipment team can expect you.</p><?php endif; ?>
        </div>
      </div>
    </div>

    <aside class="bl-story-aside" aria-label="About this story">
      <section class="bl-story-box bl-story-author">
        <h2>Written by</h2>
        <div class="bl-story-author-name">
          <?php if ($author['avatar']): ?><img src="<?= bl_escape($author['avatar']) ?>" alt="" width="64" height="64"><?php else: ?><span class="bl-story-avatar" aria-hidden="true"><?= bl_escape(mb_substr($author['name'], 0, 1)) ?></span><?php endif; ?>
          <span><b><?= bl_escape($author['name']) ?></b><?php if ($author['title']): ?><small><?= bl_escape($author['title']) ?></small><?php endif; ?></span>
        </div>
        <?php if ($author['bio'] !== ''): ?><p class="bl-story-author-bio"><?= nl2br(bl_escape($author['bio'])) ?></p><?php endif; ?>
        <dl>
          <div><dt>Published</dt><dd><time datetime="<?= bl_escape($article['date']) ?>"><?= date('j F Y', strtotime($article['date'])) ?></time></dd></div>
          <?php if (!empty($article['updated'])): ?><div><dt>Updated</dt><dd><time datetime="<?= bl_escape($article['updated']) ?>"><?= date('j F Y', strtotime($article['updated'])) ?></time></dd></div><?php endif; ?>
        </dl>
        <?php if ($article['source']): ?><a class="bl-story-box-link" href="<?= bl_escape($article['source']) ?>" target="_blank" rel="noopener">Original announcement on boblift.com <?= bl_icon('arrow-up-right') ?></a><?php endif; ?>
      </section>

      <?php /* Everything after the author follows the reader down the page on wide screens (news.css). */ ?>
      <div class="bl-story-follow">
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
      </div>
    </aside>
  </div>
</article>

<nav class="bl-story-pager bl-shell" aria-label="More stories">
  <?php if ($newer || $older): ?><div class="bl-story-pager-pair">
    <?php if ($newer): ?><a class="bl-story-pager-card bl-story-pager-newer" href="<?= bl_escape(bl_article_url($newer)) ?>"><span><?= bl_icon('arrow-left') ?> Newer</span><b><?= bl_title_text($newer['title']) ?></b></a><?php endif; ?>
    <?php if ($older): ?><a class="bl-story-pager-card bl-story-pager-older" href="<?= bl_escape(bl_article_url($older)) ?>"><span>Older <?= bl_icon('arrow-up-right') ?></span><b><?= bl_title_text($older['title']) ?></b></a><?php endif; ?>
  </div><?php endif; ?>
  <a class="bl-story-pager-all" href="<?= bl_escape(bl_url('/news/')) ?>"><?= bl_icon('arrow-left') ?> All news</a>
</nav>
<?php if ($leadFigure || $poster): ?><dialog class="bl-lightbox" id="story-lightbox" aria-label="<?= bl_escape($article['title']) ?>"><figure><img alt=""><figcaption><span data-lightbox-caption></span><small data-lightbox-count></small></figcaption></figure><button class="bl-lightbox-close bl-circle-button" type="button" data-lightbox-close aria-label="Close"><?= bl_icon('x') ?></button><button class="bl-lightbox-step bl-lightbox-prev bl-circle-button" type="button" data-lightbox-step="-1" aria-label="Previous photograph"><?= bl_icon('arrow-left') ?></button><button class="bl-lightbox-step bl-lightbox-next bl-circle-button" type="button" data-lightbox-step="1" aria-label="Next photograph"><?= bl_icon('arrow-left') ?></button></dialog><?php endif; ?>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
