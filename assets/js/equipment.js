/** Keep tab selection, keyboard navigation and visible panels in one place. */
function createTabs(selector, dataKey) {
  const tabs = [...document.querySelectorAll(selector)];
  const activate = (key, focus = false) => {
    tabs.forEach((tab) => {
      const selected = tab.dataset[dataKey] === key;
      tab.setAttribute('aria-selected', String(selected));
      tab.tabIndex = selected ? 0 : -1;
      document.getElementById(tab.getAttribute('aria-controls')).hidden = !selected;
      if (selected && focus) tab.focus();
    });
  };
  tabs.forEach((tab, index) => {
    tab.addEventListener('click', () => activate(tab.dataset[dataKey]));
    tab.addEventListener('keydown', (event) => {
      const vertical = tab.parentElement.getAttribute('aria-orientation') === 'vertical';
      const previous = vertical ? 'ArrowUp' : 'ArrowLeft';
      const next = vertical ? 'ArrowDown' : 'ArrowRight';
      let target;
      if (event.key === previous) target = (index - 1 + tabs.length) % tabs.length;
      if (event.key === next) target = (index + 1) % tabs.length;
      if (event.key === 'Home') target = 0;
      if (event.key === 'End') target = tabs.length - 1;
      if (target === undefined) return;
      event.preventDefault();
      activate(tabs[target].dataset[dataKey], true);
    });
  });
  return activate;
}

export function initializeEquipment() {
  const selectEquipment = createTabs('[data-equipment-tab]', 'equipmentTab');
  createTabs('[data-application-tab]', 'applicationTab');
  const applicationList = document.querySelector('.bl-application-nav');
  if (applicationList) {
    const compact = window.matchMedia('(max-width: 800px)');
    const setOrientation = () => applicationList.setAttribute('aria-orientation', compact.matches ? 'horizontal' : 'vertical');
    compact.addEventListener('change', setOrientation);
    setOrientation();
  }
  document.querySelectorAll('[data-select-equipment]').forEach((link) => {
    link.addEventListener('click', () => selectEquipment(link.dataset.selectEquipment));
  });
  const photo = document.querySelector('#product-photo');
  if (photo) {
    document.querySelectorAll('[data-gallery-image]').forEach((button, index) => {
      button.addEventListener('click', () => {
        photo.src = button.dataset.galleryImage;
        document.querySelectorAll('[data-gallery-image]').forEach((item) => item.setAttribute('aria-pressed', String(item === button)));
      });
    });
  }
  // Spec groups sit side by side on wide screens, always open. On phones they stack, so only the first (performance)
  // starts open and the rest expand on tap.
  const specGroups = [...document.querySelectorAll('.bl-spec-block')];
  if (specGroups.length > 1) {
    const phone = window.matchMedia('(max-width: 600px)');
    const layout = () => specGroups.forEach((group, index) => { group.open = !phone.matches || index === 0; });
    specGroups.forEach((group) => group.querySelector('summary').addEventListener('click', (event) => { if (!phone.matches) event.preventDefault(); }));
    phone.addEventListener('change', layout);
    layout();
  }
}

export function scrollBehavior() {
  return window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';
}
