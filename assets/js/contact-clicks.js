// Getting in touch outside the enquiry form: a click on a WhatsApp link, a click on an email address (mailto:), or copying an
// email address (selecting it and copying, or the "copy enquiry" button of the email fallback, whose text goes to that
// address). The conversation itself then happens in the salesperson's WhatsApp or mailbox, where the site never sees it,
// so the step is reported: one event in the dataLayer for whatever tracking code is added later ("whatsapp_click",
// "email_click", "email_copy"), and with the theme's own Google tag (inc/consent.php, bl_google_tag_markup) the same event
// to GA4 and, when the matching conversion is saved on the tracking screen (blGoogleWhatsApp, blGoogleEmail), a Google
// Ads conversion. Consent Mode decides, as for an enquiry, whether that goes with cookies or as a cookieless ping.
// Nothing waits for the report: the link opens and the text is copied as before.
const WHATSAPP = /^https?:\/\/(?:wa\.me|api\.whatsapp\.com|(?:web\.)?whatsapp\.com)\//i;
const EMAIL = /[^\s@<>()"',;:]+@[^\s@<>()"',;:]+\.[a-z]{2,}/i;

function report(event, conversion, details) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event, ...details });
  if (!window.blGoogleTag || typeof window.gtag !== 'function') return;
  window.gtag('event', event, { ...details, transport_type: 'beacon' });
  if (conversion) window.gtag('event', 'conversion', { send_to: conversion, transport_type: 'beacon' });
}

export function initializeContactClicks() {
  // Captured on the document, so links drawn later (dialogs, the quick enquiry, translated pages) count too, and before any
  // other handler can stop the click. A middle click still opens the link, so it counts; a right click does not.
  const onClick = (event) => {
    if (event.type === 'auxclick' && event.button !== 1) return;
    const target = event.target instanceof Element ? event.target : null;
    if (target?.closest('#copy-inquiry')) {
      report('email_copy', window.blGoogleEmail, { copied: 'enquiry' });
      return;
    }
    const link = target?.closest('a[href]');
    if (!link) return;
    if (WHATSAPP.test(link.href)) report('whatsapp_click', window.blGoogleWhatsApp, { link_url: link.href });
    else if (/^mailto:/i.test(link.href)) report('email_click', window.blGoogleEmail, { link_url: link.href.split('?')[0] });
  };
  document.addEventListener('click', onClick, true);
  document.addEventListener('auxclick', onClick, true);
  // Copying a selection that holds an email address (the side bar, the footer, the contact page all show it as text).
  document.addEventListener('copy', () => {
    const text = String(window.getSelection?.() || '');
    if (text.length <= 500 && EMAIL.test(text)) report('email_copy', window.blGoogleEmail, { copied: 'address' });
  }, true);
}
