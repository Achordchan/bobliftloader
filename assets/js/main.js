import { initializeAttachmentLoops } from './attachment-loops.js';
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

initializeAttachmentLoops();
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

// While a field of the page itself has focus (the keyboard is up), the phone contact bar steps aside (page-responsive.css).
// Fields in the quick enquiry, which hangs from the bar, and in dialogs, which cover it, leave it be.
const typingField = (element) => Boolean(element?.matches('input:not([type=radio],[type=checkbox],[type=submit],[type=button],[type=reset]),textarea,select') && !element.closest('.bl-quick,dialog'));
document.addEventListener('focusin', (event) => document.documentElement.classList.toggle('bl-typing', typingField(event.target)));
document.addEventListener('focusout', (event) => { if (!typingField(event.relatedTarget)) document.documentElement.classList.remove('bl-typing'); });

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
// while a link, button or control passes beneath it, and comes back once the spot is clear. Figures set against the
// right edge (a spec value, a table cell, a fact, any right-aligned line) count too, but only where their characters
// reach under the button itself: a row that is empty beneath it keeps it.
const topButton = document.querySelector('.bl-side-top');
if (topButton) {
  const controls = 'a[href],button,input,select,textarea,summary,label,[role=button],[role=tab],[tabindex]:not([tabindex="-1"])';
  const figures = 'dd,td,th,.bl-approach-facts li>*';
  // A line box runs a few pixels above and below its letters, and the button is round: a line that only grazes the
  // button's square counts as clear.
  // Only the text itself is measured, never a block inside the figure, whose box runs the full width.
  const wordsUnder = (figure, box) => {
    const graze = 4;
    const range = document.createRange();
    const walker = document.createTreeWalker(figure, NodeFilter.SHOW_TEXT);
    for (let text = walker.nextNode(); text; text = walker.nextNode()) {
      if (!text.data.trim()) continue;
      range.selectNodeContents(text);
      if ([...range.getClientRects()].some((r) => r.width > 0 && r.right > box.left + graze && r.left < box.right - graze && r.bottom > box.top + graze && r.top < box.bottom - graze)) return true;
    }
    return false;
  };
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
    const checked = new Map();
    const covered = !typing && xs.some((x) => ys.some((y) => {
      const element = document.elementsFromPoint(x, y).find((item) => !item.closest('.bl-side-contact'));
      const control = element?.closest(controls);
      if (!control) {
        if (!element) return false;
        // The figure itself, or, from the gap beside a short one ("8"), the figures of the row the point falls in.
        const own = element.closest(figures) || (/^(right|end)$/.test(getComputedStyle(element).textAlign) ? element : null);
        const row = own ? null : element.closest('tr,dl>div,li');
        return (own ? [own] : row ? [...row.querySelectorAll(figures)] : []).some((figure) => {
          if (!checked.has(figure)) checked.set(figure, wordsUnder(figure, box));
          return checked.get(figure);
        });
      }
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

// On phones the enquiry message grows with its text (a prefilled model line runs to four lines at 360px), up to 240px.
// The stylesheet does it with field-sizing where supported; elsewhere the box takes its text's height here.
if (!CSS.supports('field-sizing', 'content')) {
  const phoneForm = window.matchMedia('(max-width: 800px), (max-width: 1000px) and (max-height: 500px)');
  const fit = (box) => {
    box.style.height = '';
    if (!phoneForm.matches || box.closest('.bl-quick-form') || box.scrollHeight <= box.clientHeight) return;
    box.style.height = `${Math.min(box.scrollHeight + box.offsetHeight - box.clientHeight, 240)}px`;
  };
  const boxes = [...document.querySelectorAll('.bl-inquiry-form textarea')];
  boxes.forEach((box) => { fit(box); box.addEventListener('input', () => fit(box)); });
  phoneForm.addEventListener('change', () => boxes.forEach(fit));
}

// On phones the equipment choices swipe in one row: a choice made beforehand (Parts & service, say) is brought into view
// inside its row, and so is a chip tapped while partly cut off. Only the row scrolls, never the page. A Contact Form 7
// list without label elements has its .wpcf7-list-item as the chip. Nothing here may stop the rest of this script: each
// step is guarded, and a row it cannot measure is simply left as it is.
const guarded = (step) => (...args) => {
  try { step(...args); } catch { /* a layout this does not know: the row keeps its own scroll */ }
};
const uprightPhone = window.matchMedia('(max-width: 600px) and (orientation: portrait)');
document.querySelectorAll('.bl-product-choices, .bl-inquiry-form .wpcf7-radio').forEach(guarded((row) => {
  const reveal = guarded((input) => {
    if (!input || row.scrollWidth <= row.clientWidth) return;
    const item = input.closest('label, .wpcf7-list-item');
    if (!item || !row.contains(item)) return;
    const chip = item.getBoundingClientRect();
    const view = row.getBoundingClientRect();
    const pad = parseFloat(getComputedStyle(row).paddingLeft) || 0;
    // The chips snap by their start (responsive.css), so a cut chip is brought to the start of the row (or as far as the
    // row goes): a smaller step would be snapped straight back.
    if (chip.right > view.right - pad || chip.left < view.left + pad) row.scrollLeft += chip.left - view.left - pad;
  });
  const edge = guarded(() => row.classList.toggle('bl-edge-start', row.scrollLeft > 2));
  row.addEventListener('scroll', edge, { passive: true });
  const settle = () => { reveal(row.querySelector('input:checked')); edge(); };
  settle();
  row.addEventListener('change', (event) => reveal(event.target));
  // The row only swipes once the phone is held upright, so turning it upright brings the choice made beforehand into view.
  uprightPhone.addEventListener?.('change', () => { if (uprightPhone.matches) requestAnimationFrame(guarded(settle)); });
}));

// A required field left empty: the browser brings it into view and focuses it, but can leave it under the pinned header
// and section bar. The first such field of each try is moved to just below them, its label showing (and above the phone
// bar where that is up). Only the first: the others of the same try fire too, and would each scroll again.
let settling = false;
document.querySelectorAll('form.bl-inquiry-form').forEach((form) => form.addEventListener('invalid', (event) => {
  if (settling || form.closest('.bl-quick')) return;
  settling = true;
  const field = event.target;
  setTimeout(guarded(() => {
    settling = false;
    const pinned = [...document.querySelectorAll('.bl-header,.bl-section-nav,.bl-product-section-nav,.bl-side-contact')].filter((bar) => {
      const style = getComputedStyle(bar);
      if (style.visibility === 'hidden' || !bar.offsetHeight) return false;
      // A sticky bar counts only while it is held at its place, not where it sits in the page.
      return style.position === 'fixed' || (style.position === 'sticky' && Math.abs(bar.getBoundingClientRect().top - (parseFloat(style.top) || 0)) < 1);
    }).map((bar) => bar.getBoundingClientRect());
    const ceiling = Math.max(0, ...pinned.filter((box) => box.top < innerHeight / 2).map((box) => box.bottom)) + 12;
    const floor = Math.min(innerHeight, ...pinned.filter((box) => box.top >= innerHeight / 2).map((box) => box.top)) - 12;
    const top = (field.closest('label') || field).getBoundingClientRect().top;
    const bottom = field.getBoundingClientRect().bottom;
    if (top < ceiling) window.scrollBy({ top: top - ceiling, behavior: 'instant' });
    else if (bottom > floor) window.scrollBy({ top: Math.min(bottom - floor, top - ceiling), behavior: 'instant' });
    if (document.activeElement !== field) field.focus({ preventScroll: true });
  }));
}, true));

// The three support topics form one disclosure group, retaining native keyboard behavior.
document.querySelectorAll('.bl-accordions details').forEach((detail) => {
  detail.addEventListener('toggle', () => {
    if (!detail.open) return;
    document.querySelectorAll('.bl-accordions details').forEach((other) => {
      if (other !== detail) other.open = false;
    });
  });
});
