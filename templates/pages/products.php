<?php
$category = $route['category'];
$catalogPage = $category === 'mini-dumpers' ? 'dumpers' : 'loaders';
$catalogCopy = static fn(string $key): string => bl_family_copy($catalogPage, 'catalog.' . $key);
$search = bl_query('q');
$filters = bl_catalog_filters($category);
// /products/ exists only for header searches, so clearing one there opens the loaders (the bare address redirects there anyway).
$clear = $category ? ['url' => bl_url($route['path']), 'attr' => 'data-catalog-clear'] : ['url' => bl_url('/products/skid-steer-loaders/'), 'attr' => 'data-catalog-leave'];
$active = [];
foreach ($filters as $key => $group) if (isset($group['options'][bl_query($key)])) $active[$key] = bl_query($key);
// Every model in scope is rendered; the ones outside the filters are hidden, so the catalogue script can refilter in place.
$scope = [];
foreach (bl_products() as $p) {
    if ($category && $p['category'] !== $category) continue;
    $scope[] = ['product' => $p, 'facets' => bl_product_facets($p, $category), 'search' => bl_product_search_text($p)];
}
$matches = static function (array $item, string $except = '') use ($active, $search): bool {
    if ($search !== '' && !str_contains($item['search'], strtolower($search))) return false;
    foreach ($active as $key => $value) if ($key !== $except && $item['facets'][$key] !== $value) return false;
    return true;
};
$visible = count(array_filter($scope, static fn($item) => $matches($item)));
$facetCount = static fn(string $key, string $value): int => count(array_filter($scope, static fn($item) => $matches($item, $key) && ($value === '' || $item['facets'][$key] === $value)));
$heading = $category ? $catalogCopy('hero_title') : 'FIND YOUR<br>NEXT MACHINE.';
$familyCatalogue = $category ? bl_family_catalogue($category) : null;
// The family film opens in a dialog from a button beside the catalogue download; no address saved, no button.
$film = $category ? ['src' => trim(bl_content_value($catalogPage, 'catalog.film_video')), 'poster' => trim(bl_content_value($catalogPage, 'catalog.film_poster')), 'caption' => trim(bl_content_value($catalogPage, 'catalog.film_caption'))] : ['src' => ''];
?>
<section class="bl-page-hero bl-catalog-hero bl-shell">
  <?php bl_breadcrumbs([['label' => $category ? bl_family_label($category) : 'Search']]); ?>
  <div class="bl-page-heading"><div><p class="bl-eyebrow"><?= $catalogCopy('hero_eyebrow') ?></p><h1><?= $heading ?></h1></div><p><?= $catalogCopy('hero_intro') ?><?php if ($familyCatalogue || $film['src'] !== ''): ?><span class="bl-catalog-actions"><?php if ($film['src'] !== ''): ?><button class="bl-text-link bl-film-open" type="button" data-film-open="film-dialog"><span><?= $catalogCopy('hero_film') ?><small>Video</small></span><?= bl_icon('play') ?></button><?php endif; ?><?php if ($familyCatalogue): ?><a class="bl-text-link bl-catalog-download" href="<?= bl_escape($familyCatalogue['url']) ?>" target="_blank" rel="noopener"><span><?= $catalogCopy('hero_download') ?><small><?= bl_escape($familyCatalogue['kind']) ?></small></span><?= bl_icon('arrow-down') ?></a><?php endif; ?></span><?php endif; ?></p></div>
</section>
<?php if ($film['src'] !== ''): ?>
<dialog class="bl-dialog bl-film-dialog" id="film-dialog" aria-label="<?= bl_escape($catalogCopy('hero_film')) ?>">
  <button class="bl-dialog-close bl-circle-button" data-close-dialog aria-label="Close the film"><?= bl_icon('x') ?></button>
  <div class="bl-film-stage"><video controls playsinline preload="none"<?= $film['poster'] !== '' ? ' poster="' . bl_escape(bl_product_media($film['poster'])) . '"' : '' ?> data-src="<?= bl_escape(bl_product_media($film['src'])) ?>"></video></div>
  <?php if ($film['caption'] !== ''): ?><p class="bl-film-caption"><?= bl_escape($film['caption']) ?></p><?php endif; ?>
