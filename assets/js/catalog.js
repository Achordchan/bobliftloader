/**
 * Catalogue filters: refilter the rendered cards in place as chips change, update the count beside each chip, and keep
 * the address shareable. Without this script the form submits and PHP filters the same way.
 */
export function initializeCatalog() {
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
