import assert from 'node:assert/strict';
import { MaterialFlow } from '../material-flow.js';
const flow = new MaterialFlow();
flow.setTarget(1);
for (let i = 0; i < 120; i++) flow.step(1 / 60, false);
assert.equal(flow.amount, 1);
flow.setTarget(0);
for (let i = 0; i < 120; i++) flow.step(1 / 60, false);
assert.equal(flow.amount, 1, 'An upright bucket cannot empty just because the animation target dropped');
let released = flow.step(1 / 60, true);
assert.ok(flow.amount > .98 && flow.amount < 1, 'No sudden disappearance on the first dumping frame');
for (let i = 0; i < 180; i++) {
  const before = flow.amount;
  released += flow.step(1 / 60, true);
  assert.ok(before - flow.amount <= .65 / 60 + 1e-9, 'Discharge rate must be bounded');
  assert.ok(Math.abs(flow.amount + released - 1) < 1e-9, 'Every removed unit must be transferred to discharge');
}
assert.equal(flow.amount, 0);
assert.ok(Math.abs(released - 1) < 1e-9);
flow.reset(); flow.setTarget(1); flow.step(30, false);
assert.ok(flow.amount <= .04 + 1e-12, 'A suspended tab must not cause a sudden large material change');
console.log('PASS: upright retention, gradual release, transfer conservation, reset and suspended-tab timestep');
