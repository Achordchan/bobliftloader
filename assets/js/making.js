/** Loader family page (how they are made): mark the production step whose photograph has reached the middle of the viewport. */
export function initializeMaking() {
  const links = [...document.querySelectorAll('[data-making-step]')];
  const figures = links.map((link) => document.getElementById(link.getAttribute('href').slice(1)));
  if (!links.length || figures.includes(null)) return;
  const select = (index) => links.forEach((link, i) => {
    if (i === index) link.setAttribute('aria-current', 'step');
    else link.removeAttribute('aria-current');
  });
  let scheduled = false;
  const update = () => {
    scheduled = false;
    const middle = window.innerHeight / 2;
    select(Math.max(0, figures.findLastIndex((figure) => figure.getBoundingClientRect().top < middle)));
  };
  window.addEventListener('scroll', () => {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(update);
  }, { passive: true });
  update();

  // In a window shorter than the step index, stick it by its bottom edge so the step list stays in view.
  const aside = links[0].closest('.bl-making-aside');
  const fit = () => {
    aside.style.removeProperty('top');
    const room = window.innerHeight - aside.offsetHeight - 24;
    if (room < parseFloat(getComputedStyle(aside).top)) aside.style.top = `${Math.round(room)}px`;
  };
  window.addEventListener('resize', fit);
  document.fonts.ready.then(fit);
  fit();
}
