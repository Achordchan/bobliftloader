// Contact steps outside the form: WhatsApp and mailto: clicks, copying an email address, the "copy enquiry" button.
const assert = require('assert');
const path = require('path');
const { pathToFileURL } = require('url');

(async () => {
  const listeners = {};
  class El {
    constructor(href, id) { this.href = href; this.id = id; }
    closest(sel) {
      if (sel === '#copy-inquiry') return this.id === 'copy-inquiry' ? this : null;
      return sel === 'a[href]' && this.href ? this : null;
    }
  }
  global.Element = El;
  global.document = { addEventListener: (type, fn, capture) => { assert.strictEqual(capture, true); listeners[type] = fn; } };
  global.window = global;
  let selection = '';
  window.getSelection = () => selection;
  const sent = [];
  const fire = (type, href, button = 0, id) => listeners[type]({ type, button, target: new El(href, id) });
  const events = () => sent.map((a) => [a[1], a[2].send_to]);
  const mod = await import(pathToFileURL(path.join(__dirname, '../assets/js/contact-clicks.js')).href);
  mod.initializeContactClicks();

  // No theme Google tag: only the dataLayer event.
  fire('click', 'https://wa.me/8615852499415?text=Hello');
  assert.deepStrictEqual(window.dataLayer.map((e) => e.event), ['whatsapp_click']);

  window.blGoogleTag = true;
  window.gtag = (...args) => sent.push(args);
  fire('click', 'https://api.whatsapp.com/send?phone=1');
  fire('click', 'mailto:sales@bobliftloader.com?subject=Hi');
  assert.deepStrictEqual(events(), [['whatsapp_click', undefined], ['email_click', undefined]], 'GA4 events without saved conversions');
  assert.strictEqual(sent[1][2].link_url, 'mailto:sales@bobliftloader.com', 'mail subject/body kept in the event');

  window.blGoogleWhatsApp = 'AW-1/whats';
  window.blGoogleEmail = 'AW-1/mail';
  sent.length = 0;
  fire('click', 'https://wa.me/1');
  fire('click', 'mailto:sales@bobliftloader.com');
  fire('click', undefined, 0, 'copy-inquiry');
  selection = 'Email: sales@bobliftloader.com';
  listeners.copy();
  assert.deepStrictEqual(events(), [
    ['whatsapp_click', undefined], ['conversion', 'AW-1/whats'],
    ['email_click', undefined], ['conversion', 'AW-1/mail'],
    ['email_copy', undefined], ['conversion', 'AW-1/mail'],
    ['email_copy', undefined], ['conversion', 'AW-1/mail'],
  ]);

  // Other links and copies, a right click and a click on nothing report nothing; a middle click counts.
  sent.length = 0;
  fire('click', 'https://bobliftloader.com/contact/');
  fire('click', 'https://example.com/?u=https://wa.me/1');
  fire('auxclick', 'https://wa.me/1', 2);
  listeners.click({ type: 'click', button: 0, target: null });
  selection = 'Mini skid steer loader T360';
  listeners.copy();
  assert.strictEqual(sent.length, 0);
  fire('auxclick', 'https://wa.me/1', 1);
  assert.strictEqual(sent.filter((a) => a[1] === 'conversion').length, 1);
  console.log('ok');
})().catch((e) => { console.error(e); process.exit(1); });
