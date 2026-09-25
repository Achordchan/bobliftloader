import { PhotoSlides } from './slides.js?v=3';
const t = (source, vars) => window.I18N ? window.I18N.t(source, vars) : source; // i18n/i18n.js

const gsap = window.gsap;
const ScrollTrigger = window.ScrollTrigger;
const preference = matchMedia('(prefers-reduced-motion: reduce)');
const playButton = document.querySelector('#play');
const header = document.querySelector('.exp-header');
let autoplay = null;
let navigationTween = null;
let mediaContext = null;
const clips = [];

const INTRO_PHOTOS = ['IMG_2724', 'IMG_2719', 'IMG_2716', 'IMG_2740', 'IMG_2747', 'IMG_2753', 'IMG_2757', 'IMG_2799', 'IMG_2737', 'IMG_2731', 'IMG_2728'];
const INTRO_LABELS = ['正前', '右前', '右前侧', '右侧', '右后', '正后', '左后', '左侧', '左前侧', '左前', '正前偏左'];
const slideNumber = document.querySelector('#slide-number'), slideLabel = document.querySelector('#slide-label');
new PhotoSlides(document.querySelector('#slides'), INTRO_PHOTOS.map(name => `assets/turn/${name}.webp`), {
  labels: INTRO_LABELS.map(label => t(label)), dots: document.querySelector('.exp-slides-dots'),
  onChange: (index, label) => { slideNumber.textContent = String(index + 1).padStart(2, '0'); slideLabel.textContent = label; },
});

function buildMotion() {
  if (!gsap || !ScrollTrigger) {
    playButton.hidden = true;
    return;
  }
  gsap.registerPlugin(ScrollTrigger);
  mediaContext = gsap.matchMedia();
  mediaContext.add({ mobile: '(max-width: 700px)', desktop: '(min-width: 701px)', reduced: '(prefers-reduced-motion: reduce)' }, context => {
    const { mobile, reduced } = context.conditions;
    if (reduced) return;
    // Each chapter owns its own sticky stage; native document scrolling is preserved.
    // The 3D machine's exit (overhead camera, drive off screen) is scripted in explore.js; the poster zoom is the no-WebGL fallback.
    gsap.timeline({ scrollTrigger: { trigger: '.exp-hero', start: 'top top', end: 'bottom bottom', scrub: .6 } })
      .to('.exp-hero-heading', { yPercent: -48, opacity: 0, duration: .32 }, 0)
      .to('.exp-hero-product > img', { scale: mobile ? 1.12 : 1.2, yPercent: -7, duration: 1, ease: 'none' }, 0)
      .to('.exp-hero-caption, .exp-hero-index', { opacity: 0, y: -24, duration: .2 }, 0);

    gsap.from('.exp-intro-copy h2 span', { opacity: .16, y: 24, stagger: .2, ease: 'none', scrollTrigger: { trigger: '.exp-intro', start: 'top 75%', end: 'top 20%', scrub: .5 } });
    gsap.from('.exp-slides-wrap', { y: 40, scale: .96, opacity: .4, ease: 'none', scrollTrigger: { trigger: '.exp-intro', start: 'top 85%', end: 'top 30%', scrub: .5 } });
    gsap.from('.exp-intro-rule', { scaleX: 0, ease: 'none', scrollTrigger: { trigger: '.exp-intro', start: 'top 70%', end: 'bottom 65%', scrub: .5 } });

    gsap.from('.exp-specs-key > div', { opacity: 0, y: 36, stagger: .12, ease: 'none', scrollTrigger: { trigger: '.exp-specs-key', start: 'top 92%', end: 'top 60%', scrub: .5 } });

    gsap.from('.exp-finale-heading h2', { letterSpacing: '.04em', opacity: .25, ease: 'none', scrollTrigger: { trigger: '.exp-finale', start: 'top 85%', end: 'top 20%', scrub: .5 } }); // settled before the machine drives across it
  });
  ScrollTrigger.create({ trigger: 'main', start: 'top top', end: 'bottom bottom', onUpdate: self => { document.querySelector('.exp-reading-progress').style.transform = `scaleX(${self.progress})`; } });
  document.querySelectorAll('.exp-header nav a[href^="#"]').forEach(link => {
    const section = document.querySelector(link.getAttribute('href'));
    ScrollTrigger.create({ trigger: section, start: 'top 35%', end: 'bottom 35%', onToggle: self => {
      if (self.isActive) link.setAttribute('aria-current', 'location');
      else link.removeAttribute('aria-current');
    } });
  });
}

