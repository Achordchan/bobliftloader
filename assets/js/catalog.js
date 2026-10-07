/**
 * Catalogue filters: refilter the rendered cards in place as chips change, update the count beside each chip, and keep
 * the address shareable. Without this script the form submits and PHP filters the same way.
 */
// Phones (the same widths as the contact bar): a model name (BL-T360-H, FY20-2) or a figure and its unit (0.15 m³,
// 1,089 mm, the 2600 × of a size) never breaks across two lines. Each is wrapped in a <bl-nowrap> element (its own tag,
// so no span rule of the site reaches it; pages.css keeps it on one line). The words and characters are untouched, so
// copying, search and the page source read as before, and wider screens are left exactly as they were.
// Each kind is led by a group holding the start of the text or the one character before it (one that cannot belong to a
// longer word, name or number, so a match never starts inside one); the group after it is the part kept. No look-behind:
// Safari before 16.4 rejects it, and the whole script with it.
const KEEP_TOGETHER = /(^|[^\w-])((?=[A-Z0-9-]*\d)[A-Z][A-Z0-9]*(?:-[A-Z0-9]+)+(?![\w-]))|(^|[^\w.,])(\d[\d.,]*(?:[–-]\d[\d.,]*)?[ \u00a0](?:mm|cm|m³|m²|m|kg|kW|hp|km\/h|V|L|t|MPa|bar|rpm|°)(?![\p{L}\d]))|(^|[^\w.,])(\d[\d.,]*[ \u00a0]×(?=[ \u00a0]\d))/gu;
const KEEP_ANY = new RegExp(KEEP_TOGETHER.source, 'u'); // the same pattern without the global flag's lastIndex, for testing a node
function keepTogether(root) {
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
    // Text that sits straight in a flex or grid box (a filter chip's label) is left alone: there each piece would become
    // an item of its own, losing the space beside it and gaining the box's gap.
    acceptNode: (node) => (KEEP_ANY.test(node.nodeValue) && !node.parentElement.closest('script, style, textarea, option, title, svg, bl-nowrap, [contenteditable]') && !/^(inline-)?(flex|grid)$/.test(getComputedStyle(node.parentElement).display) ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT),
  });
  const nodes = [];
  for (let node = walker.nextNode(); node; node = walker.nextNode()) nodes.push(node);
  nodes.forEach((node) => {
    const text = node.nodeValue;
    const parts = document.createDocumentFragment();
    let last = 0;
    for (const match of text.matchAll(KEEP_TOGETHER)) {
      const kept = match[2] ?? match[4] ?? match[6];
      const start = match.index + match[0].length - kept.length;
      if (start > last) parts.append(text.slice(last, start));
      const keep = document.createElement('bl-nowrap');
      keep.textContent = kept;
      parts.append(keep);
      last = start + kept.length;
    }
    if (last < text.length) parts.append(text.slice(last));
    node.replaceWith(parts);
  });
}

