import { initializeDialogs } from './dialogs.js';
import { initializeEquipment, scrollBehavior } from './equipment.js';
import { initializeHero } from './hero.js';
import { initializeInquiry } from './inquiry.js';
import { initializeMaking } from './making.js';
import { initializeRuler } from './ruler.js';
import { initializeCatalog } from './catalog.js';
import { initializeSearch } from './search.js';
import { initializeSectionNav } from './section-nav.js';

initializeDialogs();
initializeEquipment();
initializeHero();
initializeInquiry();
initializeMaking();
initializeRuler();
initializeCatalog();
initializeSearch();
initializeSectionNav();

const menuToggle = document.querySelector('.bl-menu-toggle');
const navigation = document.querySelector('.bl-nav');
const header = document.querySelector('.bl-header');

function closeNavigation(returnFocus = false) {
  menuToggle.setAttribute('aria-expanded', 'false');
  menuToggle.setAttribute('aria-label', 'Open navigation');
  navigation.classList.remove('bl-open');
  if (returnFocus) menuToggle.focus();
}

menuToggle.addEventListener('click', () => {
  const open = menuToggle.getAttribute('aria-expanded') !== 'true';
  menuToggle.setAttribute('aria-expanded', String(open));
  menuToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  navigation.classList.toggle('bl-open', open);
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

// Site switch: a <details>, so it opens without the script; this closes it on Escape, an outside click or focus leaving
// it, and keeps it and the phone menu from being open together.
const siteSwitch = header.querySelector('.bl-site-switch');
if (siteSwitch) {
  const summary = siteSwitch.querySelector('summary');
  const closeSwitch = (event) => { if (siteSwitch.open && !siteSwitch.contains(event.target)) siteSwitch.open = false; };
  document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape' || !siteSwitch.open) return;
    siteSwitch.open = false;
    summary.focus();
  });
  document.addEventListener('click', closeSwitch);
  document.addEventListener('focusin', closeSwitch);
  siteSwitch.addEventListener('toggle', () => { if (siteSwitch.open) closeNavigation(); });
  menuToggle.addEventListener('click', () => { siteSwitch.open = false; });
}

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

let scheduled = false;
window.addEventListener('scroll', () => {
  if (scheduled) return;
  scheduled = true;
  requestAnimationFrame(() => {
    header.classList.toggle('bl-scrolled', window.scrollY > 30);
    scheduled = false;
  });
}, { passive: true });

// The three support topics form one disclosure group, retaining native keyboard behavior.
document.querySelectorAll('.bl-accordions details').forEach((detail) => {
  detail.addEventListener('toggle', () => {
    if (!detail.open) return;
    document.querySelectorAll('.bl-accordions details').forEach((other) => {
      if (other !== detail) other.open = false;
    });
  });
});
