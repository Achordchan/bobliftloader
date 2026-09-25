// Intro photo slideshow: site photographs of the same machine, one at a time with a cross-fade.
// They were shot in different places, so they are presented as photographs, not as a turntable.
// Autoplay is timed by the active bar's CSS fill animation: when it ends, the next photo shows. Pausing is just
// pausing that animation (hover, focus, off screen, background tab), and with reduced motion there is no animation,
// so no autoplay.
const t = (source, vars) => window.I18N ? window.I18N.t(source, vars) : source; // i18n/i18n.js

export class PhotoSlides {
  constructor(root, photos, { labels = [], dots, onChange } = {}) {
    this.root = root; this.photos = photos; this.labels = labels; this.onChange = onChange; this.index = -1;
    this.track = root.querySelector('.exp-slides-track');
    const first = this.track.querySelector('.exp-slide');
    this.slides = photos.map((src, i) => {
      if (i === 0 && first) return first;
      const figure = document.createElement('figure'); figure.className = 'exp-slide';
      // No src yet: stacked in one place, every lazy image would count as "near the viewport" and all would download at once.
      figure.innerHTML = `<img data-src="${src}" width="1600" height="1067" alt="${t('装载机实拍 · {label}', { label: labels[i] ?? String(i + 1) })}" loading="lazy" decoding="async">`;
      this.track.appendChild(figure); return figure;
    });
    dots.innerHTML = photos.map((_, i) => `<button type="button" aria-label="${t('第 {n} 张：{label}', { n: i + 1, label: labels[i] ?? '' })}"><i></i></button>`).join('');
    this.dots = [...dots.querySelectorAll('button')];
    this.dots.forEach((dot, i) => {
      dot.addEventListener('click', () => this.go(i));
      dot.querySelector('i').addEventListener('animationend', () => { if (i === this.index) this.go(i + 1); });
    });
    root.querySelectorAll('[data-slide-step]').forEach(b => b.addEventListener('click', () => this.go(this.index + +b.dataset.slideStep)));
    root.addEventListener('keydown', e => {
      if (e.key === 'ArrowLeft') { this.go(this.index - 1); e.preventDefault(); }
      if (e.key === 'ArrowRight') { this.go(this.index + 1); e.preventDefault(); }
    });
    // A horizontal swipe on touch screens changes photo; vertical movement stays page scrolling.
    let start = null;
    this.track.addEventListener('pointerdown', e => { if (e.pointerType !== 'mouse') start = [e.clientX, e.clientY]; });
    this.track.addEventListener('pointerup', e => {
      if (!start) return;
      const dx = e.clientX - start[0], dy = e.clientY - start[1]; start = null;
      if (Math.abs(dx) > 40 && Math.abs(dx) > Math.abs(dy) * 1.5) this.go(this.index + (dx < 0 ? 1 : -1));
    });
    this.track.addEventListener('pointercancel', () => { start = null; });
    const pause = () => root.parentElement.classList.toggle('is-paused', this.hover || this.focus || !this.visible || document.hidden);
    root.addEventListener('pointerenter', e => { if (e.pointerType === 'mouse') { this.hover = true; pause(); } });
    root.addEventListener('pointerleave', () => { this.hover = false; pause(); });
    root.addEventListener('focusin', () => { this.focus = true; pause(); });
    root.addEventListener('focusout', () => { this.focus = false; pause(); });
    document.addEventListener('visibilitychange', pause);
    this.observer = new IntersectionObserver(entries => { this.visible = entries.some(e => e.isIntersecting); pause(); }, { threshold: .35 });
    this.observer.observe(root);
    this.visible = false; pause();
    this.go(0);
  }

  go(i) {
    const n = this.photos.length, next = ((i % n) + n) % n;
    if (next === this.index) return;
    this.index = next;
    this.slides.forEach((s, k) => { s.classList.toggle('is-active', k === next); s.setAttribute('aria-hidden', String(k !== next)); });
    this.dots.forEach((d, k) => {
      d.setAttribute('aria-current', String(k === next));
      const bar = d.querySelector('i'); bar.style.animation = 'none'; void bar.offsetWidth; bar.style.animation = ''; // restart the fill
    });
    // Load the current photo and its neighbours only, so the fade never waits on a download and the rest stay unfetched.
    [next, next + 1, next - 1].forEach(k => { const img = this.slides[(k + n) % n].querySelector('img'); if (img.dataset.src) { img.src = img.dataset.src; delete img.dataset.src; } });
    this.onChange?.(next, this.labels[next]);
  }
}
