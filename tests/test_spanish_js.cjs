'use strict';
// The scripts on a Spanish page (inc/i18n.php) in a real browser: the header search finds and ranks translated results by
// their Spanish words (accents or not) and their English ones, and the language suggestion card (assets/js/language-suggest.js)
// links to the counterpart page keeping the enquiry's choices and the place on the page.
// Run: node tests/test_spanish_js.cjs   (Playwright and Chromium: PLAYWRIGHT_BROWSERS_PATH or CHROMIUM=/path/to/chromium)
const http = require('node:http');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const root = path.join(__dirname, '..');
// The search index as the translator leaves it: Spanish text, the English kept in `en`; 1.5 t listed before 5 t.
const index = [
  { kind: 'Minidumper', title: 'FY15-L', text: 'Minidumper sobre ruedas de 1.5 t', en: { text: '1.5 Ton Mini Dumper' }, url: '/es/product/1-5-ton/', legacy: [] },
  { kind: 'Minidumper', title: 'FY-50', text: 'Minidumper sobre ruedas de 5 t', en: { text: '5 Ton Mini Dumper' }, url: '/es/product/5-ton/', legacy: [] },
  { kind: 'Aplicación', title: 'Paisajismo', text: 'Obra y jardín', en: { title: 'Landscaping' }, url: '/es/applications/landscaping/' },
];
const search = '<dialog id="site-search"><form><input name="q"></form><p data-search-note hidden></p><ul id="site-search-results"></ul>'
  + '<div data-search-start></div><div data-search-empty hidden><strong data-search-empty-query></strong></div><button data-close-search></button></dialog>'
  + `<script type="application/json" id="site-search-index">${JSON.stringify(index)}</script>`;
const page = (lang, body, script) => `<!doctype html><html lang="${lang}"><head><title>t</title>`
  + '<link rel="alternate" hreflang="en" href="/contact/"><link rel="alternate" hreflang="es" href="/es/contact/"><link rel="alternate" hreflang="x-default" href="/contact/">'
  + `</head><body class="bl-site">${body}<script type="module">${script}</script></body></html>`;
const server = http.createServer((request, response) => {
  const url = new URL(request.url, 'http://x');
  if (url.pathname.startsWith('/assets/')) {
    response.writeHead(200, { 'content-type': 'text/javascript' });
    response.end(readFileSync(path.join(root, url.pathname)));
    return;
  }
  response.writeHead(200, { 'content-type': 'text/html; charset=utf-8' });
  response.end(url.pathname.startsWith('/es/')
    ? page('es', search, 'import { initializeSearch } from "/assets/js/search.js"; initializeSearch(); document.querySelector("#site-search").showModal();')
    : page('en', '', 'import { initializeLanguageSuggest } from "/assets/js/language-suggest.js"; initializeLanguageSuggest();'));
});

(async () => {
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const origin = `http://127.0.0.1:${server.address().port}`;
  const browser = await chromium.launch(process.env.CHROMIUM ? { executablePath: process.env.CHROMIUM } : { executablePath: '/opt/pw-browsers/chromium' });
  try {
    // Search on a Spanish page.
    let context = await browser.newContext({ locale: 'es-MX' });
    let tab = await context.newPage();
    await tab.goto(`${origin}/es/products/mini-dumpers/`);
    const results = async (query) => {
      await tab.fill('#site-search input[name="q"]', query);
      return tab.$$eval('#site-search-results a', (links) => links.map((link) => link.getAttribute('href')));
    };
    assert.equal((await results('5 ton'))[0], '/es/product/5-ton/', 'an English query ranks the 5 t model first');
    assert.equal((await results('5 t'))[0], '/es/product/5-ton/', 'a Spanish query ranks the 5 t model first');
    assert.deepEqual(await results('aplicacion'), ['/es/applications/landscaping/'], 'an unaccented query finds an accented word');
    assert.deepEqual(await results('jardin'), ['/es/applications/landscaping/'], 'accented text is found without the accent');
    assert.deepEqual(await results('landscaping'), ['/es/applications/landscaping/'], 'the English title still finds the result');
    await context.close();

    // The suggestion card on an English page, for a Spanish browser.
    context = await browser.newContext({ locale: 'es-MX' });
    tab = await context.newPage();
    await tab.goto(`${origin}/contact/?product=500kg-tracked-mini-dumper&utm_source=google#enquiry`);
    await tab.waitForSelector('.bl-lang-suggest a', { state: 'attached', timeout: 5000 });
    assert.equal(await tab.getAttribute('.bl-lang-suggest a', 'href'), `${origin}/es/contact/?product=500kg-tracked-mini-dumper#enquiry`, 'the card keeps the product and the place, not tracking tags');
    assert.equal(await tab.getAttribute('link[hreflang="es"]', 'href'), '/es/contact/', 'the hreflang link stays bare');
    await context.close();

    // The catalogue changes the address as filters are used (history.replaceState): the card follows it at the click.
    context = await browser.newContext({ locale: 'es-MX' });
    tab = await context.newPage();
    await tab.goto(`${origin}/contact/?payload=2000&gclid=abc`);
    await tab.waitForSelector('.bl-lang-suggest a', { state: 'attached', timeout: 5000 });
    assert.equal(await tab.getAttribute('.bl-lang-suggest a', 'href'), `${origin}/es/contact/?payload=2000`, 'the range load goes along, the click id does not');
    await tab.evaluate(() => history.replaceState(null, '', '/contact/?payload=1500&running=tracked'));
    await tab.evaluate(() => { const a = document.querySelector('.bl-lang-suggest a'); a.addEventListener('click', (event) => event.preventDefault()); a.click(); });
    assert.equal(await tab.getAttribute('.bl-lang-suggest a', 'href'), `${origin}/es/contact/?payload=1500&running=tracked`, 'the card takes the address as it is at the click');
    assert.equal(await tab.evaluate(() => localStorage.getItem('blLanguageChoice')), 'es');
    await context.close();

    // The quick enquiry (quick.js marks the root bl-quick-on) hides the card, opened before or after it appears.
    context = await browser.newContext({ locale: 'es-MX' });
    tab = await context.newPage();
    await tab.goto(`${origin}/contact/`);
    await tab.evaluate(() => document.documentElement.classList.add('bl-quick-on'));
    await tab.waitForSelector('.bl-lang-suggest', { state: 'attached', timeout: 5000 });
    assert.equal(await tab.evaluate(() => document.querySelector('.bl-lang-suggest').hidden), true, 'no card over an open quick enquiry');
    await tab.evaluate(() => document.documentElement.classList.remove('bl-quick-on'));
    assert.equal(await tab.evaluate(() => document.querySelector('.bl-lang-suggest').hidden), false, 'the card comes back when the enquiry closes');
    await context.close();

    // No card for a browser in the page's own language.
    context = await browser.newContext({ locale: 'en-US' });
    tab = await context.newPage();
    await tab.goto(`${origin}/contact/`);
    await tab.waitForTimeout(1800);
    assert.equal(await tab.locator('.bl-lang-suggest').count(), 0, 'no card in the page\'s own language');
    await context.close();
  } finally {
    await browser.close();
    server.close();
  }
  console.log('spanish js ok');
})().catch((error) => { console.error(error); process.exit(1); });
