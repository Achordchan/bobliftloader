// Attachment cards whose picture is a short silent film (templates/partials/attachments.php and the Works with strip on a
// loader page, through bl_attachment_media()). A film is fetched and played only while at least half of it is on screen
// and paused when it leaves, so a page of them costs nothing until a visitor scrolls to them. Visitors who ask for less
// motion or less data get the poster and a play control instead, and so does a browser without IntersectionObserver.
export function initializeAttachmentLoops() {
  const loops = [...document.querySelectorAll('video[data-bl-loop]')];
  if (!loops.length) return;
  const saveData = navigator.connection?.saveData === true;
  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches || saveData || !('IntersectionObserver' in window)) {
    loops.forEach((film) => { film.controls = true; film.loop = false; });
    return;
  }
  const watch = new IntersectionObserver((entries) => {
    entries.forEach(({ target, isIntersecting }) => {
      if (isIntersecting) target.play().catch(() => {});
      else target.pause();
    });
  }, { threshold: 0.5 });
  loops.forEach((film) => watch.observe(film));
}
