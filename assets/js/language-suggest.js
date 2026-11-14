/**
 * Language suggestion: when the visitor's browser prefers another language the site has, and this page has a copy in it
 * (the hreflang links in the head, inc/seo.php), a small card offers that copy. Nothing redirects: English stays the
 * default, and search engines and the page cache see every page as before. Closing the card, taking it, or picking a
 * language in the header switch is remembered. The same behaviour as the crane site.
 */
const STORAGE_KEY = 'blLanguageChoice';
// Each card speaks the language it offers, so these cannot come from the page's own translations (assets/js/i18n.js).
const MESSAGES = {
  en: { text: 'This page is also available in English.', action: 'View in English', close: 'Close' },
  es: { text: 'Esta página también está disponible en español.', action: 'Ver en español', close: 'Cerrar' },
};
const primary = (tag) => String(tag || '').trim().toLowerCase().split(/[-_]/)[0];
// The choices a page was opened with that its counterpart keeps (inc/languages.php, bl_i18n_switch_query), and the place on
// the page. The hreflang links stay bare for search engines; only the visitor's link carries them.
const KEEP = ['product', 'equipment', 'application', 'topic', 'category', 'load', 'ground', 'width', 'q'];
const withContext = (href) => {
  const url = new URL(href, location.href);
  const current = new URLSearchParams(location.search);
  KEEP.forEach((name) => { if (current.get(name)) url.searchParams.set(name, current.get(name)); });
  if (location.hash) url.hash = location.hash;
  return url.href;
};

const remember = (language) => {
  try { localStorage.setItem(STORAGE_KEY, language); } catch { /* storage blocked: asked again on the next page */ }
};
const remembered = () => {
  try { return localStorage.getItem(STORAGE_KEY); } catch { return 'unknown'; }
};
const report = (action, language) => {
  if (typeof window.gtag === 'function') window.gtag('event', 'language_suggestion', { suggestion_action: action, suggested_language: language, transport_type: 'beacon' });
};

// The page's copies by language, from the same hreflang links search engines read (x-default is left out).
const copies = () => {
  const found = {};
  document.querySelectorAll('link[rel="alternate"][hreflang][href]').forEach((link) => {
    const language = primary(link.hreflang);
    if (MESSAGES[language] && !found[language]) found[language] = link.href;
  });
  return found;
};

// The first browser language the site has decides; one that is this page's language means no card.
const suggestion = () => {
  const current = primary(document.documentElement.lang) || 'en';
  const preferred = (navigator.languages && navigator.languages.length ? navigator.languages : [navigator.language]).map(primary);
  const wanted = preferred.find((language) => MESSAGES[language]);
  if (!wanted || wanted === current) return null;
  const href = copies()[wanted];
  if (!href || new URL(href, location.href).pathname === location.pathname) return null;
  return { current, language: wanted, href: withContext(href) };
};

const show = ({ current, language, href }) => {
  const words = MESSAGES[language];
  const card = document.createElement('aside');
  card.className = 'bl-lang-suggest';
  card.lang = language;
  card.setAttribute('aria-label', words.text);
  card.innerHTML = '<p></p><a class="bl-lang-suggest-accept"></a><button class="bl-lang-suggest-close" type="button"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M18 6 6 18M6 6l12 12"/></svg></button>';
  card.querySelector('p').textContent = words.text;
  const accept = card.querySelector('a');
  accept.textContent = words.action;
  accept.href = href;
  accept.hreflang = language;
  const close = card.querySelector('button');
  close.setAttribute('aria-label', words.close);

  const hide = () => {
    card.classList.remove('is-shown');
    window.setTimeout(() => card.remove(), 300);
  };
  accept.addEventListener('click', () => {
    remember(language);
    report('accept', language);
    hide();
  });
  close.addEventListener('click', () => {
    remember(current);
    report('dismiss', language);
    hide();
  });
  card.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') close.click();
  });

  // The cookie notice (templates/partials/consent.php) shares the corner and comes first: the card steps aside while it is open.
  const consent = document.querySelector('[data-bl-consent-banner]');
  if (consent) {
    const stepAside = () => { card.hidden = !consent.hidden; };
    stepAside();
    new MutationObserver(stepAside).observe(consent, { attributes: true, attributeFilter: ['hidden'] });
  }

  document.body.append(card);
  window.requestAnimationFrame(() => window.requestAnimationFrame(() => card.classList.add('is-shown')));
};

export function initializeLanguageSuggest() {
  // A language picked in the header switch is the visitor's answer too, even before the page has finished loading. The
  // switch also takes the place on the page along (#enquiry, say), which the server never sees.
  document.addEventListener('click', (event) => {
    const link = event.target instanceof Element ? event.target.closest('a[data-bl-lang]') : null;
    if (!link) return;
    remember(primary(link.dataset.blLang));
    if (location.hash && !link.hash) link.hash = location.hash;
  });
  // A page restored from the back/forward cache keeps its card; drop it once a choice has been saved (e.g. in another tab).
  window.addEventListener('pageshow', (event) => {
    if (event.persisted && remembered()) document.querySelectorAll('.bl-lang-suggest').forEach((card) => card.remove());
  });
  const init = () => {
    // Crawlers see every page as it is, without the card.
    if (/bot|crawl|spider|slurp|lighthouse/i.test(navigator.userAgent) || remembered()) return;
    const offer = suggestion();
    if (offer) window.setTimeout(() => show(offer), 1200);
  };
  if (document.readyState === 'complete') init();
  else window.addEventListener('load', init, { once: true });
}
