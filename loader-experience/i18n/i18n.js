// Language for the page: ?lang= in the address (remembered), else the visitor's last choice, else the browser's
// languages, else English. The page is written in Chinese; every other language swaps the text in place from
// strings.js before the first paint (I18N.apply() runs inline at the end of <body>), and scripts translate what they
// write later through I18N.t(). Loaded as a classic, blocking script so the language is known before anything renders.
(function () {
  const LANGS = { zh: { name: '中文', tag: 'zh-CN' }, en: { name: 'English', tag: 'en' }, ru: { name: 'Русский', tag: 'ru' }, es: { name: 'Español', tag: 'es' } };
  const COLUMN = { en: 1, ru: 2, es: 3 };
  const KEY = 'boblift-lang';

  const store = { get() { try { return localStorage.getItem(KEY); } catch { return null; } }, set(v) { try { localStorage.setItem(KEY, v); } catch {} } };
  const pick = () => {
    const asked = new URLSearchParams(location.search).get('lang');
    if (asked in LANGS) { store.set(asked); return asked; }
    const saved = store.get();
    if (saved in LANGS) return saved;
    for (const tag of navigator.languages?.length ? navigator.languages : [navigator.language || '']) {
      const primary = tag.toLowerCase().split('-')[0];
      if (primary in LANGS) return primary;
    }
    return 'en';
  };
  const lang = pick();
  document.documentElement.lang = LANGS[lang].tag;

  // Exact rows, and rows with {name} parts matched as patterns (the parts translated in turn).
  const exact = new Map(), patterns = [];
  if (lang !== 'zh') for (const row of window.BOBLIFT_I18N_ROWS || []) {
    const [source, target] = [row[0], row[COLUMN[lang]]];
    if (!target) continue;
    if (!/\{\w+\}/.test(source)) { exact.set(source, target); continue; }
    const names = [];
    const re = new RegExp('^' + source.replace(/[.*+?^$()|[\]\\]/g, '\\$&').replace(/\{(\w+)\}/g, (_, n) => { names.push(n); return '(.+?)'; }) + '$');
    patterns.push({ source, re, names, target });
  }
  // Russian writes decimals with a comma.
  const number = s => lang === 'ru' ? s.replace(/(\d)\.(\d)/g, '$1,$2') : s;
  const fill = (template, vars) => template.replace(/\{(\w+)\}/g, (_, n) => vars[n] ?? '');

  /** Translate a Chinese source string (optionally a {name} template with vars). Unknown strings come back as they are. */
  function t(source, vars) {
    if (vars) {
      const template = lang === 'zh' ? source : (exact.get(source) ?? patterns.find(p => p.source === source)?.target ?? source);
      const out = {};
      for (const k in vars) out[k] = typeof vars[k] === 'string' ? t(vars[k]) : number(String(vars[k]));
      return fill(template, out);
    }
    if (lang === 'zh' || source == null) return source;
    const hit = exact.get(source);
    if (hit !== undefined) return hit;
    for (const p of patterns) {
      const m = source.match(p.re);
      if (m) { const vars = {}; p.names.forEach((n, i) => { vars[n] = /\d/.test(m[i + 1]) && !/[一-鿿]/.test(m[i + 1]) ? number(m[i + 1]) : t(m[i + 1]); }); return fill(p.target, vars); }
    }
    return /^\d+\.\d+$/.test(source) ? number(source) : source;
  }

  const ATTRS = ['alt', 'aria-label', 'aria-roledescription', 'title', 'placeholder'];
  /** Translate text and labels under `root` in place (whitespace around each text is kept). */
  function apply(root = document) {
    document.title = t(document.title);
    if (lang !== 'zh') {
      const walker = document.createTreeWalker(root.body || root, NodeFilter.SHOW_TEXT, { acceptNode: n => n.parentNode.closest('script, style') ? NodeFilter.FILTER_REJECT : NodeFilter.FILTER_ACCEPT });
      for (let node = walker.nextNode(); node; node = walker.nextNode()) {
        const raw = node.nodeValue, text = raw.trim();
        if (!text) continue;
        const out = t(text);
        if (out !== text) node.nodeValue = raw.replace(text, out);
      }
      (root.body || root).querySelectorAll(ATTRS.map(a => `[${a}]`).join(',')).forEach(el => ATTRS.forEach(a => { const v = el.getAttribute(a); if (v) { const out = t(v.trim()); if (out !== v.trim()) el.setAttribute(a, out); } }));
      document.documentElement.style.setProperty('--i18n-same', JSON.stringify(t('两款相同')));
    }
    buildSwitcher();
  }

  // Language menu in the header: each entry reloads the page in that language (and remembers it).
  function buildSwitcher() {
    const box = document.getElementById('lang'); if (!box || box.dataset.ready) return;
    box.dataset.ready = '1';
    const toggle = box.querySelector('.exp-lang-toggle'), menu = box.querySelector('.exp-lang-menu');
    box.querySelector('.exp-lang-current').textContent = LANGS[lang].name;
    menu.innerHTML = Object.entries(LANGS).map(([code, l]) => {
      const url = new URL(location.href); url.searchParams.set('lang', code);
      return `<li><a href="${url.pathname}${url.search}${url.hash}" hreflang="${l.tag}" lang="${l.tag}"${code === lang ? ' aria-current="true"' : ''}>${l.name}</a></li>`;
    }).join('');
    const close = () => { menu.hidden = true; toggle.setAttribute('aria-expanded', 'false'); };
    toggle.addEventListener('click', event => { event.stopPropagation(); const open = menu.hidden; menu.hidden = !open; toggle.setAttribute('aria-expanded', String(open)); });
    menu.addEventListener('click', event => { const a = event.target.closest('a'); if (a) store.set(a.getAttribute('hreflang').split('-')[0]); });
    document.addEventListener('click', event => { if (!box.contains(event.target)) close(); });
    document.addEventListener('keydown', event => { if (event.key === 'Escape' && !menu.hidden) { close(); toggle.focus(); } });
  }

  window.I18N = { lang, langs: LANGS, t, apply };
})();
