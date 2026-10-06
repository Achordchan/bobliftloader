/* 网站内容 screens: folding cards (remembered per screen), image picking, list rows (fold, add, remove, reorder) and unsaved-change warnings. */
(function () {
  'use strict';
  var forms = Array.prototype.slice.call(document.querySelectorAll('.bl-ce-form'));
  if (!forms.length) return;
  var submitting = false;
  window.addEventListener('beforeunload', function (event) {
    if (submitting || !forms.some(function (form) { return form.classList.contains('is-dirty'); })) return;
    event.preventDefault();
    event.returnValue = '';
  });
  forms.forEach(function (form, index) {
  var jump = index === 0 ? document.querySelector('.bl-jump') : null;
  // Sections scroll in below the sticky jump bar, whose height changes as it wraps or gains 未保存 chips.
  if (jump && window.ResizeObserver) new ResizeObserver(function () { document.documentElement.style.setProperty('--bl-jump-height', jump.offsetHeight + 'px'); }).observe(jump);
  var cards = Array.prototype.slice.call(form.querySelectorAll('[data-bl-card]'));
  var toggleAll = index === 0 ? document.querySelector('[data-bl-toggle-all]') : null;
  var stateKey = 'bl-admin-open:' + (location.search.match(/[?&]page=([^&]*)/) || ['', ''])[1];
  var status = form.querySelector('.bl-savebar-status');
  var added = 0;
  var dirty = false;

  function markDirty(el) {
    var section = el.closest('.bl-ce-section');
    var link = section && jump ? jump.querySelector('a[href="#' + section.id + '"]') : null;
    if (link) link.classList.add('is-dirty');
    if (section) section.classList.add('is-dirty');
    if (dirty) return;
    dirty = true;
    form.classList.add('is-dirty');
    if (status) status.textContent = '有未保存的修改';
  }

  function preview(box, url) {
    var img = box.querySelector('.bl-ce-image-preview img');
    var file = box.querySelector('.bl-ce-image-file');
    var src = url || box.dataset.fallback || '';
    if (file) file.textContent = url ? decodeURIComponent(url.split(/[?#]/)[0].split('/').pop()) : file.dataset.default;
    box.classList.toggle('is-default', !url);
    img.hidden = !src;
    if (src) img.src = src;
  }

  function rowField(row, key) {
    return key ? row.querySelector('[name$="[' + key + ']"]') : null;
  }

  function summarize(row) {
    var list = row.parentNode;
    var text = (list.dataset.summary || '').split(' ').map(function (key) {
      var field = rowField(row, key);
      return field ? field.value.trim() : '';
    }).filter(Boolean).join(' ');
    row.querySelector('.bl-row-title').textContent = text;
    var meta = rowField(row, list.dataset.meta);
    row.querySelector('.bl-ce-row-meta').textContent = meta ? meta.value.trim() : '';
  }

  function setOpen(row, open) {
    row.querySelector('.bl-ce-toggle').setAttribute('aria-expanded', open ? 'true' : 'false');
    row.querySelector('.bl-row-body').hidden = !open;
  }

  form.addEventListener('click', function (event) {
    var button = event.target.closest('button');
    if (!button) return;

    if (button.classList.contains('bl-ce-pick')) {
      var box = button.closest('.bl-ce-image');
      var frame = wp.media({ title: '选择图片', button: { text: '使用这张图片' }, library: { type: 'image' }, multiple: false });
      frame.on('select', function () {
        var file = frame.state().get('selection').first().toJSON();
        box.querySelector('input').value = file.url;
        preview(box, file.url);
        markDirty(box);
      });
      frame.open();
      return;
    }
    if (button.classList.contains('bl-ce-clear')) {
      var clearBox = button.closest('.bl-ce-image');
      clearBox.querySelector('input').value = '';
      preview(clearBox, '');
      markDirty(clearBox);
      return;
    }

    if (button.classList.contains('bl-ce-add')) {
      var field = button.closest('.bl-ce-field');
      var list = field.querySelector('.bl-ce-rows');
      if (+list.dataset.blLimit && list.children.length >= +list.dataset.blLimit) return;
      var holder = document.createElement('div');
      // New rows get a key no existing row uses; the server keeps rows in the order they appear on the page.
      holder.innerHTML = field.querySelector('.bl-ce-row-template').innerHTML.split('__i__').join('n' + Date.now() + '_' + (added++));
      var row = holder.firstElementChild;
      list.appendChild(row);
      var first = row.querySelector('.bl-row-body input, .bl-row-body textarea');
      if (first) first.focus();
      markDirty(list);
      return;
    }

    var current = button.closest('.bl-ce-row');
    if (!current) return;
    if (button.classList.contains('bl-ce-toggle')) {
      setOpen(current, button.getAttribute('aria-expanded') !== 'true');
      return;
    }
    if (button.classList.contains('bl-ce-remove')) {
      if (!window.confirm('删除这一项？保存后生效。')) return;
      markDirty(current);
      var next = current.nextElementSibling || current.previousElementSibling;
      current.remove();
      if (next) next.querySelector('.bl-ce-toggle').focus();
    } else if (button.classList.contains('bl-ce-up') && current.previousElementSibling) {
      current.parentNode.insertBefore(current, current.previousElementSibling);
      markDirty(current);
      button.focus();
    } else if (button.classList.contains('bl-ce-down') && current.nextElementSibling) {
      current.parentNode.insertBefore(current.nextElementSibling, current);
      markDirty(current);
      button.focus();
    }
  });

  form.addEventListener('input', function (event) {
    markDirty(event.target);
    var row = event.target.closest('.bl-ce-row');
    if (row) summarize(row);
  });

  form.addEventListener('change', function (event) { markDirty(event.target); });
  // A successful submission saves only this form; require consent before discarding another form's edits.
  form.addEventListener('submit', function (event) {
    var otherDirty = forms.some(function (other) { return other !== form && other.classList.contains('is-dirty'); });
    if (otherDirty && !window.confirm('另一个表单有未保存的修改，继续保存将丢失这些修改。是否继续？')) {
      event.preventDefault();
      return;
    }
    submitting = !event.defaultPrevented;
  });

  // Cards: saving redirects back to this screen, so the open ones are remembered for the session; a #hash opens its card.
  function storeOpen() {
    try {
      sessionStorage.setItem(stateKey, JSON.stringify(cards.filter(function (card) { return card.open; }).map(function (card) { return card.id; })));
    } catch (error) { /* private mode or a full quota: the cards still work, just forgetfully */ }
  }

  function syncToggleAll() {
    if (!toggleAll) return;
    var allOpen = cards.length > 0 && cards.every(function (card) { return card.open; });
    toggleAll.setAttribute('aria-expanded', allOpen ? 'true' : 'false');
    toggleAll.textContent = allOpen ? '全部收起' : '全部展开';
  }

  function openCard(id) {
    var card = id ? document.getElementById(id) : null;
    if (card && card.hasAttribute('data-bl-card') && !card.open) card.open = true;
    return card;
  }

  cards.forEach(function (card) {
    card.addEventListener('toggle', function () {
      storeOpen();
      syncToggleAll();
    });
  });

  var stored = [];
  try { stored = JSON.parse(sessionStorage.getItem(stateKey) || '[]'); } catch (error) { stored = []; }
  if (Array.isArray(stored)) stored.forEach(openCard);
  openCard(location.hash.slice(1));
  syncToggleAll();
  window.addEventListener('hashchange', function () { openCard(location.hash.slice(1)); });

  if (toggleAll) toggleAll.addEventListener('click', function () {
    var open = toggleAll.getAttribute('aria-expanded') !== 'true';
    cards.forEach(function (card) { card.open = open; });
  });

  // A jump link opens its card first, then scrolls to it.
  if (jump) jump.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (!link) return;
    var card = openCard(link.getAttribute('href').slice(1));
    if (!card) return;
    event.preventDefault();
    card.scrollIntoView({ behavior: 'auto', block: 'start' });
    history.replaceState(null, '', link.getAttribute('href'));
  });
  });
})();
