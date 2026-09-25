"""Read-only checks for the routed frontend and its local WordPress rendering."""
import json
import re
import sys
import unittest
from html.parser import HTMLParser
from urllib.error import HTTPError
from urllib.parse import urlsplit
from urllib.request import build_opener, ProxyHandler, HTTPRedirectHandler
from xml.etree import ElementTree

BASE = sys.argv[1].rstrip('/') if len(sys.argv) > 1 else 'http://127.0.0.1:4173'
sys.argv = [sys.argv[0]]
OPENER = build_opener(ProxyHandler({}))


def visible_cards(html):
    """Catalogue pages render every model in scope and hide the ones outside the filters."""
    return [tag for tag in re.findall(r'<article class="bl-product-card"[^>]*>', html) if ' hidden' not in tag]


def fetch(path):
    try:
        with OPENER.open(BASE + path, timeout=30) as response:
            return response.status, response.read().decode(), response.headers
    except HTTPError as error:
        with error:
            return error.code, error.read().decode(), error.headers


class Page(HTMLParser):
    def __init__(self, html):
        super().__init__()
        self.h1 = 0
        self.titles = 0
        self.canonical = []
        self.descriptions = []
        self.robots = []
        self.links = []
        self.assets = []
        self.structured = []
        self.json_mode = False
        self.json_text = ''
        self.feed(html)

    def handle_starttag(self, tag, pairs):
        attrs = dict(pairs)
        self.h1 += tag == 'h1'
        self.titles += tag == 'title'
        if tag == 'link' and attrs.get('rel') == 'canonical':
            self.canonical.append(attrs.get('href'))
        if tag == 'meta':
            if attrs.get('name') == 'description': self.descriptions.append(attrs.get('content'))
            if attrs.get('name') == 'robots': self.robots.append(attrs.get('content'))
        if tag == 'a': self.links.append(attrs.get('href', ''))
        if tag == 'img': self.assets.append(attrs.get('src', ''))
        if tag == 'script' and attrs.get('type') == 'application/ld+json':
            self.json_mode = True
            self.json_text = ''

    def handle_data(self, text):
        if self.json_mode: self.json_text += text

    def handle_endtag(self, tag):
        if tag == 'script' and self.json_mode:
            self.json_mode = False
            self.structured.append(json.loads(self.json_text))


class WebsiteTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        with open('inc/data/products.json') as source: cls.products = json.load(source)
        with open('inc/data/news.json') as source: cls.articles = json.load(source)
        cls.paths = ['/', '/about/', '/products/skid-steer-loaders/', '/products/mini-dumpers/', '/applications/', '/applications/landscaping/', '/applications/site-preparation/', '/applications/material-transport/', '/news/', '/contact/', '/privacy/']
        cls.paths += ['/product/' + p['slug'] + '/' for p in cls.products]
        cls.paths += ['/news/' + a['slug'] + '/' for a in cls.articles]

    def test_all_pages_and_metadata(self):
        titles = set()
        for path in self.paths:
            with self.subTest(path=path):
                status, html, _ = fetch(path)
                page = Page(html)
                self.assertEqual(status, 200)
                self.assertEqual(page.h1, 1)
                self.assertEqual(page.titles, 1)
                self.assertEqual(len(page.canonical), 1)
                self.assertEqual(urlsplit(page.canonical[0]).path, path)
                self.assertEqual(len(page.descriptions), 1)
                self.assertGreater(len(page.descriptions[0]), 25)
                self.assertTrue(any('noindex' in value for value in page.robots))
                self.assertNotRegex(html, r'(?:Warning|Fatal error|Notice):')
                self.assertNotIn('QA-PERSISTENCE', html)
                self.assertNotIn('qa@example.com', html)
                self.assertTrue(page.structured)
                graph = page.structured[0]['@graph']
                organization = next(item for item in graph if item['@type'] == 'Organization')
                website = next(item for item in graph if item['@type'] == 'WebSite')
                self.assertEqual(organization['url'], 'https://www.boblift.com/')
                self.assertEqual(website['publisher']['@id'], organization['@id'])
                titles.add(re.search(r'<title>(.*?)</title>', html).group(1))
        self.assertEqual(len(titles), len(self.paths))

    def test_real_product_records_and_specifications(self):
        self.assertEqual(len(self.products), 18)
        self.assertEqual(sum(p['category'] == 'skid-steer-loaders' for p in self.products), 8)
        for product in self.products:
            status, html, _ = fetch('/product/' + product['slug'] + '/')
            self.assertEqual(status, 200)
            self.assertIn(product['model'], html)
            # Sources stay in the data for provenance; product pages do not send buyers to another site.
            if product['source']: self.assertNotIn('href="' + product['source'], html)
            for label, value in product['specs'].items():
                self.assertIn(value.replace('&', '&amp;'), html)
            schema = next(item for item in Page(html).structured[0]['@graph'] if item['@type'] == 'Product')
            self.assertNotIn('offers', schema)
            self.assertNotIn('aggregateRating', schema)

    def test_search_filters_and_empty_state(self):
        _, html, _ = fetch('/products/?q=BL-T1000-H')
        self.assertEqual(len(visible_cards(html)), 1)
        self.assertIn('BL-T1000-H', html)
        # The loader page explains the naming; product pages decode their own model and link to it.
        _, html, _ = fetch('/products/skid-steer-loaders/')
        self.assertIn('id="model-names"', html)
        _, html, _ = fetch('/product/bl-s390-c-mini-skid-steer-loader/')
        self.assertIn('<b>S</b>Wheeled', html)
        self.assertIn('<b>C</b>Cabin · seated', html)
        self.assertIn('#model-names', html)
        # Older literature drops the T / S letter: BL-390 finds both the wheeled and the tracked 390.
        _, html, _ = fetch('/products/?q=BL-390')
        self.assertEqual(len(visible_cards(html)), 2)
        _, html, _ = fetch('/products/mini-dumpers/?load=heavy')
        self.assertEqual(len(visible_cards(html)), 4)
        # Each family page compares only its own models; search results across both families have no table.
        _, html, _ = fetch('/products/?q=mini')
        self.assertNotIn('bl-compare-table', html)
        _, html, _ = fetch('/products/skid-steer-loaders/')
        self.assertIn('id="compare-skid-steer-loaders"', html)
        self.assertNotIn('id="compare-mini-dumpers"', html)
        self.assertEqual(re.search(r'<table class="bl-compare-table">.*?</thead>', html, re.S).group(0).count('<th scope="col">'), 9)
        # Loader filters: running gear, operator position and load band, combinable, with counts per chip. Width is the range chart's job.
        _, html, _ = fetch('/products/skid-steer-loaders/?gear=tracked&operator=seated')
        self.assertEqual(len(visible_cards(html)), 2)
        self.assertIn('data-result-count>2<', html)
        _, html, _ = fetch('/products/skid-steer-loaders/')
        self.assertNotIn('name="width"', html)
        _, html, _ = fetch('/products/skid-steer-loaders/?gear=wheeled&operator=stand-on')
        self.assertEqual(len(visible_cards(html)), 0)
        self.assertRegex(html, r'<div class="bl-empty" data-catalog-empty>')
        _, html, _ = fetch('/products/skid-steer-loaders/?gear=not-an-option')
        self.assertEqual(len(visible_cards(html)), 8)
        _, html, _ = fetch('/products/?q=not-a-real-model')
        self.assertIn('No matching equipment.', html)
        _, html, _ = fetch('/products/?q[]=unexpected')
        self.assertNotIn('Fatal error', html)

    def test_catalogue_layout(self):
        # Search results group by family; family pages carry an on-this-page bar whose links all land on real sections.
        _, html, _ = fetch('/products/?q=mini')
        self.assertEqual(html.count('class="bl-catalog-group-head"'), 2)
        self.assertNotIn('data-section-nav', html)
        for path, expected in [('/products/skid-steer-loaders/', 5), ('/products/mini-dumpers/', 3)]:
            _, html, _ = fetch(path)
            targets = re.findall(r'href="#([\w-]+)" data-section-link', html)
            self.assertEqual(len(targets), expected, path)
            for target in targets: self.assertIn(f'id="{target}"', html, target)
        # Cards title the model once; the rest of the name is the line beneath it.
        _, html, _ = fetch('/products/skid-steer-loaders/')
        self.assertIn('<h2><a href="/product/bl-t360-h-mini-skid-steer-loader/">BL-T360-H</a></h2><p class="bl-product-card-line">Tracked Stand-on Mini Skid Steer Loader</p>', html)
        _, html, _ = fetch('/contact/?equipment=dumper')
        self.assertRegex(html, r'value="dumper"\s+checked')

    def test_header_search_index(self):
        _, html, _ = fetch('/news/')
        # Every page carries the header search; its buttons stay hidden until the script can run them.
        self.assertIn('id="site-search"', html)
        self.assertRegex(html, r'<button class="bl-search-toggle"[^>]*data-open-search[^>]*hidden>')
        index = json.loads(re.search(r'<script type="application/json" id="site-search-index">(.*?)</script>', html, re.S).group(1))
        models = {entry['title']: entry for entry in index if entry['kind'] in ('Skid steer loader', 'Mini dumper')}
        self.assertEqual(len(models), len(self.products))
        # Older loader names resolve: BL-390 on the old product pages, BL-T360 at the fair.
        self.assertEqual(models['BL-S390-C']['legacy'], ['BL-390', 'BL-S390'])
        self.assertIn('BL-T360', models['BL-T360-H']['legacy'])
        self.assertIn('Application', {entry['kind'] for entry in index})
        # The catalogue has no search box of its own; a search that lands there shows as a removable term.
        _, html, _ = fetch('/products/skid-steer-loaders/')
        self.assertNotIn('type="search"', html.split('id="site-search"')[0])
        _, html, _ = fetch('/products/?q=FY-30')
        self.assertIn('data-catalog-query', html)
        self.assertEqual(len(visible_cards(html)), 1)

    def test_quote_context_and_input_escaping(self):
        _, html, _ = fetch('/contact/?product=bl-t1000-h-mini-skid-steer-loader')
        self.assertIn('name="model" value="BL-T1000-H"', html)
        self.assertIn('BL-T1000-H Tracked Stand-on Mini Skid Steer Loader', html)
        _, html, _ = fetch('/contact/?application=Material%20transport')
        self.assertRegex(html, r'value="dumper"\s+checked')
        _, html, _ = fetch('/products/?q=%3Cscript%3Ealert(1)%3C/script%3E')
        self.assertNotIn('<script>alert(1)</script>', html)

    def test_about_explains_the_group_and_the_compact_line(self):
        _, html, _ = fetch('/about/')
        main = html.split('<main', 1)[1].split('</main>', 1)[0]
        # The group relationship is explained once: one link to boblift.com in the page body, none to sister sites.
        self.assertEqual(main.count('href="https://www.boblift.com'), 1)
        self.assertNotIn('bobliftcrane.com', main)
        self.assertIn('YOU ARE HERE', main)
        self.assertIn('factory in Xuzhou', main)
        # Product detail and the production story live on the family pages; the about page links to the loader line.
        self.assertNotIn('bl-ruler', main)
        self.assertNotIn('data-making-step', main)
        self.assertIn('/products/skid-steer-loaders/#how-they-are-made', main)
        self.assertIn('/css/about.css', html)
        _, home, _ = fetch('/')
        self.assertNotIn('/css/about.css', home)
        # Every responsive size of the workshop photography resolves, not only the fallback src.
        for asset in set(re.findall(r'/assets/images/about/[\w-]+\.webp', html)):
            with OPENER.open(BASE + asset, timeout=30) as response:
                self.assertEqual(response.status, 200, asset)

    def test_range_charts_take_the_visitors_number(self):
        def mm(value): return int(re.search(r'([\d,]+)', value).group(1).replace(',', ''))
        def rows(html): return re.findall(r'<li class="bl-ruler-(hit|miss)( bl-ruler-over)?"[^>]*data-value="(\d+)" data-sub="(\d+)"', html)
        loaders = [p for p in self.products if p['category'] == 'skid-steer-loaders']
        # Loaders: the marker is the narrowest opening, one metre by default. The body has to fit; a wider bucket is flagged.
        for query, opening in (('', 1000), ('?opening=950', 950), ('?opening=99999', 1300)):
            _, html, _ = fetch('/products/skid-steer-loaders/' + query)
            found = rows(html)
            self.assertEqual(len(found), len(loaders))
            for state, over, body, bucket in found:
                self.assertEqual(state == 'hit', int(body) <= opening)
                self.assertEqual(bool(over), state == 'hit' and int(bucket) > opening)
            self.assertIn(f'fit through {opening:,} mm', html)
        self.assertEqual(sum(mm(p['specs']['Machine width']) <= 950 for p in loaders), sum(s == 'hit' for s, *_ in rows(fetch('/products/skid-steer-loaders/?opening=950')[1])))
        # Dumpers: no marker until a load is given; then models rated below it drop out.
        _, html, _ = fetch('/products/mini-dumpers/')
        self.assertNotIn('bl-ruler-marked', html)
        self.assertTrue(all(s == 'hit' for s, *_ in rows(html)))
        _, html, _ = fetch('/products/mini-dumpers/?payload=2000')
        self.assertIn('bl-ruler-marked', html)
        for state, _, load, _ in rows(html): self.assertEqual(state == 'hit', int(load) >= 2000)
        self.assertIn('load=2000&amp;equipment=dumper', html)
        # The enquiry opens with the site numbers written in; implausible values are ignored.
        _, html, _ = fetch('/contact/?product=bl-t360-h-mini-skid-steer-loader&application=Landscaping&width=1000&load=200&ground=soft')
        self.assertIn('The narrowest opening on my site is 1,000 mm. My heaviest load is about 200 kg. The ground is soft or sloped.', html)
        _, html, _ = fetch('/contact/?width=99999&load=-1')
        self.assertNotIn('narrowest opening on my site', html)

    def test_applications_describe_the_job_and_point_to_the_range(self):
        def mm(value): return int(re.search(r'([\d,]+)', value).group(1).replace(',', ''))
        loaders = [p for p in self.products if p['category'] == 'skid-steer-loaders']
        widest = min(max(mm(p['specs']['Machine width']), mm(p['specs']['Bucket width'])) for p in loaders)
        _, html, _ = fetch('/applications/')
        main = html.split('<main', 1)[1].split('</main>', 1)[0]
        # Headline figures come from the specifications; model selection lives on the family pages, not here.
        self.assertIn(f'{widest:,}<small>mm', main)
        self.assertNotIn('type="range"', main)
        self.assertNotIn('boblift.com/', main)
        self.assertIn('/css/applications.css', html)
        _, home, _ = fetch('/')
        self.assertNotIn('/css/applications.css', home)
        # Each job lists its machines in the order that matters to it and hands over to the family page.
        _, html, _ = fetch('/applications/landscaping/')
        cards = re.findall(r'<article class="bl-product-card">\s*<a class="bl-product-card-image" href="/product/([^/]+)/', html)
        by_width = sorted(loaders, key=lambda p: (max(mm(p['specs']['Machine width']), mm(p['specs']['Bucket width'])), p['model']))
        self.assertEqual(cards, [p['slug'] for p in by_width[:3]])
        self.assertIn('/products/skid-steer-loaders/#about-the-range', html)
        self.assertNotIn('type="range"', html.split('<main', 1)[1].split('</main>', 1)[0])
        _, html, _ = fetch('/applications/material-transport/')
        self.assertIn('/products/mini-dumpers/#about-the-range', html)

    def test_family_pages_introduce_their_range(self):
        loaders = [p for p in self.products if p['category'] == 'skid-steer-loaders']
        loaded_dumpers = [p for p in self.products if p['category'] == 'mini-dumpers' and p['load']]
        _, html, _ = fetch('/products/skid-steer-loaders/')
        # One width row per loader, one payload row per dumper with a rated load; the intro sits before the comparison.
        self.assertEqual(html.count('<li class="bl-ruler-'), len(loaders))
        self.assertLess(html.index('id="about-the-range"'), html.index('id="compare-skid-steer-loaders"'))
        self.assertIn('Painted to order.', html)
        # Loader production photography only: both workshop halls build loaders, none has been supplied for dumpers.
        self.assertEqual(html.count('class="bl-making-figure '), 6)
        self.assertLess(html.index('id="about-the-range"'), html.index('id="how-they-are-made"'))
        self.assertLess(html.index('id="how-they-are-made"'), html.index('id="compare-skid-steer-loaders"'))
        for asset in set(re.findall(r'/assets/images/about/[\w-]+\.webp', html)):
            with OPENER.open(BASE + asset, timeout=30) as response:
                self.assertEqual(response.status, 200, asset)
        _, html, _ = fetch('/product/bl-t360-h-mini-skid-steer-loader/')
        self.assertIn('#how-they-are-made', html)
        _, html, _ = fetch('/product/' + next(p['slug'] for p in self.products if p['category'] == 'mini-dumpers') + '/')
        self.assertNotIn('#how-they-are-made', html)
        _, html, _ = fetch('/products/mini-dumpers/')
        self.assertNotIn('id="how-they-are-made"', html)
        self.assertEqual(html.count('<li class="bl-ruler-'), len(loaded_dumpers))
        self.assertNotIn('Painted to order.', html)
        _, html, _ = fetch('/products/?q=mini')
        self.assertNotIn('id="about-the-range"', html)

    def test_range_opens_on_the_loaders(self):
        # /products/ goes straight to the loaders, the main line; a header search there still spans both families.
        class Stay(HTTPRedirectHandler):
            def redirect_request(self, *args, **kwargs): return None
        for path in ('/products/', '/products/?load=light'):
            try:
                build_opener(ProxyHandler({}), Stay).open(BASE + path, timeout=30)
                self.fail(path + ' did not redirect')
            except HTTPError as error:
                self.assertEqual(error.code, 301)
                self.assertTrue(error.headers['Location'].endswith('/products/skid-steer-loaders/'))
        _, html, _ = fetch('/products/?q=FY')
        self.assertTrue(visible_cards(html))
        # Every page's Equipment link opens the loaders and its panel names both families; the switch marks the current one.
        _, html, _ = fetch('/about/')
        self.assertIn('<a href="/products/skid-steer-loaders/" >Equipment', html)
        panel = html.split('class="bl-nav-panel"', 1)[1].split('</div>', 1)[0]
        self.assertIn('/products/skid-steer-loaders/', panel)
        self.assertIn('/products/mini-dumpers/', panel)
        for family in ('skid-steer-loaders', 'mini-dumpers'):
            _, html, _ = fetch('/products/' + family + '/')
            switch = html.split('class="bl-family-switch"', 1)[1].split('</nav>', 1)[0]
            self.assertEqual(re.findall(r'href="/products/([a-z-]+)/" aria-current="page"', switch), [family])
        self.assertNotIn('href="/products/"', html.split('<footer', 1)[1])
        _, sitemap, _ = fetch('/sitemap.xml')
        self.assertNotIn('/products/</loc>', sitemap)

    def test_news_has_sources_and_dates(self):
        for article in self.articles:
            _, html, _ = fetch('/news/' + article['slug'] + '/')
            self.assertIn(article['source'], html)
            self.assertIn(article['date'], html)
        # One list, newest first. Section tabs appear once a section has a story; group stories sit under All only.
        _, html, _ = fetch('/news/')
        main = html.split('<main', 1)[1].split('</main>', 1)[0]
        ordered = sorted(self.articles, key=lambda a: a['date'], reverse=True)
        self.assertEqual(re.findall(r'<a class="bl-news-item" href="/news/([^/]+)/"', main), [a['slug'] for a in ordered])
        self.assertEqual(main.count('>BOB-LIFT group<'), sum(not a['category'] for a in self.articles))
        sections = {a['category'] for a in self.articles if a['category']}
        self.assertEqual(set(re.findall(r'\?category=([a-z-]+)"', main)), sections)
        self.assertIn('/css/news.css', html)
        for section in sections:
            _, html, _ = fetch('/news/?category=' + section)
            main = html.split('<main', 1)[1].split('</main>', 1)[0]
            self.assertEqual(len(re.findall(r'class="bl-news-item"', main)), sum(a['category'] == section for a in self.articles))
            self.assertIn('noindex', html)
        _, html, _ = fetch('/news/?category=company-news')
        self.assertEqual(len(re.findall(r'class="bl-news-item"', html)), len(self.articles))
        # An event story carries its details, a schema.org Event and the source of its dates; nothing is guessed.
        fair = next(a for a in self.articles if a.get('event'))
        _, html, _ = fetch('/news/' + fair['slug'] + '/')
        main = html.split('<main', 1)[1].split('</main>', 1)[0]
        self.assertIn(fair['event']['booth'], main)
        self.assertIn('15–19 October 2026', main)
        self.assertIn(fair['event']['source'], main)
        self.assertIn('Written by', main)
        for slug in fair['products']:
            self.assertIn('/product/' + slug + '/', main.split('Related products', 1)[1])
        event = next(item for item in Page(html).structured[0]['@graph'] if item['@type'] == 'Event')
        self.assertEqual((event['startDate'], event['endDate']), (fair['event']['start'], fair['event']['end']))
        # Group stories are labelled as such and link to boblift.com once in the page body.
        for article in (a for a in self.articles if not a['category']):
            _, html, _ = fetch('/news/' + article['slug'] + '/')
            main = html.split('<main', 1)[1].split('</main>', 1)[0]
            self.assertEqual(main.count('href="https://www.boblift.com'), 1)
            self.assertIn('>BOB-LIFT group<', main)
            self.assertNotIn('Compact equipment team', main)

    def test_not_found_has_real_http_status(self):
        for path in ['/not-a-page/', '/product/nonexistent/', '/news/nonexistent/', '/inc/data/products.json']:
            status, html, _ = fetch(path)
            self.assertEqual(status, 404, path)
            self.assertEqual(Page(html).canonical, [])

    def test_asset_paths_resolve(self):
        paths = set()
        for page_path in self.paths:
            _, html, _ = fetch(page_path)
            for asset in Page(html).assets:
                if asset.startswith('/') or asset.startswith(BASE): paths.add(urlsplit(asset).path)
        for path in paths:
            with OPENER.open(BASE + path, timeout=30) as response:
                self.assertEqual(response.status, 200, path)
                self.assertTrue(response.headers.get_content_type().startswith('image/'), path)


if __name__ == '__main__':
    unittest.main(verbosity=2)
