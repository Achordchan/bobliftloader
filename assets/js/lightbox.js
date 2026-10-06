// Product photographs open larger in one lightbox: the gallery beside the main image, then the dimension drawing and the
// photographs further down, in page order (templates/pages/product.php numbers them). Without the script the
// photographs stay at their page size and nothing is lost.
export function initializeLightbox() {
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
