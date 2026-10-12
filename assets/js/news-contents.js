import { scrollBehavior } from './equipment.js';

/**
 * A story's contents list. Beside the text it marks the heading being read (the last one to have reached the top of the
 * window). The fold-out above the text on phones shuts when a heading is picked, and the page then scrolls to it: the
 * list closing shortens the page above the heading, so a plain jump would land short.
 */
export function initializeNewsContents() {
  keepFollowInView();
  const side = document.querySelector('[data-story-contents-nav]');
  const fold = document.querySelector('[data-story-contents]');
  if (!side && !fold) return;
  const targetOf = (link) => {
    try { return document.getElementById(decodeURIComponent(link.hash.slice(1))); } catch { return null; }
  };

  if (side) {
    const links = [...side.querySelectorAll('a[href^="#"]')];
    const headings = links.map(targetOf);
    const header = document.querySelector('.bl-header');
    let frame = 0;
    const mark = () => {
      frame = 0;
      const line = (header?.getBoundingClientRect().bottom ?? 0) + 48;
      let current = -1;
      headings.forEach((heading, index) => { if (heading && heading.getBoundingClientRect().top <= line) current = index; });
      links.forEach((link, index) => (index === current ? link.setAttribute('aria-current', 'location') : link.removeAttribute('aria-current')));
    };
    const schedule = () => { if (!frame) frame = requestAnimationFrame(mark); };
    window.addEventListener('scroll', schedule, { passive: true });
    window.addEventListener('resize', schedule);
    mark();
  }

  if (fold) {
    fold.addEventListener('click', (event) => {
      const link = event.target.closest?.('a[href^="#"]');
      const target = link && targetOf(link);
      if (!target) return;
      event.preventDefault();
      fold.open = false;
      target.scrollIntoView({ behavior: scrollBehavior(), block: 'start' });
      // The link that had focus is gone with the closed list: focus moves to the heading (as a native jump would), so the
      // next Tab continues from there.
      if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
      target.focus({ preventScroll: true });
      history.replaceState(null, '', link.hash);
    });
  }
}

/**
 * Beside the text on wide screens the boxes after the author card stay in view while the story scrolls (news.css). When
 * they are taller than the window, the top that holds them under the header would hide their foot, so it is lowered until
 * the foot shows: the stack then scrolls up with the page to its foot and stays there.
 */
function keepFollowInView() {
  const follow = document.querySelector('.bl-story-follow');
  if (!follow) return;
  const wide = window.matchMedia('(min-width: 1001px)');
  const foot = 24; // space kept under the foot, in px
  const place = () => {
    follow.style.removeProperty('--follow-top');
    if (!wide.matches) return;
    const normal = parseFloat(getComputedStyle(follow).top) || 0;
    const fits = normal + follow.offsetHeight + foot <= window.innerHeight;
    if (!fits) follow.style.setProperty('--follow-top', `${Math.round(window.innerHeight - follow.offsetHeight - foot)}px`);
  };
  window.addEventListener('resize', place);
  window.addEventListener('load', place);
  if ('ResizeObserver' in window) new ResizeObserver(place).observe(follow);
  place();
}
