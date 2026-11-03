// The quick enquiry in the bottom-right corner (templates/partials/quick-enquiry.php): its button opens and closes the
// panel; Escape or the close button shuts it and gives focus back. Where the contact rail is a bottom bar (up to 800 px,
// and phones turned sideways) the bar's Enquiry opens and closes it instead, as a sheet: the page behind is dimmed and
// held still, and Tab stays inside the sheet until it closes.
const SHEET = '(max-width: 800px), (max-width: 1000px) and (max-height: 500px)'; // as in page-responsive.css and pages.css

// Phones get the enquiry as a full-screen page instead of a sheet over the bottom bar (the sheet fought the iOS keyboard).
// Trial switch for now: ?fs=1 turns it on for this browser, ?fs=0 turns it off; ordinary visitors keep the sheet until
// it is switched on for everyone here. Same form, same fields, same submit and tracking either way.
const FULLSCREEN_FOR_EVERYONE = false;
const fullscreenWanted = () => {
  if (FULLSCREEN_FOR_EVERYONE) return true;
  const flag = new URLSearchParams(location.search).get('fs');
  try {
    if (flag === '1') localStorage.setItem('boblift_inquiry_fs', '1');
    if (flag === '0') localStorage.removeItem('boblift_inquiry_fs');
    return localStorage.getItem('boblift_inquiry_fs') === '1';
  } catch {
    return flag === '1';
  }
};

