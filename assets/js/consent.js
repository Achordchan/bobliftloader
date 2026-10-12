// The cookie banner (templates/partials/consent.php). Tracking code is saved in WordPress as inert <template> elements
// tagged with a category (inc/wp-tracking.php); nothing in them runs until the visitor agrees to that category. The
// choice is kept for a year in one cookie of this site's own. A category agreed to starts its code at once; withdrawing
// one clears the tools' own cookies and reloads, so a script already running stops.
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

// Scripts run strictly one after another, across slots and categories: a plain external library is awaited (loaded or failed) before the inline code that needs it, which a plain insertion cannot guarantee; async and defer scripts are not waited for.
const jobs = [];
let pumping = false;
let writeSlot = 0; // where the next job written by the running script goes, so writes keep their order and run before the rest
let cursor = null; // the node the running script's markup goes after

async function pump() {
  if (pumping) return;
  pumping = true;
  while (jobs.length) {
    const job = jobs.shift();
    writeSlot = 0;
    cursor = null;
    await job();
  }
  pumping = false;
}

function schedule(placeholder, old, at = jobs.length) {
  jobs.splice(at, 0, () => runScript(placeholder, old));
  pump();
}

// A saved snippet was written for the page head, where it ran while the page was still loading. Agreed to later, it is run
// with three allowances while any of its scripts is still starting up: a wait for DOMContentLoaded or load (listener or
// `window.onload =`) is answered at once because those events are over, and document.write is redirected because after
// loading it would wipe the page. Shared by all running scripts and removed when the last one has started.
let lateUsers = 0;
let undoLate = () => {};

// Scripts started without being waited for (async, defer) that have not arrived yet: DOMContentLoaded would have waited for the
// deferred ones and load for all of them, so a replayed callback waits too, and keeps the allowances until it has finished.
const pending = { all: new Set(), deferred: new Set() };

function replay(type, callback) {
  const release = holdLateAllowances();
  Promise.all([...(type === 'load' ? pending.all : pending.deferred)]).then(() => setTimeout(() => {
    try { callback(); } finally { release(); }
  }));
}

function lateAllowances() {
  const past = { DOMContentLoaded: () => document.readyState !== 'loading', load: () => document.readyState === 'complete' };
  const restore = [document, window].map((target) => {
    const own = Object.prototype.hasOwnProperty.call(target, 'addEventListener') ? target.addEventListener : null;
    const original = target.addEventListener;
    target.addEventListener = function (type, listener, options) {
      if (!past[type]?.() || !listener) return original.call(this, type, listener, options);
      replay(type, () => { const event = new Event(type); if (typeof listener === 'function') listener.call(this, event); else listener.handleEvent(event); });
    };
    return () => { if (own) target.addEventListener = own; else delete target.addEventListener; };
  });
  let holder = window;
  while (holder && !Object.getOwnPropertyDescriptor(holder, 'onload')) holder = Object.getPrototypeOf(holder);
  const native = holder && Object.getOwnPropertyDescriptor(holder, 'onload');
  const ownLoad = Object.getOwnPropertyDescriptor(window, 'onload');
  if (native?.set && (!ownLoad || ownLoad.configurable)) {
    Object.defineProperty(window, 'onload', {
      configurable: true,
      enumerable: native.enumerable,
      get: () => native.get.call(window),
      set(handler) {
        if (document.readyState === 'complete' && typeof handler === 'function') replay('load', () => handler.call(window, new Event('load')));
        else native.set.call(window, handler);
      },
    });
    restore.push(() => { if (ownLoad) Object.defineProperty(window, 'onload', ownLoad); else delete window.onload; });
  }
  ['write', 'writeln'].forEach((name) => {
    const own = Object.prototype.hasOwnProperty.call(document, name) ? document[name] : null;
    const original = document[name];
    // After loading, document.write would replace the page; what the snippet writes is put in after the writing script instead.
    document[name] = function (...args) {
      if (document.readyState === 'loading') return original.apply(this, args);
      const holder = document.createElement('template');
      holder.innerHTML = args.join('') + (name === 'writeln' ? '\n' : '');
      cursor = cursor || document.currentScript || document.body.lastElementChild || document.body;
      // Markup goes in after the writing script, in order; a script in it joins the ordered queue right behind the writer, so it runs (and is awaited) before the code that followed.
      [...holder.content.childNodes].forEach((node) => {
        if (node.nodeName === 'SCRIPT') {
          const placeholder = document.createComment('');
          cursor.after(placeholder);
          cursor = placeholder;
          if (pumping) schedule(placeholder, node, writeSlot++);
          else schedule(placeholder, node);
        } else {
          cursor.after(node);
          cursor = node;
        }
      });
      return undefined;
    };
    restore.push(() => { if (own) document[name] = own; else delete document[name]; });
  });
  return () => restore.forEach((undo) => undo());
}

