<?php
/** Company facts transcribed from www.<?= bl_content_text('about', 'group.website_domain') ?>(About BOB-LIFT, homepage) on 2026-09-20. Keep figures in sync with the main site. */
$companyFacts = bl_editable('about')['group']['facts'];
$families = [];
foreach (bl_editable('about')['group']['product_links'] as $item) {
    $category = $item['family'] === 'dumper' ? 'mini-dumpers' : 'skid-steer-loaders';
    $families[] = ['label' => $item['label'], 'url' => bl_application_link($item['url']), 'count' => count(array_filter(bl_products(), static fn($p) => $p['category'] === $category))];
}
?>
<section class="bl-about-hero" aria-labelledby="about-title">
  <div class="bl-shell">
    <?php bl_breadcrumbs([['label' => bl_content_value('about', 'hero.breadcrumb')]]); ?>
    <div class="bl-about-hero-grid">
      <div>
        <p class="bl-eyebrow"><span class="bl-status-dot"></span><?= bl_content_text('about', 'hero.eyebrow') ?></p>
        <h1 id="about-title"><span><?= bl_content_text('about', 'hero.line1') ?></span><span class="bl-outline-text"><?= bl_content_text('about', 'hero.line2') ?></span><span class="bl-blue-text"><?= bl_content_text('about', 'hero.line3') ?></span></h1>
      </div>
      <div class="bl-about-hero-side">
        <p><?= bl_company_text('about', 'hero.text') ?></p>
        <a class="bl-text-link" href="<?= bl_escape(bl_application_link(bl_content_value('about', 'hero.link_url'))) ?>"><?= bl_content_text('about', 'hero.link_label') ?><?= bl_icon('arrow-down') ?></a>
      </div>
    </div>
  </div>
  <div class="bl-about-hero-stage">
  <figure class="bl-about-hero-photo">
    <img srcset="<?= bl_escape(bl_content_asset('about', 'hero.image_small', 'images/about/hall-1200.webp')) ?> 1200w, <?= bl_escape(bl_content_asset('about', 'hero.image', 'images/about/hall-2560.webp')) ?> 2560w" sizes="100vw" src="<?= bl_escape(bl_content_asset('about', 'hero.image', 'images/about/hall-2560.webp')) ?>" alt="<?= bl_escape(bl_content_value('about', 'hero.image_alt')) ?>" width="2560" height="1707" fetchpriority="high">
    <figcaption><?= bl_content_text('about', 'hero.caption') ?></figcaption>
  </figure>
  <dl class="bl-about-plate bl-shell" aria-label="BOB-LIFT at a glance">
    <?php foreach ($companyFacts as $fact): ?><div><dt><?= bl_escape($fact['label']) ?></dt><dd<?= ctype_digit(substr($fact['value'], 0, 1)) ? '' : ' class="bl-about-plate-word"' ?>><?= bl_escape($fact['value']) ?><?php if ($fact['unit']): ?><small><?= bl_escape($fact['unit']) ?></small><?php endif; ?></dd></div><?php endforeach; ?>
  </dl>
  </div>
</section>

