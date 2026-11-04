import { openDialog } from './dialogs.js';

/** WeChat links (side contact bar, contact page) show the QR code in a dialog instead of opening the bare image. */
export function initializeWeChat() {
  const dialog = document.getElementById('wechat-dialog');
  if (!dialog) return;
  document.addEventListener('click', (event) => {
    const link = event.target.closest?.('a[data-wechat]');
    if (!link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    openDialog(dialog, link);
  });
}
