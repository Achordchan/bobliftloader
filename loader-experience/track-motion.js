/** Rigid tread/guide motion around an unchanged continuous rubber belt. */
const TAU = Math.PI * 2;
const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
const angleMix = (a, b, t) => a + Math.atan2(Math.sin(b - a), Math.cos(b - a)) * t;
const smooth = t => t * t * (3 - 2 * t);

function components(position, index) {
  const count = position.length / 3;
  const parent = Int32Array.from({ length: count }, (_, i) => i);
  const find = i => {
    while (parent[i] !== i) { parent[i] = parent[parent[i]]; i = parent[i]; }
    return i;
  };
  const join = (a, b) => { a = find(a); b = find(b); if (a !== b) parent[b] = a; };
  // UV/normal seams duplicate indices in the exported GLB. Weld positions only for
  // connectivity; retain the original vertex buffers, UVs, normals and triangle indices.
  const weld = new Map();
  for (let i = 0; i < count; i++) {
    const key = `${Math.round(position[3 * i] * 1e6)},${Math.round(position[3 * i + 1] * 1e6)},${Math.round(position[3 * i + 2] * 1e6)}`;
    if (weld.has(key)) join(i, weld.get(key)); else weld.set(key, i);
  }
  for (let i = 0; i < index.length; i += 3) { join(index[i], index[i + 1]); join(index[i], index[i + 2]); }
  const groups = new Map();
  for (let i = 0; i < count; i++) {
    const root = find(i);
    if (!groups.has(root)) groups.set(root, { indices: [], minZ: Infinity, maxZ: -Infinity, minY: Infinity, maxY: -Infinity });
    const part = groups.get(root), y = position[3 * i + 1], z = position[3 * i + 2];
    part.indices.push(i);
    part.minZ = Math.min(part.minZ, z); part.maxZ = Math.max(part.maxZ, z);
    part.minY = Math.min(part.minY, y); part.maxY = Math.max(part.maxY, y);
  }
  return [...groups.values()];
}

function outline(position, body) {
  const unique = new Map();
  for (const i of body.indices) {
    const z = position[3 * i + 2], y = position[3 * i + 1];
    unique.set(`${z},${y}`, [z, y]);
  }
  const points = [...unique.values()].sort((a, b) => a[0] - b[0] || a[1] - b[1]);
  const cross = (o, a, b) => (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]);
  const lower = [], upper = [];
  for (const point of points) {
    while (lower.length > 1 && cross(lower.at(-2), lower.at(-1), point) <= 0) lower.pop();
    lower.push(point);
  }
  for (let i = points.length - 1; i >= 0; i--) {
    const point = points[i];
    while (upper.length > 1 && cross(upper.at(-2), upper.at(-1), point) <= 0) upper.pop();
    upper.push(point);
  }
  const hull = lower.slice(0, -1).concat(upper.slice(0, -1));
  const edges = []; let L = 0;
  for (let i = 0; i < hull.length; i++) {
    const a = hull[i], b = hull[(i + 1) % hull.length];
    const dz = b[0] - a[0], dy = b[1] - a[1], len = Math.hypot(dz, dy);
    if (len < 1e-10) continue;
    edges.push({ a, len, s: L, d: [dz / len, dy / len], ang: Math.atan2(dy, dz) }); L += len;
  }
  if (edges.length < 3 || !Number.isFinite(L)) throw new Error('Invalid continuous-belt outline');
  return { edges, L };
}

function sample(path, distance) {
  const { edges, L } = path;
  const s = ((distance % L) + L) % L;
  let lo = 0, hi = edges.length - 1;
  while (lo < hi) {
    const mid = (lo + hi + 1) >> 1;
    if (edges[mid].s <= s) lo = mid; else hi = mid - 1;
  }
  const edge = edges[lo], t = clamp(s - edge.s, 0, edge.len);
  // Blend the tangent only near polygon joins so each rigid block turns continuously
  // around the idlers without steering along the long straight ground-contact run.
  const blend = Math.min(.025, edge.len / 2);
  let angle = edge.ang;
  if (t < blend) angle = angleMix(angleMix(edges[(lo + edges.length - 1) % edges.length].ang, edge.ang, .5), edge.ang, smooth(t / blend));
  else if (t > edge.len - blend) angle = angleMix(edge.ang, angleMix(edge.ang, edges[(lo + 1) % edges.length].ang, .5), smooth((t - edge.len + blend) / blend));
  return { z: edge.a[0] + edge.d[0] * t, y: edge.a[1] + edge.d[1] * t, angle };
}

/** Inputs are owned float buffers in one mesh's local coordinate system. */
export function createTrackMotion(position, normal, index, metresPerUnit) {
  if (!(metresPerUnit > 0) || !Number.isFinite(metresPerUnit)) throw new Error('Invalid track scale');
  const rest = { pos: position.slice(), nor: normal.slice() };
  const groups = components(rest.pos, index);
  const body = groups.reduce((a, b) => b.maxZ - b.minZ > a.maxZ - a.minZ ? b : a);
  const path = outline(rest.pos, body);
  const parts = groups.filter(part => part !== body).map(part => {
    const z = (part.minZ + part.maxZ) / 2, y = (part.minY + part.maxY) / 2;
    let best = Infinity, distance = 0;
    for (const edge of path.edges) {
      const t = clamp((z - edge.a[0]) * edge.d[0] + (y - edge.a[1]) * edge.d[1], 0, edge.len);
      const error = (z - edge.a[0] - edge.d[0] * t) ** 2 + (y - edge.a[1] - edge.d[1] * t) ** 2;
      if (error < best) { best = error; distance = edge.s + t; }
    }
    return { indices: part.indices, distance, anchor: sample(path, distance) };
  });
  if (!parts.length) throw new Error('No independent tread blocks in the rubber primitive');
  return {
    rest, path, bodyIndices: body.indices, parts,
    update(metres) {
      if (!Number.isFinite(metres)) throw new Error('Track travel must be finite');
      const shift = metres / metresPerUnit;
      const phase = ((shift % path.L) + path.L) % path.L;
      if (phase < 1e-9 || path.L - phase < 1e-9) {
        position.set(rest.pos); normal.set(rest.nor); return;
      }
      for (const part of parts) {
        const point = sample(path, part.distance + shift);
        const angle = point.angle - part.anchor.angle;
        const cos = Math.cos(angle), sin = Math.sin(angle);
        for (const i of part.indices) {
          const j = i * 3, z = rest.pos[j + 2] - part.anchor.z, y = rest.pos[j + 1] - part.anchor.y;
          position[j + 2] = point.z + z * cos - y * sin;
          position[j + 1] = point.y + z * sin + y * cos;
          normal[j + 2] = rest.nor[j + 2] * cos - rest.nor[j + 1] * sin;
          normal[j + 1] = rest.nor[j + 2] * sin + rest.nor[j + 1] * cos;
        }
      }
    },
  };
}