function holdLateAllowances() {
  if (lateUsers++ === 0) undoLate = lateAllowances();
  let released = false;
  return () => {
    if (released) return;
    released = true;
    if (--lateUsers === 0) undoLate();
  };
}

const JAVASCRIPT = /^(?:(?:application|text)\/(?:x-)?(?:java|ecma|live|j)script|module)(?:;.*)?$/i;

function runScript(placeholder, old) {
  return new Promise((resolve) => {
    const script = document.createElement('script');
    [...old.attributes].forEach(({ name, value }) => script.setAttribute(name, value));
    script.text = old.textContent;
    // A browser skips a nomodule script when it understands modules, and a script of another type (a JSON block) is never fetched or run;
    // neither fires load or error, so neither may be waited for.
    const type = (old.getAttribute('type') || '').trim();
    const runs = (!type || JAVASCRIPT.test(type)) && !(old.hasAttribute('nomodule') && 'noModule' in HTMLScriptElement.prototype);
    const external = runs && Boolean(script.src);
    // A script its author marked async or defer is not waited for, as on the original page: the code after it never depended on it.
    const waits = external && !old.hasAttribute('async') && !old.hasAttribute('defer');
    if (!old.hasAttribute('async')) script.async = false;
    const release = holdLateAllowances();
    // The allowances last until the script has executed: for a downloaded script that is its load, even when the queue does not wait for it.
    if (external) {
      script.addEventListener('load', release);
      script.addEventListener('error', release);
    }
    if (waits) {
      script.addEventListener('load', resolve);
      script.addEventListener('error', resolve);
    }
    if (external && !waits) {
      const arrived = new Promise((done) => { script.addEventListener('load', done); script.addEventListener('error', done); });
      pending.all.add(arrived);
      if (old.hasAttribute('defer') && !old.hasAttribute('async')) pending.deferred.add(arrived);
      arrived.then(() => { pending.all.delete(arrived); pending.deferred.delete(arrived); });
    }
    placeholder.replaceWith(script);
    if (!external) release();
    if (!waits) resolve();
  });
}

// Puts a category's saved code into the page where its template sits. Scripts parsed inside a template do not run, so each
// is rebuilt (attributes and text copied) and run in order; <noscript> is for visitors without scripts, who cannot agree.
function start(category) {
  document.querySelectorAll(`template[data-bl-consent="${category}"]`).forEach((template) => {
    if (template.dataset.blStarted) return;
    template.dataset.blStarted = 'true';
    const code = template.content.cloneNode(true);
    code.querySelectorAll('noscript').forEach((node) => node.remove());
    const scripts = [...code.querySelectorAll('script')].map((old) => {
      const placeholder = document.createComment('');
      old.replaceWith(placeholder);
      return [placeholder, old];
    });
    template.after(code);
    scripts.forEach(([placeholder, old]) => schedule(placeholder, old));
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
  const active = new Set();
  const run = (name) => { active.add(name); start(name); };

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
  // A choice made here or in another tab: categories already running that are no longer agreed to stop with a reload
  // (their cookies are cleared first); anything newly agreed to starts, and a banner still open is no longer needed.
  const apply = (next) => {
    choice = next;
    const declined = categories.filter((name) => !next[name]);
    if (declined.length) clearToolCookies(declined);
    if (declined.some((name) => active.has(name))) {
      location.reload();
      return;
    }
    categories.filter((name) => next[name]).forEach(run);
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
    categories.filter((name) => choice[name]).forEach(run);
  } else open(false);
}
