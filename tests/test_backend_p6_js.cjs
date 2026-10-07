/* Execute the shipped delegated download handlers with media/DOM boundary doubles. */
'use strict';
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const listeners = {};
const form = { addEventListener: (name, fn) => (listeners[name] ||= []).push(fn) };
let frame, configuration, opened = 0;
class Event { constructor(type, options) { this.type = type; Object.assign(this, options); } }
const dispatch = (type, target) => (listeners[type] || []).forEach(fn => fn({ target }));
function row() {
  const output = { textContent: '' }, models = { hidden: true, value: 'Stored model' };
  const element = {
    querySelector: selector => ({ '[name$="[url]"]': url, '[data-bl-download-size]': output, '[data-bl-download-models]': models })[selector],
  };
  const field = name => ({
    value: '', closest: selector => selector === '.bl-ce-row' ? element : null,
    matches: selector => selector === `[name$="[${name}]"]`,
    dispatchEvent(event) { dispatch(event.type, this); this.lastEvent = event; },
  });
  const url = field('url'), scope = field('scope');
  const button = { closest: selector => selector === '[data-bl-download-pick]' ? button : element };
  return { url, scope, output, models, button };
}
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../assets/admin/admin-ui.js'), 'utf8'), {
  document: { querySelector: () => form }, Event,
  wp: { media: options => {
    configuration = options;
    frame = { on: (name, fn) => { frame.select = fn; }, open: () => opened++, state: () => ({ get: () => ({ first: () => ({ toJSON: () => ({ url: 'https://example.test/file.zip', filesizeHumanReadable: '2 MB' }) }) }) }) };
    return frame;
  } },
});
const first = row(), added = row();
first.scope.value = 'models'; dispatch('change', first.scope);
assert.equal(first.models.hidden, false);
first.scope.value = 'all'; dispatch('change', first.scope);
assert.equal(first.models.hidden, true);
assert.equal(first.models.value, 'Stored model', 'changing scope must retain model selection');
// The second row represents one inserted after script initialization: delegation must target only that row.
dispatch('click', added.button);
assert.equal(opened, 1);
assert.equal(configuration.library, undefined, 'media picker must accept non-PDF files');
frame.select();
assert.equal(added.url.value, 'https://example.test/file.zip');
assert.equal(added.output.textContent, '2 MB');
assert.equal(added.url.lastEvent.bubbles, true, 'media change must reach shared dirty tracking');
assert.equal(first.url.value, '', 'media selection changed another row');
added.url.value = 'https://outside.test/new.pdf'; dispatch('input', added.url);
assert.equal(added.output.textContent, '保存后自动读取', 'external URL retained stale media size');
console.log('P6 downloads JavaScript: 10 assertions passed');
