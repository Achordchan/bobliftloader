/** Rate-limited material transfer; lowering the target alone cannot empty a closed bucket. */
export class MaterialFlow {
  constructor({ fillRate = .8, emptyRate = .65 } = {}) {
    this.fillRate = fillRate;
    this.emptyRate = emptyRate;
    this.reset();
  }
  reset() { this.amount = 0; this.target = 0; this.discharged = 0; }
  setTarget(value) { this.target = Math.max(0, Math.min(1, Number.isFinite(value) ? value : 0)); }
  step(seconds, outletDownward) {
    const dt = Math.max(0, Math.min(seconds, .05));
    const before = this.amount;
    if (this.target > before) this.amount = Math.min(this.target, before + this.fillRate * dt);
    else if (outletDownward) this.amount = Math.max(this.target, before - this.emptyRate * dt);
    const released = Math.max(0, before - this.amount);
    this.discharged += released;
    return released;
  }
}