export function initializeQuick() {
  const quick = document.querySelector('.bl-quick');
  if (!quick) return;
  quick.classList.toggle('bl-quick-full', fullscreenWanted());
  const full = quick.classList.contains('bl-quick-full');
  const toggle = quick.querySelector('.bl-quick-toggle');
  const panel = quick.querySelector('.bl-quick-panel');
  const sheet = window.matchMedia(SHEET);
  const barEnquiry = document.querySelector('.bl-side-contact a[data-quick]');
  let opener = toggle;
  let muted = [];
  // In the sheet the page behind leaves the accessibility tree too (a screen reader's cursor can't wander out); the bar
  // stays reachable so its Enquiry can close the sheet again.
  const mute = (on) => {
    muted.forEach((element) => { element.inert = false; });
    muted = [];
    if (!on) return;
    for (let node = quick; node.parentElement && node !== document.body; node = node.parentElement) {
      for (const sibling of node.parentElement.children) {
        if (sibling === node || sibling.inert || (barEnquiry && sibling.contains(barEnquiry)) || ['SCRIPT', 'STYLE', 'LINK'].includes(sibling.tagName)) continue;
        sibling.inert = true;
        muted.push(sibling);
      }
    }
  };
  const setModal = (on) => {
    if (on) { panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-modal', 'true'); }
    else { panel.removeAttribute('role'); panel.removeAttribute('aria-modal'); }
    mute(on);
  };
  // Full-screen (bl-quick-full) on a phone: the panel is not laid over the page at all. Everything else on the page is
  // hidden and the panel sits in the normal page flow, so the phone treats it as an ordinary page with a form and moves
  // it for the keyboard like any page, with no fixed layer to shift or cut off. Closing puts the page back where it was.
  let page = null;
  const enterPage = () => {
    if (page) return;
    const y = scrollY; // before hiding anything: the shorter page would already have scrolled back to the top
    const hidden = [];
    for (let node = quick; node.parentElement && node !== document.body; node = node.parentElement) {
      for (const sibling of node.parentElement.children) {
        if (sibling === node || ['SCRIPT', 'STYLE', 'LINK', 'TEMPLATE'].includes(sibling.tagName) || sibling.classList.contains('bl-quick-page-hidden')) continue;
        sibling.classList.add('bl-quick-page-hidden');
        hidden.push(sibling);
      }
    }
    page = { y, hidden };
    document.documentElement.classList.add('bl-quick-page');
    scrollTo({ top: 0, left: 0, behavior: 'instant' });
  };
  const leavePage = () => {
    if (!page) return;
    const { y, hidden } = page;
    page = null;
    hidden.forEach((element) => element.classList.remove('bl-quick-page-hidden'));
    document.documentElement.classList.remove('bl-quick-page');
    const restore = () => scrollTo({ top: y, left: 0, behavior: 'instant' });
    restore();
    requestAnimationFrame(restore); // again once the page has laid out in full
  };
  const setOpen = (open, from = toggle) => {
    const asPage = open && full && sheet.matches;
    if (asPage) enterPage();
    panel.hidden = !open;
    setModal(open && sheet.matches);
    toggle.setAttribute('aria-expanded', String(open));
    quick.classList.toggle('bl-quick-open', open);
    document.documentElement.classList.toggle('bl-quick-on', open);
    if (!asPage) leavePage(); // after the sheet classes are gone, so the page is back at full height to scroll in
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
  // A product page has its own enquiry form, so there the bar's Enquiry goes down to it instead of opening the sheet.
  const pageForm = document.querySelector('.bl-product-enquiry');
  barEnquiry?.addEventListener('click', (event) => {
    if (!sheet.matches) return;
    event.preventDefault();
    if (pageForm && panel.hidden) {
      const title = pageForm.querySelector('h2') || pageForm;
      if (!title.hasAttribute('tabindex')) title.tabIndex = -1;
      // Focus goes to the section's heading, which names what the form is for. The heading or the form, whichever stands
      // higher, comes to the top of the screen (scroll margins clear the header, product.css): side by side that is the
      // form itself, so its first field shows; in one column the heading, so the focused heading is never above the screen.
      const form = pageForm.querySelector('.bl-product-enquiry-form') || pageForm;
      const target = title !== pageForm && title.getBoundingClientRect().top < form.getBoundingClientRect().top ? title : form;
      target.scrollIntoView({ behavior: matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
      title.focus({ preventScroll: true });
      return;
    }
    setOpen(panel.hidden, barEnquiry);
  });
  // A range page's first screen has its own quote button: it opens the panel (the sheet on phones), family already chosen.
  document.querySelectorAll('[data-quick-open]').forEach((link) => link.addEventListener('click', (event) => {
    event.preventDefault();
    setOpen(true, link);
  }));
  // Turning the phone past the sheet's limits leaves a dimmed, locked page behind: close it instead.
  sheet.addEventListener('change', () => { if (!panel.hidden) setOpen(false); });

  // iOS does not shrink the fixed sheet when the keyboard opens: it pans the visible area instead, so the sheet sits half
  // under the keyboard and jumps from field to field. While the sheet is open it follows the visible area
  // (visualViewport): with the keyboard up it stands on the keyboard and is no taller than what is left, the form scrolls
  // inside it, and the focused field is brought into view there.
  let viewportFrame = 0;
  const clearViewport = () => {
    quick.style.removeProperty('bottom');
    panel.style.removeProperty('max-height');
  };
  const syncViewport = () => {
    cancelAnimationFrame(viewportFrame);
    viewportFrame = requestAnimationFrame(() => {
      const viewport = window.visualViewport;
      if (!viewport || panel.hidden || !sheet.matches || page) { clearViewport(); return; }
      const covered = innerHeight - viewport.offsetTop - viewport.height;
      if (covered < 80 && viewport.offsetTop < 1) { clearViewport(); return; } // no keyboard: the stylesheet's place
      quick.style.bottom = `${Math.round(Math.max(covered, 0))}px`;
      panel.style.maxHeight = `${Math.round(viewport.height - 8)}px`;
    });
  };
  window.visualViewport?.addEventListener('resize', syncViewport);
  window.visualViewport?.addEventListener('scroll', syncViewport);
  new MutationObserver(syncViewport).observe(panel, { attributes: true, attributeFilter: ['hidden'] });
  panel.addEventListener('focusin', (event) => {
    const field = event.target;
    if (!field.matches?.('input:not([type=radio]), textarea, select')) return;
    setTimeout(() => {
      syncViewport();
      requestAnimationFrame(() => { if (document.activeElement === field) field.scrollIntoView({ block: 'nearest', inline: 'nearest' }); });
    }, 320);
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
