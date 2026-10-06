<?php
/**
 * One application case: a specific job written up in full, in one centred reading column (news.css, .bl-story-case):
 * head with the job it belongs to, the facts of the job in a row, lead image and text, then the machines used and a way
 * to ask about a similar one side by side. Only what an editor filled in is shown.
 */
$case = $route['record'];
$hub = bl_application_hub((string) $case['hub']);
$hubTitle = $hub ? $hub['title'] : '';
$hubUrl = $hub ? bl_url('/applications/' . bl_application_slug($hub) . '/') : bl_url('/applications/');
$facts = array_filter((array) ($case['facts'] ?? []), static fn($value): bool => trim((string) $value) !== '');
$factLabels = ['location' => 'Location', 'customer' => 'Customer', 'task' => 'The work', 'site' => 'The site', 'duration' => 'Duration', 'result' => 'Result'];
$machines = array_values(array_filter(array_map(static fn($slug) => bl_find_record(bl_products(), $slug), $case['products'] ?? [])));
$all = bl_application_cases();
// A preview of an unpublished case is not in the list, so it has no neighbours to link to.
$index = array_search($case['id'] ?? null, array_column($all, 'id'), true);
$newer = $index === false ? null : ($all[$index - 1] ?? null);
$older = $index === false ? null : ($all[$index + 1] ?? null);
?>
<article class="bl-story bl-story-case bl-shell" aria-labelledby="story-title">
  <?php bl_breadcrumbs(array_values(array_filter([['label' => 'Applications', 'url' => bl_url('/applications/')], $hub ? ['label' => $hubTitle, 'url' => $hubUrl] : null, ['label' => $case['title']]]))); ?>
  <header class="bl-story-head">
    <a class="bl-story-category" href="<?= bl_escape($hubUrl) ?>"><?= bl_escape($hubTitle ?: 'Applications') ?></a>
    <h1 id="story-title"><?= bl_escape($case['title']) ?></h1>
    <?php if ($case['excerpt']): ?><p class="bl-story-lede"><?= bl_escape($case['excerpt']) ?></p><?php endif; ?>
  </header>

  <div class="bl-story-layout">
    <?php if ($facts): ?>
    <section class="bl-story-facts" aria-label="At a glance">
      <dl>
        <?php foreach ($factLabels as $key => $label): if (empty($facts[$key])) continue; ?><div><dt><?= bl_escape($label) ?></dt><dd><?= bl_escape((string) $facts[$key]) ?></dd></div><?php endforeach; ?>
        <div><dt>Published</dt><dd><time datetime="<?= bl_escape($case['date']) ?>"><?= date('j F Y', strtotime($case['date'])) ?></time></dd></div>
      </dl>
    </section>
    <?php endif; ?>

    <div class="bl-story-main">
      <figure class="bl-story-figure"><img src="<?= bl_escape($case['image']) ?>" alt="<?= bl_escape($case['title']) ?>" width="1500" height="900" fetchpriority="high"></figure>
      <?php /* The body is WordPress's own rendering of what an editor saved (blocks, embeds), echoed as the_content() does. */ ?>
      <div class="bl-story-text"><div class="bl-wp-article-content"><?= $case['content_html'] ?></div></div>
    </div>

    <aside class="bl-story-aside" aria-label="Machines and enquiry">
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
