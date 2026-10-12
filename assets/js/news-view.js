// The news list's layout switch: list (default) or grid. The choice is kept in the browser, so the list opens the way the visitor last left it.
const STORAGE_KEY = 'bl-news-view';
const VIEWS = ['list', 'grid'];

function savedView() {
  try {
    const view = window.localStorage.getItem(STORAGE_KEY);
    return VIEWS.includes(view) ? view : 'list';
  } catch {
    return 'list';
  }
}

function saveView(view) {
  try {
    window.localStorage.setItem(STORAGE_KEY, view);
  } catch {
    // Storage can be blocked (private windows, site data off): the switch still works for this visit.
  }
}

export function initializeNewsView() {
  const switcher = document.querySelector('[data-bl-news-view]');
  const list = document.querySelector('.bl-news-list');
  if (!switcher || !list) return;
  const buttons = [...switcher.querySelectorAll('button[data-view]')];
  const show = (view) => {
    list.dataset.view = view;
    buttons.forEach((button) => button.setAttribute('aria-pressed', String(button.dataset.view === view)));
  };
  show(savedView());
  switcher.hidden = false;
  buttons.forEach((button) => button.addEventListener('click', () => {
    show(button.dataset.view);
    saveView(button.dataset.view);
  }));
}
