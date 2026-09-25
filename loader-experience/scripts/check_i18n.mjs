// List page text that has no translation row in i18n/strings.js (and rows no longer used by the page).
//   node loader-experience/scripts/check_i18n.mjs
// Looks at the text and labels in index.html and the Chinese strings the scripts hand to t(), part labels, photo sets
// and slide labels. Rows with {name} parts count as patterns.
import { readFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import vm from 'node:vm';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const read = f => readFileSync(join(ROOT, f), 'utf8');
const box = { window: {} }; vm.runInNewContext(read('i18n/strings.js'), box);
const rows = box.window.BOBLIFT_I18N_ROWS, CJK = /[一-鿿]/;
const exact = new Set(rows.map(r => r[0]).filter(k => !/\{\w+\}/.test(k)));
const patterns = rows.filter(r => /\{\w+\}/.test(r[0])).map(r => new RegExp('^' + r[0].replace(/[.*+?^$()|[\]\\]/g, '\\$&').replace(/\{\w+\}/g, '(.+?)') + '$'));
const known = s => exact.has(s) || patterns.some(re => re.test(s));

const found = new Map(); // text → where
const add = (s, where) => { s = s.replace(/\s+/g, ' ').trim(); if (s && CJK.test(s) && !found.has(s)) found.set(s, where); };
const html = read('index.html').replace(/<(script|style)\b[\s\S]*?<\/\1>/g, '');
for (const m of html.matchAll(/>([^<>]+)</g)) add(m[1], 'index.html');
for (const m of html.matchAll(/\s(?:alt|aria-label|title|placeholder)="([^"]*)"/g)) add(m[1], 'index.html');
add(read('index.html').match(/<title>([^<]*)<\/title>/)[1], 'index.html <title>');
for (const file of ['app.js', 'explore.js', 'slides.js']) {
  const src = read(file).replace(/^\s*\/\/.*$/gm, '');
  for (const m of src.matchAll(/'([^'\n]*[一-鿿][^'\n]*)'/g)) add(m[1], file);
}
for (const m of read('viewer.js').matchAll(/label: '([^']+)'/g)) add(m[1], 'viewer.js');
add('两款相同', 'specs.css');
found.delete('中文'); // the language menu's own name for Chinese

const missing = [...found].filter(([s]) => !known(s));
const used = new Set(found.keys());
const unused = rows.map(r => r[0]).filter(k => !/\{\w+\}/.test(k) && CJK.test(k) && !used.has(k));
console.log(`${found.size} strings, ${rows.length} rows`);
if (missing.length) { console.log(`\nNo translation (${missing.length}):`); missing.forEach(([s, w]) => console.log(`  [${w}] ${s}`)); }
if (unused.length) { console.log(`\nRows not used by the page (${unused.length}):`); unused.forEach(s => console.log(`  ${s}`)); }
const incomplete = rows.filter(r => r.length < 4 || r.slice(1).some(v => !v));
if (incomplete.length) { console.log(`\nRows missing a language (${incomplete.length}):`); incomplete.forEach(r => console.log(`  ${r[0]}`)); }
if (!missing.length && !incomplete.length) console.log('All page text is translated.');
process.exitCode = missing.length || incomplete.length ? 1 : 0;
