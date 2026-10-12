// The cookie banner (templates/partials/consent.php). Tracking code is saved in WordPress as inert <template> elements
// tagged with a category (inc/wp-tracking.php, bl_tracking_markup); a few lines beside each write it into the page while
// the page is being read, but only when the cookie below has agreed to that category. So this script never runs tracking
// code itself: it keeps the choice (one cookie of this site's own, for a year) and reloads the page when the set of
// agreed categories changes, which starts newly agreed code in the page's normal way and stops withdrawn code (after
// clearing the tools' own cookies).
const COOKIE = 'bl_consent';
const VERSION = 1;
const YEAR = 365 * 24 * 60 * 60;
// Cookies the usual tools leave on this site's own domain, by category; declining a category removes them, so cookies
// set before the banner existed (or by an earlier choice) do not outlive a refusal.
const TOOL_COOKIES = {
  analytics: /^(_ga|_gid|_gat|_dc_gtm|_ym|_clck|_clsk|_hj)/,
  advertising: /^(_gcl|_fb[pc]$|_ttp|_tt_|_uet|_pin_|_rdt|_li_|li_)/,
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
  // The categories whose code this page was served with.
  const active = new Set(categories.filter((name) => choice?.[name]));

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
    const declined = categories.filter((name) => !next[name]);
    if (declined.length) clearToolCookies(declined);
    if (categories.some((name) => Boolean(next[name]) !== active.has(name))) {
      location.reload();
      return;
    }
    close();
  };
  const sync = () => {
    const next = readChoice(categories);
    if (next) {
      if (JSON.stringify(next) !== JSON.stringify(choice)) apply(next);
      return;
    }
    // The other tab knew other categories, so it left some unanswered here; what it refused is still refused.
    const declined = categories.filter((name) => readSaved()?.[name] === false);
    if (!declined.length) return;
    clearToolCookies(declined);
    if (declined.some((name) => active.has(name))) location.reload();
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

  // What was refused stays cleaned up: a cookie on a path this page could not reach when the choice was made is removed when it can.
  if (choice) {
    const declined = categories.filter((name) => !choice[name]);
    if (declined.length) clearToolCookies(declined);
  } else open(false);
}
