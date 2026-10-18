'use strict';
// assets/js/consent.js in a real browser: a banner that lists regions asks on its own only visitors there (a European time
// zone at once, else the country from WordPress.com's geolocation, kept for the visit); an unknown country is asked, the
// footer link opens it for anyone, and a banner without a list asks everyone as before.
// Run: node tests/test_consent_region_js.cjs   (Playwright and Chromium: PLAYWRIGHT_BROWSERS_PATH or CHROMIUM=/path/to/chromium)
const http = require('node:http');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const root = path.join(__dirname, '..');
const banner = (regions) => `<section data-bl-consent-banner data-categories="analytics,advertising"${regions ? ' data-regions="FR,DE,GB,CH"' : ''} hidden>`
  + '<button data-bl-consent="close" hidden>x</button><h2 id="bl-consent-title" tabindex="-1">Cookies</h2>'
  + '<div class="bl-consent-choices" hidden><input type="checkbox" name="analytics"><input type="checkbox" name="advertising"></div>'
  + '<button data-bl-consent="reject">Reject</button><button data-bl-consent="accept">Accept</button><button data-bl-consent="save" hidden>Save</button><button data-bl-consent="choose">Choose</button></section>'
  + '<button data-bl-consent-open hidden>Cookie settings</button>';
const server = http.createServer((request, response) => {
  const url = new URL(request.url, 'http://x');
  if (url.pathname.startsWith('/assets/')) {
    response.writeHead(200, { 'content-type': 'text/javascript' });
    response.end(readFileSync(path.join(root, url.pathname)));
    return;
  }
  response.writeHead(200, { 'content-type': 'text/html' });
  response.end(`<!doctype html><title>t</title>${banner(url.searchParams.get('regions') !== '0')}<script type="module">import { initializeConsent } from "/assets/js/consent.js"; initializeConsent();</script>`);
});

(async () => {
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const origin = `http://127.0.0.1:${server.address().port}`;
  const browser = await chromium.launch(process.env.CHROMIUM ? { executablePath: process.env.CHROMIUM } : { executablePath: '/opt/pw-browsers/chromium' });
  // country: what the geolocation answers ('fail' for an error, 'slow' for one that never answers); returns whether the banner shows.
  const visit = async ({ timezoneId = 'America/New_York', country = 'US', target = '/', context = null } = {}) => {
    const own = context || await browser.newContext({ timezoneId });
    let asked = 0;
    await own.route('https://public-api.wordpress.com/geo/', (route) => {
      asked++;
      if (country === 'fail') return route.abort();
      if (country === 'slow') return;
      return route.fulfill({ status: 200, contentType: 'application/json', headers: { 'access-control-allow-origin': '*' }, body: JSON.stringify({ country_short: country }) });
    });
    const page = await own.newPage();
    await page.goto(origin + target);
    await page.waitForTimeout(country === 'slow' ? 4600 : 500);
    const shown = await page.evaluate(() => !document.querySelector('[data-bl-consent-banner]').hidden);
    await own.unrouteAll({ behavior: 'ignoreErrors' });
    return { shown, asked, page, context: own };
  };

  // Outside the regions: no banner, but the footer link still opens it.
  let result = await visit({ country: 'US' });
  assert.equal(result.shown, false, 'a US visitor was asked');
  assert.equal(result.asked, 1);
  await result.page.click('[data-bl-consent-open]');
  assert.equal(await result.page.evaluate(() => !document.querySelector('[data-bl-consent-banner]').hidden), true, 'the footer link no longer opens the banner');
  // The country is kept for the visit (the tab's session): the next page does not ask the geolocation again.
  let again = 0;
  await result.context.route('https://public-api.wordpress.com/geo/', (route) => { again++; return route.abort(); });
  await result.page.goto(`${origin}/next/`);
  await result.page.waitForTimeout(500);
  assert.equal(await result.page.evaluate(() => document.querySelector('[data-bl-consent-banner]').hidden), true);
  assert.equal(again, 0, 'the country was looked up again on the next page');

  // In the regions by country, by a European time zone (no lookup), and when the country cannot be told.
  assert.equal((await visit({ country: 'DE' })).shown, true, 'a German visitor was not asked');
  result = await visit({ timezoneId: 'Europe/London', country: 'US' });
  assert.equal(result.shown, true, 'a European time zone was not asked');
  assert.equal(result.asked, 0, 'a European time zone still looked the country up');
  assert.equal((await visit({ timezoneId: 'Atlantic/Canary', country: 'US' })).shown, true, 'the Canary Islands were not asked');
  assert.equal((await visit({ country: 'fail' })).shown, true, 'a failed lookup was not asked');
  assert.equal((await visit({ country: '-' })).shown, true, 'an unknown country was not asked');
  assert.equal((await visit({ country: 'slow' })).shown, true, 'a lookup that never answers was not asked');

  // A banner without a list asks everyone, and never looks the country up.
  result = await visit({ country: 'US', target: '/?regions=0' });
  assert.equal(result.shown, true, 'a banner without regions did not ask');
  assert.equal(result.asked, 0);

  // A visitor who has chosen is not asked anywhere.
  const chosen = await browser.newContext({ timezoneId: 'Europe/Paris' });
  await chosen.addCookies([{ name: 'bl_consent', value: encodeURIComponent(JSON.stringify({ v: 1, analytics: false, advertising: false })), url: origin }]);
  assert.equal((await visit({ context: chosen, country: 'FR' })).shown, false, 'a visitor who chose was asked again');

  await browser.close();
  server.close();
  console.log('Consent regions (browser): ok');
})().catch((error) => { console.error(error); process.exit(1); });
