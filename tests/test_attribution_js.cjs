'use strict';
// assets/js/attribution.js in a real browser: it keeps the Google Ads click and campaign tags only while advertising storage is
// agreed (the banner's cookie, else Google's own consent state), and fills the enquiry form's hidden fields when it is sent.
// Run: node tests/test_attribution_js.cjs   (Playwright and Chromium: PLAYWRIGHT_BROWSERS_PATH or CHROMIUM=/path/to/chromium)
const http = require('node:http');
const { readFileSync } = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const { chromium } = require(process.env.PLAYWRIGHT_MODULE || 'playwright');

const root = path.join(__dirname, '..');
const page_html = '<!doctype html><title>t</title><form class="bl-inquiry-form" onsubmit="return false"><input name="name" value="Ann"><button>Send</button></form>'
  + '<script type="module">import { initializeAttribution } from "/assets/js/attribution.js"; initializeAttribution();</script>';
const server = http.createServer((request, response) => {
  const url = new URL(request.url, 'http://x');
  if (url.pathname.startsWith('/assets/')) {
    response.writeHead(200, { 'content-type': 'text/javascript' });
    response.end(readFileSync(path.join(root, url.pathname)));
    return;
  }
  response.writeHead(200, { 'content-type': 'text/html' });
  response.end(page_html);
});

// Google's tag state as a visitor in a region with that default would have it; `null` is a page where the tag never loaded.
const googleState = (adStorage) => `window.google_tag_data = ${adStorage === null ? 'undefined' : `{ ics: { entries: { ad_storage: { default: ${adStorage} } } } }`};`;
const cookie = (advertising) => `document.cookie = 'bl_consent=' + encodeURIComponent(JSON.stringify({ v: 1, analytics: true, advertising: ${advertising} })) + '; path=/';`;

