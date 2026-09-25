/**
 * Header search: open with the search button, "/" or Ctrl/Cmd+K, then results update as you type. Matching ignores
 * case, spaces and hyphens, so "bl t360", "BL-T360" and "blt360" are the same query. Older loader names (BL-390,
 * BL-T360) resolve to today's models with a note saying so. Enter opens the highlighted result; with no results the
 * form submits to the catalogue, which searches the same way.
 */
const MAX_RESULTS = 8;
const normalize = (text) => text.toLowerCase().replace(/[^a-z0-9]/g, '');
// BL-T360 keeps the running-gear letter and drops only the operator suffix (as at the fair); BL-390 predates both.
const isShortForm = (name) => /^BL-[TS]\d/.test(name);

export function initializeSearch() {
  const dialog = document.getElementById('site-search');
  const source = document.getElementById('site-search-index');
  if (!dialog || !source) return;
  const input = dialog.querySelector('input[name="q"]');
  const list = dialog.querySelector('#site-search-results');
  const note = dialog.querySelector('[data-search-note]');
  const start = dialog.querySelector('[data-search-start]');
  const empty = dialog.querySelector('[data-search-empty]');
  const emptyQuery = dialog.querySelector('[data-search-empty-query]');
  const entries = JSON.parse(source.textContent).map((entry) => ({
    ...entry,
    key: normalize(entry.title),
    legacyKeys: (entry.legacy || []).map(normalize),
    haystack: normalize([entry.title, entry.text, entry.meta, entry.kind, entry.terms, ...(entry.legacy || [])].filter(Boolean).join(' ')),
  }));
  let results = [];
  let active = -1;
  let opener = null;

  const score = (entry, whole, tokens) => {
    if (!tokens.every((token) => entry.haystack.includes(token))) return 0;
    if (entry.key === whole) return 100;
    if (entry.legacyKeys.includes(whole)) return 90;
    if (entry.key.startsWith(whole)) return 60;
    if (entry.legacyKeys.some((key) => key.startsWith(whole))) return 55;
    if (entry.key.includes(whole)) return 40;
    const text = normalize(entry.text || '');
    if (text.startsWith(whole)) return 30; // "5 ton" puts the 5 Ton dumper ahead of the 1.5 Ton one
    if (text.includes(whole)) return 20;
    return entry.legacy ? 12 : 10; // loose matches: models (the only entries with a legacy list) before pages
  };

  const setActive = (index) => {
    active = index;
    [...list.children].forEach((option, i) => option.setAttribute('aria-selected', String(i === index)));
    if (index >= 0) {
      input.setAttribute('aria-activedescendant', list.children[index].id);
      list.children[index].scrollIntoView({ block: 'nearest' });
    } else input.removeAttribute('aria-activedescendant');
  };

  const option = (entry, index, legacyHit) => {
    const item = document.createElement('li');
    item.className = 'bl-search-result';
    item.id = `site-search-option-${index}`;
    item.setAttribute('role', 'option');
    item.setAttribute('aria-selected', 'false');
    const link = document.createElement('a');
    link.href = entry.url;
    link.tabIndex = -1;
    const thumb = document.createElement('span');
    thumb.className = 'bl-search-thumb';
    if (entry.image) {
      const image = document.createElement('img');
      image.src = entry.image;
      image.alt = '';
      image.loading = 'lazy';
      thumb.append(image);
    } else thumb.textContent = entry.legacy ? entry.title.replace(/^BL-|-[HC]$/g, '') : entry.kind.charAt(0); // models awaiting photos show their code
    const text = document.createElement('span');
    text.className = 'bl-search-text';
    const title = document.createElement('strong');
    title.textContent = entry.title;
    const line = document.createElement('span');
    line.textContent = entry.text;
    text.append(title, line);
    if (legacyHit) {
      const older = document.createElement('small');
      older.textContent = `${isShortForm(legacyHit) ? 'Also written' : 'Formerly written'} ${legacyHit}`;
      text.append(older);
    }
    const kind = document.createElement('span');
    kind.className = 'bl-search-kind';
    kind.textContent = entry.meta ? `${entry.kind} · ${entry.meta}` : entry.kind;
    link.append(thumb, text, kind);
    item.append(link);
    item.addEventListener('mousemove', () => { if (active !== index) setActive(index); });
    return item;
  };

  const render = () => {
    const query = input.value.trim();
    const whole = normalize(query);
    const tokens = query.split(/\s+/).map(normalize).filter(Boolean);
    results = whole ? entries
      .map((entry, order) => ({ entry, order, points: score(entry, whole, tokens) }))
      .filter((hit) => hit.points)
      .sort((a, b) => b.points - a.points || a.order - b.order)
      .slice(0, MAX_RESULTS) : [];
    list.replaceChildren(...results.map(({ entry }, index) => {
      // Found only through part of an older name: say which one (an exact older name gets the note above instead).
      const legacyIndex = entry.key.includes(whole) || entry.legacyKeys.includes(whole) ? -1 : entry.legacyKeys.findIndex((key) => key.includes(whole));
      return option(entry, index, legacyIndex >= 0 ? entry.legacy[legacyIndex] : '');
    }));
    // An exact older name gets a plain-language note: what it is called now.
    const renamed = results.filter(({ entry }) => entry.legacyKeys.includes(whole));
    note.hidden = !renamed.length;
    if (renamed.length) {
      const older = renamed[0].entry.legacy[renamed[0].entry.legacyKeys.indexOf(whole)];
      const names = renamed.map(({ entry }) => entry.title);
      const list = names.length > 1 ? `${names.slice(0, -1).join(', ')} and ${names.at(-1)}` : names[0];
      note.textContent = isShortForm(older)
        ? `${older} is the short form of ${list}.`
        : `${older} is the older name for ${names.length > 1 ? 'these models' : 'this model'}. It is now ${list}.`;
    }
    start.hidden = Boolean(whole);
    empty.hidden = !whole || results.length > 0;
    emptyQuery.textContent = `“${query}”`;
    input.setAttribute('aria-expanded', String(results.length > 0));
    setActive(results.length ? 0 : -1);
  };

  const open = (trigger = document.activeElement) => {
    if (dialog.open) return;
    opener = trigger;
    dialog.showModal();
    document.body.style.overflow = 'hidden';
    input.focus();
    input.select();
    render();
  };
  const close = () => { if (dialog.open) dialog.close(); };

  document.querySelectorAll('[data-open-search]').forEach((button) => {
    button.hidden = false;
    button.addEventListener('click', () => open(button));
  });
  dialog.querySelector('[data-close-search]').addEventListener('click', close);
  dialog.addEventListener('click', (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (event.clientY > bounds.bottom || event.clientY < bounds.top) close();
  });
  dialog.addEventListener('close', () => {
    document.body.style.overflow = '';
    if (opener?.isConnected) opener.focus({ preventScroll: true });
    opener = null;
  });
  dialog.querySelectorAll('[data-search-try]').forEach((button) => button.addEventListener('click', () => {
    input.value = button.dataset.searchTry;
    input.focus();
    render();
  }));

  input.addEventListener('input', render);
  input.addEventListener('keydown', (event) => {
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
      if (!results.length) return;
      event.preventDefault();
      const step = event.key === 'ArrowDown' ? 1 : -1;
      setActive((active + step + results.length) % results.length);
    } else if (event.key === 'Enter' && active >= 0) {
      event.preventDefault();
      window.location.href = results[active].entry.url;
    } else if (event.key === 'Escape') {
      // A search field swallows the first Escape to clear itself; here Escape always closes the panel.
      event.preventDefault();
      close();
    }
  });

  document.addEventListener('keydown', (event) => {
    const typing = event.target.closest?.('input, textarea, select, [contenteditable="true"]');
    if ((event.key === '/' && !typing) || ((event.key || '').toLowerCase() === 'k' && (event.metaKey || event.ctrlKey))) {
      event.preventDefault();
      open();
    }
  });
}
