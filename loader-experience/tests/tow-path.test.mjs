// Tow kinematics for the hero wordmark: the chain never stretches, the take-up is smooth, the wordmark stays hooked
// on, and a slack chain keeps its length. Run: node loader-experience/tests/tow-path.test.mjs
import assert from 'node:assert/strict';
import { createTow, chainPath, alongPath } from '../tow-path.js';

const length = .75, gap = length * .62;
const tow = createTow({ length, gap });

// Before the slack is taken up the wordmark barely moves; afterwards it follows the machine one-for-one.
assert.ok(tow.shift(0) === 0, 'no movement at rest');
assert.ok(tow.shift((length - gap) * .5) < .005, 'still lying while the chain is slack');
const late = 3;
assert.ok(Math.abs((tow.shift(late + .5) - tow.shift(late)) - .5) < 1e-6, 'towed one-for-one once taut');

// Monotonic, never stretches the chain, never pushes the wordmark ahead of the chain.
let previous = -1, maxSpan = 0, maxStep = 0;
for (let a = 0; a <= 8; a += .002) {
  const s = tow.shift(a), span = tow.span(a);
  assert.ok(s >= previous - 1e-12, `shift is monotonic at ${a}`);
  if (previous >= 0) maxStep = Math.max(maxStep, s - previous);
  previous = s; maxSpan = Math.max(maxSpan, span);
}
assert.ok(maxSpan <= length + 1e-9, `chain never exceeds its length (max span ${maxSpan})`);
assert.ok(maxStep <= .002 + 1e-9, 'no jump when the chain goes taut');

// advanceFor inverts shift (used to stop the machine with the wordmark centred, and to tow it out of frame).
for (const target of [.1, 1, 3.37, 6]) assert.ok(Math.abs(tow.shift(tow.advanceFor(target)) - target) < 1e-6, `advanceFor(${target})`);

// Chain shapes: a slack chain keeps its length, a taut one is straight, and the link walker spaces links evenly.
const arc = pts => pts.slice(1).reduce((sum, p, i) => sum + Math.hypot(p[0] - pts[i][0], p[1] - pts[i][1], p[2] - pts[i][2]), 0);
const slack = chainPath([0, .3, 1], [0, .025, 1.45], length);
assert.ok(Math.abs(arc(slack) - length) < .003, `slack chain keeps its length (${arc(slack).toFixed(4)})`);
assert.deepEqual(slack[0], [0, .3, 1]); assert.ok(Math.hypot(...slack.at(-1).map((v, i) => v - [0, .025, 1.45][i])) < 1e-9, 'slack chain ends on its anchors');
const taut = chainPath([0, .3, 1], [0, .025, 1 + length], length);
assert.ok(taut.every(p => Math.abs(p[0]) < 1e-9), 'taut chain is straight');
const again = chainPath([0, .3, 1], [0, .025, 1.45], length);
assert.deepEqual(again, slack, 'deterministic: scrubbing back reproduces the same shape');
const placed = []; alongPath(slack, .05, p => placed.push(p));
assert.ok(Math.abs(placed.length - Math.floor(length / .05)) <= 1, `links along the chain (${placed.length})`);

console.log('PASS: smooth take-up, no stretch, monotonic tow, inverse advance, slack length, straight when taut, deterministic shapes');
