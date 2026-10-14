// Which advertisement brought an enquiry. A visit that lands with a Google Ads click ID (gclid, or gbraid / wbraid on iOS) or
// campaign tags (utm_*) keeps them in this browser for 90 days, and an enquiry sent from any page carries them in hidden
// fields (read in inc/wp-inquiry.php, then kept by Flamingo and shown in the notification email). The visitor often looks at
// other pages before writing, so the landing page is kept with them.
// It follows the cookie banner exactly as the Google tag does: nothing is kept, and nothing is sent, unless advertising
// storage is agreed to. That is the banner's cookie when the visitor has chosen; before a choice it is Google's own
// consent state, which already holds the regional default (denied in the EEA, the UK and Switzerland, granted elsewhere).
// With neither readable, nothing is kept.
const KEY = 'bl_attribution';
const VERSION = 1;
const DAYS = 90;
const WAIT = 6000; // how long to wait for Google's tag to say what the default is
// Click IDs: a newer ad click replaces the stored record. Tags alone never replace a click ID.
const CLICK_IDS = ['gclid', 'gbraid', 'wbraid'];
const PARAMS = [...CLICK_IDS, 'gad_source', 'gad_campaignid', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];
const FIELD_PREFIX = 'bl_attr_';
const FIELDS = [...PARAMS, 'landing', 'first_seen'];

// Same limits as the server (bl_enquiry_attribution_clean): printable characters only, one line, short.
function clean(value, length) {
  return String(value ?? '').replace(/[\u0000-\u001f\u007f<>]/g, ' ').replace(/\s+/g, ' ').trim().slice(0, length);
}

function cleanParams(source) {
  const found = {};
  PARAMS.forEach((name) => {
    const value = clean(source[name], CLICK_IDS.includes(name) ? 255 : 120);
    if (value) found[name] = value;
  });
  return found;
}

function readCookieChoice() {
  const match = document.cookie.split('; ').find((row) => row.startsWith('bl_consent='));
  if (!match) return null;
  try {
    const saved = JSON.parse(decodeURIComponent(match.slice('bl_consent='.length)));
    return saved?.v === 1 && typeof saved.advertising === 'boolean' ? saved.advertising : null;
  } catch {
    return null;
  }
}

// Google's tag keeps its consent state in google_tag_data.ics (an internal that is not documented, so every step is checked
// and anything unexpected means "not agreed"). The latest update wins over the default.
function googleAdStorage() {
  try {
    const entry = window.google_tag_data?.ics?.entries?.ad_storage;
    if (!entry || typeof entry !== 'object') return null;
    const value = entry.update !== undefined ? entry.update : entry.default;
    return typeof value === 'boolean' ? value : null;
  } catch {
    return null;
  }
}

// true: agreed; false: refused; null: not known yet.
function advertisingAgreed() {
  const chosen = readCookieChoice();
  return chosen !== null ? chosen : googleAdStorage();
}

function readStored() {
  try {
    const saved = JSON.parse(localStorage.getItem(KEY) || 'null');
    if (!saved || saved.v !== VERSION || !(saved.expires > Date.now()) || typeof saved.params !== 'object' || !saved.params) return null;
    return saved;
  } catch {
    return null;
  }
}

function forget() {
  try { localStorage.removeItem(KEY); } catch { /* storage blocked */ }
}

// The record to keep after this visit: the new click or tags if the address has them, else what is already kept.
function nextRecord(stored) {
  const query = Object.fromEntries(new URLSearchParams(location.search));
  const found = cleanParams(query);
  const hasClick = CLICK_IDS.some((name) => found[name]);
  const storedHasClick = stored && CLICK_IDS.some((name) => stored.params[name]);
  if (!hasClick && (Object.keys(found).length === 0 || storedHasClick)) return stored;
  const now = Date.now();
  return { v: VERSION, first_seen: new Date(now).toISOString(), landing: clean(location.origin + location.pathname, 300), params: found, expires: now + DAYS * 864e5 };
}

function store(record) {
  if (!record) return;
  try { localStorage.setItem(KEY, JSON.stringify(record)); } catch { /* storage blocked: the visit simply is not recorded */ }
}

// The values the hidden fields hold, empty when advertising is not agreed.
function currentValues() {
  if (advertisingAgreed() !== true) return {};
  const record = readStored();
  if (!record) return {};
  return { ...record.params, landing: clean(record.landing, 300), first_seen: clean(record.first_seen, 40) };
}

function fillForm(form) {
  const values = currentValues();
  FIELDS.forEach((name) => {
    let input = form.querySelector(`input[name="${FIELD_PREFIX}${name}"]`);
    if (!values[name]) { input?.remove(); return; }
    if (!input) {
      input = document.createElement('input');
      input.type = 'hidden';
      input.name = FIELD_PREFIX + name;
      form.append(input);
    }
    input.value = values[name];
  });
}

// Keep this visit's tags if advertising is agreed; drop a record kept earlier if it has been refused since.
function settle() {
  const agreed = advertisingAgreed();
  if (agreed === true) store(nextRecord(readStored()));
  else if (agreed === false) forget();
  return agreed;
}

export function initializeAttribution() {
  try {
    // Google's tag loads after the page: until it has said what the default is, the answer is "not known yet", so ask again for a few seconds.
    let waited = 0;
    const ask = () => {
      if (settle() !== null || waited >= WAIT) return;
      waited += 250;
      setTimeout(ask, 250);
    };
    ask();
    // The form is filled in when it is sent, so a choice made on this page counts; the capture phase runs before Contact Form 7 reads the form.
    document.addEventListener('submit', (event) => {
      const form = event.target;
      if (!(form instanceof HTMLFormElement) || !form.classList.contains('bl-inquiry-form')) return;
      settle();
      fillForm(form);
    }, true);
  } catch { /* the enquiry form works without it */ }
}

// Called by the cookie banner when advertising is refused (assets/js/consent.js): the kept record goes with the cookies.
export function forgetAttribution() {
  forget();
}