export function initializeCatalog() {
  initializePhoneSections();
  // Only the family and product pages, and only where the contact bar runs along the foot (page-responsive.css).
  if (document.querySelector('.bl-catalog, .bl-product-hero')) {
    const phone = window.matchMedia('(max-width: 800px), (max-width: 1000px) and (max-height: 500px)');
    const main = document.querySelector('main') || document.body;
    let kept = false;
    const keep = () => { if (phone.matches && !kept) { kept = true; keepTogether(main); } };
    keep();
    phone.addEventListener('change', keep);
  }
  // Phones scroll the comparison, the filter row and a model name's key sideways; each right edge fades (pages.css) until
  // the end is in view. Only strips marked here fade, so without the script nothing stays faded.
  document.querySelectorAll('.bl-compare-scroll, .bl-catalog-filters, .bl-model-decode').forEach((box) => {
    // A box that wraps instead of scrolling (the name's key on phones) has no hidden end; a link's enlarged tap layer
    // reaching past its edge must not keep it faded.
    const edge = () => box.classList.toggle('bl-edge-end', getComputedStyle(box).overflowX === 'visible' || box.scrollLeft + box.clientWidth >= box.scrollWidth - 2);
    box.classList.add('bl-edge-fade');
    box.addEventListener('scroll', edge, { passive: true });
    const sizes = new ResizeObserver(edge);
    [box, ...box.children].forEach((part) => sizes.observe(part));
    edge();
  });
  // Phones show the comparison's first eight rows (pages.css); the button under it opens the rest and closes them again,
  // bringing the top of the table back into view when the rows it was reading go.
  document.querySelectorAll('.bl-compare-more').forEach((button) => {
    const section = button.closest('.bl-compare');
    const table = section.querySelector('.bl-compare-scroll');
    const label = button.querySelector('span');
    if (section.querySelectorAll('tbody tr:not(.bl-compare-actions)').length <= 8) return;
    section.classList.add('bl-compare-short');
    button.hidden = false;
    button.addEventListener('click', () => {
      const open = section.classList.toggle('bl-compare-short') === false;
      button.setAttribute('aria-expanded', String(open));
      label.textContent = open ? 'Show fewer specs' : 'Show all specs';
      if (open) return;
      // The table's top comes back just under the header and section bar; where the eight rows and the button do not
      // both fit above the contact bar, the button (which keeps focus) stays in sight instead.
      const pinned = [...document.querySelectorAll('.bl-header,.bl-section-nav,.bl-product-section-nav,.bl-side-contact')].filter((bar) => ['fixed', 'sticky'].includes(getComputedStyle(bar).position)).map((bar) => bar.getBoundingClientRect());
      const ceiling = Math.max(0, ...pinned.filter((box) => box.top < innerHeight / 2).map((box) => box.bottom)) + 12;
      const floor = Math.min(innerHeight, ...pinned.filter((box) => box.top >= innerHeight / 2).map((box) => box.top)) - 12;
      const top = table.getBoundingClientRect().top;
      if (top >= ceiling) return;
      const shift = Math.max(top - ceiling, button.getBoundingClientRect().bottom - floor);
      window.scrollTo({ top: window.scrollY + shift, behavior: 'instant' });
    });
  });
  const form = document.querySelector('[data-catalog]');
  if (!form) return;
  const cards = [...document.querySelectorAll('[data-catalog-card]')];
  const groups = [...form.querySelectorAll('[data-filter]')].map((fieldset) => fieldset.dataset.filter);
  const familyGroups = [...document.querySelectorAll('[data-catalog-group]')];
  const empty = document.querySelector('[data-catalog-empty]');
  const clearLinks = [...document.querySelectorAll('[data-catalog-clear], [data-catalog-leave]')];
  const resultCount = form.querySelector('[data-result-count]');
  const resultNoun = form.querySelector('[data-result-noun]');
  const search = form.elements.q; // hidden: the term a header search (or shared link) brought to this page
  const query = form.querySelector('[data-catalog-query]');

  const readState = () => {
    const state = { q: search.value.trim().toLowerCase() };
    groups.forEach((group) => { state[group] = form.querySelector(`[name="${group}"]:checked`)?.value ?? ''; });
    return state;
  };
  const matches = (card, state, except = '') => (!state.q || card.dataset.search.includes(state.q))
    && groups.every((group) => group === except || !state[group] || card.dataset[group] === state[group]);

  const apply = () => {
    const state = readState();
    let visible = 0;
    cards.forEach((card) => {
      const show = matches(card, state);
      if (show && card.hidden) {
        card.classList.remove('bl-card-enter');
        void card.offsetWidth; // restart the entrance animation
        card.classList.add('bl-card-enter');
      }
      card.hidden = !show;
      visible += show;
    });
    resultCount.textContent = visible;
    resultNoun.textContent = visible === 1 ? 'result' : 'results';
    // A family group with nothing left disappears, and its heading counts what is still shown.
    familyGroups.forEach((group) => {
      const shown = group.querySelectorAll('[data-catalog-card]:not([hidden])').length;
      group.hidden = !shown;
      const count = group.querySelector('[data-group-count]');
      if (count) count.textContent = shown;
    });
    empty.hidden = Boolean(visible);

    form.querySelectorAll('.bl-chip input').forEach((input) => {
      const group = input.name;
      const count = cards.filter((card) => matches(card, state, group) && (!input.value || card.dataset[group] === input.value)).length;
      input.closest('.bl-chip').querySelector('[data-count]').textContent = count;
      input.disabled = !count && !input.checked;
    });

    const active = Boolean(state.q) || groups.some((group) => state[group]);
    clearLinks[0].hidden = !active;
    const params = new URLSearchParams();
    if (search.value.trim()) params.set('q', search.value.trim());
    groups.forEach((group) => { if (state[group]) params.set(group, state[group]); });
    const address = params.toString();
    history.replaceState(null, '', `${location.pathname}${address ? `?${address}` : ''}${location.hash}`);
  };

  form.addEventListener('change', apply);
  form.addEventListener('submit', (event) => { event.preventDefault(); apply(); });
  // On a family page Clear resets in place. On /products/ (search only) it is a plain link to the loaders: an emptied
  // search there has no address of its own to keep.
  clearLinks.filter((link) => link.hasAttribute('data-catalog-clear')).forEach((link) => link.addEventListener('click', (event) => {
    event.preventDefault();
    search.value = '';
    query?.remove();
    form.querySelectorAll('.bl-chip input').forEach((input) => { input.checked = input.type === 'radio' && input.value === ''; });
    apply();
  }));
}

