import { openDialog, closeDialog } from './dialogs.js';

/**
 * The header's and footer's site switch: a link to one of our other websites first shows a notice (the site's name and
 * domain, marked as an official BOB-LIFT website), and Continue opens it in a new tab so this page stays where it is.
 * Without the script the link opens in the same tab.
 */
export function initializeLeaveSite() {
  const dialog = document.getElementById('leave-dialog');
  if (!dialog) return;
  const field = (name) => dialog.querySelector(`[data-leave-${name}]`);
  const go = field('go');
  document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[data-leave-site]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    field('name').textContent = link.dataset.name;
    field('domain').textContent = link.dataset.domain;
    field('go-domain').textContent = link.dataset.domain;
    go.href = link.href;
    link.closest('details')?.removeAttribute('open');
    openDialog(dialog, link.closest('details')?.querySelector('summary') ?? link);
  });
  // The new tab opens by the link itself; the notice closes behind it.
  go.addEventListener('click', () => setTimeout(() => closeDialog(dialog), 0));
}
