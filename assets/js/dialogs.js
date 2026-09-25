let opener = null;

export function openDialog(dialog, trigger = document.activeElement) {
  opener = trigger;
  dialog.showModal();
  document.body.style.overflow = 'hidden';
}

export function closeDialog(dialog) {
  dialog.close();
}

export function initializeDialogs() {
  document.querySelectorAll('.bl-dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-close-dialog]').forEach((button) => {
      button.addEventListener('click', () => closeDialog(dialog));
    });
    dialog.addEventListener('click', (event) => {
      if (event.target !== dialog) return;
      const bounds = dialog.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right ||
          event.clientY < bounds.top || event.clientY > bounds.bottom) closeDialog(dialog);
    });
    dialog.addEventListener('close', () => {
      document.body.style.overflow = '';
      if (opener?.isConnected) opener.focus({ preventScroll: true });
      opener = null;
    });
  });
}