</dialog>
<?php endif; ?>
<?php if ($category) require __DIR__ . '/../partials/section-nav.php'; // appears once the family switch scrolls away ?>
<section class="bl-catalog bl-shell" id="models" aria-label="Product catalogue">
  <?php
  // Both families side by side at the top of the range: loaders, the main line, first and open by default; the other
  // family is one click away. On a search both show, neither current.
  $families = [
      'skid-steer-loaders' => ['label' => bl_content_value('loaders', 'catalog.card_label'), 'image' => bl_family_image('loader'), 'ranges' => bl_family_ranges('loader'), 'line' => static fn($r) => bl_family_copy_value('loaders', 'catalog.card_description', ['load_range' => bl_load_range($r['load']), 'min_width' => number_format($r['width'][0])])],
      'mini-dumpers' => ['label' => bl_content_value('dumpers', 'catalog.card_label'), 'image' => bl_family_image('dumper'), 'ranges' => bl_family_ranges('dumper'), 'line' => static fn($r) => bl_family_copy_value('dumpers', 'catalog.card_description', ['load_range' => bl_load_range($r['load']), 'min_width' => number_format($r['width'][0])])],
  ];
  ?>
  <nav class="bl-family-switch" aria-label="Equipment range">
    <?php foreach ($families as $key => $family): ?>
    <a href="<?= bl_escape(bl_url('/products/' . $key . '/')) ?>"<?= $category === $key ? ' aria-current="page"' : '' ?>>
      <img src="<?= bl_escape($family['image']) ?>" alt="" width="1374" height="1145">
      <span><small><?= $family['ranges']['count'] ?> <?= bl_family_copy($key === 'mini-dumpers' ? 'dumpers' : 'loaders', 'catalog.card_models') ?></small><b><?= bl_escape($family['label']) ?></b><span><?= bl_escape($family['line']($family['ranges'])) ?></span></span>
      <?= bl_icon('arrow-up-right') ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <form class="bl-catalog-tools" method="get" action="<?= bl_escape(bl_url($route['path'])) ?>" data-catalog>
    <?php /* Searching starts in the header; a search that lands here (no script, or a shared link) shows as a removable term. */ ?>
    <input type="hidden" name="q" value="<?= bl_escape($search) ?>">
    <div class="bl-catalog-filters">
      <?php foreach ($filters as $key => $group): $single = count($group['options']) === 1; ?>
      <fieldset class="bl-filter" data-filter="<?= $key ?>"><legend><?= bl_escape($group['label']) ?></legend><div class="bl-chips">
        <?php if (!$single): $n = $facetCount($key, ''); ?><label class="bl-chip"><input type="radio" name="<?= $key ?>" value=""<?= isset($active[$key]) ? '' : ' checked' ?>><span><?= $catalogCopy('all') ?> <small data-count><?= $n ?></small></span></label><?php endif; ?>
        <?php foreach ($group['options'] as $value => $label): $n = $facetCount($key, $value); $on = ($active[$key] ?? '') === $value; ?><label class="bl-chip"><input type="<?= $single ? 'checkbox' : 'radio' ?>" name="<?= $key ?>" value="<?= $value ?>"<?= $on ? ' checked' : '' ?><?= !$n && !$on ? ' disabled' : '' ?>><span><?= bl_escape($label) ?> <small data-count><?= $n ?></small></span></label><?php endforeach; ?>
      </div></fieldset>
      <?php endforeach; ?>
    </div>
    <div class="bl-catalog-status"><?php if ($search !== ''): ?><span class="bl-catalog-query" data-catalog-query>Search <b>“<?= bl_escape($search) ?>”</b></span><?php endif; ?><p aria-live="polite"><b data-result-count><?= $visible ?></b> <span data-result-noun><?= $visible === 1 ? 'result' : 'results' ?></span></p><a class="bl-catalog-clear" href="<?= bl_escape($clear['url']) ?>" <?= $clear['attr'] ?><?= $active || $search !== '' ? '' : ' hidden' ?>><?= $catalogCopy('clear') ?></a></div>
    <noscript><button class="bl-button bl-button-blue" type="submit"><?= $catalogCopy('apply') ?> <?= bl_icon('arrow-up-right') ?></button></noscript>
  </form>
  <?php
  // The whole range groups by family, each group linking to its own page; a family page is one group without a heading.
  $groups = $category ? [$category => $scope] : [];
  if (!$category) foreach ($scope as $item) $groups[$item['product']['category']][] = $item;
  foreach ($groups as $groupKey => $items): $shown = count(array_filter($items, static fn($item) => $matches($item))); ?>
  <div class="bl-catalog-group" data-catalog-group<?= $shown ? '' : ' hidden' ?>>
    <?php if (!$category): ?><header class="bl-catalog-group-head"><h2><?= $groupKey === 'mini-dumpers' ? 'Mini dumpers' : 'Mini skid steer loaders' ?> <small data-group-count><?= $shown ?></small></h2><a class="bl-text-link" href="<?= bl_escape(bl_url('/products/' . $groupKey . '/')) ?>"><?= $groupKey === 'mini-dumpers' ? 'The dumper range' : 'The loader range' ?> <?= bl_icon('arrow-up-right') ?></a></header><?php endif; ?>
    <div class="bl-product-grid"><?php foreach ($items as $item) { $attributes = ' data-catalog-card data-search="' . bl_escape($item['search']) . '"'; foreach ($item['facets'] as $key => $value) $attributes .= ' data-' . $key . '="' . $value . '"'; bl_product_card($item['product'], $attributes . ($matches($item) ? '' : ' hidden')); } ?></div>
  </div>
  <?php endforeach; ?>
  <div class="bl-empty" data-catalog-empty<?= $visible ? ' hidden' : '' ?>><h2><?= $catalogCopy('empty_title') ?></h2><p><?= $catalogCopy('empty_intro') ?></p><a class="bl-text-link" href="<?= bl_escape($clear['url']) ?>" <?= $clear['attr'] ?>><?= $catalogCopy('empty_clear') ?> <?= bl_icon('arrow-up-right') ?></a></div>
</section>
<?php if ($category) { $storyCategory = $category; require __DIR__ . '/../partials/range-story.php'; } // what the family is for, before the model-by-model comparison ?>
<?php if ($category === 'mini-dumpers') require __DIR__ . '/../partials/dumper-jobs.php'; // the jobs each dumper suits, after the load chart ?>
<?php if ($category === 'skid-steer-loaders') require __DIR__ . '/../partials/attachments.php'; // tools belong to the machine, so they sit with the range, not in a catalogue of their own ?>
<?php if ($category) require __DIR__ . '/../partials/making-story.php'; // how the machines are made; the dumper section appears once its photographs are saved ?>
<?php if ($category) { $compareCategory = $category; require __DIR__ . '/../partials/compare-table.php'; } ?>
<?php if ($category === 'skid-steer-loaders') require __DIR__ . '/../partials/model-key.php'; // after the comparison: the names are read once the models are known ?>
<?php if ($category === 'mini-dumpers') require __DIR__ . '/../partials/dumper-names.php'; ?>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
