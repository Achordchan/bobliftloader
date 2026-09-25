import { FrameSequence } from './sequence.js';

const gsap = window.gsap;
const ScrollTrigger = window.ScrollTrigger;
const preference = matchMedia('(prefers-reduced-motion: reduce)');
const stage = document.querySelector('.exp-stage');
const panels = [...document.querySelectorAll('[data-panel]')];
const chapterButtons = [...document.querySelectorAll('.exp-chapters button')];
const progress = document.querySelector('#progress');
const playButton = document.querySelector('#play');
const loading = document.querySelector('#loading');
const retryButton = document.querySelector('#retry');
const stops = [0, .32, .66, 1];
const keyframes = [0, 38, 79, 119];
let chapter = 0;
stage.dataset.chapter = '0';
let autoplay = null;
let navigation = null;
let motion = null;
let trigger = null;
let lastProgress = 0;

const sequence = new FrameSequence(document.querySelector('#sequence'), {
  reduced: preference.matches,
  onDraw: () => document.querySelector('#media').classList.add('is-ready'),
  onStatus: ({ loaded, total, failed }) => {
    loading.classList.toggle('is-complete', loaded === total && !failed);
    loading.classList.toggle('is-error', failed > 0);
    retryButton.hidden = !failed;
    document.querySelector('#loading-text').textContent = failed
      ? '部分视角未加载'
      : `正在准备视角 ${Math.round(loaded / total * 100)}%`;
    stage.dataset.loaded = String(loaded);
    stage.dataset.total = String(total);
  },
});
retryButton.addEventListener('click', () => sequence.retry());

function updateChapter(next) {
  if (chapter === next) return;
  const previous = chapter;
  chapter = next;
  panels.forEach((panel, i) => {
    panel.inert = i !== next;
    panel.setAttribute('aria-hidden', String(i !== next));
    panel.classList.toggle('is-active', i === next);
  });
  chapterButtons.forEach((button, i) => {
    if (i === next) button.setAttribute('aria-current', 'step');
    else button.removeAttribute('aria-current');
  });
  if (gsap && !preference.matches) {
    gsap.fromTo(panels[previous], { autoAlpha: 1, y: 0 }, { autoAlpha: 0, y: -20, duration: .25, overwrite: true });
    gsap.fromTo(panels[next], { autoAlpha: 0, y: 26 }, { autoAlpha: 1, y: 0, duration: .55, delay: .1, ease: 'power2.out', overwrite: true });
  }
  stage.dataset.chapter = String(next);
  document.querySelector('#scroll-hint').textContent = next === 3 ? '每一面，都值得再看' : '向下滚动，探索每一面';
}

function paint(value) {
  lastProgress = Math.max(0, Math.min(1, value));
  const next = value < .18 ? 0 : value < .48 ? 1 : value < .85 ? 2 : 3;
  updateChapter(next);
  sequence.update(preference.matches ? keyframes[next] : lastProgress * 119);
  progress.style.transform = `scaleX(${.04 + lastProgress * .96})`;
  document.querySelector('.exp-numerals').style.opacity = String(1 - Math.sin(lastProgress * Math.PI) * .55);
}

function initScroll() {
  motion?.kill(); trigger?.kill();
  if (!gsap || !ScrollTrigger) {
    document.querySelector('#loading-text').textContent = '动效组件未加载，请刷新页面';
    playButton.disabled = true;
    return;
  }
  gsap.registerPlugin(ScrollTrigger);
  if (preference.matches) {
    trigger = ScrollTrigger.create({ trigger: '.exp-story', start: 'top top', end: 'bottom bottom', onUpdate: self => paint(self.progress) });
  } else {
    const state = { value: lastProgress };
    motion = gsap.fromTo(state, { value: 0 }, { value: 1, ease: 'none', onUpdate: () => paint(state.value), scrollTrigger: { trigger: '.exp-story', start: 'top top', end: 'bottom bottom', scrub: .55 } });
    trigger = motion.scrollTrigger;
  }
  playButton.querySelector('span').textContent = preference.matches ? '逐段查看' : '播放演示';
  ScrollTrigger.refresh();
  paint(trigger.progress);
}

