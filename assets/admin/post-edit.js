/* Product and news edit screens: spec and video rows, the photo gallery, media pickers; news opens its settings pane. */
(function () {
  'use strict';

  // News (block editor): show the boxes under the text by default, at a height that leaves the text in view.
  // Defaults only: once the editor opens, closes or resizes the pane, WordPress keeps that choice.
  if (window.wp && wp.data && wp.data.dispatch('core/preferences') && !document.getElementById('post-body')) {
    wp.data.dispatch('core/preferences').setDefaults('core/edit-post', { metaBoxesMainIsOpen: true, metaBoxesMainOpenHeight: Math.max(280, Math.round(window.innerHeight * 0.4)) });
    return;
  }

  var form = document.getElementById('post');
  if (!form || !document.getElementById('bl_main-sortables')) return;
  var gallery = form.querySelector('[data-bl-gallery]');
  var base = gallery ? gallery.dataset.blBase : '';
  var GALLERY_MAX = 12;
  var dirty = false;

  // Theme paths (images/…, videos/…) live under the theme's assets folder; anything else is already a full address.
  function resolve(path) {
    return /^(images|videos)\//.test(path) ? base + path : path;
  }

  function fromTemplate(name) {
    var template = form.querySelector('[data-bl-template="' + name + '"]');
    return template.content.firstElementChild.cloneNode(true);
  }

  function addGalleryImage(path) {
    if (!path || gallery.children.length >= GALLERY_MAX) return false;
    var item = fromTemplate('gallery');
    item.title = path;
    item.querySelector('img').src = resolve(path);
    item.querySelector('input').value = path;
    gallery.appendChild(item);
    return true;
  }

  function galleryFull() {
    var full = gallery.children.length >= GALLERY_MAX;
    if (full) window.alert('图库最多 ' + GALLERY_MAX + ' 张。');
    return full;
  }

  function pick(type, multiple, done) {
    var frame = wp.media({
      title: type === 'video' ? '选择视频' : '选择图片',
      button: { text: multiple ? '加入图库' : '使用这个文件' },
      library: { type: type },
      multiple: multiple ? 'add' : false
    });
    frame.on('select', function () {
      done(frame.state().get('selection').map(function (file) { return file.toJSON().url; }));
    });
    frame.open();
  }

  function setPoster(row, path) {
    var holder = row.querySelector('.bl-video-poster');
    holder.textContent = '';
    if (path) {
      var img = document.createElement('img');
      img.src = resolve(path);
      img.alt = '';
      holder.appendChild(img);
    } else {
      var icon = document.createElement('span');
      icon.className = 'dashicons dashicons-format-video';
      holder.appendChild(icon);
    }
  }

  form.addEventListener('click', function (event) {
    var button = event.target.closest('[data-bl]');
    if (!button || !form.contains(button)) return;
    var action = button.dataset.bl;
    event.preventDefault();
    dirty = true;

    if (action === 'add') {
      var list = form.querySelector('[data-bl-rows="' + button.dataset.blTarget + '"]');
      if (list.dataset.blMax && list.children.length >= +list.dataset.blMax) {
        window.alert('最多 ' + list.dataset.blMax + ' 个。');
        return;
      }
      var row = fromTemplate(button.dataset.blTarget);
      list.appendChild(row);
      row.querySelector('input').focus();
      return;
    }
    if (action === 'media-gallery') {
      if (galleryFull()) return;
      pick('image', true, function (urls) {
        urls.forEach(function (url) { addGalleryImage(url); });
      });
      return;
    }
    if (action === 'add-path') {
      var field = form.querySelector('[data-bl-gallery-path]');
      var path = field.value.trim();
      if (!path) return field.focus();
      if (!/^(images\/|https?:\/\/)/.test(path)) {
        window.alert('请填写 http(s):// 开头的图片网址，或主题里的 images/ 路径。');
        return field.focus();
      }
      if (galleryFull()) return;
      addGalleryImage(path);
      field.value = '';
      return;
    }
    if (action === 'media-video' || action === 'media-image') {
      var input = button.parentNode.querySelector('input');
      pick(action === 'media-video' ? 'video' : 'image', false, function (urls) {
        input.value = urls[0];
        if (action === 'media-image') setPoster(button.closest('.bl-row'), urls[0]);
      });
      return;
    }

    var item = button.closest('.bl-row, .bl-gallery-item');
    if (!item) return;
    if (action === 'remove') {
      var filled = item.classList.contains('bl-row') && Array.prototype.some.call(item.querySelectorAll('input'), function (el) { return el.value.trim(); });
      if (!filled || window.confirm('删除这一项？保存后生效。')) item.remove();
    } else if (action === 'up' && item.previousElementSibling) {
      item.parentNode.insertBefore(item, item.previousElementSibling);
      button.focus();
    } else if (action === 'down' && item.nextElementSibling) {
      item.parentNode.insertBefore(item.nextElementSibling, item);
      button.focus();
    }
  });

  form.addEventListener('input', function (event) {
    var input = event.target;
    if (!input.closest('#bl_main-sortables')) return;
    dirty = true;
    if (input.classList.contains('bl-video-caption')) input.closest('.bl-row').querySelector('.bl-row-title').textContent = input.value.trim() || '新视频';
  });

  form.addEventListener('change', function (event) {
    var input = event.target;
    if (input.name === 'bl_videos[poster][]') setPoster(input.closest('.bl-row'), input.value.trim());
  });

  // Enter in the path box adds the image instead of submitting the whole post.
  form.addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && event.target.matches('[data-bl-gallery-path]')) {
      event.preventDefault();
      form.querySelector('[data-bl="add-path"]').click();
    }
  });

  if (gallery && window.jQuery && jQuery.fn.sortable) {
    jQuery(gallery).sortable({ items: '> li', placeholder: 'bl-gallery-placeholder', tolerance: 'pointer', cancel: 'button', update: function () { dirty = true; } });
  }

  // WordPress only warns about unsaved title and text; the product boxes need the same care.
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (event) {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });
})();
