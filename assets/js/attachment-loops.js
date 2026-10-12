// Attachment cards whose picture is a short silent film (templates/partials/attachments.php and the Works with strip on a
// loader page, through bl_attachment_media()). A film is fetched and played only while at least half of it is on screen
// and paused when it leaves, so a page of them costs nothing until a visitor scrolls to them. Motion that starts by itself
// carries a pause button, and a pause the visitor chose holds when the card scrolls back into view. Visitors who ask for
// less motion or less data get the poster and the browser's own controls instead; so does a film the browser will not
// start by itself, and a browser without IntersectionObserver. A pause the visitor chooses shows the card's own picture in
// place of the film until they play it again. A film with controls is no longer decoration, so it is named.
const SHOWN = 0.5;
const PAUSE = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 4h4v16H6zM14 4h4v16h-4z"/></svg>';
const PLAY = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 4v16l13-8z"/></svg>';

const filmName = (film) => film.closest('li')?.querySelector('strong')?.textContent.trim() || 'Attachment';

function useControls(film) {
  film.parentElement?.querySelector('.bl-loop-toggle')?.remove();
  film.controls = true;
  film.loop = false;
  film.removeAttribute('aria-hidden');
  if (!film.hasAttribute('aria-label')) film.setAttribute('aria-label', `${filmName(film)}, in use`);
}

// The button follows the film's state, whoever changed it: it offers Pause while the film plays and Play while it does not.
function addToggle(film, held) {
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'bl-loop-toggle';
  const sync = () => {
    button.setAttribute('aria-label', `${film.paused ? 'Play' : 'Pause'} the ${filmName(film)} film`);
    button.innerHTML = film.paused ? PLAY : PAUSE;
  };
  // A pause the visitor chose shows the card's own picture again; playing again brings the film back.
  const still = (on) => film.parentElement?.classList.toggle('is-still', on);
  button.addEventListener('click', () => {
    if (film.paused) {
      held.delete(film);
      still(false);
      film.play().catch(() => {});
    } else {
      held.add(film);
      film.pause();
      still(true);
    }
  });
  film.addEventListener('play', () => still(false));
  film.addEventListener('play', sync);
  film.addEventListener('pause', sync);
  sync();
  film.after(button);
}

export function initializeAttachmentLoops() {
  const loops = [...document.querySelectorAll('video[data-bl-loop]')];
  if (!loops.length) return;
  const saveData = navigator.connection?.saveData === true;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || saveData || !('IntersectionObserver' in window)) {
    loops.forEach(useControls);
    return;
  }
  const held = new WeakSet();
  const watch = new IntersectionObserver((entries) => {
    entries.forEach(({ target, intersectionRatio }) => {
      // isIntersecting is true for any overlap, so the ratio decides: a card crossing back under half pauses.
      if (intersectionRatio < SHOWN) {
        target.pause();
        return;
      }
      if (held.has(target)) return;
      target.play().catch((error) => {
        // Only a refusal to autoplay (a phone's low-power mode, say) hands the film to the visitor; a play() that a
        // pause() cut short, or a file the browser cannot read, is not that.
        if (error?.name !== 'NotAllowedError') return;
        watch.unobserve(target);
        useControls(target);
      });
    });
  }, { threshold: [SHOWN] });
  loops.forEach((film) => {
    addToggle(film, held);
    watch.observe(film);
  });
}
