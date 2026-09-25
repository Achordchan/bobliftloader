/** Regression checks against the actual quantised, meshopt-compressed delivery model. */
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { MeshoptDecoder } from '../assets/vendor/three/addons/libs/meshopt_decoder.module.js';
import { createTrackMotion } from '../track-motion.js';

const bytes = readFileSync(new URL('../assets/model/loader.glb', import.meta.url));
const jsonLength = bytes.readUInt32LE(12);
const model = JSON.parse(bytes.subarray(20, 20 + jsonLength).toString());
const binary = bytes.subarray(28 + jsonLength);
await MeshoptDecoder.ready;
const views = new Map();
function accessor(id) {
  const a = model.accessors[id], v = model.bufferViews[a.bufferView];
  if (!views.has(a.bufferView)) {
    const packed = v.extensions?.EXT_meshopt_compression;
    if (packed) {
      const target = new Uint8Array(packed.count * packed.byteStride);
      MeshoptDecoder.decodeGltfBuffer(target, packed.count, packed.byteStride,
        binary.subarray(packed.byteOffset, packed.byteOffset + packed.byteLength), packed.mode, packed.filter);
      views.set(a.bufferView, target);
    } else views.set(a.bufferView, binary.subarray(v.byteOffset, v.byteOffset + v.byteLength));
  }
  const data = views.get(a.bufferView), view = new DataView(data.buffer, data.byteOffset, data.byteLength);
  const types = { 5120: ['getInt8', 1, 127], 5121: ['getUint8', 1, 255], 5122: ['getInt16', 2, 32767], 5123: ['getUint16', 2, 65535], 5125: ['getUint32', 4, 4294967295], 5126: ['getFloat32', 4, 1] };
  const [getter, size, max] = types[a.componentType], count = { SCALAR: 1, VEC2: 2, VEC3: 3, VEC4: 4 }[a.type];
  const result = count === 1 ? new Uint32Array(a.count) : new Float32Array(a.count * count);
  for (let i = 0; i < a.count; i++) for (let k = 0; k < count; k++) {
    const value = view[getter]((a.byteOffset || 0) + i * (v.byteStride || size * count) + k * size, true);
    result[i * count + k] = a.normalized ? Math.max(-1, value / max) : value;
  }
  return result;
}

const results = [];
for (const name of ['trackL', 'trackR']) {
  const node = model.nodes.find(node => node.name === name);
  const primitive = model.meshes[node.mesh].primitives.find(p => model.materials[p.material].name === '细纹橡胶');
  const cachedPosition = accessor(primitive.attributes.POSITION), cachedNormal = accessor(primitive.attributes.NORMAL);
  const position = cachedPosition.slice(), normal = cachedNormal.slice(), index = accessor(primitive.indices);
  const motion = createTrackMotion(position, normal, index, node.scale[0]);
  assert.equal(motion.parts.length, 138, '92 tread blocks plus 46 guides');
  const edges = [];
  for (let i = 0; i < index.length; i += 3) for (let k = 0; k < 3; k++) {
    const a = index[i + k] * 3, b = index[i + (k + 1) % 3] * 3;
    edges.push([a, b, Math.hypot(cachedPosition[a] - cachedPosition[b], cachedPosition[a + 1] - cachedPosition[b + 1], cachedPosition[a + 2] - cachedPosition[b + 2])]);
  }
  let maximumEdgeError = 0;
  const distances = [0, .0001, .001, .02, .1, .2, .7, 1.2, 2.4, -1.2, -.2, 10];
  for (const travel of distances) {
    motion.update(travel);
    for (const i of motion.bodyIndices) for (let k = 0; k < 3; k++) assert.equal(position[i * 3 + k], cachedPosition[i * 3 + k], 'Continuous belt must stay on the wheels');
    for (const [a, b, length] of edges) {
      const updated = Math.hypot(position[a] - position[b], position[a + 1] - position[b + 1], position[a + 2] - position[b + 2]);
      maximumEdgeError = Math.max(maximumEdgeError, Math.abs(updated - length));
      assert.ok(Math.abs(updated - length) < 2e-6, `Triangle stretched at travel=${travel}`);
    }
    for (let i = 0; i < position.length; i += 3) {
      assert.equal(position[i], cachedPosition[i], 'No lateral tread displacement');
      assert.ok(Number.isFinite(position[i + 1]) && Number.isFinite(position[i + 2]));
      assert.ok(Math.abs(Math.hypot(...normal.subarray(i, i + 3)) - Math.hypot(...cachedNormal.subarray(i, i + 3))) < 2e-6);
    }
  }
  motion.update(0);
  assert.deepEqual(position, cachedPosition, 'Reverse/reset must return exactly to original mesh');
  motion.update(motion.path.L * node.scale[0]);
  assert.deepEqual(position, cachedPosition, 'A full belt loop must close exactly');
  results.push({ name, movingParts: motion.parts.length, travelCases: distances.length, maximumEdgeError, fullLoopAndReset: true });
}
console.log(JSON.stringify({ passed: true, results }, null, 2));
