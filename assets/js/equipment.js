/** Keep tab selection, keyboard navigation and visible panels in one place. */
function createTabs(selector, dataKey) {
  const tabs = [...document.querySelectorAll(selector)];
  const activate = (key, focus = false) => {
    tabs.forEach((tab) => {
      const selected = tab.dataset[dataKey] === key;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      document.getElementById(tab.getAttribute('aria-controls')).hidden = !selected;
      if (selected && focus) tab.focus();
    });
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(tab.dataset[dataKey]));
    tab.addEventListener('keydown', (event) => {
      const vertical = tab.parentElement.getAttribute('aria-orientation') === 'vertical';
      const previous = vertical ? 'ArrowUp' : 'ArrowLeft';
      const next = vertical ? 'ArrowDown' : 'ArrowRight';
      let target;
      if (event.key === previous) target = (index - 1 + tabs.length) % tabs.length;
      if (event.key === next) target = (index + 1) % tabs.length;
      if (event.key === 'Home') target = 0;
      if (event.key === 'End') target = tabs.length - 1;
      if (target === undefined) return;
      event.preventDefault();
      activate(tabs[target].dataset[dataKey], true);
    });
  });
  return activate;
}

export function initializeEquipment() {
  const selectEquipment = createTabs('[data-equipment-tab]', 'equipmentTab');
  createTabs('[data-application-tab]', 'applicationTab');
  const applicationList = document.querySelector('.bl-application-nav');
  if (applicationList) {
    const compact = window.matchMedia('(max-width: 800px)');
    const setOrientation = () => applicationList.setAttribute('aria-orientation', compact.matches ? 'horizontal' : 'vertical');
    compact.addEventListener('change', setOrientation);
    setOrientation();
  }
  document.querySelectorAll('[data-select-equipment]').forEach((link) => {
    link.addEventListener('click', () => selectEquipment(link.dataset.selectEquipment));
  });
  // The thumbnails change the photograph on the stage; the films play in their own player beside the overview.
  const photo = document.querySelector('#product-photo');
  // A long set scrolls inside its own column (a row on phones): bring the thumbnail into view there, never moving the page.
  const revealThumbnail = (button) => {
    const box = button.parentElement;
    const inner = box.getBoundingClientRect();
    const outer = button.getBoundingClientRect();
    if (outer.top < inner.top) box.scrollTop += outer.top - inner.top;
    else if (outer.bottom > inner.bottom) box.scrollTop += outer.bottom - inner.bottom;
    if (outer.left < inner.left) box.scrollLeft += outer.left - inner.left;
    else if (outer.right > inner.right) box.scrollLeft += outer.right - inner.right;
  };
  const galleryButtons = [...document.querySelectorAll('[data-gallery-image]')];
  const showPhoto = (button, direction = 0) => {
    if (photo) {
      photo.src = button.dataset.galleryImage;
      photo.alt = button.dataset.galleryAlt || photo.alt;
      // An arrow slides the new photograph in from the side it comes from; a thumbnail just swaps it.
      if (direction && !window.matchMedia('(prefers-reduced-motion: reduce)').matches && photo.animate) {
        photo.animate([{ transform: `translateX(${direction * 14}%)`, opacity: 0.2 }, { transform: 'none', opacity: 1 }], { duration: 240, easing: 'ease-out' });
      }
    }
    const zoom = document.querySelector('.bl-gallery-zoom');
    if (zoom) zoom.dataset.lightboxOpen = button.dataset.galleryIndex || '0';
    galleryButtons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
    revealThumbnail(button);
    // The neighbours load while the visitor looks at this one, so the next arrow shows its photograph at once.
    const at = galleryButtons.indexOf(button);
    [galleryButtons[at - 1], galleryButtons[at + 1]].forEach((neighbour) => { if (neighbour) new Image().src = neighbour.dataset.galleryImage; });
  };
  galleryButtons.forEach((button) => button.addEventListener('click', () => showPhoto(button)));
  // Phones step through the photographs with the arrows on the stage (the photographs have pale margins, so a swipe would be a guess); they wrap round, and the dots follow.
  document.querySelectorAll('[data-gallery-step]').forEach((arrow) => {
    arrow.hidden = false;
    arrow.addEventListener('click', () => {
      const step = Number(arrow.dataset.galleryStep);
      const at = galleryButtons.findIndex((item) => item.getAttribute('aria-pressed') === 'true');
      showPhoto(galleryButtons[(at + step + galleryButtons.length) % galleryButtons.length], step);
    });
  });
  const shown = document.querySelector('[data-gallery-image][aria-pressed="true"]');
  if (shown) revealThumbnail(shown);
  // The column fades where more thumbnails wait above or below (only the tall column; a row has no height to spare).
  const thumbnails = document.querySelector('.bl-gallery-thumbs');
  if (thumbnails) {
    const markOverflow = () => {
      const spare = thumbnails.scrollHeight - thumbnails.clientHeight;
      thumbnails.classList.toggle('bl-more-above', spare > 1 && thumbnails.scrollTop > 1);
      thumbnails.classList.toggle('bl-more-below', spare > 1 && thumbnails.scrollTop < spare - 1);
    };
    thumbnails.addEventListener('scroll', markOverflow, { passive: true });
    if ('ResizeObserver' in window) new ResizeObserver(markOverflow).observe(thumbnails);
    markOverflow();
  }
  const video = document.querySelector('#product-video');
  const caption = document.querySelector('#product-video-caption');
  if (video) {
    const tiles = [...document.querySelectorAll('[data-gallery-video]')];
    tiles.forEach((tile) => {
      tile.addEventListener('click', (event) => {
        event.preventDefault();
        video.poster = tile.dataset.galleryPoster;
        video.src = tile.dataset.galleryVideo;
        if (caption) caption.textContent = tile.dataset.galleryCaption || 'Video';
        video.play().catch(() => {});
        tiles.forEach((item) => item.setAttribute('aria-pressed', String(item === tile)));
        video.scrollIntoView({ behavior: scrollBehavior(), block: 'nearest' });
      });
    });
    video.addEventListener('play', () => caption?.setAttribute('hidden', ''));
  }
  // Overview: the highlights go under whichever column leaves the two sides closest in height, and the shorter side is
  // centred against the other (assets/css/support.css), so a long or a short description never leaves one side empty.
  // Without videos they stack under the heading; under a video they sit in a row.
  const overview = document.querySelector('[data-overview-balance]');
  const points = overview?.querySelector('.bl-product-highlights');
  if (points) {
    const lead = overview.querySelector('.bl-product-overview-lead');
    const body = overview.querySelector('.bl-product-overview-body');
    const stack = !lead.querySelector('video');
    const put = (inLead) => {
      (inLead ? lead : body).append(points);
      points.classList.toggle('bl-product-highlights-stacked', inLead && stack);
      return Math.abs(lead.offsetHeight - body.offsetHeight);
    };
    const balance = () => {
      if (getComputedStyle(overview).gridTemplateColumns.split(' ').length < 2) { // one column: after the text
        put(false);
        overview.classList.remove('bl-product-overview-follow');
        return;
      }
      if (put(true) <= put(false)) put(true);
      // Much longer text: the heading, films and highlights stay in view beside it while it is read, if they fit the screen.
      const follow = body.offsetHeight > lead.offsetHeight * 1.25;
      overview.classList.toggle('bl-product-overview-follow', follow);
      const fits = () => lead.offsetHeight + parseFloat(getComputedStyle(lead).top || '0') <= window.innerHeight;
      if (follow && !fits() && lead.contains(points)) put(false); // give the highlights to the text if that lets the rest fit
      if (!fits()) overview.classList.remove('bl-product-overview-follow');
    };
    let frame = 0;
    const later = () => { cancelAnimationFrame(frame); frame = requestAnimationFrame(balance); };
    balance();
    window.addEventListener('resize', later);
    window.addEventListener('load', later);
    document.fonts?.ready.then(later);
    // Text that changes size after load (browser translation, late fonts) rebalances too. Only the description and the
    // heading are watched: they keep their size when the highlights move, so moving them does not set this off again.
    if ('ResizeObserver' in window) {
      const heights = new WeakMap();
      const watch = new ResizeObserver((entries) => {
        if (entries.some((entry) => heights.get(entry.target) !== entry.contentRect.height)) later();
        entries.forEach((entry) => heights.set(entry.target, entry.contentRect.height));
      });
      [overview.querySelector('.bl-prose'), lead.querySelector('.bl-product-display')].forEach((item) => item && watch.observe(item));
    }
  }
  // The specification sheet prints from the page itself (assets/css/support.css holds the print layout).
  document.querySelectorAll('[data-print-sheet]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => window.print());
  });
  // Spec groups sit side by side on wide screens, always open. On phones they stack, so only the first (performance)
  // starts open and the rest expand on tap.
  const specGroups = [...document.querySelectorAll('.bl-spec-block')];
  if (specGroups.length > 1) {
    const phone = window.matchMedia('(max-width: 600px)');
    const layout = () => specGroups.forEach((group, index) => { group.open = !phone.matches || index === 0; });
    specGroups.forEach((group) => group.querySelector('summary').addEventListener('click', (event) => { if (!phone.matches) event.preventDefault(); }));
    phone.addEventListener('change', layout);
    layout();
  }
}

export function scrollBehavior() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
}
