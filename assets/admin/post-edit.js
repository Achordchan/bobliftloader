/* Product and news edit screens: spec and video rows, the photo gallery, media pickers; news opens its settings pane. */
(function () {
  'use strict';

  // News (block editor): show the boxes under the text by default, at a height that leaves the text in view.
  // Defaults only: once the editor opens, closes or resizes the pane, WordPress keeps that choice.
  if (window.wp && wp.data && wp.data.dispatch('core/preferences') && !document.getElementById('post-body')) {
    wp.data.dispatch('core/preferences').setDefaults('core/edit-post', { metaBoxesMainIsOpen: true, metaBoxesMainOpenHeight: Math.max(280, Math.round(window.innerHeight * 0.4)) });
    // The news boxes hold one media picker (the fallback image); the block editor has no #post form to listen on.
    document.addEventListener('click', function (event) {
      var button = event.target.closest('[data-bl-media] [data-bl]');
      if (button) mediaAction(button);
    });
    return;
  }

  var form = document.getElementById('post');
  if (!form || !document.getElementById('bl_main-sortables')) return;
  var gallery = form.querySelector('[data-bl-gallery]');
  var base = gallery ? gallery.dataset.blBase : '';
  var GALLERY_MAX = gallery ? +gallery.dataset.blMax : 0;
  var dirty = false;

  // Theme paths (images/…, videos/…) live under the theme's assets folder; anything else is already a full address.
  function resolve(path) {
    return /^(images|videos)\//.test(path) ? (base || '') + path : path;
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

  // Shows the chosen file (name, and a thumbnail for images) and stores its address in the hidden field.
  function setMedia(media, path) {
    var input = media.querySelector('input');
    var name = media.querySelector('.bl-media-name');
    var thumb = media.querySelector('.bl-media-thumb');
    input.value = path;
    input.dispatchEvent(new Event('change', { bubbles: true }));
    name.textContent = path ? decodeURIComponent(path.split(/[?#]/)[0].split('/').pop()) : media.dataset.empty;
    name.classList.toggle('is-empty', !path);
    media.querySelector('.bl-media-clear').hidden = !path;
    if (thumb) {
      thumb.textContent = '';
      if (path) {
        var img = document.createElement('img');
        img.src = resolve(path);
        img.alt = '';
        thumb.appendChild(img);
      }
    }
    if (input.name === 'bl_videos[poster][]') setPoster(media.closest('.bl-row'), path);
  }

  function mediaAction(button) {
    var media = button.closest('[data-bl-media]');
    if (button.dataset.bl === 'clear-media') return setMedia(media, '');
    pick(media.dataset.blMedia, false, function (urls) { setMedia(media, urls[0]); });
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
      var body = row.querySelector('[data-bl-fold-body]') || row.querySelector('.bl-row-body');
      var fold = row.querySelector('[data-bl-fold]');
      if (body) body.hidden = false;
      if (fold) fold.setAttribute('aria-expanded', 'true');
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
    if (action === 'pick' || action === 'clear-media') return mediaAction(button);

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
    if (!form.contains(input)) return;
    dirty = true;
    if (input.classList.contains('bl-video-caption')) input.closest('.bl-row').querySelector('.bl-row-title').textContent = input.value.trim() || '新视频';
    var pageRow = input.closest('[data-bl-page-kind]');
    if (pageRow) {
      var kind = pageRow.dataset.blPageKind;
      var value = function (key) { var field = pageRow.querySelector('[name="bl_page[' + kind + '][' + key + '][]"]'); return field ? field.value.trim() : ''; };
      pageRow.querySelector('.bl-row-title').textContent = kind === 'faq' ? (value('q') || '新问题') : kind === 'highlights' ? (value('title') || '新亮点') : (value('caption') || value('alt') || '新照片');
    }
  });

  if (gallery && window.jQuery && jQuery.fn.sortable) {
    jQuery(gallery).sortable({ items: '> li', placeholder: 'bl-gallery-placeholder', tolerance: 'pointer', cancel: 'button, input', update: function () { dirty = true; } });
  }

  // WordPress only warns about unsaved title and text; the product boxes need the same care.
  form.addEventListener('submit', function () { dirty = false; });
  window.addEventListener('beforeunload', function (event) {
    if (!dirty) return;
    event.preventDefault();
    event.returnValue = '';
  });
})();