function stopPlayback() {
  autoplay?.kill(); autoplay = null;
  playButton.setAttribute('aria-pressed', 'false');
  playButton.querySelector('span:last-child').textContent = t(preference.matches ? '浏览章节' : '播放导览');
}
function interrupt() { stopPlayback(); clips.forEach(clip => clip.pause()); navigationTween?.kill(); navigationTween = null; }
// The opening's scroll drives the machine off screen (explore.js), so jumping through it in the usual 1.1 s made the
// machine race: that stretch is travelled at a watching pace (OPENING_PACE seconds per screen height, about 5 s for
// the whole opening), anything beyond it as before.
const OPENING_PACE = 2.2;
function goTo(target) {
  interrupt();
  const y = Math.max(0, target.getBoundingClientRect().top + scrollY - header.offsetHeight + 2);
  if (preference.matches || !gsap) { window.scrollTo(0, y); return; }
  const opening = document.querySelector('.exp-hero'), end = opening.offsetTop + opening.offsetHeight;
  const legs = []; // [to, through the opening]
  if (y > scrollY) { if (scrollY < end) legs.push([Math.min(y, end), true]); if (y > end) legs.push([y, false]); }
  else { if (scrollY > end) legs.push([Math.max(y, end), false]); if (y < end) legs.push([y, true]); }
  const position = { y: scrollY };
  navigationTween = gsap.timeline({ onUpdate: () => window.scrollTo(0, position.y), onComplete: () => { navigationTween = null; } });
  let from = scrollY;
  legs.forEach(([to, slow], i) => {
    const single = legs.length === 1, ease = single ? (slow ? 'sine.inOut' : 'power2.inOut') : i === 0 ? 'sine.in' : 'sine.out';
    navigationTween.to(position, { y: to, ease, duration: slow ? Math.max(1.1, Math.abs(to - from) / innerHeight * OPENING_PACE) : single ? 1.1 : .9 });
    from = to;
  });
}
document.querySelectorAll('a[href^="#"]').forEach(link => link.addEventListener('click', event => {
  const target = document.querySelector(link.getAttribute('href'));
  if (!target) return;
  event.preventDefault(); goTo(target);
  if (link.classList.contains('exp-skip')) { target.tabIndex = -1; target.focus({ preventScroll: true }); }
}));
playButton.addEventListener('click', () => {
  if (preference.matches) { goTo(document.querySelector('#chassis')); return; }
  if (autoplay) { stopPlayback(); return; }
  navigationTween?.kill(); navigationTween = null;
  const end = document.documentElement.scrollHeight - innerHeight;
  if (scrollY > end - 20) window.scrollTo(0, 0);
  const position = { y: scrollY };
  playButton.setAttribute('aria-pressed', 'true');
  playButton.querySelector('span:last-child').textContent = t('暂停导览');
  autoplay = gsap.to(position, { y: end, duration: Math.max(5, 56 * (1 - scrollY / end)), ease: 'none', onUpdate: () => window.scrollTo(0, position.y), onComplete: stopPlayback });
});
window.addEventListener('wheel', interrupt, { passive: true });
window.addEventListener('touchstart', interrupt, { passive: true });
window.addEventListener('keydown', event => {
  if (['ArrowDown', 'ArrowUp', 'PageDown', 'PageUp', 'Home', 'End', 'Escape'].includes(event.key)) interrupt();
});

preference.addEventListener('change', () => { interrupt(); });
window.addEventListener('pagehide', () => { interrupt(); mediaContext?.revert(); clips.forEach(clip => clip.dispose()); });
window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
buildMotion(); stopPlayback();
document.fonts.ready.then(() => ScrollTrigger?.refresh());

const realDialog = document.querySelector('#real-dialog');
const realVideo = document.querySelector('#real-video');
let realOpener = null;
const realClips = { drive: ['行驶转向', 'MVI_2902'], lift: ['举升回落', 'MVI_2897'], bucket: ['铲斗翻转', 'MVI_2905'] };
function selectReal(name) {
  realVideo.pause(); realVideo.src = `assets/real-motion/${name}.mp4`; realVideo.load();
  document.querySelector('#real-caption').textContent = `${t(realClips[name][0])} · ${realClips[name][1]}`;
  document.querySelectorAll('[data-real-choice]').forEach(button => button.setAttribute('aria-pressed', String(button.dataset.realChoice === name)));
}
document.querySelectorAll('[data-real]').forEach(button => button.addEventListener('click', () => {
  interrupt(); realOpener = button; selectReal(button.dataset.real); realDialog.showModal(); document.body.style.overflow = 'hidden'; document.querySelector('#real-close').focus();
}));
document.querySelectorAll('[data-real-choice]').forEach(button => button.addEventListener('click', () => selectReal(button.dataset.realChoice)));
document.querySelector('#real-close').addEventListener('click', () => realDialog.close());
realDialog.addEventListener('click', event => { if (event.target === realDialog) realDialog.close(); });
realDialog.addEventListener('close', () => { realVideo.pause(); document.body.style.overflow = ''; realOpener?.focus({ preventScroll: true }); });
document.addEventListener('visibilitychange', () => { if (document.hidden) { stopPlayback(); clips.forEach(clip => clip.pause()); realVideo.pause(); } });

// Specifications: show only the rows where 360 and 460 differ.
const specsFilter = document.querySelector('#specs-filter');
specsFilter.addEventListener('click', () => {
  const on = specsFilter.getAttribute('aria-pressed') !== 'true';
  specsFilter.setAttribute('aria-pressed', String(on));
  document.querySelector('#specs').classList.toggle('is-diff-only', on);
  ScrollTrigger?.refresh();
});

// Contact: copy the WeChat number (to add it by search in WeChat).
document.querySelectorAll('[data-copy]').forEach(button => {
  const label = button.querySelector('.exp-contact-action'), hint = button.querySelector('.exp-contact-hint'), idle = label.textContent;
  let timer;
  button.addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(button.dataset.copy.replace(/\s/g, '')); } catch { return; } // no clipboard: the number is on screen
    label.textContent = t('已复制'); hint.textContent = t('在微信中搜索此号码添加');
    button.classList.add('is-copied'); clearTimeout(timer);
    timer = setTimeout(() => { label.textContent = idle; hint.textContent = ''; button.classList.remove('is-copied'); }, 3200);
  });
});
