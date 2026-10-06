import { initializeDialogs } from './dialogs.js';
import { initializeEquipment, scrollBehavior } from './equipment.js';
import { initializeHero } from './hero.js';
import { initializeInquiry } from './inquiry.js';
import { initializeLightbox } from './lightbox.js';
import { initializeMaking } from './making.js';
import { initializeRuler } from './ruler.js';
import { initializeCatalog } from './catalog.js';
import { initializeSearch } from './search.js';
import { initializeSectionNav } from './section-nav.js';
import { initializeQuick } from './quick.js';

initializeDialogs();
initializeEquipment();
initializeHero();
initializeInquiry();
initializeLightbox();
initializeMaking();
initializeRuler();
initializeCatalog();
initializeSearch();
initializeSectionNav();
initializeQuick();

const menuToggle = document.querySelector('.bl-menu-toggle');
const navigation = document.querySelector('.bl-nav');
const header = document.querySelector('.bl-header');

// The root carries the menu's state too, so the stylesheet can clear the contact bar without :has().
function closeNavigation(returnFocus = false) {
  menuToggle.setAttribute('aria-expanded', 'false');
  menuToggle.setAttribute('aria-label', 'Open navigation');
  navigation.classList.remove('bl-open');
  document.documentElement.classList.remove('bl-menu-on');
  if (returnFocus) menuToggle.focus();
}

menuToggle.addEventListener('click', () => {
  const open = menuToggle.getAttribute('aria-expanded') !== 'true';
  menuToggle.setAttribute('aria-expanded', String(open));
  menuToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  navigation.classList.toggle('bl-open', open);
  document.documentElement.classList.toggle('bl-menu-on', open);
});

document.addEventListener('keydown', (event) => {
  if (event.key === 'Escape' && menuToggle.getAttribute('aria-expanded') === 'true') closeNavigation(true);
});
document.addEventListener('click', (event) => {
  if (!header.contains(event.target)) closeNavigation();
});
document.addEventListener('focusin', (event) => {
  if (!header.contains(event.target)) closeNavigation();
});
window.matchMedia('(min-width: 601px)').addEventListener('change', () => closeNavigation());

// Site switch (header, and the footer's copy that opens upward) and the language switch: each a <details>, so it opens without the script; this closes it
// on Escape, an outside click or focus leaving it, and keeps the header one and the phone menu from being open together.
document.querySelectorAll('.bl-site-switch, .bl-lang-switch').forEach((siteSwitch) => {
  const summary = siteSwitch.querySelector('summary');
  const inHeader = header.contains(siteSwitch);
  const closeSwitch = (event) => { if (siteSwitch.open && !siteSwitch.contains(event.target)) siteSwitch.open = false; };
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !siteSwitch.open) return;
    siteSwitch.open = false;
    summary.focus();
  });
  document.addEventListener('click', closeSwitch);
  document.addEventListener('focusin', closeSwitch);
  if (!inHeader) return;
  // The language switch sits inside the menu on phones, so opening it must leave the menu open.
  if (!navigation.contains(siteSwitch)) siteSwitch.addEventListener('toggle', () => { if (siteSwitch.open) closeNavigation(); });
  menuToggle.addEventListener('click', () => { siteSwitch.open = false; });
});

// The Equipment panel opens on hover or focus (CSS). Escape hides it without moving the pointer or focus; it comes back
// on the next hover of Equipment, or the next time focus arrives there after leaving.
const equipmentPanel = navigation.querySelector('.bl-nav-panel');
if (equipmentPanel) {
  const equipmentLink = equipmentPanel.previousElementSibling;
  const reopen = () => equipmentPanel.classList.remove('bl-nav-panel-closed');
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || getComputedStyle(equipmentPanel).position !== 'absolute' || getComputedStyle(equipmentPanel).visibility !== 'visible') return;
    equipmentPanel.classList.add('bl-nav-panel-closed');
    if (equipmentPanel.contains(document.activeElement)) equipmentLink.focus();
  });
  equipmentLink.addEventListener('mouseenter', reopen);
  equipmentLink.addEventListener('blur', reopen);
  equipmentPanel.addEventListener('focusin', reopen);
  window.matchMedia('(min-width: 601px)').addEventListener('change', reopen); // the phone menu always lists it
}

