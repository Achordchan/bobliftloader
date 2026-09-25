import { openDialog } from './dialogs.js';

export function initializeInquiry() {
  const form = document.querySelector('#inquiry-form');
  if (!form) return;
  const dialog = document.querySelector('#inquiry-dialog');
  const summary = document.querySelector('#inquiry-summary');
  const emailLink = document.querySelector('#inquiry-email');
  const copyStatus = document.querySelector('#copy-status');
  const productLabels = { loader: 'Skid steer loader', dumper: 'Mini dumper', help: 'Help choosing equipment' };
  let emailBody = '';
  const modelNote = form.querySelector('.bl-selected-model');
  if (modelNote) {
    form.querySelectorAll('[name="product"]').forEach((radio) => radio.addEventListener('change', () => {
      modelNote.hidden = form.querySelector('[name="product"]:checked').value !== modelNote.dataset.modelFamily;
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
    emailBody = `Hello BOB-LIFT team,\n\nI'd like to discuss the following equipment enquiry.\n\n${entries.map(([label, value]) => `${label}: ${value}`).join('\n')}\n\nPlease let me know about available models, specifications and quotation details.\n\nThank you.`;
    const subject = `Equipment enquiry — ${productLabels[fields.get('product')]}`;
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
