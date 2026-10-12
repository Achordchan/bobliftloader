// The cookie banner (templates/partials/consent.php). Tracking code is saved in WordPress as inert <template> elements
// tagged with a category (inc/wp-tracking.php); nothing in them runs until the visitor agrees to that category. The
// choice is kept for a year in one cookie of this site's own. A category agreed to starts its code at once; withdrawing
// one clears the tools' own cookies and reloads, so a script already running stops.
const COOKIE = 'bl_consent';
const VERSION = 1;
const YEAR = 365 * 24 * 60 * 60;
// Cookies the usual tools leave on this site's own domain; withdrawing a category removes them.
const TOOL_COOKIES = /^(_ga|_gid|_gat|_gcl|_dc_gtm|_fb[pc]$|_ym|_ttp|_tt_|_clck|_clsk|_uet|_hj|_pin_|_rdt|_li_|li_)/;

function readChoice(categories) {
  const match = document.cookie.split('; ').find((row) => row.startsWith(`${COOKIE}=`));
  if (!match) return null;
  try {
    const saved = JSON.parse(decodeURIComponent(match.slice(COOKIE.length + 1)));
    // A category added since the visitor chose has no answer yet, so they are asked again.
    return saved?.v === VERSION && categories.every((name) => typeof saved[name] === 'boolean') ? Object.fromEntries(categories.map((name) => [name, saved[name]])) : null;
  } catch {
    return null;
  }
}

function saveChoice(choice) {
  const secure = location.protocol === 'https:' ? '; Secure' : '';
  document.cookie = `${COOKIE}=${encodeURIComponent(JSON.stringify({ v: VERSION, ...choice }))}; Max-Age=${YEAR}; Path=/; SameSite=Lax${secure}`;
}

function clearToolCookies() {
  const labels = location.hostname.split('.');
  const domains = ['', ...labels.map((_, index) => labels.slice(index).join('.')).filter((name) => name.includes('.')).flatMap((name) => [name, `.${name}`])];
  document.cookie.split('; ').map((row) => row.split('=')[0]).filter((name) => TOOL_COOKIES.test(name)).forEach((name) => {
    domains.forEach((domain) => {
      document.cookie = `${name}=; Max-Age=0; Path=/${domain ? `; Domain=${domain}` : ''}`;
    });
  });
}

// Puts a category's saved code into the page where its template sits. Scripts parsed inside a template do not run, so each
// is rebuilt (attributes and text copied) and kept in order; <noscript> is for visitors without scripts, who cannot agree.
function start(category) {
  document.querySelectorAll(`template[data-bl-consent="${category}"]`).forEach((template) => {
    if (template.dataset.blStarted) return;
    template.dataset.blStarted = 'true';
    const code = template.content.cloneNode(true);
    code.querySelectorAll('noscript').forEach((node) => node.remove());
    code.querySelectorAll('script').forEach((old) => {
      const script = document.createElement('script');
      [...old.attributes].forEach(({ name, value }) => script.setAttribute(name, value));
      script.text = old.textContent;
      script.async = false;
      old.replaceWith(script);
    });
    template.after(code);
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
    const withdrawn = categories.some((name) => choice?.[name] && !next[name]);
    saveChoice(next);
    choice = next;
    categories.filter((name) => next[name]).forEach(start);
    if (withdrawn) {
      clearToolCookies();
      location.reload();
      return;
    }
    close();
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

  if (choice) categories.filter((name) => choice[name]).forEach(start);
  else open(false);
}
