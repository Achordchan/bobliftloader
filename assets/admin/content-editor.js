/* 网站内容 screens: image picking, list rows (fold, add, remove, reorder), the section list and unsaved-change warnings. */
(function () {
  'use strict';
  var form = document.querySelector('.bl-ce-form');
  if (!form) return;
  var toc = document.querySelector('.bl-ce-toc');
  var status = form.querySelector('.bl-savebar-status');
  var added = 0;
  var dirty = false;

  function markDirty(el) {
    var section = el.closest('.bl-ce-section');
    var link = section && toc ? toc.querySelector('a[href="#' + section.id + '"]') : null;
    if (link) link.classList.add('is-dirty');
    if (dirty) return;
    dirty = true;
    form.classList.add('is-dirty');
    status.textContent = '有未保存的修改';
  }

  function preview(box, url) {
    var img = box.querySelector('.bl-ce-image-preview img');
    var src = url || box.dataset.fallback || '';
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

  form.addEventListener('change', function (event) {
    var input = event.target;
    var box = input.closest('.bl-ce-image');
    if (box && input.tagName === 'INPUT') preview(box, input.value.trim());
  });

  // Leaving with unsaved edits asks first.
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (event) {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });

  // The section list marks the section being read.
  if (!toc) return;
  var sections = Array.prototype.slice.call(form.querySelectorAll('.bl-ce-section'));
  var links = {};
  toc.querySelectorAll('a').forEach(function (a) { links[a.getAttribute('href').slice(1)] = a; });
  var chosen = null;
  var chosenAt = 0;
  var ticking = false;

  function highlight() {
    ticking = false;
    var active = sections[0];
    if (chosen && Date.now() - chosenAt < 1000) {
      active = chosen;
    } else {
      sections.forEach(function (section) { if (section.getBoundingClientRect().top <= 120) active = section; });
      if (window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 4) {
        var last = sections[sections.length - 1];
        if (last.getBoundingClientRect().top < window.innerHeight) active = last;
      }
    }
    Object.keys(links).forEach(function (id) {
      if (id === active.id) links[id].setAttribute('aria-current', 'true');
      else links[id].removeAttribute('aria-current');
    });
  }

  toc.addEventListener('click', function (event) {
    var link = event.target.closest('a');
    if (!link) return;
    chosen = document.getElementById(link.getAttribute('href').slice(1));
    chosenAt = Date.now();
    highlight();
  });
  window.addEventListener('scroll', function () {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(highlight);
  }, { passive: true });
  highlight();
})();
