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
  document.querySelectorAll('[data-gallery-image]').forEach((button, _, buttons) => {
    button.addEventListener('click', () => {
      if (photo) {
        photo.src = button.dataset.galleryImage;
        photo.alt = button.dataset.galleryAlt || photo.alt;
      }
      const zoom = document.querySelector('.bl-gallery-zoom');
      if (zoom) zoom.dataset.lightboxOpen = button.dataset.galleryIndex || '0';
      buttons.forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
    });
  });
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
