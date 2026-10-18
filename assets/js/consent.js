// The cookie banner (templates/partials/consent.php). Tracking code is saved in WordPress as inert <template> elements
// tagged with a category (inc/wp-tracking.php, bl_tracking_markup); a few lines beside each write it into the page while
// the page is being read, but only when the cookie below has agreed to that category. So this script never runs tracking
// code itself: it keeps the choice (one cookie of this site's own, for a year) and reloads the page when the set of
// agreed categories changes, which starts newly agreed code in the page's normal way and stops withdrawn code (after
// clearing the tools' own cookies).
import { forgetAttribution } from './attribution.js';

const COOKIE = 'bl_consent';
const VERSION = 1;
const YEAR = 365 * 24 * 60 * 60;
// Cookies the usual tools leave on this site's own domain, by category; declining a category removes them, so cookies
// set before the banner existed (or by an earlier choice) do not outlive a refusal.
// Yandex's Metrica cookies (_ym…) serve both: analytics, and the audiences used for its advertising, so withdrawing either clears them (the one still agreed sets its own again on the reload that follows).
const TOOL_COOKIES = {
  analytics: /^(_ga|_gid|_gat|_dc_gtm|_ym|_clck|_clsk|_hj)/,
  advertising: /^(_gcl|_fb[pc]$|_ttp|_tt_|ttcsid|_uet|_pin_|_rdt|_li_|li_|_ym|ymex$|yandexuid$|yabs-sid$)/,
};

function readSaved() {
  const match = document.cookie.split('; ').find((row) => row.startsWith(`${COOKIE}=`));
  if (!match) return null;
  try {
    const saved = JSON.parse(decodeURIComponent(match.slice(COOKIE.length + 1)));
    return saved?.v === VERSION ? saved : null;
  } catch {
    return null;
  }
}

function readChoice(categories) {
  const saved = readSaved();
  // A category added since the visitor chose has no answer yet, so they are asked again.
  return saved && categories.every((name) => typeof saved[name] === 'boolean') ? Object.fromEntries(categories.map((name) => [name, saved[name]])) : null;
}

