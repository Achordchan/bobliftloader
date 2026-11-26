// Product photographs open larger in one lightbox: the gallery beside the main image, then the dimension drawing and the
// photographs further down, in page order (templates/pages/product.php numbers them). Without the script the
// photographs stay at their page size and nothing is lost. A news story's lead picture and stand artwork open in the same
// way (templates/pages/article.php), in the zoom viewer: see initializeStoryPhotos below.
import { zoomViewer } from './zoom-viewer.js';

export function initializeLightbox() {
  const story = document.querySelector('#story-lightbox');
  if (story) {
    initializeStoryPhotos(story);
    return;
  }
  const dialog = document.querySelector('#product-lightbox');
  if (!dialog) return;
  const items = [...document.querySelectorAll('[data-lightbox-item]')]
    .sort((a, b) => Number(a.dataset.lightboxItem) - Number(b.dataset.lightboxItem));
  if (!items.length) return;
  const image = dialog.querySelector('img');
  const caption = dialog.querySelector('[data-lightbox-caption]');
  const count = dialog.querySelector('[data-lightbox-count]');
  const steps = dialog.querySelectorAll('[data-lightbox-step]');
  let current = 0;
  let opener = null;

  const show = (index) => {
    current = (index + items.length) % items.length;
    const item = items[current].dataset;
    image.src = item.src;
    image.alt = item.alt || '';
    caption.textContent = item.caption || item.alt || '';
    count.textContent = items.length > 1 ? `${current + 1} / ${items.length}` : '';
  };
  const open = (index, trigger) => {
    opener = trigger;
    show(index);
    steps.forEach((step) => { step.hidden = items.length < 2; });
    dialog.showModal();
    document.body.style.overflow = 'hidden';
  };

  document.querySelectorAll('[data-lightbox-open]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => open(Number(button.dataset.lightboxOpen) || 0, button));
  });
  const photo = document.querySelector('#product-photo');
  const zoom = document.querySelector('.bl-gallery-zoom');
  photo?.addEventListener('click', () => open(Number(zoom?.dataset.lightboxOpen) || 0, zoom || photo));

  steps.forEach((step) => step.addEventListener('click', () => show(current + Number(step.dataset.lightboxStep))));
  dialog.querySelector('[data-lightbox-close]').addEventListener('click', () => dialog.close());
  dialog.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowLeft') show(current - 1);
    if (event.key === 'ArrowRight') show(current + 1);
  });
  // A tap on the dark surround closes; a tap on the photograph does not.
  dialog.addEventListener('click', (event) => { if (event.target === dialog) dialog.close(); });
  // Swipe between photographs on touch screens.
  let touchX = null;
  dialog.addEventListener('touchstart', (event) => { touchX = event.touches[0].clientX; }, { passive: true });
  dialog.addEventListener('touchend', (event) => {
    if (touchX === null) return;
    const distance = event.changedTouches[0].clientX - touchX;
    touchX = null;
    if (Math.abs(distance) > 48) show(current + (distance < 0 ? 1 : -1));
  });
  dialog.addEventListener('close', () => {
    document.body.style.overflow = '';
    if (opener?.isConnected) opener.focus({ preventScroll: true });
    opener = null;
  });
}

