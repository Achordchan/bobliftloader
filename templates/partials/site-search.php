<?php
/**
 * Header search: a small index of models, ranges, applications, pages and news for assets/js/search.js. Loaders
 * also carry the older ways their names were written (BL-390 on the old product pages, BL-T360 at the fair), and
 * dumpers their codes from before the 2026 brochure (FY-30, FC10-L), so a buyer holding old literature still lands
 * on the right model. Without the script, the form submits to the catalogue.
 */
$searchIndex = [];
$searchProducts = bl_products();
foreach ($searchProducts as $p) {
    $legacy = preg_match('/^BL-([TS])(\d+)-[HC]$/', $p['model'], $m) ? ['BL-' . $m[2], 'BL-' . $m[1] . $m[2]] : [];
    $legacy = array_merge($legacy, BL_FORMER_MODELS[$p['model']] ?? []);
    $spec = bl_key_specs($p, true);
    $searchIndex[] = [
        'kind' => $p['category'] === 'mini-dumpers' ? 'Mini dumper' : 'Skid steer loader',
        'title' => $p['model'],
        'text' => trim(preg_replace('/^' . preg_quote($p['model'], '/') . '\s*/', '', $p['name'])),
        'meta' => $spec ? array_key_first($spec) . ' ' . reset($spec) : '',
        'url' => bl_product_url($p),
        'image' => $p['image'],
        'legacy' => $legacy,
    ];
}
foreach (['skid-steer-loaders' => ['Mini skid steer loaders', 'loader bucket'], 'mini-dumpers' => ['Mini dumpers', 'dumper tipper transport mixer']] as $key => [$label, $terms]) {
    $searchIndex[] = ['kind' => 'Range', 'title' => $label, 'text' => count(array_filter($searchProducts, static fn($p) => $p['category'] === $key)) . ' models', 'url' => bl_url('/products/' . $key . '/'), 'terms' => $terms];
}
foreach ($applications as $app) {
    $searchIndex[] = ['kind' => 'Application', 'title' => $app['title'], 'text' => $app['description'], 'url' => bl_url('/applications/' . bl_application_slug($app) . '/')];
}
foreach (bl_application_cases() as $case) {
    $searchIndex[] = ['kind' => 'Application', 'title' => $case['title'], 'text' => $case['excerpt'], 'url' => bl_case_url($case)];
}
foreach ([['/about/', 'About us', 'Company, factory in Xuzhou, certificates'], ['/support/', 'Buying & support', 'Ordering, lead time, shipping, warranty, spare parts, downloads, FAQ'], ['/products/skid-steer-loaders/#attachments', 'Loader attachments', 'Pallet forks, augers, breakers, sweepers and more'], ['/contact/', 'Contact', 'Enquiries, quotations, parts and service'], ['/news/', 'News & updates', 'Company stories and exhibitions']] as [$path, $title, $text]) {
    $searchIndex[] = ['kind' => 'Page', 'title' => $title, 'text' => $text, 'url' => bl_url($path)];
}
foreach (bl_articles() as $article) {
    $searchIndex[] = ['kind' => 'News', 'title' => $article['title'], 'text' => date('j F Y', strtotime($article['date'])), 'url' => bl_article_url($article)];
}
?>
<dialog class="bl-search" id="site-search" aria-label="Search the site">
  <form class="bl-search-form" method="get" action="<?= bl_escape(bl_url('/products/')) ?>" role="search">
    <div class="bl-search-bar bl-shell">
      <?= bl_icon('magnifying-glass', 'bl-search-icon') ?>
      <input type="search" name="q" placeholder="Search a model, e.g. BL-T360-H or FY30" autocomplete="off" autocapitalize="off" spellcheck="false" role="combobox" aria-label="Search models and pages" aria-autocomplete="list" aria-expanded="false" aria-controls="site-search-results">
      <button class="bl-search-close" type="button" data-close-search aria-label="Close search"><kbd>Esc</kbd><?= bl_icon('x') ?></button>
    </div>
  </form>
  <div class="bl-search-body bl-shell">
    <p class="bl-search-note" data-search-note hidden></p>
    <ul class="bl-search-results" id="site-search-results" role="listbox" aria-label="Search results"></ul>
    <div class="bl-search-start" data-search-start>
      <p class="bl-eyebrow">TRY</p>
      <div><?php foreach (['BL-T360-H', 'BL-390', 'FY30', 'Tracked', 'Mixer'] as $example): ?><button type="button" data-search-try="<?= $example ?>"><?= $example ?></button><?php endforeach; ?></div>
      <p>Older and short model names work too: BL-390 finds both 390s, BL-T360 finds BL-T360-H.</p>
    </div>
    <div class="bl-search-empty" data-search-empty hidden>
      <p><strong data-search-empty-query></strong> doesn’t match a model or page.</p>
      <div><a href="<?= bl_escape(bl_url('/products/skid-steer-loaders/')) ?>">Skid steer loaders <?= bl_icon('arrow-up-right') ?></a><a href="<?= bl_escape(bl_url('/products/mini-dumpers/')) ?>">Mini dumpers <?= bl_icon('arrow-up-right') ?></a><a href="<?= bl_escape(bl_contact_url()) ?>">Ask our team <?= bl_icon('arrow-up-right') ?></a></div>
    </div>
    <p class="bl-search-keys" aria-hidden="true"><span><kbd>↑</kbd><kbd>↓</kbd> move</span><span><kbd>Enter</kbd> open</span><span><kbd>Esc</kbd> close</span></p>
  </div>
  <script type="application/json" id="site-search-index"><?= json_encode($searchIndex, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
</dialog>
