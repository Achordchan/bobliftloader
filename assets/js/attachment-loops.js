// Attachment cards whose picture is a short silent film (templates/partials/attachments.php and the Works with strip on a
// loader page, through bl_attachment_media()). Every card opens as its picture and nothing more: the film is not fetched
// until a visitor presses Play, so a page of them costs no more than a page of pictures. Once playing it loops, and it
// stops again when its card leaves the screen or the visitor presses Pause; a card that is not playing shows its picture
// (the poster the page already fetched), never a frozen frame of the film.
const PAUSE = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>';
const PLAY = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 4v16l13-8z"/></svg>';

const filmName = (film) => film.closest('li')?.querySelector('strong')?.textContent.trim() || 'Attachment';

// The button follows the film's state, whoever changed it: it offers Play while the film is not playing and Pause while it is.
function addToggle(film) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'bl-loop-toggle';
  const frame = film.parentElement;
  const sync = () => {
    button.setAttribute('aria-label', `${film.paused ? 'Play' : 'Pause'} the ${filmName(film)} film`);
    button.innerHTML = film.paused ? PLAY : PAUSE;
    frame?.classList.toggle('is-still', film.paused);
  };
  button.addEventListener('click', () => {
    if (film.paused) film.play().catch(() => {});
    else film.pause();
  });
  film.addEventListener('play', sync);
  film.addEventListener('pause', sync);
  film.addEventListener('ended', sync);
  sync();
  film.after(button);
}

export function initializeAttachmentLoops() {
  const loops = [...document.querySelectorAll('video[data-bl-loop]')];
  if (!loops.length) return;
  loops.forEach(addToggle);
  if (!('IntersectionObserver' in window)) return;
  const watch = new IntersectionObserver((entries) => {
    entries.forEach(({ target, isIntersecting }) => {
      if (!isIntersecting) target.pause();
    });
  });
  loops.forEach((film) => watch.observe(film));
}
