/**
 * Home hero: one slide per family. Without the script only the first slide shows. With it, the switch at the top right
 * appears and the slides take turns; the line under the current button fills (a CSS animation) and its end moves to the
 * next slide. Hovering or focusing the hero holds the line; choosing a slide or the pause button stops the turns, and
 * they never start for visitors who ask for reduced motion.
 */
export function initializeHero() {
  const hero = document.querySelector('[data-hero]');
  const switchRow = hero?.querySelector('[data-hero-switch]');
  if (!switchRow) return;
  const slides = [...hero.querySelectorAll('[data-hero-slide]')];
  const pictures = [...hero.querySelectorAll('[data-hero-picture]')];
  const tabs = [...switchRow.querySelectorAll('[data-hero-tab]')];
  const pause = switchRow.querySelector('[data-hero-pause]');
  const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
  let current = 0;

  slides.forEach((slide, index) => {
    slide.hidden = false;
    slide.inert = index !== current;
  });
  switchRow.hidden = false;

  const setPlaying = (playing) => {
    hero.toggleAttribute('data-hero-playing', playing);
    pause.setAttribute('aria-label', playing ? 'Pause the slideshow' : 'Play the slideshow');
  };
  const show = (next) => {
    if (next === current) return;
    current = next;
    slides.forEach((slide, index) => {
      const active = index === next;
      slide.classList.toggle('bl-active', active);
      slide.inert = !active;
    });
    pictures.forEach((picture, index) => picture.classList.toggle('bl-active', index === next));
    tabs.forEach((tab, index) => tab.setAttribute('aria-pressed', String(index === next)));
  };

  tabs.forEach((tab, index) => tab.addEventListener('click', () => {
    setPlaying(false);
    show(index);
  }));
  pause.addEventListener('click', () => setPlaying(!hero.hasAttribute('data-hero-playing')));
  // The progress line is a pseudo-element; its animation ending is the cue for the next slide.
  switchRow.addEventListener('animationend', (event) => {
    if (event.animationName === 'bl-hero-progress' && hero.hasAttribute('data-hero-playing')) show((current + 1) % slides.length);
  });
  const followMotion = () => {
    pause.hidden = reducedMotion.matches;
    setPlaying(!reducedMotion.matches);
  };
  followMotion();
  reducedMotion.addEventListener('change', followMotion);
}