function stopPlayback() {
  autoplay?.kill(); autoplay = null;
  playButton.setAttribute('aria-pressed', 'false');
  playButton.querySelector('span').textContent = preference.matches ? '逐段查看' : '播放演示';
}

function moveTo(index, { duration = 1.6 } = {}) {
  stopPlayback(); navigation?.kill();
  const maxScroll = document.querySelector('.exp-story').offsetHeight - innerHeight;
  const destination = maxScroll * stops[index];
  if (preference.matches || !gsap) { window.scrollTo(0, destination); return; }
  const position = { y: scrollY };
  navigation = gsap.to(position, { y: destination, duration, ease: 'power2.inOut', onUpdate: () => window.scrollTo(0, position.y), onComplete: () => { navigation = null; } });
}

playButton.addEventListener('click', () => {
  if (preference.matches) { moveTo((chapter + 1) % 4); return; }
  if (autoplay) { stopPlayback(); return; }
  navigation?.kill(); navigation = null;
  const maxScroll = document.querySelector('.exp-story').offsetHeight - innerHeight;
  if (scrollY > maxScroll - 12) window.scrollTo(0, 0);
  const position = { y: scrollY };
  playButton.setAttribute('aria-pressed', 'true');
  playButton.querySelector('span').textContent = '暂停演示';
  autoplay = gsap.to(position, { y: maxScroll, duration: Math.max(4, 26 * (1 - scrollY / maxScroll)), ease: 'none', onUpdate: () => window.scrollTo(0, position.y), onComplete: stopPlayback });
});

function interruptScroll() { stopPlayback(); navigation?.kill(); navigation = null; }
window.addEventListener('wheel', interruptScroll, { passive: true });
window.addEventListener('touchstart', interruptScroll, { passive: true });
window.addEventListener('keydown', event => {
  if (['ArrowDown', 'ArrowUp', 'PageDown', 'PageUp', 'Home', 'End', 'Escape'].includes(event.key)) interruptScroll();
});
document.querySelectorAll('[data-go], .exp-nav [data-chapter]').forEach(button => button.addEventListener('click', event => {
  event.preventDefault(); moveTo(Number(button.dataset.go ?? button.dataset.chapter));
}));
document.querySelector('.exp-brand').addEventListener('click', event => { event.preventDefault(); moveTo(0); });

const gallery = document.querySelector('#gallery');
const openGallery = document.querySelector('#photos-open');
openGallery.addEventListener('click', () => {
  interruptScroll(); gallery.showModal();
  document.body.style.overflow = 'hidden';
  document.querySelector('#photos-close').focus();
});
function closeGallery() { gallery.close(); }
document.querySelector('#photos-close').addEventListener('click', closeGallery);
gallery.addEventListener('click', event => { if (event.target === gallery) closeGallery(); });
gallery.addEventListener('close', () => { document.body.style.overflow = ''; openGallery.focus(); });
document.querySelectorAll('[data-photo]').forEach(button => button.addEventListener('click', () => {
  const image = document.querySelector('#gallery-image');
  image.src = `assets/photos/${button.dataset.photo}.webp`;
  image.alt = `${button.textContent}实拍`;
  document.querySelector('#gallery-caption').textContent = button.textContent;
  document.querySelectorAll('[data-photo]').forEach(tab => tab.setAttribute('aria-pressed', String(tab === button)));
}));
preference.addEventListener('change', () => {
  interruptScroll();
  gsap?.set(panels, { clearProps: 'opacity,visibility,transform' });
  sequence.setReduced(preference.matches); initScroll();
});
window.addEventListener('pagehide', () => { stopPlayback(); sequence.dispose(); });
window.addEventListener('pageshow', event => { if (event.persisted) location.reload(); });
initScroll();
document.fonts.ready.then(() => ScrollTrigger?.refresh());
