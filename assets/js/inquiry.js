import { openDialog } from './dialogs.js';

const SENT_KEY = 'bl-enquiry-sent';

// A message written for the model the visitor came from ("…a quotation for the FY-30") only fits that model's family:
// it clears when they choose another family and comes back if they return, unless they have written their own.
function syncPrefill(container, matches) {
  const message = container.querySelector('textarea[name="message"]');
  if (!message) return;
  if (message.dataset.prefill === undefined) message.dataset.prefill = message.defaultValue;
  const prefill = message.dataset.prefill;
  if (!prefill) return;
  if (!matches && message.value === prefill) message.value = '';
  else if (matches && message.value === '') message.value = prefill;
}

const TURNSTILE = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
const NETWORK_ERROR = 'The enquiry could not be sent. Please check your connection and try again, or email us directly.';
let turnstileScript = null;

// Cloudflare's script, loaded once, the first time a form with a check comes into view.
function loadTurnstile() {
  turnstileScript ??= new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = TURNSTILE;
    script.async = true;
    script.onload = () => (window.turnstile ? resolve(window.turnstile) : reject(new Error('turnstile')));
    script.onerror = reject;
    document.head.append(script);
  });
  return turnstileScript;
}

// The check (when 设置 → 询盘设置 turns it on) is drawn when its form is first seen: the quick enquiry's stays unloaded
// until its panel opens.
function initializeTurnstile(form) {
  const box = form.querySelector('[data-turnstile-widget]');
  if (!box || !('IntersectionObserver' in window)) return null;
  const state = { id: null };
  const observer = new IntersectionObserver((entries) => {
    if (!entries.some((entry) => entry.isIntersecting)) return;
    observer.disconnect();
    loadTurnstile().then((turnstile) => {
      state.id = turnstile.render(box, { sitekey: box.dataset.sitekey, theme: box.dataset.theme || 'light' });
    }).catch(() => { /* the server says so if the check is missing */ });
  });
  observer.observe(box);
  return state;
}

// Send the form to the inquiry plugin and read its JSON answer; a page the cache kept past its form key gets a fresh key
// (wp-inquiry.php, bl_wp_inquiry_fresh_nonce) and is sent once more.
async function post(form, retried = false) {
  const response = await fetch(form.action, { method: 'POST', body: new FormData(form), credentials: 'same-origin', headers: { Accept: 'application/json' } });
  let answer = null;
  try { answer = await response.json(); } catch { /* not JSON: a server error page */ }
  if (response.status === 403 && !retried && form.dataset.nonceUrl) {
    try {
      const fresh = await (await fetch(form.dataset.nonceUrl, { credentials: 'same-origin', cache: 'no-store' })).json();
      const field = form.querySelector('input[name="bl_inquiry_nonce"]');
      if (fresh?.data?.nonce && field) {
        field.value = fresh.data.nonce;
        return post(form, true);
      }
    } catch { /* falls through to the message below */ }
  }
  return { ok: response.ok && answer?.success === true, data: answer?.data || {} };
}

// Enquiry sent to the inquiry plugin: the model the visitor came from only travels with their equipment choice.
function initializeSentInquiry(wrap) {
  const familyLabels = { loader: 'Skid steer loader', dumper: 'Mini dumper', service: 'Parts & service' };
  const form = wrap.querySelector('form.bl-inquiry-form');
  if (!form) return;
  const note = wrap.querySelector('.bl-selected-model');
  const model = form.querySelector('input[name="model"]');
  const status = form.querySelector('.bl-form-status');
  const button = form.querySelector('[type="submit"]');
  const sync = () => {
    const chosen = form.querySelector('input[name="product"]:checked')?.value;
    const matches = chosen === familyLabels[wrap.dataset.modelFamily];
    if (note) note.hidden = !matches;
    if (model) model.value = matches ? wrap.dataset.model : '';
    if (wrap.dataset.model) syncPrefill(wrap, matches);
  };
  form.querySelectorAll('input[name="product"]').forEach((radio) => radio.addEventListener('change', sync));
  sync();
  const check = initializeTurnstile(form);
  const say = (text, state) => {
    if (!status) return;
    status.textContent = text;
    status.dataset.state = state;
    status.hidden = !text;
  };
  const requiredText = [...form.querySelectorAll('input[required]:not([type="email"])')];
  requiredText.forEach((input) => input.addEventListener('input', () => input.setCustomValidity('')));

  // The ad source fields are added in the capture phase (attribution.js); this runs after, as the form is sent.
  form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.sending) return;
    for (const input of requiredText) {
      if (!input.value.trim()) {
        input.setCustomValidity('Please enter a value, not only spaces.');
        input.reportValidity();
        return;
      }
    }
    // What was chosen, read now: the visitor may change the form while it is sending.
    const sent = { product: form.querySelector('input[name="product"]:checked')?.value || '', model: model?.value || '' };
    form.dataset.sending = '1';
    form.setAttribute('aria-busy', 'true');
    if (button) button.disabled = true;
    say('', '');
    let result = { ok: false, data: {} };
    try { result = await post(form); } catch { /* network: the message below */ }
    if (result.ok) {
      // Once it is saved, the visitor goes on to the thank-you page, which reports the enquiry (see reportSentInquiry).
      // A blocked or bot enquiry is answered without an ID, so it is never counted.
      if (result.data.id) {
        try { sessionStorage.setItem(SENT_KEY, JSON.stringify(sent)); } catch { /* storage blocked: the page still loads */ }
      }
      say(result.data.message || '', 'sent');
      window.location.assign(result.data.redirect || wrap.dataset.thanks);
      return;
    }
    say(result.data.message || NETWORK_ERROR, 'error');
    delete form.dataset.sending;
    form.removeAttribute('aria-busy');
    if (button) button.disabled = false;
    if (check?.id != null) { try { window.turnstile?.reset(check.id); } catch { /* drawn again on the next try */ } }
  });
}

