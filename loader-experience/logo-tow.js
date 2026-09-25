// The BOB-LIFT wordmark towed out behind the machine in the hero exit.
// Outlines come straight from the Illustrator source (scripts/extract_logo.py → assets/logo/boblift.json) and are
// extruded as painted steel letters on a graphite tow plate. A main chain runs from under the rear standing platform
// to a bridle ring, and two legs from the ring to lugs on the plate's front edge. Kinematics live in tow-path.js.
import * as THREE from 'three';
import { mergeGeometries } from 'three/addons/utils/BufferGeometryUtils.js';
import { createTow, chainPath, alongPath } from './tow-path.js?v=2';

const LOGO_WIDTH = 2.3, MARGIN = .16, PLATE_T = .045, LETTER_T = .038, LETTER_BEVEL = .008;
const CHAIN = { main: .75, leg: .7, spread: .55, pitch: .05, link: .019, wire: .0055 };
const HITCH = [0, .3, .98];  // under the rear edge of the standing platform, machine at rest
const COLORS = { top: '#1f56b0', bottom: '#2c6cce', lower: '#1b3a7e', sideShade: .62 }; // AI gradient C1 → C0, lower band; web logo #1C50A3 / #1A3068

// Maps outline space (u right, v up, extrusion out of the page) onto the ground: reading direction runs toward −X and
// the tops of the letters toward +Z, which is "right" and "up" on screen for the hero's overhead camera looking back along +Z.
const toGround = (x, y, z) => new THREE.Matrix4().makeBasis(new THREE.Vector3(-1, 0, 0), new THREE.Vector3(0, 0, 1), new THREE.Vector3(0, 1, 0)).setPosition(x, y, z);

function contour(target, c, w) {
  target.moveTo(c.start[0] * w, c.start[1] * w);
  for (const [op, ...p] of c.segs) {
    if (op === 'L') target.lineTo(p[0][0] * w, p[0][1] * w);
    else target.bezierCurveTo(p[0][0] * w, p[0][1] * w, p[1][0] * w, p[1][1] * w, p[2][0] * w, p[2][1] * w);
  }
  return target;
}

export class LogoTow {
  constructor(viewer, data) {
    this.viewer = viewer;
    this.depth = data.aspect * LOGO_WIDTH;
    this.plateDepth = this.depth + 2 * MARGIN;
    this.group = new THREE.Group(); this.group.name = 'logo-tow';
    this.sweep = { uSweep: { value: -99 }, uSweepDir: { value: new THREE.Vector2(-.94, .34).normalize() }, uSweepWidth: { value: .22 }, uSweepColor: { value: new THREE.Color(0, 0, 0) } };
    this.buildLetters(data); this.buildPlate(); this.buildChain();
    viewer.scene.add(this.group, this.chain, this.ring);
    this.setActive(false);
    // Added after the view's own shader warm-up, so compile now: otherwise each material compiles on the frame the
    // wordmark first shows, mid-drive (hundreds of milliseconds on a phone).
    viewer.precompile();
  }

  buildLetters(data) {
    const w = LOGO_WIDTH, top = new THREE.Color(COLORS.top), bottom = new THREE.Color(COLORS.bottom), lower = new THREE.Color(COLORS.lower), c = new THREE.Color();
    const place = toGround(w / 2, PLATE_T + LETTER_BEVEL, MARGIN);
    const parts = data.shapes.map(s => {
      const shape = contour(new THREE.Shape(), s.outer, w);
      s.holes.forEach(h => shape.holes.push(contour(new THREE.Path(), h, w)));
      const g = new THREE.ExtrudeGeometry(shape, { depth: LETTER_T, curveSegments: 10, bevelEnabled: true, bevelThickness: LETTER_BEVEL, bevelSize: .005, bevelSegments: 2 });
      g.applyMatrix4(place);
      // Upper parts carry the source's vertical gradient per letter; walls are shaded darker so the letters read as solid.
      const pos = g.attributes.position, colors = new Float32Array(pos.count * 3);
      const [v0, v1] = [s.bounds[0][1] * w + MARGIN, s.bounds[1][1] * w + MARGIN];
      const walls = g.groups.find(gr => gr.materialIndex === 1);
      for (let i = 0; i < pos.count; i++) {
        if (s.part === 'upper') c.copy(bottom).lerp(top, THREE.MathUtils.clamp((pos.getZ(i) - v0) / (v1 - v0), 0, 1)); else c.copy(lower);
        if (walls && i >= walls.start && i < walls.start + walls.count) c.multiplyScalar(COLORS.sideShade);
        c.toArray(colors, i * 3);
      }
      g.setAttribute('color', new THREE.BufferAttribute(colors, 3));
      g.clearGroups();
      return g;
    });
    const geometry = mergeGeometries(parts); parts.forEach(p => p.dispose());
    const material = new THREE.MeshPhysicalMaterial({ vertexColors: true, roughness: .36, metalness: .12, clearcoat: .7, clearcoatRoughness: .2 });
    material.onBeforeCompile = shader => {
      // Light sweep: an emissive band that crosses the letters diagonally once the wordmark has come to rest.
      Object.assign(shader.uniforms, this.sweep);
      shader.vertexShader = shader.vertexShader.replace('#include <common>', '#include <common>\nvarying vec3 vSweepWorld;')
        .replace('#include <project_vertex>', '#include <project_vertex>\nvSweepWorld = (modelMatrix * vec4(transformed, 1.0)).xyz;');
      shader.fragmentShader = shader.fragmentShader.replace('#include <common>', '#include <common>\nvarying vec3 vSweepWorld;\nuniform float uSweep;\nuniform vec2 uSweepDir;\nuniform float uSweepWidth;\nuniform vec3 uSweepColor;')
        .replace('#include <emissivemap_fragment>', '#include <emissivemap_fragment>\n{ float k = dot(vSweepWorld.xz, uSweepDir) - uSweep; totalEmissiveRadiance += uSweepColor * exp(-k * k / (uSweepWidth * uSweepWidth)); }');
    };
    material.customProgramCacheKey = () => 'logo-sweep';
    this.letters = new THREE.Mesh(geometry, material);
    this.letters.castShadow = this.letters.receiveShadow = true;
    this.group.add(this.letters);
  }