document.querySelectorAll('a[href^="#"]').forEach((anchor) => {
  anchor.addEventListener('click', (event) => {
    const target = document.getElementById(anchor.getAttribute('href').slice(1));
    if (!target) return;
    event.preventDefault();
    closeNavigation();
    if (target.id === 'top') {
      window.scrollTo({ top: 0, behavior: scrollBehavior() });
    } else {
      target.scrollIntoView({ behavior: scrollBehavior(), block: 'start' });
    }
    // Move keyboard context with same-page navigation without moving the viewport again.
    if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
    target.focus({ preventScroll: true });
  });
});

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
const reveals = [...document.querySelectorAll('.bl-reveal')];
let revealObserver;
function setMotionPreference() {
  revealObserver?.disconnect();
  document.body.classList.toggle('bl-motion', !reducedMotion.matches);
  if (reducedMotion.matches) return;
  revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.add('bl-visible');
      revealObserver.unobserve(entry.target);
    });
  }, { threshold: 0.08, rootMargin: '0px 0px 20px 0px' });
  reveals.forEach((element) => revealObserver.observe(element));
}
setMotionPreference();
reducedMotion.addEventListener('change', setMotionPreference);

// Past the first screen or so, the phone bar's Top button shows (page-responsive.css).
const markScroll = () => {
  header.classList.toggle('bl-scrolled', window.scrollY > 30);
  document.documentElement.classList.toggle('bl-past-fold', window.scrollY > 600);
};
let scheduled = false;
window.addEventListener('scroll', () => {
  if (scheduled) return;
  scheduled = true;
  requestAnimationFrame(() => {
    markScroll();
    scheduled = false;
  });
}, { passive: true });
markScroll();

// Rows that scroll sideways on phones take keyboard focus, so the arrow keys can scroll them, but only while they do scroll.
const swipeRows = [...document.querySelectorAll('[data-swipe-row]')];
if (swipeRows.length) {
  const markRows = () => swipeRows.forEach((row) => {
    if (row.scrollWidth > row.clientWidth + 1) row.tabIndex = 0;
    else row.removeAttribute('tabindex');
  });
  new ResizeObserver(markRows).observe(document.documentElement);
  markRows();
}

// The phone bar's Top button never covers something to press or type in: it steps aside while a field has focus, or
// while a link, button or control passes beneath it, and comes back once the spot is clear.
const topButton = document.querySelector('.bl-side-top');
if (topButton) {
  const controls = 'a[href],button,input,select,textarea,summary,label,[role=button],[role=tab],[tabindex]:not([tabindex="-1"])';
  let pending = false;
  const checkTop = () => {
    pending = false;
    const root = document.documentElement;
    if (getComputedStyle(topButton).position !== 'fixed' || window.scrollY <= 600) {
      root.classList.remove('bl-top-aside');
      return;
    }
    const typing = Boolean(document.activeElement?.matches('input,select,textarea'));
    const box = topButton.getBoundingClientRect();
    const pad = 6;
    const xs = [box.left - pad, box.left + 4, box.left + box.width / 2, box.right - 4, box.right + pad];
    const ys = [box.top - pad, box.top + 4, box.top + box.height / 2, box.bottom - 4, box.bottom + pad];
    const covered = !typing && xs.some((x) => ys.some((y) => {
      const element = document.elementsFromPoint(x, y).find((item) => !item.closest('.bl-side-contact'));
      const control = element?.closest(controls);
      if (!control) return false;
      // It may sit over the photograph of a large card, which stays easy to tap around it; never over a card's words,
      // or over anything button-sized.
      return control.getBoundingClientRect().height < 160 || !(element.matches('img') && element.getBoundingClientRect().width > 120);
    }));
    root.classList.toggle('bl-top-aside', typing || covered);
  };
  const scheduleTop = () => {
    if (pending) return;
    pending = true;
    requestAnimationFrame(checkTop);
  };
  ['scroll', 'focusin', 'focusout', 'toggle', 'transitionend'].forEach((type) => document.addEventListener(type, scheduleTop, { capture: true, passive: true }));
  window.addEventListener('resize', scheduleTop, { passive: true });
  new ResizeObserver(scheduleTop).observe(document.body);
  document.addEventListener('click', () => setTimeout(scheduleTop, 50), { passive: true });
  scheduleTop();
}

// The three support topics form one disclosure group, retaining native keyboard behavior.
document.querySelectorAll('.bl-accordions details').forEach((detail) => {
  detail.addEventListener('toggle', () => {
    if (!detail.open) return;
    document.querySelectorAll('.bl-accordions details').forEach((other) => {
      if (other !== detail) other.open = false;
    });
  });
});
