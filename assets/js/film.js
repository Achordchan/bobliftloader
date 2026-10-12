import { openDialog } from './dialogs.js';

// The family film and its poster load only when its button is pressed; it plays straight away and stops when the dialog closes.
export function initializeFilm() {
  document.querySelectorAll('[data-film-open]').forEach((button) => {
    const dialog = document.getElementById(button.dataset.filmOpen);
    const video = dialog?.querySelector('video');
    if (!video) return;
    button.addEventListener('click', () => {
      if (video.dataset.poster && !video.getAttribute('poster')) video.poster = video.dataset.poster;
      if (!video.getAttribute('src')) video.src = video.dataset.src;
      openDialog(dialog, button);
      video.play().catch(() => {});
    });
    dialog.addEventListener('close', () => {
      video.pause();
      video.currentTime = 0;
    });
  });
}
