/**
 * Family pages: pin the on-this-page bar under the header once the family switch has scrolled away, mark the section
 * in view, and fill the progress line as the page is read.
 */
export function initializeSectionNav() {
  const nav = document.querySelector('[data-section-nav]');
  if (!nav) return;
  const links = [...nav.querySelectorAll('[data-section-link]')];
  const sections = links.map((link) => document.getElementById(link.hash.slice(1)));
  const header = document.querySelector('.bl-header');
  const tabs = document.querySelector('.bl-family-switch');
  nav.classList.add('bl-section-nav-live');

  let scheduled = false;
  let shownIndex = -1;
  const update = () => {
    scheduled = false;
    const headerBottom = header.getBoundingClientRect().bottom;
    const pinned = tabs.getBoundingClientRect().bottom < headerBottom;
    nav.classList.toggle('bl-section-nav-shown', pinned);
    nav.inert = !pinned;
    // The current section is the last one whose top has passed just below the bar.
    const line = headerBottom + nav.offsetHeight + 40;
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
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(update);
  }, { passive: true });
  window.addEventListener('resize', update);
  update();
}