// A story's pictures full screen in the zoom viewer (zoom-viewer.js), so a visitor can zoom into a chart or a photograph
// without zooming the whole page: the lead picture and the stand artwork (their buttons in templates/pages/article.php)
// and the pictures in the text, in page order. A click, a tap, Enter or Space on a picture opens it; a swipe, the arrows or
// the arrow keys go to the story's other pictures; Esc, the close button or a click on the dark surround close it, and the focus goes
// back to the picture it was opened from. Left as they are: a picture inside a link (it already goes somewhere) or a
// gallery, WordPress's own "Expand on click" lightbox, a video's cover and small pictures (icons, under 160 px across).
// A chart drawn twice, wide for computers and tall for phones (news.css shows one), counts once: only the pictures showing
// on this screen are in the list, so a phone opens and steps through the tall drawings and a computer the wide ones.
function initializeStoryPhotos(dialog) {
  if (typeof dialog.showModal !== 'function') return;
  const stage = dialog.querySelector('[data-viewer-stage]');
  const image = dialog.querySelector('[data-viewer-image]');
  const body = document.querySelector('.bl-story-body');
  if (!stage || !image || !body) return;
  const caption = dialog.querySelector('[data-lightbox-caption]');
  const count = dialog.querySelector('[data-lightbox-count]');
  const failure = dialog.querySelector('[data-viewer-error]');
  const tip = dialog.querySelector('[data-viewer-tip]');
  const steps = [...dialog.querySelectorAll('[data-lightbox-step]')];
  const tipDefault = tip ? tip.textContent : '';
  const touch = window.matchMedia('(hover: none) and (pointer: coarse)');
  const textPicture = (img) => !img.matches('[aria-hidden="true"]')
    && !img.closest('a, button, video, .bl-story-zoom-frame, .wp-block-gallery, .tiled-gallery, .gallery, .wp-lightbox-container, [data-wp-interactive="core/image"]')
    && (img.getAttribute('src') || img.currentSrc);
  // Size is judged once the picture is known: a lazy one not yet loaded counts by its width attribute, or counts at all.
  const bigEnough = (img) => {
    const width = img.naturalWidth || Number(img.getAttribute('width')) || 0;
    return !width || width >= 160;
  };
  const pictures = [...body.querySelectorAll('.bl-wp-article-content img')].filter(textPicture);
  // The lead picture and the artwork: their buttons were in place before (hidden until the script runs), with what to show
  // in the data-lightbox-item beside each.
  const buttons = [...body.querySelectorAll('.bl-story-zoom')].filter((button) => button.parentElement?.querySelector('[data-lightbox-item]'));
  if (!pictures.length && !buttons.length) return;
  const showing = (element) => element.getClientRects().length > 0;
  const textOf = (value) => (value || '').replace(/\s+/g, ' ').trim();
  // srcset candidates as [url, descriptor], read the way browsers do: a URL runs to the next whitespace and may itself contain
  // commas, and only a comma after a URL's end or a descriptor ends a candidate.
  const srcsetCandidates = (value) => {
    const found = [];
    let pos = 0;
    while (pos < value.length) {
      while (pos < value.length && /[\s,]/.test(value[pos])) pos += 1;
      if (pos >= value.length) break;
      let url = '';
      while (pos < value.length && !/\s/.test(value[pos])) url += value[pos++];
      let descriptor = '';
      if (/,$/.test(url)) {
        url = url.replace(/,+$/, '');
      } else {
        // The descriptor runs to the next comma that is not inside brackets.
        let depth = 0;
        while (pos < value.length && (value[pos] !== ',' || depth > 0)) {
          if (value[pos] === '(') depth += 1;
          else if (value[pos] === ')') depth = Math.max(0, depth - 1);
          descriptor += value[pos++];
        }
        descriptor = descriptor.trim().split(/\s+/)[0] || '';
      }
      if (url) found.push([url, descriptor]);
    }
    return found;
  };
  // The largest copy the page knows of: the original upload the server wrote in data-full-src (a media-library picture,
  // inc/helpers.php bl_content_image_full), else the widest srcset entry (the theme's srcset ends with the picture as it was
  // inserted), else for a srcset by pixel density (1x, 2x) its densest entry, else the address shown. No file name is
  // guessed. If it fails, the copy the page shows is used.
  const largest = (img) => {
    if (img.dataset.fullSrc) return img.dataset.fullSrc;
    const src = img.getAttribute('src') || img.currentSrc || '';
    const candidates = srcsetCandidates(img.getAttribute('srcset') || '');
    const widths = candidates
      .filter(([url, size]) => url && /^\d+w$/.test(size || ''))
      .sort((a, b) => parseInt(b[1], 10) - parseInt(a[1], 10));
    if (widths.length) return widths[0][0];
    // A candidate without a descriptor is 1x, as is the src itself.
    const densities = [...candidates, ...(src ? [[src, '1x']] : [])]
      .filter(([url, size]) => url && /^(\d+(\.\d+)?|\.\d+)x$|^$/.test(size || ''))
      .map(([url, size]) => [url, size ? parseFloat(size) : 1])
      .sort((a, b) => b[1] - a[1]);
    return densities.length ? densities[0][0] : src;
  };
  const captionOf = (img) => textOf(img.closest('figure')?.querySelector('figcaption')?.textContent || img.alt);
  const entryOf = (element) => {
    if (element.matches('.bl-story-zoom')) {
      const item = element.parentElement.querySelector('[data-lightbox-item]').dataset;
      const shown = element.parentElement.querySelector('img');
      return { opener: element, full: item.src, shown: shown?.currentSrc || '', alt: item.alt || '', label: textOf(item.caption || item.alt) };
    }
    return { opener: element, full: largest(element), shown: element.currentSrc || element.getAttribute('src') || '', alt: element.alt || '', label: captionOf(element) };
  };

  let photos = [];
  let index = 0;
  // The picture the viewer was opened from: closing hands the focus back to it, whichever picture was shown last.
  let opener = null;
  const wrap = (value) => (value + photos.length) % photos.length;
  const showFailure = (shown) => {
    if (failure) failure.hidden = !shown;
  };
  const setPhoto = (value) => {
    index = wrap(value);
    const photo = photos[index];
    photo.tried = false;
    image.alt = photo.alt;
    if (caption) caption.textContent = photo.label;
    if (count) count.textContent = photos.length > 1 ? `${index + 1} / ${photos.length}` : '';
    showFailure(false);
    if (image.getAttribute('src') !== photo.full) image.src = photo.full;
  };
  const preload = (value) => {
    if (photos.length < 2) return;
    const next = new Image();
    next.decoding = 'async';
    next.src = photos[wrap(value)].full;
  };
  let viewer = null;
  const step = (direction) => {
    viewer.endSwipe();
    setPhoto(index + direction);
    viewer.load();
    preload(index + direction);
  };
  viewer = zoomViewer({
    dialog,
    stage,
    image,
    level: dialog.querySelector('[data-viewer-level]'),
    zoomIn: dialog.querySelector('[data-viewer-zoom="in"]'),
    zoomOut: dialog.querySelector('[data-viewer-zoom="out"]'),
    fitButton: dialog.querySelector('[data-viewer-zoom="fit"]'),
    // Charts carry small print: up to twice their own pixels (and always 4x the fit), so the smallest figures can be read.
    nativeMax: 2,
    loadTimeout: 20000,
    loadingStatus: dialog.querySelector('[data-viewer-loading]'),
    onStep: (direction) => {
      if (photos.length > 1) step(direction);
    },
    canStep: () => photos.length > 1,
    onSurroundTap: () => dialog.close(),
    // The original cannot be had (or stalls): the copy the page itself shows is tried, then the viewer says so.
    onError: () => {
      const photo = photos[index];
      if (!photo.tried && photo.shown && photo.shown !== image.getAttribute('src')) {
        photo.tried = true;
        image.src = photo.shown;
        viewer.load();
      } else {
        stage.classList.remove('is-loading');
        showFailure(true);
      }
    },
    onClose: () => {
      if (image.getAttribute('src') && !image.complete) image.removeAttribute('src');
    },
  });
  const open = (element, event) => {
    opener = element;
    // Built at each opening, in page order: the pictures showing on this screen now (one drawing of a two-drawing chart),
    // with lazy ones that have loaded since counted at their real size.
    photos = [...body.querySelectorAll('.bl-story-zoom, .bl-wp-article-content img')]
      .filter((item) => item === element || (buttons.includes(item) && !item.hidden && showing(item.parentElement))
        || (pictures.includes(item) && bigEnough(item) && showing(item)))
      .map(entryOf);
    const many = photos.length > 1;
    steps.forEach((button) => { button.hidden = !many; });
    if (tip) tip.textContent = many && touch.matches && tip.dataset.tipSwipe ? tip.dataset.tipSwipe : tipDefault;
    setPhoto(Math.max(photos.findIndex((photo) => photo.opener === element), 0));
    viewer.show(event);
    preload(index + 1);
    preload(index - 1);
  };
  // As a button a picture is named by what it does and which picture it is (its caption, else its alt text), so a picture
  // without alt text is never an unnamed button. One that turns out small once it loads (an icon) goes back to a plain
  // picture: no pointer, no tab stop.
  const openLabel = dialog.dataset.openLabel || 'View larger';
  const mark = (img) => {
    const on = bigEnough(img);
    const name = captionOf(img);
    img.classList.toggle('bl-story-photo', on);
    [['tabindex', '0'], ['role', 'button'], ['aria-haspopup', 'dialog'], ['aria-label', name ? `${openLabel}: ${name}` : openLabel]].forEach(([attr, value]) => {
      if (on) img.setAttribute(attr, value);
      else img.removeAttribute(attr);
    });
  };
  pictures.forEach((img) => {
    mark(img);
    if (!img.complete) img.addEventListener('load', () => mark(img), { once: true });
    img.addEventListener('click', (event) => {
      if (!bigEnough(img)) return;
      event.preventDefault();
      open(img, event);
    });
    img.addEventListener('keydown', (event) => {
      if ((event.key !== 'Enter' && event.key !== ' ') || !bigEnough(img)) return;
      event.preventDefault();
      open(img, event);
    });
  });
  buttons.forEach((button) => {
    button.hidden = false;
    button.setAttribute('aria-haspopup', 'dialog');
    button.addEventListener('click', (event) => open(button, event));
  });
  steps.forEach((button) => {
    button.addEventListener('click', () => {
      if (photos.length > 1) step(Number(button.dataset.lightboxStep) || 1);
    });
  });
  dialog.querySelector('[data-lightbox-close]')?.addEventListener('click', () => dialog.close());
  // A click beside the picture closes the viewer (onSurroundTap above): the stage and the bar under it fill the whole dialog.
  dialog.addEventListener('close', () => {
    if (opener?.isConnected) opener.focus({ preventScroll: true });
    opener = null;
  });
}
