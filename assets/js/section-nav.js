/**
 * Family pages and the Support page: pin the on-this-page bar under the header once the family switch (or the element
 * marked data-section-nav-after) has scrolled away, mark the section in view, and fill the progress line as the page is read.
 */
export function initializeSectionNav() {
  const nav = document.querySelector('[data-section-nav]');
  if (!nav) return;
  const links = [...nav.querySelectorAll('[data-section-link]')];
  const sections = links.map((link) => document.getElementById(link.hash.slice(1)));
  const header = document.querySelector('.bl-header');
  const tabs = document.querySelector('[data-section-nav-after]') || document.querySelector('.bl-family-switch');
  nav.classList.add('bl-section-nav-live');
  const phone = window.matchMedia('(max-width: 800px), (max-width: 1000px) and (max-height: 500px)');
  // A phone on its side slides the header away while the bar is pinned (pages.css); scrolling back up brings it back,
  // with the bar below it, until the reader scrolls on down. A jump to a section (a link to it, or the address's #) keeps
  // the header away until the page has settled, so it never comes back over the heading the jump brought into view.
  const sideways = window.matchMedia('(max-width: 1000px) and (max-height: 500px)');
  const root = document.documentElement;
  const slides = window.CSS?.supports?.('selector(:has(a))') ?? false; // the stylesheet's rule for it needs :has()
  let lastY = window.scrollY;
  let turnY = lastY;
  let jumping = false;
  let settle = 0;
  const jump = () => {
    jumping = true;
    root.classList.remove('bl-header-back');
    clearTimeout(settle);
    settle = setTimeout(() => { jumping = false; lastY = turnY = window.scrollY; }, 1500);
  };
  document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[href*="#"]');
    if (link && link.pathname === window.location.pathname && link.hash.length > 1 && document.getElementById(link.hash.slice(1))) jump();
  }, true);
  window.addEventListener('hashchange', jump);
  // An open site or language switch, or a keyboard on a header control, holds the header in view: sliding it away
  // would leave the panel over the page with the control that opened it gone.
  const held = () => !!header.querySelector('details[open], :focus-visible');
  const direction = () => {
    const y = window.scrollY;
    if (held()) { root.classList.add('bl-header-back'); lastY = turnY = y; return; }
    if (jumping) {
      // The jump has settled once the page stops moving.
      clearTimeout(settle);
      settle = setTimeout(() => { jumping = false; lastY = turnY = window.scrollY; }, 200);
      return;
    }
    const end = root.scrollHeight - window.innerHeight;
    // Ignore the bounce past either end; a turn counts once the page has moved 24px the new way, so a finger's jitter
    // never toggles it.
    if (y < 0 || y > end || lastY > end) { lastY = y; return; }
    if ((y - lastY) * (lastY - turnY) < 0) turnY = lastY;
    lastY = y;
    if (turnY - y > 24) root.classList.add('bl-header-back');
    else if (y - turnY > 24) root.classList.remove('bl-header-back');
  };

  let scheduled = false;
  let shownIndex = -1;
  const update = () => {
    scheduled = false;
    // The header is sticky at the very top, so its height is where it ends (a phone on its side slides it away while the
    // bar is pinned, pages.css; its height still counts here, so the bar never flickers between the two).
    // (Its top is 0, or below the WordPress toolbar when that shows.)
    const headerBottom = header.offsetHeight + (parseFloat(getComputedStyle(header).top) || 0);
    // On phones the bar arrives as the family switch passes under the place it takes, so a jump to the first section
    // (which leaves room for it) finds it there; wider screens keep it for once the switch has gone under the header.
    const pinned = tabs.getBoundingClientRect().bottom < headerBottom + (phone.matches ? nav.offsetHeight + 1 : 0);
    nav.classList.toggle('bl-section-nav-shown', pinned);
    nav.inert = !pinned;
    if (!pinned || !sideways.matches) root.classList.remove('bl-header-back');
    // The current section is the last one whose top has passed just below the bar. The line runs from the header's foot
    // as seen: with the header slid away (pinned on a phone on its side, where the bar shows) the bar alone is above it.
    const slid = pinned && sideways.matches && !root.classList.contains('bl-header-back') && nav.offsetHeight > 0 && slides;
    const line = (slid ? 0 : headerBottom) + nav.offsetHeight + 40;
    let current = -1;
    sections.forEach((section, index) => { if (section && section.getBoundingClientRect().top <= line) current = index; });
    links.forEach((link, index) => {
      if (index === current) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
    const room = document.documentElement.scrollHeight - window.innerHeight;
    nav.style.setProperty('--progress', room > 0 ? Math.min(1, window.scrollY / room).toFixed(4) : '0');
    if (pinned && current >= 0 && current !== shownIndex) {
      // On narrow screens the bar scrolls sideways: bring a newly current link into view, once per change.
      const active = links[current];
      const list = active.closest('ol');
      if (list.scrollWidth > list.clientWidth) list.scrollTo({ left: active.offsetLeft - list.clientWidth / 2 + active.offsetWidth / 2, behavior: 'smooth' });
    }
    if (pinned) shownIndex = current;
  };
  window.addEventListener('scroll', () => {
    if (sideways.matches) direction();
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(update);
  }, { passive: true });
  window.addEventListener('resize', update);
  update();
}
