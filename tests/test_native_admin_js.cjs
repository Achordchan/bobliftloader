/* Exercise the shipped clipboard handler; no browser, database, or clipboard access. */
'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
async function checkCopy(clipboard, success) {
  const handlers = [];
  const source = { value: '<h2>Company</h2><p>Policy</p>', hidden: true,
    focus() { this.focused = true; }, select() { this.selected = true; } };
  const status = { textContent: '' };
  const card = { querySelector: selector => selector === 'textarea' ? source : status };
  const button = { closest: () => card };
  const document = {
    addEventListener(type, handler) { if (type === 'click') handlers.push(handler); },
    getElementById: () => null, querySelector: () => null, querySelectorAll: () => [],
  };
  vm.runInNewContext(fs.readFileSync('assets/admin/interactions.js', 'utf8'), { document, navigator: { clipboard } });
  const event = { target: { closest: selector => selector === '[data-bl-copy-policy]' ? button : null } };
  for (const handler of handlers) await handler(event);
  assert.equal(source.hidden, success);
  assert.match(status.textContent, success ? /已复制/ : /手动复制/);
  if (!success) assert.ok(source.focused && source.selected);
  assert.equal(source.value, '<h2>Company</h2><p>Policy</p>');
}
(async () => {
  let copied;
  await checkCopy({ writeText: async text => { copied = text; } }, true);
  assert.equal(copied, '<h2>Company</h2><p>Policy</p>');
  await checkCopy({ writeText: async () => { throw new Error('denied'); } }, false);
  await checkCopy(undefined, false);
  console.log('native policy copy success, denial and unavailable clipboard ok');
})().catch(error => { console.error(error); process.exitCode = 1; });