<section class="bl-about-group" id="how-we-fit" aria-labelledby="how-we-fit-title">
  <div class="bl-shell">
    <div class="bl-about-group-head">
      <div class="bl-reveal"><p class="bl-eyebrow"><?= bl_content_text('about', 'group.eyebrow') ?></p><h2 id="how-we-fit-title"><?= bl_content_text('about', 'group.line1') ?><br><span><?= bl_content_text('about', 'group.line2') ?></span></h2></div>
      <div class="bl-about-group-copy bl-reveal"><?php $aboutPage = bl_is_wordpress() ? get_queried_object() : null; $customCompany = bl_is_wordpress() ? bl_page_editor_content() : ''; if ($aboutPage instanceof WP_Post && post_password_required($aboutPage)): /* a protected page offers WordPress's password form */ ?><?= get_the_password_form($aboutPage) ?><?php elseif ($customCompany): ?><?= wp_kses_post($customCompany) ?><?php else: ?><p class="bl-lead">BOB-LIFT is one company with two lines of machines. Truck-mounted cranes are its core business; compact equipment is its second line.</p><p>This website covers the compact line on its own: mini skid steer loaders and mini dumpers, their specifications, and a direct line to the team that builds them.</p><?php endif; ?></div>
    </div>
    <ul class="bl-about-tree bl-reveal">
      <li class="bl-tree-root">
        <div class="bl-tree-root-card">
          <div class="bl-tree-root-photo"><img src="<?= bl_escape(bl_content_asset('about', 'group.image', 'images/company/boblift-workshop-aerial-mobile.webp')) ?>" alt="<?= bl_escape(bl_content_value('about', 'factory.image_alt_2')) ?>" width="1280" height="720" loading="lazy"></div>
          <div class="bl-tree-root-name"><span><?= bl_content_text('about', 'group.company_label') ?></span><h3><?= bl_content_text('about', 'group.company_brand') ?></h3><p><?= bl_escape(bl_company('name')) ?></p></div>
          <a class="bl-tree-link" href="<?= bl_company_url('website') ?>" target="_blank" rel="noopener"><span><?= bl_content_text('about', 'group.website_label') ?></span><?= bl_content_text('about', 'group.website_domain') ?><?= bl_icon('arrow-up-right') ?></a>
        </div>
        <ul class="bl-tree-branches">
          <li class="bl-tree-branch"><span><?= bl_content_text('about', 'group.cranes_label') ?></span><h3><?= bl_content_text('about', 'group.cranes_title') ?></h3><p><?= bl_content_text('about', 'group.cranes_text') ?></p></li>
          <li class="bl-tree-branch bl-tree-here" aria-current="page"><span><?= bl_content_text('about', 'group.loader_label') ?><small><?= bl_content_text('about', 'group.current_label') ?></small></span><h3><?= bl_content_text('about', 'group.loader_title') ?></h3><p><?= bl_content_text('about', 'group.loader_text') ?></p><div class="bl-tree-here-links"><?php foreach ($families as $family): ?><a href="<?= bl_escape($family['url']) ?>"><b><?= $family['count'] ?></b> <?= bl_escape($family['label']) ?> <?= bl_icon('arrow-up-right') ?></a><?php endforeach; ?></div></li>
        </ul>
      </li>
    </ul>
    <?php $guideUrl = bl_content_value('about', 'group.guide_url'); if ($guideUrl !== ''): ?><p class="bl-about-group-guide bl-reveal"><a class="bl-text-link" href="<?= bl_escape(bl_application_link($guideUrl)) ?>"><?= bl_content_text('about', 'group.guide_label') ?></a></p><?php endif; ?>
    <ol class="bl-about-promises">
      <li class="bl-reveal"><span>01</span><h3><?= bl_content_text('about', 'promises.company_title') ?></h3><p><?= bl_company_text('about', 'promises.company_text') ?></p></li>
      <li class="bl-reveal"><span>02</span><h3><?= bl_content_text('about', 'promises.order_title') ?></h3><p><?= bl_content_text('about', 'promises.order_text') ?></p></li>
      <li class="bl-reveal"><span>03</span><h3><?= bl_content_text('about', 'promises.team_title') ?></h3><p><a href="<?= bl_escape(bl_mailto(bl_company('email'))) ?>"><?= bl_escape(bl_company('email')) ?></a> <?= bl_content_text('about', 'promises.team_text') ?> <a href="<?= bl_escape(bl_application_link(bl_content_value('about', 'promises.service_url'))) ?>"><?= bl_content_text('about', 'promises.service_label') ?></a> <?= bl_content_text('about', 'promises.team_suffix') ?></p></li>
    </ol>
  </div>
</section>

<section class="bl-about-factory bl-shell" aria-labelledby="factory-title">
  <div class="bl-about-factory-copy bl-reveal">
    <p class="bl-eyebrow"><?= bl_content_text('about', 'factory.eyebrow') ?></p>
    <h2 id="factory-title"><?= bl_content_text('about', 'factory.line1') ?><br><span class="bl-blue-text"><?= bl_content_text('about', 'factory.line2') ?></span></h2>
    <p><?= bl_content_text('about', 'factory.text') ?></p>
    <a class="bl-text-link" href="<?= bl_escape(bl_application_link(bl_content_value('about', 'factory.link_url'))) ?>"><?= bl_content_text('about', 'factory.link_label') ?><?= bl_icon('arrow-up-right') ?></a>
  </div>
  <div class="bl-about-factory-photos bl-reveal">
    <img srcset="<?= bl_escape(bl_content_asset('about', 'factory.assembly_small', 'images/about/assembly-900.webp')) ?> 900w, <?= bl_escape(bl_content_asset('about', 'factory.assembly', 'images/about/assembly-1600.webp')) ?> 1600w" sizes="(max-width: 1000px) 100vw, 40vw" src="<?= bl_escape(bl_content_asset('about', 'factory.assembly', 'images/about/assembly-1600.webp')) ?>" alt="<?= bl_escape(bl_content_value('about', 'factory.image_alt_3')) ?>" width="1600" height="1067" loading="lazy">
    <img src="<?= bl_escape(bl_content_asset('about', 'factory.welding', 'images/about/weld-900.webp')) ?>" alt="<?= bl_escape(bl_content_value('about', 'factory.image_alt_4')) ?>" width="900" height="600" loading="lazy">
    <img src="<?= bl_escape(bl_content_asset('about', 'factory.painting', 'images/about/paint-900.webp')) ?>" alt="<?= bl_escape(bl_content_value('about', 'factory.image_alt_5')) ?>" width="900" height="600" loading="lazy">
  </div>
</section>

<?php require __DIR__ . '/../partials/page-cta.php'; ?>
