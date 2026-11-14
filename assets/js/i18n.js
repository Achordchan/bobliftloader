/**
 * Strings the scripts show, in the page's language: a page under a language folder carries its translations in
 * <script id="bl-i18n"> (inc/i18n.php, BL_I18N_SCRIPT_STRINGS); English pages carry none and every string is its own.
 * {name} in a string is filled from vars.
 */
let strings = null;

export function t(text, vars = {}) {
  if (strings === null) {
    try { strings = JSON.parse(document.getElementById('bl-i18n')?.textContent || '{}'); } catch { strings = {}; }
  }
  return (strings[text] ?? text).replace(/\{(\w+)\}/g, (match, name) => (name in vars ? String(vars[name]) : match));
}
