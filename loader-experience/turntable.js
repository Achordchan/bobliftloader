// Real-photo walk-around. Eleven photographs shot around the machine, pre-cropped so the body sits at
// the same place and scale in every frame. A fractional "position" eases toward its target and adjacent
// frames are cross-faded on a canvas, so dragging, the arrow buttons and idle auto-rotation all feel continuous.
// Scrolling deliberately does not turn it: a walk-around is something you drive by hand.
export class Turntable {
  constructor(container, frames, { onStatus, onChange, labels = [] } = {}) {
    this.container = container; this.frames = frames; this.onStatus = onStatus; this.onChange = onChange; this.labels = labels;
    this.images = frames.map(() => null); this.loaded = 0; this.failed = 0;
    this.position = 0; this.target = 0; this.velocity = 0; this.lastIndex = -1;
    this.dragging = false; this.idleSince = performance.now(); this.autoplay = true; this.visible = false;
    this.img = container.querySelector('img');
    this.canvas = document.createElement('canvas'); this.canvas.className = 'exp-turntable-canvas'; this.canvas.setAttribute('aria-hidden', 'true');
    this.img.after(this.canvas);
    this.ctx = this.canvas.getContext('2d');
    this.bind();
    this.observer = new IntersectionObserver(entries => { this.visible = entries.some(e => e.isIntersecting); if (this.visible) this.tick(); }, { rootMargin: '80px' });
    this.observer.observe(container);
  }
  get count() { return this.frames.length; }
  prepare() {
    this.loaded = 0; this.failed = 0;
    return Promise.all(this.frames.map((src, i) => new Promise(resolve => {
      const image = new Image(); image.decoding = 'async'; image.src = src;
      image.decode().then(() => { this.images[i] = image; this.loaded++; }).catch(() => { this.failed++; }).finally(() => { this.onStatus?.({ loaded: this.loaded, failed: this.failed, total: this.count }); resolve(); });
    }))).then(() => { this.ready = true; this.container.classList.add('is-ready'); this.needsDraw = true; this.tick(); });
  }
  // Public: move by whole frames (keyboard, buttons) or set a fractional target (scroll).
  step(delta) { this.target = Math.round(this.target) + delta; this.touch(); }
  touch() { this.userTouched = true; this.idleSince = performance.now(); }
  bind() {
    const c = this.container;
    let startX = 0, startPos = 0, lastX = 0, lastT = 0;
    c.addEventListener('pointerdown', e => {
      if (e.button !== 0 && e.pointerType === 'mouse') return;
      this.dragging = true; this.touch(); startX = lastX = e.clientX; startPos = this.target; lastT = performance.now(); this.velocity = 0;
      c.setPointerCapture(e.pointerId); c.classList.add('is-dragging');
    });
    c.addEventListener('pointermove', e => {
      if (!this.dragging) return;
      const px = this.pixelsPerFrame();
      this.target = startPos - (e.clientX - startX) / px;
      const now = performance.now(); if (now > lastT) this.velocity = -(e.clientX - lastX) / px / ((now - lastT) / 1000);
      lastX = e.clientX; lastT = now;
    });
    const release = () => {
      if (!this.dragging) return;
      this.dragging = false; c.classList.remove('is-dragging'); this.touch();
      const fling = Math.max(-8, Math.min(8, this.velocity * .18)); // frames of momentum, then settle on the nearest frame
      this.target = Math.round(this.target + fling);
    };
    c.addEventListener('pointerup', release); c.addEventListener('pointercancel', release); c.addEventListener('lostpointercapture', release);
    c.addEventListener('keydown', e => { if (e.key === 'ArrowLeft') { this.step(-1); e.preventDefault(); } if (e.key === 'ArrowRight') { this.step(1); e.preventDefault(); } });
    c.addEventListener('pointerenter', () => { this.hover = true; }); c.addEventListener('pointerleave', () => { this.hover = false; });
    c.querySelectorAll('[data-turn-step]').forEach(b => b.addEventListener('click', () => this.step(+b.dataset.turnStep)));
  }
  pixelsPerFrame() { return Math.max(28, this.container.clientWidth / 9); }
  tick = () => {
    if (!this.visible || !this.ready) return;
    requestAnimationFrame(this.tick);
    const now = performance.now();
    // Idle auto-rotation: one frame every 2.8 s once the visitor has left it alone for a while.
    if (this.autoplay && !this.dragging && !this.hover && now - this.idleSince > 3200) { this.target = Math.round(this.target) + 1; this.idleSince = now; }
    const diff = this.target - this.position;
    if (Math.abs(diff) > .0005) { this.position += diff * (this.dragging ? .35 : .16); this.needsDraw = true; }
    else if (this.position !== this.target) { this.position = this.target; this.needsDraw = true; }
    if (this.needsDraw) { this.draw(); this.needsDraw = false; }
  };
  draw() {
    const n = this.count, w = this.container.clientWidth, h = this.container.clientHeight, dpr = Math.min(devicePixelRatio, 1.5);
    if (!w || !h) return;
    if (this.canvas.width !== Math.round(w * dpr) || this.canvas.height !== Math.round(h * dpr)) { this.canvas.width = Math.round(w * dpr); this.canvas.height = Math.round(h * dpr); }
    const p = ((this.position % n) + n) % n, i0 = Math.floor(p), i1 = (i0 + 1) % n, f = p - i0;
    const ctx = this.ctx, W = this.canvas.width, H = this.canvas.height;
    const blit = (image, alpha) => {
      if (!image) return;
      const scale = Math.max(W / image.naturalWidth, H / image.naturalHeight);
      const dw = image.naturalWidth * scale, dh = image.naturalHeight * scale;
      ctx.globalAlpha = alpha; ctx.drawImage(image, (W - dw) / 2, (H - dh) / 2, dw, dh);
    };
    ctx.globalAlpha = 1; blit(this.images[i0], 1);
    if (f > .001) blit(this.images[i1], f);
    ctx.globalAlpha = 1;
    const nearest = Math.round(p) % n;
    if (nearest !== this.lastIndex) { this.lastIndex = nearest; this.onChange?.(nearest, this.labels[nearest] || ''); }
    this.container.style.setProperty('--turn-angle', `${(p / n * 360).toFixed(1)}deg`);
  }
  dispose() { this.observer.disconnect(); this.visible = false; }
}