function saveChoice(choice) {
  const secure = location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${COOKIE}=${encodeURIComponent(JSON.stringify({ v: VERSION, ...choice }))}; Max-Age=${YEAR}; Path=/; SameSite=Lax${secure}`;
}

function clearToolCookies(categories) {
  // The advertisement the visitor came from (assets/js/attribution.js) is kept in the browser's storage, not a cookie, and goes with advertising.
  if (categories.includes('advertising')) forgetAttribution();
  const labels = location.hostname.split('.');
  const domains = ['', ...labels.map((_, index) => labels.slice(index).join('.')).filter((name) => name.includes('.')).flatMap((name) => [name, `.${name}`])];
  // A tool may have been told to keep its cookie on a deeper path (GA's cookie_path), so every ancestor of this page's path is tried as well as the root.
  const parts = location.pathname.split('/').filter(Boolean);
  const paths = ['/', ...parts.flatMap((_, index) => { const path = `/${parts.slice(0, index + 1).join('/')}`; return [path, `${path}/`]; })];
  document.cookie.split('; ').map((row) => row.split('=')[0]).filter((name) => categories.some((category) => TOOL_COOKIES[category]?.test(name))).forEach((name) => {
    domains.forEach((domain) => paths.forEach((path) => {
      document.cookie = `${name}=; Max-Age=0; Path=${path}${domain ? `; Domain=${domain}` : ''}`;
    }));
  });
}

// Where the banner lists regions (templates/partials/consent.php), it asks on its own only there: Europe's time zones count
// at once, otherwise the country comes from WordPress.com's geolocation (kept for the visit). A visitor whose country
// cannot be told is asked.
const GEO = 'https://public-api.wordpress.com/geo/';
const EUROPE_ZONES = /^(?:Europe\/|Atlantic\/(?:Reykjavik|Canary|Madeira|Azores|Faroe)$|Arctic\/Longyearbyen$)/;

export async function askedHere(list) {
  if (!list) return true;
  try {
    if (EUROPE_ZONES.test(Intl.DateTimeFormat().resolvedOptions().timeZone || '')) return true;
  } catch {
    // no time zone: the country decides
  }
  let country = '';
  try { country = sessionStorage.getItem('bl_country') || ''; } catch { country = ''; }
  if (!/^[A-Z]{2}$/.test(country)) {
    const abort = new AbortController();
    const timer = setTimeout(() => abort.abort(), 4000);
    try {
      const response = await fetch(GEO, { signal: abort.signal, credentials: 'omit' });
      country = String((await response.json())?.country_short || '').toUpperCase();
    } catch {
      return true;
    } finally {
      clearTimeout(timer);
    }
    if (!/^[A-Z]{2}$/.test(country)) return true;
    try { sessionStorage.setItem('bl_country', country); } catch { /* asked again on the next page */ }
  }
  return list.split(',').includes(country);
}

function reloadedJustNow() {
  try {
    const last = Number(sessionStorage.getItem('bl_consent_reload') || 0);
    sessionStorage.setItem('bl_consent_reload', String(Date.now()));
    return Date.now() - last < 10000;
  } catch {
    return false;
  }
}

export function initializeConsent() {
  const banner = document.querySelector('[data-bl-consent-banner]');
  if (!banner) return;
  const categories = banner.dataset.categories.split(',');
  const choices = banner.querySelector('.bl-consent-choices');
  const boxes = [...banner.querySelectorAll('input[type="checkbox"]')];
  const control = (name) => banner.querySelector(`[data-bl-consent="${name}"]`);
  const openers = [...document.querySelectorAll('[data-bl-consent-open]')];
  let choice = readChoice(categories);
  let opener = null;
  // The categories whose code is running on this page: those the gates beside the saved code actually started, which can
  // differ from what the cookie says now (a category added since the visitor chose leaves the choice incomplete while the
  // answered categories' code already runs).
  const active = new Set(categories.filter((name) => window.blConsentRan?.[name] === true));

  const showChoices = (on) => {
    choices.hidden = !on;
    control('save').hidden = !on;
    control('choose')?.setAttribute('aria-expanded', String(on));
    if (control('choose')) control('choose').hidden = on;
  };
  const open = (withChoices, from = null) => {
    opener = from;
    boxes.forEach((box) => { box.checked = choice?.[box.name] === true; });
    showChoices(withChoices);
    // Once a choice exists the banner can be dismissed without changing it.
    control('close').hidden = !choice;
    banner.hidden = false;
    if (from) banner.querySelector('#bl-consent-title').focus();
  };
  const close = () => {
    banner.hidden = true;
    opener?.focus();
    opener = null;
  };
  const decide = (next) => {
    saveChoice(next);
    channel?.postMessage('changed');
    apply(next);
  };
  // A choice made here or in another tab. Code is only ever started while a page is read, so a page whose agreed categories
  // differ from what it was served with reloads (declined cookies are cleared first); otherwise there is nothing to redo.
  const apply = (next) => {
    choice = next;
    // The theme's Google tag runs before any choice (Consent Mode, inc/consent.php): it is told at once, reload or not.
    if (typeof window.gtag === 'function') {
      const ads = next.advertising === true ? 'granted' : 'denied';
      window.gtag('consent', 'update', { analytics_storage: next.analytics === true ? 'granted' : 'denied', ad_storage: ads, ad_user_data: ads, ad_personalization: ads });
    }
    const declined = categories.filter((name) => !next[name]);
    if (declined.length) clearToolCookies(declined);
    if (categories.some((name) => Boolean(next[name]) !== active.has(name))) {
      location.reload();
      return;
    }
    close();
  };
  // The page's running code and the cookie must agree, in both directions: a category running here that the cookie no longer
  // agrees to (withdrawn in another tab, cookie expired or deleted) is stopped, its cookies cleared, by reloading without it;
  // a category agreed in another tab after this page's gates ran (the broadcast may have come before this script) is started
  // by reloading, where the gates now write it. True when it reloads.
  const reconcile = () => {
    const saved = readSaved();
    const stale = [...active].filter((name) => saved?.[name] !== true);
    const missing = categories.filter((name) => saved?.[name] === true && !active.has(name));
    if (!stale.length && !missing.length) return false;
    // A gate that did not run for an agreed category (a script that failed) must not reload the page in a loop.
    if (!stale.length && reloadedJustNow()) return false;
    choice = readChoice(categories);
    if (stale.length) clearToolCookies(stale);
    location.reload();
    return true;
  };
  const sync = () => {
    if (reconcile()) return;
    const next = readChoice(categories);
    if (next) {
      if (JSON.stringify(next) !== JSON.stringify(choice)) apply(next);
      return;
    }
    choice = null;
    // The other tab knew other categories, so it left some unanswered here; what it refused is still refused.
    const declined = categories.filter((name) => readSaved()?.[name] === false);
    if (declined.length) clearToolCookies(declined);
  };

  const decisions = {
    accept: () => decide(Object.fromEntries(categories.map((name) => [name, true]))),
    reject: () => decide(Object.fromEntries(categories.map((name) => [name, false]))),
    save: () => decide(Object.fromEntries(boxes.map((box) => [box.name, box.checked]))),
    choose: () => { showChoices(true); boxes[0]?.focus(); },
    close,
  };
  banner.addEventListener('click', (event) => {
    const action = event.target.closest('[data-bl-consent]')?.dataset.blConsent;
    if (action) decisions[action]();
  });
  banner.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && choice) close();
  });
  openers.forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => open(categories.length > 1, button));
  });

  // Other tabs of this site learn of a change at once where BroadcastChannel exists, and on return to the tab otherwise.
  const channel = typeof BroadcastChannel === 'function' ? new BroadcastChannel('bl-consent') : null;
  channel?.addEventListener('message', sync);
  document.addEventListener('visibilitychange', () => { if (!document.hidden) sync(); });

  // The choice may have changed between the gates running (while the page was read) and this script: running code is checked against the cookie as it is now.
  if (reconcile()) return;
  // What was refused stays cleaned up: a cookie on a path this page could not reach when the choice was made is removed when it can.
  if (choice) {
    const declined = categories.filter((name) => !choice[name]);
    if (declined.length) clearToolCookies(declined);
  } else {
    // Opened meanwhile from the footer, or chosen in another tab: nothing more to ask.
    askedHere(banner.dataset.regions).then((ask) => { if (ask && !choice && banner.hidden) open(false); });
  }
}
