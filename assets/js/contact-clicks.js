// Getting in touch outside the enquiry form: a click on a WhatsApp link, a click on an email address (mailto:), or copying one
// of this site's own email addresses (selecting it and copying, or the "copy enquiry" button of the email fallback, whose
// text goes to that address). The conversation itself then happens in the salesperson's WhatsApp or mailbox, where the site never sees it,
// so the step is reported: one event in the dataLayer for whatever tracking code is added later ("whatsapp_click",
// "email_click", "email_copy"), and with the theme's own Google tag (inc/consent.php, bl_google_tag_markup) the same event
// to GA4 and, when the matching conversion is saved on the tracking screen (blGoogleWhatsApp, blGoogleEmail), a Google
// Ads conversion. Consent Mode decides, as for an enquiry, whether that goes with cookies or as a cookieless ping.
// Nothing waits for the report: the link opens and the text is copied as before.
const WHATSAPP = /^https?:\/\/(?:wa\.me|api\.whatsapp\.com|(?:web\.)?whatsapp\.com)\//i;

// This site's addresses: every mailto: link on the page (the side bar, the footer, the contact page) and the enquiry form's
// recipient. Only copying one of these counts; the visitor's own address in the enquiry summary, or any other, does not.
function siteAddresses() {
  const addresses = new Set();
  document.querySelectorAll('a[href^="mailto:" i]').forEach((link) => {
    try { addresses.add(decodeURIComponent(link.getAttribute('href').slice(7).split('?')[0]).trim().toLowerCase()); } catch { /* malformed */ }
  });
  document.querySelectorAll('[data-email]').forEach((element) => addresses.add(String(element.dataset.email || '').trim().toLowerCase()));
  addresses.delete('');
  return addresses;
}

function report(event, conversion, details) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push({ event, ...details });
  if (!window.blGoogleTag || typeof window.gtag !== 'function') return;
  window.gtag('event', event, { ...details, transport_type: 'beacon' });
  if (conversion) window.gtag('event', 'conversion', { send_to: conversion, transport_type: 'beacon' });
}

// The email fallback's "copy enquiry" button (inquiry.js) calls this once the text is actually on the clipboard; a denied or
// unavailable clipboard reports nothing.
export function reportEnquiryCopied() {
  report('email_copy', window.blGoogleEmail, { copied: 'enquiry' });
}

export function initializeContactClicks() {
  // Captured on the document, so links drawn later (dialogs, the quick enquiry, translated pages) count too, and before any
  // other handler can stop the click. A middle click still opens the link, so it counts; a right click does not.
  const onClick = (event) => {
    if (event.type === 'auxclick' && event.button !== 1) return;
    const target = event.target instanceof Element ? event.target : null;
    const link = target?.closest('a[href]');
    if (!link) return;
    if (WHATSAPP.test(link.href)) report('whatsapp_click', window.blGoogleWhatsApp, { link_url: link.href });
    else if (/^mailto:/i.test(link.href)) report('email_click', window.blGoogleEmail, { link_url: link.href.split('?')[0] });
  };
  document.addEventListener('click', onClick, true);
  document.addEventListener('auxclick', onClick, true);
  // Copying a short selection that holds one of this site's addresses (shown as text in the side bar, footer and contact page).
  document.addEventListener('copy', () => {
    const text = String(window.getSelection?.() || '').toLowerCase();
    if (!text.includes('@') || text.length > 500) return;
    for (const address of siteAddresses()) {
      if (text.includes(address)) { report('email_copy', window.blGoogleEmail, { copied: 'address' }); return; }
    }
  }, true);
}
