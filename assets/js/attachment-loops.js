// Attachment cards whose picture is a short silent film (templates/partials/attachments.php and the Works with strip on a
// loader page, through bl_attachment_media()). A film is fetched and played only while at least half of it is on screen
// and paused when it leaves, so a page of them costs nothing until a visitor scrolls to them. Visitors who ask for less
// motion or less data get the poster and a play control instead; so does a film the browser will not start by itself, and
// a browser without IntersectionObserver. A film with controls is no longer decoration, so it is named for screen readers.
const SHOWN = 0.5;

function useControls(film) {
  film.controls = true;
  film.loop = false;
  film.removeAttribute('aria-hidden');
  if (!film.hasAttribute('aria-label')) {
    const name = film.closest('li')?.querySelector('strong')?.textContent.trim();
    film.setAttribute('aria-label', `${name || 'Attachment'}, in use`);
  }
}

export function initializeAttachmentLoops() {
  const loops = [...document.querySelectorAll('video[data-bl-loop]')];
  if (!loops.length) return;
  const saveData = navigator.connection?.saveData === true;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || saveData || !('IntersectionObserver' in window)) {
    loops.forEach(useControls);
    return;
  }
  const watch = new IntersectionObserver((entries) => {
    entries.forEach(({ target, intersectionRatio }) => {
      // isIntersecting is true for any overlap, so the ratio decides: a card crossing back under half pauses.
      if (intersectionRatio < SHOWN) {
        target.pause();
        return;
      }
      target.play().catch((error) => {
        // Only a refusal to autoplay (a phone's low-power mode, say) hands the film to the visitor; a play() that a
        // pause() cut short, or a file the browser cannot read, is not that.
        if (error?.name !== 'NotAllowedError') return;
        watch.unobserve(target);
        useControls(target);
      });
    });
  }, { threshold: [SHOWN] });
  loops.forEach((film) => watch.observe(film));
}
