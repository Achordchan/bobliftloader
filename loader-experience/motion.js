/** A single pending video seek prevents a backlog when the user scrolls quickly. */
export class MechanicalClip {
  constructor(container, { onPlay } = {}) {
    this.container = container;
    this.video = container.querySelector('video');
    this.button = container.querySelector('[data-motion-play]');
    this.slider = container.querySelector('[data-motion-progress]');
    this.status = container.querySelector('[data-motion-status]');
    this.retry = container.querySelector('[data-motion-retry]');
    this.onPlay = onPlay;
    this.target = 0;
    this.manual = false;
    this.ready = false;
    this.loading = false;
    this.disposed = false;
    this.video.muted = true;
    this.video.playsInline = true;
    this.observer = new IntersectionObserver(entries => {
      for (const entry of entries) {
        if (entry.isIntersecting) this.load();
        else this.pause();
      }
    }, { rootMargin: '600px' });
    this.observer.observe(container);
    this.video.addEventListener('loadeddata', () => {
      this.ready = true;
      this.loading = false;
      this.container.classList.add('is-ready');
      this.status.hidden = true;
      this.slider.disabled = false;
      this.commit();
    });
    this.video.addEventListener('seeked', () => { this.updateUI(); this.commit(); });
    this.video.addEventListener('timeupdate', () => this.updateUI());
    this.video.addEventListener('ended', () => this.pause());
    this.video.addEventListener('error', () => {
      this.loading = false;
      this.status.hidden = false;
      this.status.querySelector('span').textContent = '动作视频未能加载';
      this.retry.hidden = false;
      this.pause();
    });
    this.button.addEventListener('click', () => this.toggle());
    this.slider.addEventListener('input', () => { this.pause(); this.seek(Number(this.slider.value) / 1000); });
    this.retry.addEventListener('click', () => this.load(true));
  }

  load(retry = false) {
    if (this.disposed || this.loading || (this.ready && !retry)) return;
    this.loading = true;
    this.status.hidden = false;
    this.status.querySelector('span').textContent = '正在准备机械动作';
    this.retry.hidden = true;
    this.video.preload = 'auto';
    this.video.src = this.video.dataset.src;
    this.video.load();
  }

  seek(progress) {
    this.target = Math.max(0, Math.min(1, progress));
    this.commit();
  }

  commit() {
    if (!this.ready || this.manual || this.video.seeking || this.disposed) return;
    const end = Math.max(0, this.video.duration - 1 / 24);
    const wanted = this.target * end;
    if (Number.isFinite(wanted) && Math.abs(this.video.currentTime - wanted) > 1 / 48) this.video.currentTime = wanted;
  }

  async toggle() {
    if (this.manual) { this.pause(); return; }
    if (!this.ready) { this.load(); return; }
    this.onPlay?.(this);
    if (this.video.currentTime > this.video.duration - .1) this.video.currentTime = 0;
    this.manual = true;
    try {
      await this.video.play();
      this.button.setAttribute('aria-pressed', 'true');
      this.button.querySelector('span:last-child').textContent = '暂停动作';
    } catch (error) {
      this.manual = false;
      this.status.hidden = false;
      this.status.querySelector('span').textContent = '请再次点击播放动作';
    }
  }

  pause() {
    if (this.manual) this.target = this.video.duration ? this.video.currentTime / this.video.duration : this.target;
    this.manual = false;
    this.video.pause();
    this.button.setAttribute('aria-pressed', 'false');
    this.button.querySelector('span:last-child').textContent = '播放动作';
  }

  updateUI() {
    if (!this.video.duration) return;
    const progress = this.video.currentTime / this.video.duration;
    this.slider.value = String(Math.round(progress * 1000));
    this.container.dataset.motionProgress = progress.toFixed(3);
    if (this.container.dataset.motion === 'operation') {
      const phase = progress < .34 ? 0 : progress < .78 ? 1 : 2;
      document.querySelectorAll('.exp-hydraulic-points li').forEach((item, i) => item.classList.toggle('is-current', i === phase));
    }
  }

  dispose() { this.disposed = true; this.pause(); this.observer.disconnect(); this.video.removeAttribute('src'); this.video.load(); }
}