/*
 * Family pages on phones (portrait, up to 600px): the long sections under the models fold into shapes made for a thumb.
 * Each part is built while the phone query matches and taken apart when it stops, so tablets, phones on their side and
 * desktops keep the page exactly as written; without this script phones read every section in full, as before. What the
 * visitor opened or chose is kept while they are taken apart, so turning the phone and back finds the page as they left it. No
 * words are added or removed: buttons carry the titles the page already shows, and folded text stays in the page.
 *   - Where it works (loader attachments, dumper jobs): an accordion, each title a button that opens its text.
 *   - Attachment groups: one row of group tabs over a single sideways strip.
 *   - The comparison: two models side by side, each picked from a list of the whole range, every row full width.
 *   - Model names: the heading opens the key; a link to #model-names opens it on the way.
 */
const PHONE = '(max-width: 600px) and (orientation: portrait)'; // phones on their side keep the full sections
let phoneUid = 0;
const phoneId = (prefix) => `${prefix}-${++phoneUid}`;

function phoneOnly(build) {
  const query = window.matchMedia(PHONE);
  let undo = null;
  const sync = () => {
    if (query.matches && !undo) undo = build() || (() => {});
    else if (!query.matches && undo) { undo(); undo = null; }
  };
  sync();
  query.addEventListener('change', sync);
}

// A title becomes the button that shows or hides the content after it (wrapped in one region for aria-controls).
function foldItem(item, title, open) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'bl-fold-toggle';
  while (title.firstChild) button.append(title.firstChild);
  title.append(button);
  const body = document.createElement('div');
  body.className = 'bl-fold-body';
  body.id = phoneId('bl-fold');
  [...item.childNodes].filter((node) => node !== title).forEach((node) => body.append(node));
  item.append(body);
  item.classList.add('bl-fold');
  button.setAttribute('aria-controls', body.id);
  const set = (state) => {
    button.setAttribute('aria-expanded', String(state));
    item.classList.toggle('bl-fold-open', state);
  };
  set(open);
  button.addEventListener('click', () => set(button.getAttribute('aria-expanded') !== 'true'));
  return {
    set,
    open: () => button.getAttribute('aria-expanded') === 'true',
    undo: () => {
      [...body.childNodes].forEach((node) => item.append(node));
      body.remove();
      while (button.firstChild) title.append(button.firstChild);
      button.remove();
      item.classList.remove('bl-fold', 'bl-fold-open');
    },
  };
}

