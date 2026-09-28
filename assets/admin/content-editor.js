/* 网站内容 screens: pick images from the media library, and add, remove and reorder list rows. */
(function () {
  'use strict';
  var form = document.querySelector('.bl-ce-form');
  if (!form) return;
  var added = 0;

  function preview(box, url) {
    var holder = box.querySelector('.bl-ce-image-preview');
    holder.textContent = '';
    if (url) {
      var img = document.createElement('img');
      img.src = url;
      img.alt = '';
      holder.appendChild(img);
    } else {
      var span = document.createElement('span');
      span.textContent = '主题默认';
      holder.appendChild(span);
    }
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
      });
      frame.open();
      return;
    }
    if (button.classList.contains('bl-ce-clear')) {
      var clearBox = button.closest('.bl-ce-image');
      clearBox.querySelector('input').value = '';
      preview(clearBox, '');
      return;
    }

    if (button.classList.contains('bl-ce-add')) {
      var list = button.closest('.bl-ce-field').querySelector('.bl-ce-list');
      var row = document.createElement('li');
      row.className = 'bl-ce-row';
      // New rows get a key no existing row uses; the server keeps rows in the order they appear on the page.
      var key = 'n' + Date.now() + '_' + (added++);
      row.innerHTML = '<div class="bl-ce-row-fields">' + list.dataset.template.split('__i__').join(key) + '</div>' + form.querySelector('.bl-ce-row-tools-template').innerHTML;
      list.appendChild(row);
      var first = row.querySelector('input, textarea');
      if (first) first.focus();
      return;
    }

    var current = button.closest('.bl-ce-row');
    if (!current) return;
    if (button.classList.contains('bl-ce-remove')) {
      if (window.confirm('删除这一项？保存后生效。')) current.remove();
    } else if (button.classList.contains('bl-ce-up') && current.previousElementSibling) {
      current.parentNode.insertBefore(current, current.previousElementSibling);
      button.focus();
    } else if (button.classList.contains('bl-ce-down') && current.nextElementSibling) {
      current.parentNode.insertBefore(current.nextElementSibling, current);
      button.focus();
    }
  });

  form.addEventListener('change', function (event) {
    var input = event.target;
    var box = input.closest('.bl-ce-image');
    if (box && input.tagName === 'INPUT') preview(box, input.value.trim());
  });

  // Leaving with unsaved edits asks first.
  var dirty = false;
  form.addEventListener('input', function () { dirty = true; });
  form.addEventListener('click', function (event) { if (event.target.closest('.bl-ce-pick, .bl-ce-clear, .bl-ce-add, .bl-ce-row-tools button')) dirty = true; });
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (event) {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });
})();
