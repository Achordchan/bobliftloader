// The quick enquiry in the bottom-right corner (templates/partials/quick-enquiry.php): its button opens and closes the
// panel; Escape or the close button shuts it and gives focus back. Up to 800 px, where the contact rail is a bottom bar, the bar's Enquiry opens it instead.
export function initializeQuick() {
  const quick = document.querySelector('.bl-quick');
  if (!quick) return;
  const toggle = quick.querySelector('.bl-quick-toggle');
  const panel = quick.querySelector('.bl-quick-panel');
  let opener = toggle;
  const setOpen = (open, from = toggle) => {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    quick.classList.toggle('bl-quick-open', open);
    if (open) {
      opener = from;
      // Phones would pop the keyboard up over the form, so there the close button takes focus instead of the name field.
      const field = window.matchMedia('(min-width: 801px)').matches && panel.querySelector('input:not([type="hidden"]):not([type="radio"]):not([tabindex="-1"]), textarea');
      (field || panel.querySelector('.bl-quick-close')).focus({ preventScroll: true });
    } else if (panel.contains(document.activeElement)) opener.focus({ preventScroll: true });
  };
  toggle.addEventListener('click', () => setOpen(panel.hidden));
  quick.querySelector('.bl-quick-close').addEventListener('click', () => setOpen(false));
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !panel.hidden) setOpen(false);
  });
  const barEnquiry = document.querySelector('.bl-side-contact a[data-quick]');
  barEnquiry?.addEventListener('click', (event) => {
    if (!window.matchMedia('(max-width: 800px)').matches) return;
    event.preventDefault();
    setOpen(true, barEnquiry);
  });

  // A product page opens with its section bar along the bottom of the screen; the button rises above it until it scrolls up.
  const bar = document.querySelector('.bl-product-section-nav');
  if (bar) {
    let frame = 0;
    const lift = () => {
      frame = 0;
      const top = bar.getBoundingClientRect().top;
      quick.style.setProperty('--bl-quick-lift', `${top < innerHeight && top > innerHeight - bar.offsetHeight - 80 ? Math.ceil(innerHeight - top) : 0}px`);
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(lift); };
    addEventListener('scroll', schedule, { passive: true });
    addEventListener('resize', schedule);
    lift();
  }
}
