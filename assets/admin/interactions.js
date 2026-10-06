/* Shared administrative controls. Visibility never removes or disables stored field values. */
(function () {
  'use strict';

  function changed(element) {
    element.dispatchEvent(new Event('input', { bubbles: true }));
    element.dispatchEvent(new Event('change', { bubbles: true }));
  }

  function syncSelector(box) {
    var selected = Array.from(box.querySelectorAll('input[type="checkbox"]')).filter(function (input) { return input.checked; });
    if (box.dataset.blFormat === 'csv') box.querySelector('input[type="hidden"]').value = selected.map(function (input) { return input.value; }).join(', ');
    changed(box);
  }

  document.addEventListener('click', function (event) {
    var button = event.target.closest('[data-bl-choice]');
    if (button) {
      var row = button.closest('li');
      var prev = row.previousElementSibling;
      var next = row.nextElementSibling;
      // Only ticked items have an order on the page, so the arrows move among them.
      if (button.dataset.blChoice === 'up' && prev) row.parentNode.insertBefore(row, prev);
      if (button.dataset.blChoice === 'down' && next && next.querySelector('input:checked')) row.parentNode.insertBefore(next, row);
      syncSelector(button.closest('[data-bl-selector]'));
      button.focus();
      limits();
    }
    var fold = event.target.closest('[data-bl-fold]');
    if (fold) {
      var body = fold.closest('.bl-row').querySelector('[data-bl-fold-body]');
      body.hidden = !body.hidden;
      fold.setAttribute('aria-expanded', String(!body.hidden));
    }
  });
  document.addEventListener('change', function (event) {
    var box = event.target.closest('[data-bl-selector]');
    if (box && event.target.matches('input[type="checkbox"]')) {
      // A newly ticked item joins the end of the ticked ones; an unticked one goes back among the choices.
      var item = event.target.closest('li');
      var ticked = Array.from(item.parentNode.children).filter(function (li) { return li !== item && li.querySelector('input:checked'); });
      var anchor = ticked.length ? ticked[ticked.length - 1].nextElementSibling : item.parentNode.firstElementChild;
      if (anchor !== item) item.parentNode.insertBefore(item, anchor);
      syncSelector(box);
    }
    conditions();
    limits();
    effectiveImage();
  });

  function conditions() {
    var category = document.getElementById('bl-category');
    var attachments = document.getElementById('bl-product-attachments');
    if (category && attachments) attachments.hidden = category.value === 'mini-dumpers';
    var line = document.getElementById('bl-line');
    var newsCategory = document.querySelector('[data-bl-news-category]');
    if (line && newsCategory) newsCategory.hidden = line.value === 'group';
  }

  function textStatus(parent, selector, attribute, text) {
    var status = parent.querySelector(selector);
    if (!status) { status = document.createElement('span'); status.setAttribute(attribute, ''); parent.appendChild(status); }
    if (status.textContent !== text) status.textContent = text;
  }

  function limits() {
    document.querySelectorAll('[data-bl-choice-limit]').forEach(function (box) {
      var max = +box.dataset.blChoiceLimit;
      if (!max) return;
      var choices = Array.from(box.querySelectorAll('input[type="checkbox"]'));
      var count = choices.filter(function (input) { return input.checked && input.dataset.blChoiceValid !== '0'; }).length;
      var shown = 0;
      choices.forEach(function (input) {
        input.disabled = !input.checked && count >= max;
        if (input.checked && input.dataset.blChoiceValid !== '0') shown++;
        textStatus(input.closest('li'), '[data-bl-overflow]', 'data-bl-overflow', input.checked && input.dataset.blChoiceValid !== '0' && shown > max ? ' 不显示（超出前台上限）' : '');
      });
      textStatus(box, '[data-bl-limit-note]', 'data-bl-limit-note', '前台最多显示 ' + max + ' 条；超出部分保留但不显示。');
    });
    document.querySelectorAll('[data-bl-max], [data-bl-limit]').forEach(function (list) {
      var max = +(list.dataset.blMax || list.dataset.blLimit);
      if (!max) return;
      var rows = Array.from(list.children);
      var scope = list.closest('.bl-ce-field') || list.parentNode;
      var button = scope.querySelector('.bl-ce-add, [data-bl="add"], [data-bl="media-gallery"]');
      if (button) button.disabled = rows.length >= max;
      textStatus(scope, '[data-bl-limit-note]', 'data-bl-limit-note', '前台最多显示 ' + max + ' 条；超出部分保留但不显示。');
      var drawings = 0;
      var drawingMax = +list.dataset.blDrawingMax;
      var eligible = 0;
      var seen = new Set();
      rows.forEach(function (row, index) {
        var kind = list.dataset.blRows || list.dataset.blLimitKind;
        var value = function (key) {
          var input = Array.from(row.querySelectorAll('input, textarea, select')).find(function (field) {
            var name = field.getAttribute('name') || '';
            return name.endsWith('[' + key + ']') || name.endsWith('[' + key + '][]');
          });
          return input ? input.value.trim() : '';
        };
        var reason = '';
        if (kind === 'page-faq' || kind === 'product.faq') {
          var question = value('q').toLowerCase();
          if (!question || !value('a')) reason = '未完整填写';
          else if (seen.has(question)) reason = '问题重复';
          else seen.add(question);
        }
        if (kind === 'product.factory.images' && !value('image')) reason = '未选择图片';
        if (!reason) eligible++;
        if (!reason && eligible > max) reason = '超出前台上限';
        var group = row.querySelector('[name="bl_page[photos][group][]"]');
        if (group && group.value === 'drawing') { drawings++; if (drawings > drawingMax) reason = '超出尺寸图上限'; }
        textStatus(row.querySelector('.bl-row-head') || row, '[data-bl-overflow]', 'data-bl-overflow', reason ? ' 不显示（' + reason + '）' : '');
      });
      if (list.dataset.blRows === 'page-photos') {
        rows.forEach(function (row) {
          var group = row.querySelector('select');
          var option = group && group.querySelector('option[value="drawing"]');
          if (option) option.disabled = drawings >= drawingMax && group.value !== 'drawing';
        });
      }
    });
  }

  function effectiveImage() {
    var box = document.querySelector('[data-bl-effective-image]');
    if (!box) return;
    var fallbackInput = document.querySelector('[name="bl_record[image]"]');
    var fallback = fallbackInput ? fallbackInput.value : '';
    if (/^images\//.test(fallback)) fallback = box.dataset.blBase + fallback;
    var featured = box.dataset.blFeatured;
    var src = featured || fallback || box.dataset.blDefault;
    var img = box.querySelector('img');
    img.hidden = !src;
    if (src && img.getAttribute('src') !== src) img.src = src;
    var source = '来源：' + (featured ? '特色图片' : (fallback ? '备用图片' : (src ? '主题默认图片' : '未设置图片')));
    var label = box.querySelector('[data-bl-image-source]');
    if (label.textContent !== source) label.textContent = source;
    box.querySelector('[data-bl-featured-clear]').hidden = !featured;
  }
  document.addEventListener('click', function (event) {
    var pick = event.target.closest('[data-bl-featured-pick], [data-bl-featured-clear]');
    if (!pick) return;
    var box = pick.closest('[data-bl-effective-image]');
    function set(id, url) {
      box.querySelector('input').value = id || -1;
      box.dataset.blFeatured = url || '';
      if (window.wp && wp.data && !document.getElementById('post')) wp.data.dispatch('core/editor').editPost({ featured_media: id || 0 });
      changed(box.querySelector('input'));
      effectiveImage();
    }
    if (pick.hasAttribute('data-bl-featured-clear')) { set(0, ''); return; }
    var frame = wp.media({ title: '选择特色图片', button: { text: '使用这张图片' }, library: { type: 'image' }, multiple: false });
    frame.on('select', function () { var file = frame.state().get('selection').first().toJSON(); set(file.id, file.url); });
    frame.open();
  });
  // Gutenberg has its own featured-image panel; our source-aware control owns this interaction.
  if (document.querySelector('[data-bl-effective-image]') && window.wp && wp.data && !document.getElementById('post')) {
    var editor = wp.data.dispatch('core/edit-post');
    if (editor && editor.removeEditorPanel) editor.removeEditorPanel('featured-image');
  }

  conditions(); limits(); effectiveImage();
  // Observe only row insertion/removal/reordering. No polling and no observers of generated status text.
  document.querySelectorAll('[data-bl-max], [data-bl-limit]').forEach(function (list) {
    new MutationObserver(limits).observe(list, { childList: true });
  });
})();
