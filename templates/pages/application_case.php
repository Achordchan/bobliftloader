<?php
/**
 * One application case: a specific job written up in full. Laid out like a news story (news.css): head with the job it
 * belongs to, lead image and text on the left; on the right the facts of the job, the machines used and a way to ask
 * about a similar one. Only what an editor filled in is shown.
 */
$case = $route['record'];
$hub = bl_application_hub((string) $case['hub']);
$hubTitle = $hub ? $hub['title'] : '';
$hubUrl = $hub ? bl_url('/applications/' . bl_application_slug($hub) . '/') : bl_url('/applications/');
$facts = array_filter((array) ($case['facts'] ?? []), static fn($value): bool => trim((string) $value) !== '');
$factLabels = ['location' => 'Location', 'customer' => 'Customer', 'task' => 'The work', 'site' => 'The site', 'duration' => 'Duration', 'result' => 'Result'];
$machines = array_values(array_filter(array_map(static fn($slug) => bl_find_record(bl_products(), $slug), $case['products'] ?? [])));
$all = bl_application_cases();
$index = (int) array_search($case['slug'], array_column($all, 'slug'), true);
$newer = $all[$index - 1] ?? null;
$older = $all[$index + 1] ?? null;
?>
<article class="bl-story bl-shell" aria-labelledby="story-title">
  <?php bl_breadcrumbs(array_values(array_filter([['label' => 'Applications', 'url' => bl_url('/applications/')], $hub ? ['label' => $hubTitle, 'url' => $hubUrl] : null, ['label' => $case['title']]]))); ?>
  <header class="bl-story-head">
    <a class="bl-story-category" href="<?= bl_escape($hubUrl) ?>"><?= bl_escape($hubTitle ?: 'Applications') ?></a>
    <h1 id="story-title"><?= bl_escape($case['title']) ?></h1>
    <?php if ($case['excerpt']): ?><p class="bl-story-lede"><?= bl_escape($case['excerpt']) ?></p><?php endif; ?>
  </header>

  <div class="bl-story-layout">
    <div class="bl-story-main">
      <figure class="bl-story-figure"><img src="<?= bl_escape($case['image']) ?>" alt="<?= bl_escape($case['title']) ?>" width="1500" height="900" fetchpriority="high"></figure>
      <div class="bl-story-text"><div class="bl-wp-article-content"><?= wp_kses_post($case['content_html']) ?></div></div>
    </div>

    <aside class="bl-story-aside" aria-label="About this application">
      <?php if ($facts): ?>
      <section class="bl-story-box">
        <h2>At a glance</h2>
        <dl>
          <?php foreach ($factLabels as $key => $label): if (empty($facts[$key])) continue; ?><div><dt><?= bl_escape($label) ?></dt><dd><?= bl_escape((string) $facts[$key]) ?></dd></div><?php endforeach; ?>
          <div><dt>Published</dt><dd><time datetime="<?= bl_escape($case['date']) ?>"><?= date('j F Y', strtotime($case['date'])) ?></time></dd></div>
        </dl>
      </section>
      <?php endif; ?>

      <?php if ($machines): ?>
      <section class="bl-story-box">
        <h2>Machines used</h2>
        <ul class="bl-story-product-list">
          <?php foreach ($machines as $product): $key = bl_key_specs($product); $first = $key ? array_key_first($key) : ''; ?>
          <li><a href="<?= bl_escape(bl_product_url($product)) ?>">
            <span class="bl-story-product-image"><?php if ($product['image']): ?><img src="<?= bl_escape($product['image']) ?>" alt="" width="800" height="650" loading="lazy"><?php endif; ?></span>
            <span><b><?= bl_escape($product['model']) ?></b><small><?= bl_escape(trim(preg_replace('/^' . preg_quote($product['model'], '/') . '\s*/', '', $product['name']))) ?></small><?php if ($first): ?><small><?= bl_escape($first) ?> <?= bl_escape($key[$first]) ?></small><?php endif; ?></span>
          </a></li>
          <?php endforeach; ?>
        </ul>
      </section>
      <?php endif; ?>

      <section class="bl-story-box bl-story-ask">
        <h2>A similar job?</h2>
        <p>Tell us about your site and we will suggest a model and configuration.</p>
        <a class="bl-button bl-button-blue" href="<?= bl_escape(bl_contact_url($machines[0] ?? null, $hubTitle)) ?>">Discuss your job <?= bl_icon('arrow-up-right') ?></a>
      </section>
    </aside>
  </div>
</article>

<nav class="bl-story-pager bl-shell" aria-label="More applications">
  <?php if ($newer): ?><a href="<?= bl_escape(bl_case_url($newer)) ?>"><span><?= bl_icon('arrow-left') ?> Newer</span><b><?= bl_escape($newer['title']) ?></b></a><?php else: ?><a href="<?= bl_escape(bl_url('/applications/')) ?>"><span><?= bl_icon('arrow-left') ?> All applications</span><b>Back to the jobs we work on</b></a><?php endif; ?>
  <?php if ($older): ?><a class="bl-story-pager-older" href="<?= bl_escape(bl_case_url($older)) ?>"><span>Older <?= bl_icon('arrow-up-right') ?></span><b><?= bl_escape($older['title']) ?></b></a><?php endif; ?>
</nav>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