  buildPlate() {
    const w = LOGO_WIDTH + 2 * MARGIN, d = this.plateDepth, r = .07, bevel = .01;
    const s = new THREE.Shape();
    s.moveTo(-MARGIN + r, -MARGIN); s.lineTo(-MARGIN + w - r, -MARGIN); s.quadraticCurveTo(-MARGIN + w, -MARGIN, -MARGIN + w, -MARGIN + r);
    s.lineTo(-MARGIN + w, -MARGIN + d - r); s.quadraticCurveTo(-MARGIN + w, -MARGIN + d, -MARGIN + w - r, -MARGIN + d);
    s.lineTo(-MARGIN + r, -MARGIN + d); s.quadraticCurveTo(-MARGIN, -MARGIN + d, -MARGIN, -MARGIN + d - r);
    s.lineTo(-MARGIN, -MARGIN + r); s.quadraticCurveTo(-MARGIN, -MARGIN, -MARGIN + r, -MARGIN);
    const g = new THREE.ExtrudeGeometry(s, { depth: PLATE_T - 2 * bevel, curveSegments: 6, bevelEnabled: true, bevelThickness: bevel, bevelSize: bevel, bevelSegments: 2 });
    g.applyMatrix4(toGround(LOGO_WIDTH / 2, bevel, MARGIN));
    const steel = new THREE.MeshPhysicalMaterial({ color: 0x2a2e34, roughness: .62, metalness: .3, clearcoat: .15, clearcoatRoughness: .5 });
    this.plate = new THREE.Mesh(g, steel); this.plate.castShadow = this.plate.receiveShadow = true;
    this.group.add(this.plate);
    // Tow lugs on the front edge (the edge facing the machine), each with a shackle the bridle hooks into.
    this.metal = new THREE.MeshPhysicalMaterial({ color: 0x80868d, roughness: .42, metalness: .85 });
    this.hardware = this.metal.clone(); // the plate's shackles fade in with the wordmark, the chain on its own
    const lug = new THREE.BoxGeometry(.08, .05, .09), shackle = new THREE.TorusGeometry(.024, .007, 6, 14);
    this.lugs = [-1, 1].map(side => {
      const x = side * CHAIN.spread;
      const block = new THREE.Mesh(lug, steel); block.position.set(x, PLATE_T + .02, .02);
      const ring = new THREE.Mesh(shackle, this.hardware); ring.position.set(x, PLATE_T + .02, -.035); ring.rotation.y = Math.PI / 2;
      block.castShadow = ring.castShadow = true;
      this.group.add(block, ring);
      return new THREE.Vector3(x, PLATE_T + .02, -.05);
    });
  }

  buildChain() {
    const link = new THREE.TorusGeometry(CHAIN.link, CHAIN.wire, 6, 12); link.scale(1.45, 1, 1); // long axis along X
    const capacity = Math.ceil((CHAIN.main + 2 * CHAIN.leg * 1.2) / CHAIN.pitch) + 8;
    this.chain = new THREE.InstancedMesh(link, this.metal, capacity);
    this.chain.castShadow = true; this.chain.frustumCulled = false; this.chain.count = 0;
    this.ring = new THREE.Mesh(new THREE.TorusGeometry(.034, .008, 8, 18), this.metal); this.ring.castShadow = true;
    this.ring.rotation.x = Math.PI / 2;
    this.fades = [this.letters.material, this.plate.material, this.hardware, this.metal].map(m => ({ m, color: m.color.clone(), env: m.envMapIntensity, coat: m.clearcoat, chain: m === this.metal }));
  }