function initializePhoneSections() {
  // Where it works: the first job stays open as a sample of what each title holds.
  document.querySelectorAll('.bl-attachments-jobs ul').forEach((list) => {
    let opened = [0];
    phoneOnly(() => {
      const folds = [...list.children].map((item, i) => foldItem(item, item.querySelector('strong'), opened.includes(i)));
      list.classList.add('bl-fold-list');
      return () => {
        opened = folds.flatMap((fold, i) => (fold.open() ? [i] : []));
        folds.forEach((fold) => fold.undo());
        list.classList.remove('bl-fold-list');
      };
    });
  });

  // Attachment groups: tabs named and counted as the group headings are; the chosen group's strip shows below.
  document.querySelectorAll('.bl-attachments-groups').forEach((wrap) => { let chosen = 0; phoneOnly(() => {
    const groups = [...wrap.querySelectorAll(':scope > .bl-attachments-group')];
    if (groups.length < 2) return null;
    const tabs = document.createElement('div');
    tabs.className = 'bl-attachments-tabs';
    tabs.setAttribute('role', 'tablist');
    const heading = wrap.closest('section')?.querySelector('h2');
    if (heading?.id) tabs.setAttribute('aria-labelledby', heading.id);
    const buttons = groups.map((group) => {
      const tab = document.createElement('button');
      tab.type = 'button';
      tab.id = phoneId('bl-attachments-tab');
      tab.setAttribute('role', 'tab');
      group.querySelector('h3').childNodes.forEach((node) => tab.append(node.cloneNode(true)));
      if (!group.id) group.id = phoneId('bl-attachments-panel'); // no ||=: Safari before 14 rejects it, and the whole script
      group.setAttribute('role', 'tabpanel');
      group.setAttribute('aria-labelledby', tab.id);
      tab.setAttribute('aria-controls', group.id);
      tabs.append(tab);
      return tab;
    });
    const select = (index, focus) => {
      chosen = index;
      buttons.forEach((tab, i) => {
        const on = i === index;
        tab.setAttribute('aria-selected', String(on));
        tab.tabIndex = on ? 0 : -1;
        groups[i].hidden = !on;
      });
      const row = groups[index].querySelector('[data-swipe-row]');
      if (row) {
        row.scrollLeft = 0;
        if (row.scrollWidth > row.clientWidth + 1) row.tabIndex = 0;
      }
      if (focus) buttons[index].focus();
      buttons[index].scrollIntoView({ block: 'nearest', inline: 'nearest' });
    };
    tabs.addEventListener('click', (event) => {
      const tab = event.target.closest('[role=tab]');
      if (tab) select(buttons.indexOf(tab), false);
    });
    tabs.addEventListener('keydown', (event) => {
      const current = buttons.indexOf(document.activeElement);
      const next = { ArrowRight: current + 1, ArrowLeft: current - 1, Home: 0, End: buttons.length - 1 }[event.key];
      if (current < 0 || next === undefined) return;
      event.preventDefault();
      select((next + buttons.length) % buttons.length, true);
    });
    wrap.prepend(tabs);
    wrap.classList.add('bl-attachments-tabbed');
    if (chosen >= groups.length) chosen = 0;
    buttons.forEach((tab, i) => { tab.setAttribute('aria-selected', String(i === chosen)); tab.tabIndex = i === chosen ? 0 : -1; groups[i].hidden = i !== chosen; });
    return () => {
      tabs.remove();
      wrap.classList.remove('bl-attachments-tabbed');
      groups.forEach((group) => {
        group.hidden = false;
        group.removeAttribute('role');
        group.removeAttribute('aria-labelledby');
        if (group.id.startsWith('bl-attachments-panel')) group.removeAttribute('id');
      });
    };
  }); });

  // The comparison: two models, each chosen from the range under the table's own "Model" heading; the other columns
  // stay in the table for the rest of the page and for wider screens.
  document.querySelectorAll('.bl-compare').forEach((section) => { const picked = [0, 1]; phoneOnly(() => {
    const table = section.querySelector('.bl-compare-table');
    const box = section.querySelector('.bl-compare-scroll');
    const heads = [...table.querySelectorAll('thead th')];
    const models = heads.slice(1);
    if (models.length < 2) return null;
    const rows = [...table.rows];
    const names = models.map((th) => th.querySelector('span')?.textContent.trim() || th.textContent.trim());
    const label = heads[0].textContent.trim();
    const picker = document.createElement('div');
    picker.className = 'bl-compare-picker';
    const selects = picked.map((start, side) => {
      const field = document.createElement('label');
      const caption = document.createElement('span');
      caption.className = 'bl-visually-hidden';
      caption.textContent = label;
      const select = document.createElement('select');
      names.forEach((name, i) => select.add(new Option(name, String(i), false, i === start)));
      field.append(caption, select);
      picker.append(field);
      select.addEventListener('change', () => {
        const value = Number(select.value);
        const other = 1 - side;
        // Choosing the model already on the other side swaps the two, so two different models are always shown.
        if (value === picked[other]) { picked[other] = picked[side]; selects[other].value = String(picked[other]); }
        picked[side] = value;
        paint();
      });
      return select;
    });
    // A figure neither of the two models publishes (a dash in both columns) steps aside until another pair has it. The
    // short view counts only the rows left, so it always shows eight figures (or all of them, and no button, if a pair has no more).
    const paint = () => {
      let shown = 0;
      rows.forEach((row) => {
        const cells = [...row.cells].slice(1);
        cells.forEach((cell, i) => {
          cell.classList.toggle('bl-compare-a', i === picked[0]);
          cell.classList.toggle('bl-compare-b', i === picked[1]);
        });
        const figure = row.parentElement.tagName === 'TBODY' && !row.classList.contains('bl-compare-actions');
        const blank = figure && picked.every((i) => cells[i]?.textContent.trim() === '—');
        row.classList.toggle('bl-compare-blank', blank);
        if (figure && !blank) shown += 1;
        row.classList.toggle('bl-compare-later', figure && !blank && shown > 8);
      });
      section.classList.toggle('bl-compare-few', shown <= 8);
    };
    if (Math.max(...picked) >= models.length) picked.splice(0, 2, 0, 1);
    paint();
    box.before(picker);
    section.classList.add('bl-compare-picked');
    // The two lists draw a rule under them only while they are pinned under the header (a marker just above them has
    // gone under that line).
    const marker = document.createElement('div');
    marker.className = 'bl-compare-picker-marker';
    picker.before(marker);
    const pinnedAt = parseFloat(getComputedStyle(picker).top) || 0;
    const watch = new IntersectionObserver(([entry]) => {
      picker.classList.toggle('bl-compare-picker-pinned', !entry.isIntersecting && entry.boundingClientRect.top < pinnedAt + 1);
    }, { rootMargin: `${-Math.ceil(pinnedAt + 1)}px 0px 100000px 0px` }); // everything below the line counts, so a jump past it still reports
    watch.observe(marker);
    box.dispatchEvent(new Event('scroll')); // the fade's end marker reads the box again now that nothing overflows
    return () => {
      watch.disconnect();
      marker.remove();
      picker.remove();
      section.classList.remove('bl-compare-picked', 'bl-compare-few');
      rows.forEach((row) => { row.classList.remove('bl-compare-blank', 'bl-compare-later'); [...row.cells].forEach((cell) => cell.classList.remove('bl-compare-a', 'bl-compare-b')); });
      box.dispatchEvent(new Event('scroll'));
    };
  }); });

  // Model names: the heading opens the key. Arriving at #model-names, or following a link to it, opens it first.
  const key = document.querySelector('.bl-model-key');
  let keyOpen = false;
  if (key) phoneOnly(() => {
    const head = key.querySelector('.bl-model-key-head');
    const title = head.querySelector('h2');
    const button = document.createElement('button');
    button.type = 'button';
    button.className = 'bl-fold-toggle';
    while (title.firstChild) button.append(title.firstChild);
    title.append(button);
    const parts = [head.querySelector('.bl-model-key-intro'), key.querySelector('.bl-model-key-parts')].filter(Boolean);
    parts.forEach((part) => { if (!part.id) part.id = phoneId('bl-model-key-part'); });
    button.setAttribute('aria-controls', parts.map((part) => part.id).join(' '));
    const set = (open) => {
      button.setAttribute('aria-expanded', String(open));
      key.classList.toggle('bl-fold-open', open);
    };
    key.classList.add('bl-fold');
    set(keyOpen || location.hash === `#${key.id}`);
    button.addEventListener('click', () => set(button.getAttribute('aria-expanded') !== 'true'));
    const follow = (event) => {
      const link = event.target.closest?.('a[href]');
      if (link && link.hash === `#${key.id}` && link.pathname === location.pathname) set(true);
    };
    const arrive = () => { if (location.hash === `#${key.id}`) set(true); };
    document.addEventListener('click', follow, true);
    window.addEventListener('hashchange', arrive);
    return () => {
      keyOpen = button.getAttribute('aria-expanded') === 'true';
      document.removeEventListener('click', follow, true);
      window.removeEventListener('hashchange', arrive);
      while (button.firstChild) title.append(button.firstChild);
      button.remove();
      parts.forEach((part) => { if (part.id.startsWith('bl-model-key-part')) part.removeAttribute('id'); });
      key.classList.remove('bl-fold', 'bl-fold-open');
    };
  });
}