// On the thank-you page: an enquiry this tab has just sent becomes one "generate_lead" event in the dataLayer, for whatever
// tracking code is added later (Google Ads, GA4, Tag Manager). A reload or a visit without sending reports nothing.
function reportSentInquiry() {
  let sent = null;
  try {
    sent = JSON.parse(sessionStorage.getItem(SENT_KEY) || 'null');
    sessionStorage.removeItem(SENT_KEY);
  } catch { return; }
  if (!sent) return;
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event: 'generate_lead', form: 'enquiry', enquiry_product: sent.product || '', enquiry_model: sent.model || '' });
  // With the theme's own Google tag (inc/consent.php, bl_google_tag_markup, which sets blGoogleTag) the same enquiry is reported
  // to GA4 and, when a conversion is set, to Google Ads; Consent Mode decides whether that goes with cookies or as a cookieless
  // ping. A gtag from pasted code is left alone: that setup reports the dataLayer event above in its own way.
  if (!window.blGoogleTag || typeof window.gtag !== 'function') return;
  window.gtag('event', 'generate_lead', { form: 'enquiry', enquiry_product: sent.product || '', enquiry_model: sent.model || '' });
  if (window.blGoogleConversion) window.gtag('event', 'conversion', { send_to: window.blGoogleConversion });
}

export function initializeInquiry() {
  if (document.body.classList.contains('bl-page-thanks')) reportSentInquiry();
  // The page's own form and the quick enquiry in the corner both go to the inquiry plugin.
  document.querySelectorAll('.bl-inquiry-live').forEach(initializeSentInquiry);
  const form = document.querySelector('#inquiry-form');
  if (!form) return;
  const dialog = document.querySelector('#inquiry-dialog');
  const summary = document.querySelector('#inquiry-summary');
  const emailLink = document.querySelector('#inquiry-email');
  const recipient = form.dataset.email ?? decodeURIComponent(document.querySelector('.bl-side-contact a[href^="mailto:"]').getAttribute('href').slice(7).split('?')[0]);
  const copyStatus = document.querySelector('#copy-status');
  const productLabels = { loader: 'Skid steer loader', dumper: 'Mini dumper', help: 'Help choosing equipment', service: 'Parts & service' };
  let emailBody = '';
  const modelNote = form.querySelector('.bl-selected-model');
  if (modelNote) {
    form.querySelectorAll('[name="product"]').forEach((radio) => radio.addEventListener('change', () => {
      modelNote.hidden = form.querySelector('[name="product"]:checked').value !== modelNote.dataset.modelFamily;
      syncPrefill(form, !modelNote.hidden);
    }));
  }

  const requiredText = [...form.querySelectorAll('input[required]:not([type="email"])')];
  requiredText.forEach((input) => input.addEventListener('input', () => input.setCustomValidity('')));

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    for (const input of requiredText) {
      if (!input.value.trim()) {
        input.setCustomValidity('Please enter a value, not only spaces.');
        input.reportValidity();
        return;
      }
    }
    const fields = new FormData(form);
    const entries = [
      ['Equipment', productLabels[fields.get('product')]],
      ['Model', form.querySelector('[name="model"]')?.dataset.modelFamily === fields.get('product') ? fields.get('model') : ''],
      ['Name', fields.get('name').trim()],
      ['Email', fields.get('email').trim()],
      ['Country', fields.get('country').trim()],
      ['Company', fields.get('company').trim()],
      ['Project', fields.get('message').trim()],
    ].filter(([, value]) => value);

    summary.replaceChildren();
    entries.forEach(([label, value]) => {
      const row = document.createElement('div');
      const term = document.createElement('dt');
      const detail = document.createElement('dd');
      term.textContent = label;
      detail.textContent = value;
      row.append(term, detail);
      summary.append(row);
    });
    // An owner asking about parts or a fault gets a service request, not an equipment enquiry.
    const service = fields.get('product') === 'service';
    emailBody = `Hello BOB-LIFT team,\n\n${service ? 'I need help with a machine I already have.' : "I'd like to discuss the following equipment enquiry."}\n\n${entries.map(([label, value]) => `${label}: ${value}`).join('\n')}\n\n${service ? 'Please let me know how you can help.' : 'Please let me know about available models, specifications and quotation details.'}\n\nThank you.`;
    const subject = service ? 'Parts & service request' : `Equipment enquiry — ${productLabels[fields.get('product')]}`;
    // %, ?, # and & are URL syntax; the address itself may legitimately hold them.
    emailLink.href = `mailto:${recipient.replace(/[%?#&]/g, encodeURIComponent)}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(emailBody)}`;
    copyStatus.textContent = '';
    openDialog(dialog, form.querySelector('[type="submit"]'));
  });

  document.querySelector('#copy-inquiry').addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(emailBody);
      copyStatus.textContent = `Enquiry copied. Paste it into an email to ${recipient}.`;
    } catch {
      copyStatus.textContent = `Your browser could not copy automatically. Select and copy the details above, then email ${recipient}.`;
    }
  });
}