  /**
   * @param {number} scale        wordmark scale (fits narrow screens)
   * @param {number} centreZ      world Z where the plate's centre should come to rest
   */
  /**
   * @param {number} scale    wordmark scale (fits narrow screens)
   * @param {number} centreZ  world Z where the plate's centre should pass the middle of the frame (the machine pauses there)
   * @param {number} exitZ    world Z of the frame's bottom edge on the ground
   * → machine advances at which the wordmark is centred, and at which it has been towed fully out of frame
   */
  layout({ scale, centreZ, exitZ }) {
    this.scale = scale;
    this.group.scale.setScalar(scale);
    this.bridle = Math.sqrt(CHAIN.leg ** 2 - CHAIN.spread ** 2) * scale; // ring ahead of the plate's front edge
    const gap = CHAIN.main * .62;                                          // starts slack, lying on the ground
    this.front0 = HITCH[2] + gap + this.bridle;
    this.tow = createTow({ length: CHAIN.main, gap });
    const centre0 = this.front0 + this.plateDepth / 2 * scale, far0 = this.front0 + this.plateDepth * scale;
    return { centred: this.tow.advanceFor(centre0 - centreZ), gone: this.tow.advanceFor(far0 - exitZ + .15) };
  }


  get plateWidth() { return LOGO_WIDTH + 2 * MARGIN; }
  /** World Z of the plate's centre as currently posed. */
  get centreZ() { return this.group.position.z + this.plateDepth / 2 * this.scale; }

  setActive(on) { this.group.visible = this.chain.visible = this.ring.visible = on; this.viewer.needsRender = true; }

  /** How visible the wordmark and the chain are: 0 = lost in the dark stage, 1 = fully lit. */
  setReveal(logo, chain) {
    this.fades.forEach(f => {
      const k = f.chain ? chain : logo;
      // Clearcoat never reaches 0: crossing zero swaps MeshPhysicalMaterial to another shader, which then compiles mid-scroll.
      f.m.color.copy(f.color).multiplyScalar(k); f.m.envMapIntensity = f.env * k; f.m.clearcoat = f.coat ? Math.max(f.coat * k, 1e-3) : 0;
    });
    const casts = logo > .15;
    this.group.traverse(n => { if (n.isMesh) n.castShadow = casts; });
    this.chain.castShadow = this.ring.castShadow = chain > .15;
  }

  /** Light sweep progress 0…1 across the resting wordmark. */
  setSweep(t) {
    const reach = LOGO_WIDTH / 2 * this.scale + .6, dir = this.sweep.uSweepDir.value;
    this.sweep.uSweep.value = dir.y * (this.group.position.z + this.plateDepth / 2 * this.scale) + (t * 2 - 1) * reach;
    this.sweep.uSweepColor.value.setRGB(.55, .75, 1).multiplyScalar(t > 0 && t < 1 ? Math.sin(Math.PI * t) * .9 : 0);
  }

  /** Pose everything for a machine advance (metres along −Z). Returns the tow state. */
  /** Pose the wordmark and chain for a machine advance (metres along −Z); the chain stays hooked on throughout. */
  update(advance) {
    const shift = this.tow.shift(advance), s = this.scale;
    const front = this.front0 - shift;
    this.group.position.set(0, 0, front);
    const hitch = [HITCH[0], HITCH[1], HITCH[2] - advance];
    const apex = [0, .025, front - this.bridle];
    const paths = [chainPath(hitch, apex, CHAIN.main)];
    this.lugs.forEach(l => paths.push(chainPath([l.x * s, l.y * s, front + l.z * s], apex, CHAIN.leg * s)));
    this.ring.position.set(...apex);
    this.layChain(paths);
    this.viewer.needsRender = true;
    return shift;
  }


  layChain(paths) {
    const m = new THREE.Matrix4(), x = new THREE.Vector3(), y = new THREE.Vector3(), z = new THREE.Vector3(), up = new THREE.Vector3(0, 1, 0);
    let n = 0;
    for (const points of paths) {
      n += alongPath(points, CHAIN.pitch, (p, t, i) => {
        x.set(...t);
        // Links alternate lying flat and standing on edge, as a real chain does on the ground.
        if (i % 2 === 0) z.copy(up).addScaledVector(x, -x.dot(up)); else z.set(-x.z, 0, x.x);
        if (z.lengthSq() < 1e-8) z.set(0, 0, 1);
        z.normalize(); y.crossVectors(z, x);
        const lift = i % 2 === 0 ? CHAIN.wire : CHAIN.link + CHAIN.wire;
        m.makeBasis(x, y, z).setPosition(p[0], Math.max(p[1], lift), p[2]);
        this.chain.setMatrixAt(n + i, m);
      }, this.chain.instanceMatrix.count - n);
    }
    this.chain.count = n;
    this.chain.instanceMatrix.needsUpdate = true;
  }

  dispose() {
    this.group.traverse(n => { if (n.isMesh) n.geometry.dispose(); });
    this.chain.geometry.dispose(); this.ring.geometry.dispose();
    [this.letters.material, this.plate.material, this.hardware, this.metal].forEach(m => m.dispose());
    this.group.removeFromParent(); this.chain.removeFromParent(); this.ring.removeFromParent();
  }
}
