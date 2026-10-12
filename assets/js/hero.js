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

  // Later slides' pictures wait (see templates/home.php) until a little after the page has loaded, so on a slow phone
  // connection they never compete with the first screen; the first turn comes seconds later (--hero-interval), and a
  // slide chosen sooner fetches its picture at once (show() below).
  const fetchLaterPictures = () => pictures.slice(1).forEach((picture) => picture.querySelectorAll('[data-srcset], [data-src]').forEach((element) => {
    if (element.dataset.srcset) element.srcset = element.dataset.srcset;
    if (element.dataset.src) element.src = element.dataset.src;
    element.removeAttribute('data-srcset');
    element.removeAttribute('data-src');
  }));
  const fetchSoon = () => window.setTimeout(fetchLaterPictures, 2500);
  if (document.readyState === 'complete') fetchSoon();
  else window.addEventListener('load', fetchSoon, { once: true });

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
    fetchLaterPictures(); // a slide chosen before the page finished loading still gets its picture
    current = next;
    slides.forEach((slide, index) => {
      const active = index === next;
      slide.classList.toggle('bl-active', active);
      slide.inert = !active;
    });
    // The picture changes once it can be drawn, so a slide chosen while its picture is still loading keeps the current
    // one in view until then, and keeps it if the new one fails to load.
    const showPicture = () => {
      if (current !== next) return;
      pictures.forEach((picture, index) => {
        picture.classList.toggle('bl-active', index === next);
        if (index === next) picture.removeAttribute('aria-hidden');
        else picture.setAttribute('aria-hidden', 'true');
      });
    };
    const image = pictures[next]?.querySelector('img');
    if (!image || (image.complete && image.naturalWidth)) showPicture();
    // decode() also rejects when a turn of the phone swaps the picture's source mid-load; the swapped picture's load still shows it.
    else image.decode().then(showPicture, () => image.addEventListener('load', showPicture, { once: true }));
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
