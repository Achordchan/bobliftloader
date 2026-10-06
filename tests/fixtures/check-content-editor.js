// Execute the shipped script with isolated DOM boundaries; no browser or third-party package is required.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const code = fs.readFileSync(require('node:path').join(__dirname, '../../assets/admin/content-editor.js'), 'utf8');
function setup(withStatus = true) {
  const handlers = {};
  function form() {
    const classes = new Set();
    const events = {};
    const status = withStatus ? { textContent: '' } : null;
    return { events, status, classList: { add: x => classes.add(x), contains: x => classes.has(x) },
      querySelectorAll: () => [], querySelector: x => x === '.bl-savebar-status' ? status : null,
      addEventListener: (name, callback) => { events[name] = callback; } };
  }
  const forms = [form(), form()];
  let accept = false, prompts = 0;
  const window = { addEventListener: (name, fn) => { handlers[name] = fn; }, confirm: () => { prompts++; return accept; } };
  vm.runInNewContext(code, { document: { querySelectorAll: () => forms, querySelector: () => null, getElementById: () => null }, window,
    location: { search: '?page=bl-content-global', hash: '' }, sessionStorage: { getItem: () => null } });
  const event = () => ({ defaultPrevented: false, preventDefault() { this.defaultPrevented = true; }, target: { closest: () => null } });
  return { forms, handlers, event, accept: () => { accept = true; }, prompts: () => prompts };
}
for (const index of [0, 1]) {
  const s = setup();
  s.forms[index].events.input(s.event());
  assert.equal(s.forms[index].status.textContent, '有未保存的修改');
  let leave = s.event(); s.handlers.beforeunload(leave); assert.equal(leave.defaultPrevented, true);
  let submit = s.event(); s.forms[1 - index].events.submit(submit); assert.equal(submit.defaultPrevented, true);
  leave = s.event(); s.handlers.beforeunload(leave); assert.equal(leave.defaultPrevented, true);
  s.accept(); submit = s.event(); s.forms[1 - index].events.submit(submit); assert.equal(submit.defaultPrevented, false);
  leave = s.event(); s.handlers.beforeunload(leave); assert.equal(leave.defaultPrevented, false);
  assert.equal(s.prompts(), 2);
}
const s = setup(false);
s.forms[1].events.change(s.event());
s.forms[1].events.submit(s.event());
const leave = s.event(); s.handlers.beforeunload(leave);
assert.equal(leave.defaultPrevented, false);
assert.equal(s.prompts(), 0);
console.log('content editor independent form warnings ok');
