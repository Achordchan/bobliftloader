/* Run the actual classic editor script: known/custom disclosure and dirty state, without a browser or packages. */
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const handlers = {};
const windowHandlers = {};
const row = (value, custom) => {
  const input = {value: custom, hidden: false};
  return {value, parentElement: {querySelector: () => input}, input, matches: () => true};
};
const known = row('Rated load', 'Rated load');
const custom = row('', 'My custom parameter');
const form = {
  querySelectorAll: () => [known, custom],
  querySelector: () => null,
  addEventListener: (event, handler) => (handlers[event] ??= []).push(handler),
};
const document = {getElementById: (id) => id === 'post' ? form : {}};
const window = {addEventListener: (event, handler) => windowHandlers[event] = handler};
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../assets/admin/post-edit.js'), 'utf8'), {document, window});
assert.equal(known.input.hidden, true);
assert.equal(custom.input.hidden, false);
const change = select => handlers.change.forEach(fn => fn({target: select}));
custom.value = 'Bucket capacity'; change(custom);
assert.equal(custom.input.hidden, true);
assert.equal(custom.input.value, 'My custom parameter', 'choosing a preset must not erase the custom draft');
custom.value = ''; change(custom);
assert.equal(custom.input.hidden, false);
assert.equal(custom.input.value, 'My custom parameter');
const added = row('Machine width', 'Draft'); change(added);
assert.equal(added.input.hidden, true, 'delegated change must work on newly inserted rows');
let warned = false;
windowHandlers.beforeunload({preventDefault: () => warned = true});
assert.equal(warned, true, 'select-only changes must warn before leaving');
// A submission stopped beside a bad value keeps the leave warning; a real one clears it.
handlers.submit.forEach(fn => fn({defaultPrevented: true, preventDefault() {}}));
warned = false;
windowHandlers.beforeunload({preventDefault: () => warned = true});
assert.equal(warned, true, 'a blocked save must keep the leave warning');
handlers.submit.forEach(fn => fn({defaultPrevented: false, preventDefault() {}}));
warned = false;
windowHandlers.beforeunload({preventDefault: () => warned = true});
assert.equal(warned, false);
console.log('backend P4 specification controls ok');
