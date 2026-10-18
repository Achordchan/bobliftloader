<?php
/**
 * On-this-page navigation for a family page. The script pins it under the header once the family switch scrolls away
 * and marks the section in view; without the script it sits in the page as a plain list of links.
 */
$loaderPage = $category === 'skid-steer-loaders';
$sectionLinks = array_filter([
    'models' => 'Models',
    'about-the-range' => 'About the range',
    'dumper-jobs' => !$loaderPage && bl_dumper_jobs() ? 'Where they work' : null,
    'attachments' => $loaderPage ? 'Attachments' : null,
    'how-they-are-made' => bl_editable($loaderPage ? 'loaders' : 'dumpers')['making']['steps'] ? 'How they’re made' : null,
    'compare-' . $category => 'Compare',
    'model-names' => $loaderPage || bl_dumper_name_groups() ? 'Model names' : null,
    'buying-guide' => array_filter(array_intersect_key(bl_family_buying($loaderPage ? 'loaders' : 'dumpers'), ['points' => 1, 'faq' => 1])) ? 'Buying guide' : null,
]);
?>
<nav class="bl-section-nav" aria-label="On this page" data-section-nav>
  <div class="bl-section-nav-inner bl-shell">
    <strong class="bl-section-nav-title"><?= $loaderPage ? 'Skid steer loaders' : 'Mini dumpers' ?></strong>
    <ol>
      <?php $n = 0; foreach ($sectionLinks as $id => $label): $n++; ?><li><a href="#<?= $id ?>" data-section-link><span>0<?= $n ?></span><?= $label ?><?php if ($id === 'models'): ?> <small><?= count($scope) ?></small><?php endif; ?></a></li><?php endforeach; ?>
    </ol>
    <a class="bl-section-nav-cta" href="<?= bl_escape(bl_url('/contact/') . '?equipment=' . ($loaderPage ? 'loader' : 'dumper') . '#enquiry') ?>">Get a quote <?= bl_icon('arrow-up-right') ?></a>
  </div>
  <i class="bl-section-nav-progress" aria-hidden="true"></i>
</nav>
