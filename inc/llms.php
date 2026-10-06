<?php
declare(strict_types=1);

/**
 * /llms.txt: a Markdown map of the site for AI assistants (llmstxt.org format). The opening text can be replaced through the
 * bl_llms_txt_intro setting (REST /wp/v2/settings); the lists are built from the live products, applications, cases and news
 * on every request. Served only while the site is public, so a coming-soon site lists nothing. Google does not read
 * llms.txt, so the response carries noindex to keep the plain-text file out of search results.
 */

const BL_LLMS_TXT_INTRO_OPTION = 'bl_llms_txt_intro';

function bl_llms_txt_default_intro(): string
{
    return <<<'MD'
BOB-LIFT Loader is the mini skid steer loader and mini dumper line of Xuzhou BOB-LIFT Construction Machinery Co., Ltd., a manufacturer in Xuzhou, Jiangsu, China. Machines are welded, painted and assembled at the company's own factory in Xuzhou and built to order for export.

Key facts:
- Mini skid steer loaders rated from 200 to 500 kg: stand-on, tracked and wheeled seated models
- Mini dumpers rated from 0.5 to 5 tonnes, including tracked and mixer models
- Loader model names read BL-{running gear}{size class}-{operator position}: T tracked, S wheeled; H stand-on, C seated cabin
- Specifications on each product page are the published figures; confirm configuration and availability with the sales team
- Prices are quoted per order; there is no public price list
MD;
}

/** One line of plain text: tags stripped, entities decoded, whitespace collapsed, square brackets kept out of link text. */
function bl_llms_txt_line(string $text): string
{
    $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return strtr(trim((string) preg_replace('/\s+/u', ' ', $text)), ['[' => '(', ']' => ')']);
}

function bl_llms_txt_link(string $title, string $path, string $description = ''): string
{
    $line = '- [' . bl_llms_txt_line($title) . '](' . bl_public_base() . $path . ')';
    $description = bl_llms_txt_line($description);
    return $description !== '' ? $line . ': ' . $description : $line;
}

function bl_llms_txt(): string
{
    $applications = bl_apply_application_content($GLOBALS['bl_application_base'] ?? []);
    $intro = bl_is_wordpress() ? trim((string) get_option(BL_LLMS_TXT_INTRO_OPTION, '')) : '';
    $products = ['skid-steer-loaders' => [], 'mini-dumpers' => []];
    foreach (bl_products() as $product) {
        $products[$product['category'] === 'mini-dumpers' ? 'mini-dumpers' : 'skid-steer-loaders'][] = bl_llms_txt_link(bl_product_page_title($product), '/product/' . $product['slug'] . '/', bl_product_description($product));
    }
    $sections = [
        'Skid steer loaders' => [bl_llms_txt_link('Skid Steer Loaders', '/products/skid-steer-loaders/', 'All mini skid steer loader models with specifications side by side'), ...$products['skid-steer-loaders']],
        'Mini dumpers' => [bl_llms_txt_link('Mini Dumpers', '/products/mini-dumpers/', 'All mini dumper models with specifications side by side'), ...$products['mini-dumpers']],
        'Applications' => array_map(static fn($app) => bl_llms_txt_link($app['title'], '/applications/' . bl_application_slug($app) . '/', $app['description']), $applications),
        'Application examples' => array_map(static fn($case) => bl_llms_txt_link($case['title'], '/application/' . $case['slug'] . '/', $case['excerpt']), bl_application_cases()),
        'News' => array_map(static fn($article) => bl_llms_txt_link($article['title'], '/news/' . $article['slug'] . '/', $article['excerpt']), bl_articles()),
        'Company' => [
            bl_llms_txt_link('About BOB-LIFT Loader', '/about/', 'The company, factory and team behind the machines'),
            bl_llms_txt_link('Support', '/support/', 'Ordering, shipping, warranty, parts and after-delivery service'),
            bl_llms_txt_link('Contact', '/contact/', 'Enquiry form for quotations and specifications'),
            '- Email: ' . bl_company('email'),
            '- Phone and WhatsApp: ' . bl_company('phone'),
            '- Parent company website: https://www.boblift.com/',
        ],
    ];
    $out = "# BOB-LIFT Loader\n\n> " . bl_llms_txt_line(bl_editable('home')['seo']['description']) . "\n\n" . ($intro !== '' ? $intro : bl_llms_txt_default_intro()) . "\n";
    foreach ($sections as $heading => $lines) {
        if ($lines) $out .= "\n## " . $heading . "\n\n" . implode("\n", $lines) . "\n";
    }
    return $out;
}

/** Answers /llms.txt and stops; any other path, or a site that is not public yet, is left to the normal routing. */
function bl_serve_llms_txt(string $path): void
{
    if ($path !== '/llms.txt' || !bl_indexable()) return;
    $body = bl_llms_txt();
    if (bl_is_wordpress()) status_header(200);
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex');
    header('Cache-Control: public, max-age=3600');
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'HEAD') echo $body;
    exit;
}
