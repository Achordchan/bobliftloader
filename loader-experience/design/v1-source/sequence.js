/** Bounded network and decoded-image caches for the real Blender frame sequence. */
export class FrameSequence {
  constructor(canvas, { count = 120, onStatus, onDraw, reduced = false }) {
    this.canvas = canvas;
    this.context = canvas.getContext('2d', { alpha: true });
    this.count = count;
    this.onStatus = onStatus;
    this.onDraw = onDraw;
    this.blobs = new Map();
    this.bitmaps = new Map();
    this.fetching = new Map();
    this.decoding = new Set();
    this.failed = new Set();
    this.decodeFailed = new Set();
    this.desired = 0;
    this.frame = 0;
    this.raf = 0;
    this.disposed = false;
    this.keys = [0, 38, 79, 119];
    this.cacheLimit = matchMedia('(max-width: 700px)').matches ? 10 : 16;
    this.setReduced(reduced);
    this.resizeObserver = new ResizeObserver(() => this.resize());
    this.resizeObserver.observe(canvas);
    this.resize();
  }

  setReduced(reduced) {
    this.allowed = reduced ? [...this.keys] : Array.from({ length: this.count }, (_, i) => i);
    this.pump();
  }

  resize() {
    const box = this.canvas.getBoundingClientRect();
    const ratio = Math.min(devicePixelRatio || 1, 1.6);
    this.canvas.width = Math.max(1, Math.round(box.width * ratio));
    this.canvas.height = Math.max(1, Math.round(box.height * ratio));
    this.requestDraw();
  }

  update(frame) {
    this.frame = Math.max(0, Math.min(this.count - 1, frame));
    this.desired = Math.round(this.frame);
    this.decode(Math.floor(this.frame));
    this.decode(Math.ceil(this.frame));
    this.pump();
    this.requestDraw();
  }

  priority(index) {
    if (index === this.desired) return -2000;
    if (index === 0) return -1900;
    if (this.keys.includes(index)) return -1000 + this.keys.indexOf(index);
    return Math.abs(index - this.desired);
  }

  pump() {
    if (this.disposed) return;
    const pending = this.allowed.filter(i => !this.blobs.has(i) && !this.fetching.has(i) && !this.failed.has(i));
    pending.sort((a, b) => this.priority(a) - this.priority(b));
    while (this.fetching.size < 4 && pending.length) this.fetchFrame(pending.shift());
    this.status();
  }

  async fetchFrame(index) {
    const controller = new AbortController();
    this.fetching.set(index, controller);
    const timeout = setTimeout(() => controller.abort(), 20000);
    try {
      const path = `assets/sequence/${String(index).padStart(4, '0')}.webp`;
      const response = await fetch(path, { signal: controller.signal });
      if (!response.ok) throw new Error(`Frame ${index}: HTTP ${response.status}`);
      const blob = await response.blob();
      if (!blob.size) throw new Error(`Frame ${index}: empty response`);
      if (this.disposed) return;
      this.blobs.set(index, blob);
      if (index === 0 || Math.abs(index - this.desired) <= 2) await this.decode(index);
    } catch (error) {
      if (!this.disposed) this.failed.add(index);
    } finally {
      clearTimeout(timeout);
      this.fetching.delete(index);
      this.pump();
    }
  }

  async decode(index) {
    if (this.disposed || this.bitmaps.has(index) || this.decoding.has(index) || this.decodeFailed.has(index) || this.decoding.size >= 2) return;
    const blob = this.blobs.get(index);
    if (!blob) return;
    this.decoding.add(index);
    try {
      const bitmap = await createImageBitmap(blob);
      if (this.disposed) { bitmap.close(); return; }
      this.bitmaps.set(index, bitmap);
      this.trim();
      this.requestDraw();
    } catch (error) {
      this.decodeFailed.add(index);
      this.failed.add(index);
      this.blobs.delete(index);
      this.status();
    } finally {
      this.decoding.delete(index);
      this.decode(Math.floor(this.frame));
      this.decode(Math.ceil(this.frame));
    }
  }

  trim() {
    while (this.bitmaps.size > this.cacheLimit) {
      const farthest = [...this.bitmaps.keys()].sort((a, b) => Math.abs(b - this.desired) - Math.abs(a - this.desired))[0];
      this.bitmaps.get(farthest).close();
      this.bitmaps.delete(farthest);
    }
  }

  requestDraw() {
    if (this.raf || this.disposed) return;
    this.raf = requestAnimationFrame(() => { this.raf = 0; this.draw(); });
  }

  draw() {
    if (!this.bitmaps.size) return;
    const lower = Math.floor(this.frame), upper = Math.ceil(this.frame);
    let image = this.bitmaps.get(lower);
    let displayed = lower;
    if (!image) {
      displayed = [...this.bitmaps.keys()].sort((a, b) => Math.abs(a - this.frame) - Math.abs(b - this.frame))[0];
      image = this.bitmaps.get(displayed);
    }
    const { width, height } = this.canvas;
    const ctx = this.context;
    ctx.clearRect(0, 0, width, height);
    ctx.globalAlpha = 1;
    ctx.drawImage(image, 0, 0, width, height);
    if (displayed === lower && upper !== lower && this.bitmaps.has(upper)) {
      ctx.globalAlpha = this.frame - lower;
      ctx.drawImage(this.bitmaps.get(upper), 0, 0, width, height);
      ctx.globalAlpha = 1;
    }
    this.canvas.dataset.frame = String(displayed);
    this.onDraw?.(displayed);
  }

  status() {
    this.onStatus?.({ loaded: this.allowed.filter(i => this.blobs.has(i)).length, total: this.allowed.length, failed: this.failed.size });
  }

  retry() {
    this.failed.clear(); this.decodeFailed.clear(); this.pump();
    this.decode(Math.round(this.frame));
  }

  dispose() {
    this.disposed = true;
    this.fetching.forEach(controller => controller.abort());
    this.bitmaps.forEach(bitmap => bitmap.close());
    this.bitmaps.clear(); this.blobs.clear();
    cancelAnimationFrame(this.raf);
    this.resizeObserver.disconnect();
  }
}
