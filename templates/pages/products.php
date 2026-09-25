<?php
$category = $route['category'];
$search = bl_query('q');
$filters = bl_catalog_filters($category);
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
$heading = $category === 'mini-dumpers' ? 'KEEP MATERIALS<br>MOVING.' : ($category === 'skid-steer-loaders' ? 'COMPACT SIZE.<br>MORE CAPABILITY.' : 'FIND YOUR<br>NEXT MACHINE.');
?>
<section class="bl-page-hero bl-shell">
  <?php bl_breadcrumbs($category ? [['label' => 'Equipment', 'url' => bl_url('/products/')], ['label' => $category === 'mini-dumpers' ? 'Mini dumpers' : 'Skid steer loaders']] : [['label' => 'Equipment']]); ?>
  <div class="bl-page-heading"><div><p class="bl-eyebrow">THE BOB-LIFT EQUIPMENT RANGE</p><h1><?= $heading ?></h1></div><p><?= $category === 'mini-dumpers' ? 'Published capacities. Real models. Find the mini dumper that fits your material transport requirements.' : 'Start with the work ahead. Explore our compact equipment, then get into the details.' ?></p></div>
</section>
<?php if ($category) require __DIR__ . '/../partials/section-nav.php'; // appears once the family tabs scroll away ?>
<section class="bl-catalog bl-shell" id="models" aria-label="Product catalogue">
  <nav class="bl-category-nav" aria-label="Equipment categories"><?php foreach (['' => 'All equipment', 'skid-steer-loaders' => 'Skid steer loaders', 'mini-dumpers' => 'Mini dumpers'] as $key => $label): ?><a href="<?= bl_escape(bl_url('/products/' . ($key ? $key . '/' : ''))) ?>" <?= $category === $key ? 'aria-current="page"' : '' ?>><?= $label ?></a><?php endforeach; ?></nav>
  <form class="bl-catalog-tools" method="get" action="<?= bl_escape(bl_url($route['path'])) ?>" data-catalog>
    <?php /* Searching starts in the header; a search that lands here (no script, or a shared link) shows as a removable term. */ ?>
    <input type="hidden" name="q" value="<?= bl_escape($search) ?>">
    <div class="bl-catalog-filters">
      <?php foreach ($filters as $key => $group): $single = count($group['options']) === 1; ?>
      <fieldset class="bl-filter" data-filter="<?= $key ?>"><legend><?= bl_escape($group['label']) ?></legend><div class="bl-chips">
        <?php if (!$single): $n = $facetCount($key, ''); ?><label class="bl-chip"><input type="radio" name="<?= $key ?>" value=""<?= isset($active[$key]) ? '' : ' checked' ?>><span>All <small data-count><?= $n ?></small></span></label><?php endif; ?>
        <?php foreach ($group['options'] as $value => $label): $n = $facetCount($key, $value); $on = ($active[$key] ?? '') === $value; ?><label class="bl-chip"><input type="<?= $single ? 'checkbox' : 'radio' ?>" name="<?= $key ?>" value="<?= $value ?>"<?= $on ? ' checked' : '' ?><?= !$n && !$on ? ' disabled' : '' ?>><span><?= bl_escape($label) ?> <small data-count><?= $n ?></small></span></label><?php endforeach; ?>
      </div></fieldset>
      <?php endforeach; ?>
    </div>
    <div class="bl-catalog-status"><?php if ($search !== ''): ?><span class="bl-catalog-query" data-catalog-query>Search <b>“<?= bl_escape($search) ?>”</b></span><?php endif; ?><p aria-live="polite"><b data-result-count><?= $visible ?></b> <span data-result-noun><?= $visible === 1 ? 'result' : 'results' ?></span></p><a class="bl-catalog-clear" href="<?= bl_escape(bl_url($route['path'])) ?>" data-catalog-clear<?= $active || $search !== '' ? '' : ' hidden' ?>>Clear all</a></div>
    <noscript><button class="bl-button bl-button-blue" type="submit">Apply filters <?= bl_icon('arrow-up-right') ?></button></noscript>
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
  <div class="bl-empty" data-catalog-empty<?= $visible ? ' hidden' : '' ?>><h2>No matching equipment.</h2><p>Try another model name, or remove a filter.</p><a class="bl-text-link" href="<?= bl_escape(bl_url($route['path'])) ?>" data-catalog-clear>Clear filters <?= bl_icon('arrow-up-right') ?></a></div>
</section>
<?php if ($category) { $storyCategory = $category; require __DIR__ . '/../partials/range-story.php'; } // what the family is for, before the model-by-model comparison ?>
<?php if ($category === 'skid-steer-loaders') require __DIR__ . '/../partials/attachments.php'; // tools belong to the machine, so they sit with the range, not in a catalogue of their own ?>
<?php if ($category === 'skid-steer-loaders') require __DIR__ . '/../partials/making-story.php'; // loader production photography only; none supplied for dumpers yet ?>
<?php if ($category) { $compareCategory = $category; require __DIR__ . '/../partials/compare-table.php'; } ?>
<?php if ($category === 'skid-steer-loaders') require __DIR__ . '/../partials/model-key.php'; // after the comparison: the names are read once the models are known ?>
<?php require __DIR__ . '/../partials/page-cta.php'; ?>
