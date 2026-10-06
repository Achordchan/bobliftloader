/* DOM boundary doubles exercise the shipped handlers without WordPress, a browser, or dependencies. */
'use strict';
const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
class Element {
  constructor(tag = 'div', attrs = {}) {
    this.tagName = tag; this.attributes = {}; this.children = []; this.listeners = {}; this.dataset = {};
    this.classList = { add: name => { this.attributes.class = [...new Set((this.attributes.class || '').split(' ').filter(Boolean).concat(name))].join(' '); }, contains: name => (this.attributes.class || '').split(' ').includes(name) };
    this.value = ''; this.checked = false; this.disabled = false; this.hidden = false; this.textContent = '';
    Object.entries(attrs).forEach(([key, value]) => this.setAttribute(key, value));
  }
  setAttribute(key, value) {
    this.attributes[key] = String(value);
    if (key === 'id') this.id = value;
    if (key === 'value') this.value = value;
    if (key.startsWith('data-')) this.dataset[key.slice(5).replace(/-([a-z])/g, (_, c) => c.toUpperCase())] = String(value);
  }
  getAttribute(key) { return this.attributes[key]; }
  hasAttribute(key) { return key in this.attributes; }
  matches(selector) {
    return selector.split(',').some(s => {
      s = s.trim();
      const attrs = [...s.matchAll(/\[([^=\]]+)(?:="([^"]*)")?\]/g)];
      const base = s.replace(/\[([^=\]]+)(?:="([^"]*)")?\]/g, '');
      const classes = base.match(/\.[\w-]+/g) || [];
      const tag = base.split(/[.#]/)[0];
      return (!tag || tag === this.tagName) && classes.every(c => (this.attributes.class || '').split(' ').includes(c.slice(1))) && attrs.every(([, k, v]) => this.hasAttribute(k) && (v === undefined || this.attributes[k] === v));
    });
  }
  closest(selector) { return this.matches(selector) ? this : this.parentNode?.closest(selector) || null; }
  querySelectorAll(selector) { return this.children.flatMap(child => [...(child.matches(selector) ? [child] : []), ...child.querySelectorAll(selector)]); }
  querySelector(selector) { return this.querySelectorAll(selector)[0] || null; }
  appendChild(child) { if (child.parentNode) child.parentNode.children.splice(child.parentNode.children.indexOf(child), 1); child.parentNode = this; this.children.push(child); return child; }
  insertBefore(child, reference) { if (child.parentNode) child.parentNode.children.splice(child.parentNode.children.indexOf(child), 1); child.parentNode = this; this.children.splice(this.children.indexOf(reference), 0, child); }
  get previousElementSibling() { return this.parentNode?.children[this.parentNode.children.indexOf(this) - 1]; }
  get nextElementSibling() { return this.parentNode?.children[this.parentNode.children.indexOf(this) + 1]; }
  addEventListener(type, callback) { (this.listeners[type] ||= []).push(callback); }
  dispatchEvent(event) { event.target ||= this; for (const callback of this.listeners[event.type] || []) callback(event); if (event.bubbles) this.parentNode?.dispatchEvent(event); }
  focus() { this.focused = true; }
}
const el = (tag, attrs, parent) => { const item = new Element(tag, attrs); if (parent) parent.appendChild(item); return item; };
const document = new Element('document');
document.getElementById = id => document.querySelectorAll('[id]').find(item => item.id === id) || null;
document.createElement = tag => new Element(tag);
const window = new Element('window');
const form = el('form', { class: 'bl-ce-form' }, document);
const status = el('p', { class: 'bl-savebar-status' }, form);
const otherForm = el('form', { class: 'bl-ce-form' }, document);
let accept = false, prompts = 0;
window.confirm = () => { prompts++; return accept; };
const csv = el('div', { 'data-bl-selector': '', 'data-bl-format': 'csv', 'data-bl-choice-limit': '2' }, form);
const hidden = el('input', { type: 'hidden', value: 'First, Historical' }, csv);
const choices = el('ul', {}, csv);
function choice(name, checked) {
  const row = el('li', {}, choices);
  const input = el('input', { type: 'checkbox', value: name }, row); input.checked = checked;
  const up = el('button', { 'data-bl-choice': 'up' }, row);
  const down = el('button', { 'data-bl-choice': 'down' }, row);
  return { row, input, up, down };
}
const first = choice('First', true), historic = choice('Historical', true), third = choice('Third', false);
const category = el('select', { id: 'bl-category', value: 'mini-dumpers' }, form);
const attachments = el('div', { id: 'bl-product-attachments' }, form);
const retained = el('input', { value: 'Keep me' }, attachments);
const line = el('select', { id: 'bl-line', value: 'group' }, form);
const news = el('div', { 'data-bl-news-category': '' }, form);
const field = el('div', { class: 'bl-ce-field' }, form);
const list = el('ol', { 'data-bl-max': '2', 'data-bl-rows': 'page-photos', 'data-bl-drawing-max': '1' }, field);
const add = el('button', { 'data-bl': 'add' }, field);
function photo(groupValue) {
  const row = el('li', { class: 'bl-row' }, list);
  const head = el('div', { class: 'bl-row-head' }, row);
  const fold = el('button', { 'data-bl-fold': '', 'aria-expanded': 'false' }, head);
  const body = el('div', { 'data-bl-fold-body': '' }, row); body.hidden = true;
  const select = el('select', { name: 'bl_page[photos][group][]', value: groupValue }, body);
  const drawing = el('option', { value: 'drawing' }, select);
  return { row, body, fold, select, drawing };
}
const drawing1 = photo('drawing'), drawing2 = photo('drawing'), overflow = photo('detail');
const effective = el('div', { 'data-bl-effective-image': '', 'data-bl-featured': 'featured.jpg', 'data-bl-default': 'default.jpg', 'data-bl-base': '/assets/' }, form);
const image = el('img', {}, effective);
const source = el('p', { 'data-bl-image-source': '' }, effective);
el('input', { type: 'hidden', value: '7' }, effective);
const clear = el('button', { 'data-bl-featured-clear': '' }, effective);
const backup = el('input', { name: 'bl_record[image]', value: 'images/backup.jpg' }, form);
const observers = [];
class Event { constructor(type, options = {}) { this.type = type; this.bubbles = !!options.bubbles; } preventDefault() { this.prevented = this.defaultPrevented = true; } }
vm.runInNewContext(fs.readFileSync('assets/admin/interactions.js', 'utf8'), { document, window, Event, MutationObserver: class { constructor(callback) { observers.push(callback); } observe() {} } });
vm.runInNewContext(fs.readFileSync('assets/admin/content-editor.js', 'utf8'), { document, window, location: { search: '?page=bl-content-tracking', hash: '' }, sessionStorage: { getItem: () => null } });
assert.equal(window.listeners.beforeunload.length, 1, 'only one unsaved-change handler may be registered');
const change = input => input.dispatchEvent(new Event('change', { bubbles: true }));
const click = button => button.dispatchEvent(new Event('click', { bubbles: true }));
assert(attachments.hidden && news.hidden, 'conditional fields should start hidden');
assert.equal(retained.value, 'Keep me'); assert.equal(retained.disabled, false);
category.value = 'skid-steer-loaders'; line.value = 'compact'; change(category);
assert(!attachments.hidden && !news.hidden, 'switching must reveal retained fields');
assert(add.disabled); assert(third.input.disabled);
assert(drawing2.row.querySelector('[data-bl-overflow]').textContent.includes('不显示'));
assert(overflow.row.querySelector('[data-bl-overflow]').textContent.includes('不显示'));
assert(overflow.drawing.disabled, 'cannot add another dimension drawing');
click(drawing1.fold); assert(!drawing1.body.hidden); assert.equal(drawing1.fold.getAttribute('aria-expanded'), 'true');
click(drawing1.fold); assert(drawing1.body.hidden);
click(historic.up); assert.equal(hidden.value, 'Historical, First');
first.input.checked = false; change(first.input); assert.equal(hidden.value, 'Historical'); assert(!third.input.disabled);
third.input.checked = true; change(third.input); assert.equal(hidden.value, 'Historical, Third');
assert.equal(source.textContent, '来源：特色图片');
click(clear); assert.equal(source.textContent, '来源：备用图片'); assert.equal(image.src, '/assets/images/backup.jpg');
backup.value = ''; change(backup); assert.equal(source.textContent, '来源：主题默认图片');
assert.equal(status.textContent, '有未保存的修改');
const leave = new Event('beforeunload'); window.dispatchEvent(leave); assert(leave.prevented);
const rejected = new Event('submit'); otherForm.dispatchEvent(rejected); assert(rejected.prevented);
const stillDirty = new Event('beforeunload'); window.dispatchEvent(stillDirty); assert(stillDirty.prevented);
accept = true; otherForm.dispatchEvent(new Event('submit'));
assert.equal(prompts, 2, 'each attempt should show exactly one cross-form confirmation');
const savedLeave = new Event('beforeunload'); window.dispatchEvent(savedLeave); assert(!savedLeave.prevented);
list.children.splice(1); observers.forEach(callback => callback()); assert(!add.disabled, 'removing overflow must re-enable adding');
console.log('backend P2 JS interactions ok');
