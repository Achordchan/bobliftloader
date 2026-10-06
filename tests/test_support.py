"""Read-only checks for the Support page and the pieces the equipment pages share with it."""
import json
import re
import sys
import unittest
from urllib.error import HTTPError
from urllib.request import build_opener, ProxyHandler

BASE = sys.argv[1].rstrip('/') if len(sys.argv) > 1 else 'http://127.0.0.1:4173'
sys.argv = [sys.argv[0]]
OPENER = build_opener(ProxyHandler({}))


def fetch(path):
    try:
        with OPENER.open(BASE + path, timeout=30) as response:
            return response.status, response.read().decode(errors='replace'), response.headers
    except HTTPError as error:
        with error:
            return error.code, error.read().decode(errors='replace'), error.headers


def structured(html):
    return [json.loads(block) for block in re.findall(r'<script type="application/ld\+json">(.*?)</script>', html, re.S)]


class SupportTests(unittest.TestCase):
    def test_support_page_sections_and_terms(self):
        status, html, _ = fetch('/support/')
        self.assertEqual(status, 200)
        # The on-this-page bar lands on real sections, and the plate carries the confirmed buying terms.
        targets = re.findall(r'href="#([\w-]+)" data-section-link', html)
        self.assertEqual(targets, ['buying', 'after-delivery', 'downloads', 'faq'])
        for target in targets + ['containers']: self.assertIn(f'id="{target}"', html)
        plate = re.search(r'<dl class="bl-sup-plate.*?</dl>', html, re.S).group(0)
        for text in ['Minimum order', '>1<small>unit', 'T/T', '8–30<small>working days', '12<small>months']: self.assertIn(text, plate)
        self.assertIn('aria-current="page">Support', html.replace('<span class="bl-nav-index">04</span>', ''))

    def test_container_table_covers_every_model(self):
        with open('inc/data/products.json') as source: products = json.load(source)
        _, html, _ = fetch('/support/')
        rows = re.findall(r'<tr><th scope="row"><a href="/product/([a-z0-9-]+)/">', html)
        self.assertEqual(sorted(rows), sorted(p['slug'] for p in products))

    def test_faq_structured_data_matches_the_page(self):
        _, html, _ = fetch('/support/')
        graph = structured(html)[0]['@graph']
        faq = next(node for node in graph if node['@type'] == 'FAQPage')
        questions = [q['name'] for q in faq['mainEntity']]
        self.assertEqual(questions, [re.sub('&#0?39;', "'", q) for q in re.findall(r'<summary>(.*?)<img', html)])
        self.assertGreaterEqual(len(questions), 8)

    def test_downloads_resolve(self):
        _, html, _ = fetch('/support/')
        files = re.search(r'<ul class="bl-sup-files">.*?</ul>', html, re.S).group(0)
        catalogue = re.search(r'href="([^"]+\.pdf)" download', files).group(1)
        status, _, headers = fetch(catalogue)
        self.assertEqual((status, headers['Content-Type']), (200, 'application/pdf'))
        for href in re.findall(r'href="(/[^"#]*)', files):
            self.assertEqual(fetch(href)[0], 200, href)

    def test_attachments_sit_on_the_loader_page_only(self):
        _, html, _ = fetch('/products/skid-steer-loaders/')
        section = re.search(r'<section class="bl-attachments.*?</section>', html, re.S).group(0)
        images = re.findall(r'src="(/assets/images/attachments/[a-z0-9-]+\.webp)"', section)
        self.assertEqual(len(images), 24)
        for image in images: self.assertEqual(fetch(image)[0], 200, image)
        self.assertIn('href="#attachments" data-section-link', html)
        _, html, _ = fetch('/products/mini-dumpers/')
        self.assertNotIn('id="attachments"', html)

    def test_product_videos_and_buying_terms(self):
        _, html, _ = fetch('/product/bl-t360-h-mini-skid-steer-loader/')
        videos = re.findall(r'data-gallery-video="([^"]+)"', html)
        self.assertEqual(len(videos), 5)
        for video in videos:
            status, _, headers = fetch(video)
            self.assertEqual((status, headers['Content-Type']), (200, 'video/mp4'), video)
        self.assertIn('<video id="product-video"', html)
        self.assertIn('whole-machine warranty', html)
        self.assertIn('href="/support/">How buying works', html)
        self.assertIn('data-print-sheet hidden', html)
        # A model without videos has no video strip or player.
        _, html, _ = fetch('/product/3-ton-mini-dumper/')
        self.assertNotIn('bl-gallery-videos', html)
        self.assertNotIn('<video', html)

    def test_service_enquiries_and_links_to_support(self):
        _, html, _ = fetch('/contact/?topic=service')
        self.assertRegex(html, r'value="service"\s+checked')
        self.assertIn('Serial number:', html)
        for path in ['/', '/about/']:
            _, html, _ = fetch(path)
            self.assertIn('href="/support/#after-delivery"', html, path)
        _, html, _ = fetch('/about/')
        self.assertNotIn('id="certificates"', html)  # the certificates on file cover cranes only
        _, html, _ = fetch('/sitemap.xml')
        self.assertIn('/support/</loc>', html)


if __name__ == '__main__':
    unittest.main(verbosity=2)
