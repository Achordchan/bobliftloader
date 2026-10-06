// The quick enquiry in the bottom-right corner (templates/partials/quick-enquiry.php): its button opens and closes the
// panel; Escape or the close button shuts it and gives focus back. Where the contact rail is a bottom bar (up to 800 px,
// and phones turned sideways) the bar's Enquiry opens and closes it instead, as a sheet: the page behind is dimmed and
// held still, and Tab stays inside the sheet until it closes.
const SHEET = '(max-width: 800px), (max-width: 1000px) and (max-height: 500px)'; // as in page-responsive.css and pages.css

export function initializeQuick() {
  const quick = document.querySelector('.bl-quick');
  if (!quick) return;
  const toggle = quick.querySelector('.bl-quick-toggle');
  const panel = quick.querySelector('.bl-quick-panel');
  const sheet = window.matchMedia(SHEET);
  const barEnquiry = document.querySelector('.bl-side-contact a[data-quick]');
  let opener = toggle;
  const setOpen = (open, from = toggle) => {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', String(open));
    quick.classList.toggle('bl-quick-open', open);
    document.documentElement.classList.toggle('bl-quick-on', open);
    if (open) {
      opener = from;
      // Phones would pop the keyboard up over the form, so there the close button takes focus instead of the name field.
      const field = !sheet.matches && panel.querySelector('input:not([type="hidden"]):not([type="radio"]):not([tabindex="-1"]), textarea');
      (field || panel.querySelector('.bl-quick-close')).focus({ preventScroll: true });
    } else if (panel.contains(document.activeElement) || document.activeElement === document.body) opener.focus({ preventScroll: true });
  };
  toggle.addEventListener('click', () => setOpen(panel.hidden));
  quick.querySelector('.bl-quick-close').addEventListener('click', () => setOpen(false));
  // In the sheet the page behind is dimmed (the panel's ::before); a tap there closes it.
  quick.addEventListener('click', (event) => { if (event.target === quick) setOpen(false); });
  document.addEventListener('keydown', (event) => {
    if (panel.hidden) return;
    if (event.key === 'Escape') setOpen(false);
    else if (event.key === 'Tab' && sheet.matches) {
      const stops = [...panel.querySelectorAll('a[href], button, input:not([type="hidden"]), select, textarea, [tabindex]:not([tabindex="-1"])')]
        .filter((element) => !element.disabled && element.getClientRects().length);
      const first = stops[0];
      const last = stops[stops.length - 1];
      if (!panel.contains(document.activeElement)) { event.preventDefault(); first.focus(); }
      else if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
      else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    }
  });
  // Focus that still lands outside the sheet (a screen reader's cursor, a tap on the bar) is brought back in.
  document.addEventListener('focusin', (event) => {
    if (!panel.hidden && sheet.matches && !quick.contains(event.target) && event.target !== barEnquiry) panel.querySelector('.bl-quick-close').focus({ preventScroll: true });
  });
  barEnquiry?.addEventListener('click', (event) => {
    if (!sheet.matches) return;
    event.preventDefault();
    setOpen(panel.hidden, barEnquiry);
  });
  // Turning the phone past the sheet's limits leaves a dimmed, locked page behind: close it instead.
  sheet.addEventListener('change', () => { if (!panel.hidden) setOpen(false); });

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
