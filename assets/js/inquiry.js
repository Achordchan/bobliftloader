import { openDialog } from './dialogs.js';

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

// Enquiry sent through Contact Form 7: the model the visitor came from only travels with their equipment choice.
function initializeSentInquiry(wrap) {
  const familyLabels = { loader: 'Skid steer loader', dumper: 'Mini dumper', service: 'Parts & service' };
  const note = wrap.querySelector('.bl-selected-model');
  const model = wrap.querySelector('input[name="model"]');
  const sync = () => {
    const chosen = wrap.querySelector('input[name="product"]:checked')?.value;
    const matches = chosen === familyLabels[wrap.dataset.modelFamily];
    if (note) note.hidden = !matches;
    if (model) model.value = matches ? wrap.dataset.model : '';
    if (wrap.dataset.model) syncPrefill(wrap, matches);
  };
  wrap.querySelectorAll('input[name="product"]').forEach((radio) => radio.addEventListener('change', sync));
  wrap.addEventListener('wpcf7reset', () => setTimeout(sync));
  sync();
}

export function initializeInquiry() {
  const sent = document.querySelector('.bl-inquiry-cf7');
  if (sent) initializeSentInquiry(sent);
  const form = document.querySelector('#inquiry-form');
  if (!form) return;
  const dialog = document.querySelector('#inquiry-dialog');
  const summary = document.querySelector('#inquiry-summary');
  const emailLink = document.querySelector('#inquiry-email');
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
    emailLink.href = `mailto:info@boblift.com?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(emailBody)}`;
    copyStatus.textContent = '';
    openDialog(dialog, form.querySelector('[type="submit"]'));
  });

  document.querySelector('#copy-inquiry').addEventListener('click', async () => {
    try {
      await navigator.clipboard.writeText(emailBody);
      copyStatus.textContent = 'Enquiry copied. Paste it into an email to info@boblift.com.';
    } catch {
      copyStatus.textContent = 'Your browser could not copy automatically. Select and copy the details above, then email info@boblift.com.';
    }
  });
}