(async () => {
  await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
  const origin = `http://127.0.0.1:${server.address().port}`;
  const browser = await chromium.launch(process.env.CHROMIUM ? { executablePath: process.env.CHROMIUM } : { executablePath: '/opt/pw-browsers/chromium' });
  const visit = async (context, target, init = '') => {
    const page = await context.newPage();
    if (init) await page.addInitScript(init);
    await page.goto(origin + target);
    await page.waitForTimeout(400);
    return page;
  };
  const submitted = async (page) => page.evaluate(() => {
    const form = document.querySelector('form');
    form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
    return Object.fromEntries([...form.querySelectorAll('input[name^="bl_attr_"]')].map((input) => [input.name, input.value]));
  });
  const stored = (page) => page.evaluate(() => localStorage.getItem('bl_attribution'));

  // 1. Outside the regions that start denied: kept on landing, and sent from another page with the landing page.
  let context = await browser.newContext();
  let page = await visit(context, '/product/t460/?gclid=GC-1&gad_campaignid=2435&utm_source=google&utm_medium=cpc&x=ignored', googleState(true));
  assert.ok(await stored(page), 'agreed by default: nothing was kept');
  page = await visit(context, '/product/t1000/', googleState(true));
  let fields = await submitted(page);
  assert.equal(fields.bl_attr_gclid, 'GC-1');
  assert.equal(fields.bl_attr_gad_campaignid, '2435');
  assert.equal(fields.bl_attr_utm_source, 'google');
  assert.equal(fields.bl_attr_landing, `${origin}/product/t460/`);
  assert.ok(fields.bl_attr_first_seen);
  assert.equal(Object.keys(fields).includes('bl_attr_x'), false);

  // 2. A newer click replaces the record; tags alone never replace a click ID, and a tag-only visit keeps nothing when there was none.
  page = await visit(context, '/a/?gclid=GC-2', googleState(true));
  assert.equal(JSON.parse(await stored(page)).params.gclid, 'GC-2');
  page = await visit(context, '/b/?utm_source=newsletter', googleState(true));
  assert.equal(JSON.parse(await stored(page)).params.gclid, 'GC-2', 'a tag-only visit replaced the click ID');
  await context.close();
  context = await browser.newContext();
  page = await visit(context, '/c/?utm_source=newsletter&utm_campaign=autumn', googleState(true));
  fields = await submitted(page);
  assert.equal(fields.bl_attr_utm_campaign, 'autumn');
  assert.equal(fields.bl_attr_gclid, undefined);
  await context.close();

  // 2b. The same click seen again keeps its landing time and expiry; an over-long or malformed click ID is dropped, not cut, and replaces nothing.
  context = await browser.newContext();
  page = await visit(context, '/land/?gclid=GC-SAME&utm_source=google', googleState(true));
  const first = JSON.parse(await stored(page));
  await page.evaluate((record) => localStorage.setItem('bl_attribution', JSON.stringify({ ...record, first_seen: '2026-01-01T00:00:00.000Z', expires: record.expires - 1000 })), first);
  const aged = JSON.parse(await stored(page));
  page = await visit(context, '/other/?gclid=GC-SAME', googleState(true));
  let again = JSON.parse(await stored(page));
  assert.equal(again.first_seen, aged.first_seen, 'the same click got a new landing time');
  assert.equal(again.expires, aged.expires, 'the same click renewed its 90 days');
  assert.equal(again.landing, aged.landing);
  assert.equal(again.params.utm_source, 'google', 'the same click lost its tags');
  page = await visit(context, `/x/?gclid=${'a'.repeat(300)}`, googleState(true));
  again = JSON.parse(await stored(page));
  assert.equal(again.params.gclid, 'GC-SAME', 'an over-long click ID replaced the record');
  page = await visit(context, '/y/?gclid=a%20b', googleState(true));
  assert.equal(JSON.parse(await stored(page)).params.gclid, 'GC-SAME', 'a malformed click ID replaced the record');
  await context.close();

  // 3. A region that starts denied (EEA, UK, Switzerland): nothing kept, nothing sent.
  context = await browser.newContext();
  page = await visit(context, '/p/?gclid=GC-3&utm_source=google', googleState(false));
  assert.equal(await stored(page), null, 'kept although advertising storage is denied');
  assert.deepEqual(await submitted(page), {});
  await context.close();

  // 4. The banner's choice decides over Google's state.
  context = await browser.newContext();
  page = await visit(context, '/p/?gclid=GC-4', googleState(false) + cookie(true));
  assert.ok(await stored(page), 'an agreed choice did not keep the click');
  assert.equal((await submitted(page)).bl_attr_gclid, 'GC-4');
  page = await visit(context, '/q/', googleState(true) + cookie(false));
  assert.equal(await stored(page), null, 'a refusal did not remove the kept record');
  assert.deepEqual(await submitted(page), {});
  await context.close();

  // 5. Google's tag missing (or its state unreadable) and no choice: nothing is kept.
  context = await browser.newContext();
  page = await visit(context, '/p/?gclid=GC-5', googleState(null));
  assert.equal(await stored(page), null);
  assert.deepEqual(await submitted(page), {});

  // 6. A click ID with markup is not carried as it is, and a record past its 90 days is not used.
  await context.close();
  context = await browser.newContext();
  page = await visit(context, '/p/', googleState(true));
  await page.evaluate(() => localStorage.setItem('bl_attribution', JSON.stringify({ v: 1, first_seen: 'x', landing: 'https://old.test/', params: { gclid: 'OLD' }, expires: Date.now() - 1 })));
  assert.deepEqual(await submitted(page), {}, 'an expired record was sent');
  assert.equal(await stored(page), null, 'an expired record stayed in storage');
  page = await visit(context, '/p/?gclid=%3Cscript%3E', googleState(true));
  fields = await submitted(page);
  assert.equal(fields.bl_attr_gclid, undefined, 'markup in a click ID was carried');

  await browser.close();
  server.close();
  console.log('attribution js ok');
})().catch((error) => { console.error(error); server.close(); process.exit(1); });
