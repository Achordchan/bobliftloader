<?php
/**
 * Guides and news about what is on this page, from the products each story names (bl_related_articles). Needs $guides
 * (the stories) and $guideCopy (a function returning the section's editable labels); shows nothing when there are none.
 */
if (!$guides) return; ?>
<section class="bl-related-guides bl-shell" aria-labelledby="guides-title"><div class="bl-small-section-heading"><div><p class="bl-eyebrow"><?= $guideCopy('guides_label') ?></p><h2 id="guides-title"><?= $guideCopy('guides_title') ?></h2></div><a class="bl-text-link" href="<?= bl_escape(bl_url('/news/')) ?>"><?= $guideCopy('guides_link') ?> <?= bl_icon('arrow-up-right') ?></a></div>
  <div class="bl-news-grid"><?php foreach ($guides as $article) bl_news_card($article, 'h3'); ?></div>
</section>
