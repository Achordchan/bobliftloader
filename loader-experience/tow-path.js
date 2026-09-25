// Pure tow kinematics for the hero exit: how far the towed wordmark has moved for a given machine advance,
// and the shape a chain of fixed length takes between two points. No three.js, so it can be tested in Node.
// Everything is a function of the machine's advance (not of time), so scrubbing the page backwards replays exactly.

/**
 * The wordmark stays hooked on the whole way: the machine takes up the slack, pulls it in, and later tows it off.
 * @param {object} c
 * @param {number} c.length   main chain length (hitch → bridle ring), metres
 * @param {number} c.gap      horizontal hitch → ring distance before the machine moves (< length: the chain starts slack)
 * @param {number} [c.soft]   width of the take-up: the chain tightens smoothly instead of snapping taut
 */
export function createTow({ length, gap, soft = .06 }) {
  // Hitch → ring distance can never exceed the chain: a soft minimum of (gap + advance) and the chain length.
  const reach = x => length - soft * Math.log1p(Math.exp((length - x) / soft));
  const raw = a => gap + a - reach(gap + a);
  const offset = raw(0);
  const shift = a => Math.max(0, raw(a) - offset); // wordmark displacement for a machine advance
  return {
    shift,
    /** Horizontal hitch → ring distance. */
    span: a => Math.min(length, gap + a - shift(a)),
    /** Machine advance at which the wordmark has moved `target` metres (shift is monotonic). */
    advanceFor(target) {
      let lo = 0, hi = target + length + 5;
      for (let i = 0; i < 60; i++) { const mid = (lo + hi) / 2; if (shift(mid) < target) lo = mid; else hi = mid; }
      return hi;
    },
  };
}

/**
 * Points of a chain of `length` hanging from `a` to `b` (each [x, y, z]); fills `out` with `samples` + 1 points.
 * Taut: a straight line. Slack: it lies on the ground in a shallow S whose amplitude is solved so the arc length
 * stays equal to the chain length, and it drops from the hitch height to the ground near the start.
 */
export function chainPath(a, b, length, out = [], samples = 48) {
  const dx = b[0] - a[0], dz = b[2] - a[2], d = Math.hypot(dx, dz);
  const ux = d > 1e-6 ? dx / d : 0, uz = d > 1e-6 ? dz / d : 1; // along the chain, on the ground
  const nx = -uz, nz = ux;                                       // sideways
  const shape = (amp, write) => {
    let len = 0, px = 0, py = 0, pz = 0;
    for (let i = 0; i <= samples; i++) {
      const t = i / samples, drop = (1 - t) ** 3;
      const side = amp * Math.sin(2 * Math.PI * t) * (1 - .35 * t);
      const along = (d < length ? d : length) * t;
      const x = a[0] + ux * along + nx * side, z = a[2] + uz * along + nz * side;
      const y = a[1] * drop + b[1] * t * (1 - drop);
      if (i) len += Math.hypot(x - px, y - py, z - pz);
      if (write) out[i] = [x, y, z];
      px = x; py = y; pz = z;
    }
    return len;
  };
  let lo = 0, hi = length;
  if (shape(0, false) < length) for (let i = 0; i < 30; i++) { const mid = (lo + hi) / 2; if (shape(mid, false) < length) lo = mid; else hi = mid; }
  shape(shape(0, false) < length ? lo : 0, true);
  out.length = samples + 1;
  return out;
}

/** Walk a polyline and call fn(position, tangent) every `pitch` metres; returns how many were placed. */
export function alongPath(points, pitch, fn, limit = Infinity) {
  let placed = 0, carry = pitch / 2;
  for (let i = 1; i < points.length && placed < limit; i++) {
    const p = points[i - 1], q = points[i];
    const sx = q[0] - p[0], sy = q[1] - p[1], sz = q[2] - p[2], seg = Math.hypot(sx, sy, sz);
    if (seg < 1e-9) continue;
    while (carry <= seg && placed < limit) {
      const t = carry / seg;
      fn([p[0] + sx * t, p[1] + sy * t, p[2] + sz * t], [sx / seg, sy / seg, sz / seg], placed++);
      carry += pitch;
    }
    carry -= seg;
  }
  return placed;
}
