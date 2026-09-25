/** Decode the short orbit before playback; scrolling never starts an image decode. */
export class PreparedOrbit {
  constructor(canvas, { onStatus, enabled = true } = {}) {
    this.canvas = canvas;
    this.view = canvas.parentElement;
    this.context = canvas.getContext('2d', { alpha: true });
    this.onStatus = onStatus;
    this.enabled = enabled;
    this.images = Array(24).fill(null);
    this.requests = new Set();
    this.decodeSize = matchMedia('(max-width: 700px)').matches ? 480 : 680;
    this.frame = 0;
    this.drawn = -1;
    this.raf = 0;
    this.ready = false;
    this.preparing = false;
    this.disposed = false;
    this.observer = new IntersectionObserver(entries => {
      this.nearby = entries.some(entry => entry.isIntersecting);
      if (this.nearby && this.enabled) this.prepare();
    }, { rootMargin: '800px' });
    this.observer.observe(this.view);
    this.resizeObserver = new ResizeObserver(() => this.resize());
    this.resizeObserver.observe(canvas);
  }

  resize() {
    const size = Math.min(this.decodeSize, Math.round(this.view.clientWidth * Math.min(devicePixelRatio || 1, 1.25)));
    if (size <= 0 || this.canvas.width === size) return;
    this.canvas.width = size;
    this.canvas.height = size;
    this.drawn = -1;
    this.schedule();
  }

  setEnabled(enabled) {
    this.enabled = enabled;
    this.view.classList.toggle('is-ready', enabled && this.ready);
    if (!enabled) this.requests.forEach(request => request.abort());
    else if (this.preparing) this.restartRequested = true;
    else if (this.nearby) this.prepare();
  }

  async prepare() {
    if (this.disposed || !this.enabled || this.preparing) return;
    if (this.ready) { this.view.classList.add('is-ready'); this.schedule(); return; }
    this.preparing = true;
    const queue = this.images.map((image, index) => image ? null : index).filter(index => index !== null);
    let failed = 0;
    const status = () => this.onStatus?.({ loaded: this.images.filter(Boolean).length, total: 24, failed });
    const worker = async () => {
      while (queue.length && this.enabled && !this.disposed) {
        const index = queue.shift();
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 15000);
        this.requests.add(controller);
        try {
          const response = await fetch(`assets/orbit/${String(index).padStart(3, '0')}.webp`, { signal: controller.signal });
          if (!response.ok) throw new Error('Orbit frame unavailable');
          const blob = await response.blob();
          const image = await createImageBitmap(blob, { resizeWidth: this.decodeSize, resizeHeight: this.decodeSize, resizeQuality: 'medium' });
          if (this.disposed) image.close();
          else this.images[index] = image;
        } catch (error) {
          if (this.enabled && !this.disposed) failed += 1;
        } finally {
          clearTimeout(timeout);
          this.requests.delete(controller);
          if (this.enabled) status();
        }
      }
    };
    status();
    await Promise.all([worker(), worker(), worker()]);
    this.preparing = false;
    if (this.disposed) return;
    this.ready = this.images.every(Boolean);
    this.view.classList.toggle('is-ready', this.ready && this.enabled);
    this.schedule();
    if (this.restartRequested && this.enabled) {
      this.restartRequested = false;
      this.prepare();
    }
  }

  update(progress) {
    this.frame = Math.round(Math.max(0, Math.min(1, progress)) * 23);
    this.schedule();
  }

  schedule() {
    if (!this.ready || !this.enabled || this.raf || this.disposed) return;
    this.raf = requestAnimationFrame(() => {
      this.raf = 0;
      if (!this.enabled || this.drawn === this.frame) return;
      const size = this.canvas.width;
      this.context.clearRect(0, 0, size, size);
      this.context.drawImage(this.images[this.frame], 0, 0, size, size);
      this.drawn = this.frame;
    });
  }

  dispose() {
    this.disposed = true;
    this.requests.forEach(request => request.abort());
    this.images.forEach(image => image?.close());
    this.observer.disconnect();
    this.resizeObserver.disconnect();
    cancelAnimationFrame(this.raf);
  }
}
