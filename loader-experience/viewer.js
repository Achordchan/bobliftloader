// Real-time 3D viewer for the BOB-LIFT 360 / 460 CAD model.
// One GLB is fetched once and shared by every viewer on the page; each viewer clones the scene
// graph (geometry shared, materials rebuilt) so it can explode, focus, ghost and articulate
// assembly groups independently. Lighting comes from a real sky HDRI, occlusion from AO maps
// baked per assembly group in Blender.
import * as THREE from 'three';
import { createTrackMotion } from './track-motion.js?v=1';
import { BucketPayload } from './payload.js?v=2';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { GLTFLoader } from 'three/addons/loaders/GLTFLoader.js';
import { HDRLoader } from 'three/addons/loaders/HDRLoader.js';
import { MeshoptDecoder } from 'three/addons/libs/meshopt_decoder.module.js';

export const GROUPS = {
  bucket:      { label: '铲斗',          explode: [0, .05, -.95] },
  quickattach: { label: '快换架',        explode: [0, .12, -.55] },
  arm:         { label: '举升臂与管路',  explode: [0, .62, -.12] },
  liftBarrel:  { label: '举升油缸',      explode: [0, .3, -.28] },
  liftRod:     { label: '举升油缸',      explode: [0, .42, -.36] },
  tiltBarrel:  { label: '翻斗油缸',      explode: [0, .5, -.5] },
  tiltRod:     { label: '翻斗油缸',      explode: [0, .42, -.62] },
  controls:    { label: '操纵台',        explode: [0, .78, .12] },
  engine:      { label: '发动机与散热',  explode: [0, .42, .62] },
  fueltank:    { label: '汽油箱',        explode: [.42, .18, .5] },
  hydtank:     { label: '液压油箱',      explode: [-.5, .3, -.15] },
  battery:     { label: '电瓶',          explode: [-.48, .2, .2] },
  frame:       { label: '车架与机罩',    explode: [0, 0, 0] },
  trackframe:  { label: '履带架',        explode: [0, -.32, 0] },
  trackL:      { label: '橡胶履带与轮组', explode: [-.62, -.5, 0] },
  trackR:      { label: '橡胶履带与轮组', explode: [.62, -.5, 0] },
};
// Groups that ride on the lift arm, and on the bucket hinge at its tip.
const ON_ARM = ['arm', 'tiltBarrel', 'liftRod', 'quickattach', 'bucket', 'tiltRod'];
const ON_BUCKET = ['quickattach', 'bucket', 'tiltRod'];

const t = (source, vars) => window.I18N ? window.I18N.t(source, vars) : source; // i18n/i18n.js
const PAINT = '朱红 · 喷涂钢板'; // the body colour; setPaint() recolours it
// Blender's Cycles materials use very dark linear base colours that only read correctly under the
// studio lights of the offline render; for the web each material is rebuilt from photo references.
const MATERIALS = {
  [PAINT]:  { color: 0xb8220f, roughness: .48, metalness: .05, clearcoat: .55, clearcoatRoughness: .25 }, // sampled from photos: #a91f0e–#c93d24
  '石墨灰 · 喷涂钢板': { color: 0x5f6873, roughness: .55, metalness: .1, clearcoat: .3, clearcoatRoughness: .35 },
  '哑黑 · 内部结构':  { color: 0x2f3236, roughness: .7, metalness: .1 },
  '细纹橡胶':        { color: 0x212426, roughness: .9, metalness: 0, sheen: .25, sheenRoughness: .8, sheenColor: 0x3a3d40 },
  '镀锌钢':          { color: 0xa4a9b0, roughness: .42, metalness: .8 },
  '液压杆镀铬':      { color: 0xe2e6ea, roughness: .14, metalness: 1 },
  '仪表与灯具玻璃':  { color: 0x1a2a38, roughness: .12, metalness: .2, clearcoat: 1, clearcoatRoughness: .05 },
  '白色标识与反光':  { color: 0xececec, roughness: .4, metalness: 0 },
  '绿色指示':        { color: 0x2fbf5a, roughness: .3, metalness: .1 },
  '琥珀色指示':      { color: 0xf0a020, roughness: .3, metalness: .1 },
};

let roughnessNoise = null;
function noiseTexture() {
  // Subtle roughness variation breaks up the flawless CAD look (dust, orange peel, light wear).
  if (roughnessNoise) return roughnessNoise;
  const size = 256, canvas = document.createElement('canvas'); canvas.width = canvas.height = size;
  const ctx = canvas.getContext('2d'), img = ctx.createImageData(size, size);
  let seed = 7;
  const rnd = () => (seed = (seed * 16807) % 2147483647) / 2147483647;
  for (let i = 0; i < img.data.length; i += 4) { const v = 200 + rnd() * 55; img.data[i] = img.data[i + 1] = img.data[i + 2] = v; img.data[i + 3] = 255; }
  ctx.putImageData(img, 0, 0);
  ctx.filter = 'blur(1.2px)'; ctx.drawImage(canvas, 0, 0);
  roughnessNoise = new THREE.CanvasTexture(canvas);
  roughnessNoise.wrapS = roughnessNoise.wrapT = THREE.RepeatWrapping; roughnessNoise.repeat.set(4, 4);
  return roughnessNoise;
}

function buildMaterial(source) {
  const spec = MATERIALS[source.name];
  const m = new THREE.MeshPhysicalMaterial({ name: source.name, color: spec?.color ?? source.color, roughness: spec?.roughness ?? source.roughness, metalness: spec?.metalness ?? source.metalness });
  if (spec?.clearcoat) { m.clearcoat = spec.clearcoat; m.clearcoatRoughness = spec.clearcoatRoughness; }
  if (spec?.sheen) { m.sheen = spec.sheen; m.sheenRoughness = spec.sheenRoughness; m.sheenColor = new THREE.Color(spec.sheenColor); }
  if (source.name !== '液压杆镀铬') m.roughnessMap = noiseTexture();
  m.envMapIntensity = 1;
  m.side = THREE.DoubleSide; // a few STEP shells stay inside-out even after recalculation
  return m;
}

// The model and the sky ship gzipped next to the originals (url + '.gz'): a static host may not compress binary types
// on the fly, and gzip takes the model from 4.5 MB to 1.8 MB. The .gz is inflated here; the plain file is the fallback
// where there is no .gz or no DecompressionStream. index.html starts both downloads before any script (window.__packed). A host that adds Content-Encoding itself hands over inflated bytes,
// which the magic-number check lets through as they are.
async function fetchPacked(url, onProgress) {
  const pre = window.__packed?.[url + '.gz']; // already on its way (index.html)
  if (pre) {
    if (onProgress) { const emit = () => onProgress({ loaded: pre.loaded, total: pre.total }); pre.listeners.push(emit); emit(); }
    const bytes = await pre.promise.then(b => new Uint8Array(b)).catch(() => null);
    if (bytes) return bytes[0] === 0x1f && bytes[1] === 0x8b ? new Response(new Blob([bytes]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer() : bytes.buffer;
  }
  let res = typeof DecompressionStream === 'function' ? await fetch(url + '.gz').catch(() => null) : null;
  const packed = !!res?.ok;
  if (!packed) res = await fetch(url);
  if (!res.ok) throw new Error(`${res.status} ${url}`);
  const total = +res.headers.get('content-length') || 0;
  let loaded = 0;
  const body = !onProgress || !res.body ? res : new Response(res.body.pipeThrough(new TransformStream({ transform(chunk, out) {
    loaded += chunk.byteLength; onProgress({ loaded: Math.min(loaded, total), total }); out.enqueue(chunk);
  } })));
  const bytes = new Uint8Array(await body.arrayBuffer());
  if (!packed || bytes[0] !== 0x1f || bytes[1] !== 0x8b) return bytes.buffer;
  return new Response(new Blob([bytes]).stream().pipeThrough(new DecompressionStream('gzip'))).arrayBuffer();
}

let modelPromise = null;
// Download progress for the opening covers the model, the sky and the AO maps together, in bytes: estimates until a
// response gives its length; an AO map counts when it arrives.
const ESTIMATE = { model: 1.8e6, sky: .58e6, ao: 1.05e6 };
export function loadModel(url = 'assets/model/loader.glb', onProgress) {
  if (!modelPromise) {
    const bytes = Object.fromEntries(Object.entries(ESTIMATE).map(([k, v]) => [k, [0, v]]));
    const report = () => { if (!onProgress) return; let loaded = 0, total = 0; for (const [l, t] of Object.values(bytes)) { loaded += Math.min(l, t); total += t; } onProgress({ loaded, total }); };
    const track = key => e => { bytes[key] = [e.loaded, e.total || bytes[key][1]]; report(); };
    skyHDR(track('sky')); // alongside the model: it used to wait until the first view woke
    const loader = new GLTFLoader();
    loader.setMeshoptDecoder(MeshoptDecoder);
    const gltf = fetchPacked(url, track('model')).then(buffer => new Promise((resolve, reject) => loader.parse(buffer, url.replace(/[^/]*$/, ''), resolve, reject)));
    const meta = fetch(url.replace(/\.glb$/, '.json')).then(r => r.ok ? r.json() : {}).catch(() => ({}));
    // AO maps are decoded once, off the main thread, into ImageBitmaps shared by every view. As <img> elements they were
    // decoded synchronously on each context's first upload: ~1.2 s of main-thread work (4× CPU throttle) whenever a
    // chapter's view was created. Browsers without createImageBitmap fall back to <img>.
    const ao = meta.then(data => {
      const entries = Object.entries(data.groups || {}).filter(([, g]) => g.ao);
      const bitmaps = typeof createImageBitmap === 'function';
      const loader = bitmaps ? new THREE.ImageBitmapLoader().setOptions({ imageOrientation: 'none', premultiplyAlpha: 'none', colorSpaceConversion: 'none' }) : new THREE.TextureLoader();
      return Promise.all(entries.map(([key]) => new Promise(resolve => loader.load(`assets/model/ao/${key}.webp?v=2`, image => { // v2: half-size maps
        const tex = image.isTexture ? image : new THREE.Texture(image);
        tex.flipY = false; tex.colorSpace = THREE.NoColorSpace; tex.channel = 0; tex.needsUpdate = true;
        resolve([key, tex]);
      }, undefined, () => resolve([key, null]))).then(pair => { bytes.ao[0] += bytes.ao[1] / entries.length; report(); return pair; })))
        .then(pairs => Object.fromEntries(pairs.filter(p => p[1])));
    });
    modelPromise = Promise.all([gltf, meta, ao]).then(([g, m, a]) => ({ gltf: g, pins: m.pins || null, ao: a }));
  }
  return modelPromise;
}

// The sky HDRI is fetched once; each WebGL context prefilters its own environment map from it, because a
// PMREM target lives in the context that rendered it (other contexts would sample an empty texture).
let skyPromise = null;
const skyHDR = onProgress => skyPromise ??= fetchPacked('assets/env/sky.hdr', onProgress).then(async buffer => {
  const url = URL.createObjectURL(new Blob([buffer]));
  try { return await new HDRLoader().loadAsync(url); } finally { URL.revokeObjectURL(url); }
});

const easeOut = t => 1 - Math.pow(1 - t, 3);
const yz = v => Math.atan2(v.z, v.y); // angle of a vector in the hinge plane (all hinges run along X)

export class LoaderViewer {
  constructor(container, options = {}) {
    this.container = container;
    this.options = Object.assign({ autoRotate: true, controls: true, pixelRatio: 1.5, shadow: true, zoom: false, fov: 26, labels: false, ghostOpacity: .08, envIntensity: 1.1, exposure: 1.08 }, options);
    this.groups = new Map();
    this.explodeValue = 0; this.pose = { lift: 0, tilt: 0 };
    this.visible = false; this.disposed = false;
    this.cameraTween = null; this.labelElements = new Map();
    this.onSelect = options.onSelect || null;
    // Wall clock by default; the scene video capture swaps in a virtual clock and calls step() once per frame.
    this.clock = () => performance.now(); this.manual = false;
    this.exposure = this.options.exposure; this.renderer = null;
    this.loaded = new Promise(resolve => { this.markLoaded = resolve; });
    this.build();
  }

  build() {
    const { container, options } = this;
    this.scene = new THREE.Scene();

    this.camera = new THREE.PerspectiveCamera(options.fov, 1, .05, 60);
    this.camera.position.set(3.2, 1.6, -3.6);
    this.target = new THREE.Vector3(0, .62, 0);

    // Sun: warm key from the same quadrant as the HDRI's brightest patch, so shadows agree with reflections.
    const sun = new THREE.DirectionalLight(0xfff1dc, 2.8);
    sun.position.set(...(options.sunPosition || [3.5, 6, -2.5]));
    sun.castShadow = options.shadow;
    sun.shadow.mapSize.set(1536, 1536);
    sun.shadow.camera.near = 1; sun.shadow.camera.far = 16;
    sun.shadow.camera.left = sun.shadow.camera.bottom = -2.4; sun.shadow.camera.right = sun.shadow.camera.top = 2.4;
    sun.shadow.bias = -.0004; sun.shadow.normalBias = .025; sun.shadow.radius = 6;
    this.scene.add(sun, sun.target); this.sun = sun; this.sunBase = sun.position.clone();
    this.hemi = new THREE.HemisphereLight(0xcfd8e6, 0x6c6a66, .6); this.scene.add(this.hemi);

    if (options.shadow) {
      const ground = new THREE.Mesh(new THREE.PlaneGeometry(14, 14), new THREE.ShadowMaterial({ opacity: options.shadowOpacity ?? .32 }));
      ground.rotation.x = -Math.PI / 2; ground.position.y = -.005; ground.receiveShadow = true;
      this.scene.add(ground); this.ground = ground;
    }
    this.root = new THREE.Group(); this.scene.add(this.root);
    if (options.contactShadow) {
      // Soft occlusion blob under the undercarriage: overcast plates have almost no cast shadow, but they do have contact darkening.
      const c = document.createElement('canvas'); c.width = c.height = 256;
      const g = c.getContext('2d'), grad = g.createRadialGradient(128, 128, 0, 128, 128, 128);
      grad.addColorStop(0, 'rgba(0,0,0,1)'); grad.addColorStop(.45, 'rgba(0,0,0,.55)'); grad.addColorStop(1, 'rgba(0,0,0,0)');
      g.fillStyle = grad; g.fillRect(0, 0, 256, 256);
      const tex = new THREE.CanvasTexture(c);
      const blob = new THREE.Mesh(new THREE.PlaneGeometry(1.9, 1.35), new THREE.MeshBasicMaterial({ map: tex, transparent: true, opacity: options.contactShadow, depthWrite: false, color: 0x000000 }));
      blob.rotation.x = -Math.PI / 2; blob.position.set(0, .004, .05); blob.renderOrder = -1;
      this.root.add(blob); this.contact = blob;
    }

    if (options.controls) {
      this.controls = new OrbitControls(this.camera); // attached to each canvas in wake()
      this.controls.enableDamping = true; this.controls.dampingFactor = options.dampingFactor ?? .07;
      // The wheel stays with the page. On touch screens a view can take `pinch`: two fingers zoom (within min/maxDistance,
      // which the scripted camera moves respect too), one finger still scrolls vertically and rotates horizontally.
      this.controls.enablePan = false; this.controls.enableZoom = options.zoom || (!!options.pinch && matchMedia('(pointer: coarse)').matches);
      this.controls.touches = { ONE: THREE.TOUCH.ROTATE, TWO: THREE.TOUCH.DOLLY_PAN };
      this.controls.minDistance = 1.8; this.controls.maxDistance = 12;
      this.controls.maxPolarAngle = Math.PI / 2 - .04; this.controls.minPolarAngle = .35;
      this.controls.autoRotate = options.autoRotate; this.controls.autoRotateSpeed = options.autoRotateSpeed ?? .55;
      this.controls.target.copy(this.target);
      this.controls.addEventListener('change', () => { this.cameraMoved = true; }); // camera only: redraw, keep the shadow map
      this.controls.addEventListener('start', () => { this.userInteracted = true; this.cameraTween = null; if (this.options.autoRotate) this.controls.autoRotate = false; this.container.classList.add('is-touched'); });
    }

    this.resizeObserver = new ResizeObserver(() => this.resize()); this.resizeObserver.observe(container);
    if (options.frame) this.resizeObserver.observe(options.frame);
    this.intersection = new IntersectionObserver(entries => { this.visible = entries.some(e => e.isIntersecting); if (this.visible) this.tick(); }, { rootMargin: '120px' });
    this.intersection.observe(container);
    this.pointer = new THREE.Vector2(); this.raycaster = new THREE.Raycaster();
    this.resize();
    if (!options.deferRenderer) this.wake();
  }

  /**
   * Create a WebGL context for this view (again). The scene graph and all its state live on the CPU side, so a view
   * can drop its context while far from the viewport (sleep) and come back unchanged: geometry and textures are
   * re-uploaded, the environment is prefiltered again, and shaders are compiled (in the background where the browser
   * supports it) before the first frame is drawn. Resolves when the view can draw.
   */
  wake() {
    if (this.disposed) return Promise.resolve();
    if (this.renderer) return this.ready;
    const { container, options } = this;
    const renderer = this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(devicePixelRatio, this.pixelRatioCap ?? options.pixelRatio)); // cap lowered by adaptQuality() on slow GPUs
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.NeutralToneMapping; // hue-preserving, keeps the red paint red
    renderer.toneMappingExposure = this.exposure;
    renderer.shadowMap.enabled = options.shadow;
    renderer.shadowMap.autoUpdate = false; // re-rendered only when the scene changes, see step()
    renderer.shadowMap.type = THREE.PCFShadowMap; // r186 dropped PCFSoft and fell back to this anyway
    const canvas = this.canvas = renderer.domElement;
    canvas.className = 'exp-3d-canvas'; canvas.setAttribute('aria-hidden', 'true');
    container.prepend(canvas); // under the label layer
    if (this.controls) {
      this.controls.connect(canvas);
      canvas.style.touchAction = 'pan-y'; // vertical touch gestures scroll the page; horizontal drags rotate
    }
    if (this.onSelect) {
      let down = null;
      canvas.addEventListener('pointerdown', e => { down = [e.clientX, e.clientY]; });
      canvas.addEventListener('pointerup', e => { const start = down; down = null; if (!start || Math.hypot(e.clientX - start[0], e.clientY - start[1]) > 6) return; const hit = this.pick(e.clientX, e.clientY); if (hit) this.onSelect(hit); });
      canvas.addEventListener('pointermove', e => { if (!down) { const hit = this.pick(e.clientX, e.clientY); canvas.style.cursor = hit ? 'pointer' : ''; this.setHover(hit); } });
      canvas.addEventListener('pointerleave', () => this.setHover(null));
    }
    this.resize();
    this.compiled = false;
    const sky = skyHDR().then(hdr => {
      if (this.renderer !== renderer || this.ownEnv) return;
      const pmrem = new THREE.PMREMGenerator(renderer);
      this.skyTarget = pmrem.fromEquirectangular(hdr); pmrem.dispose(); hdr.dispose(); // the HDR's own upload is only needed here
      this.scene.environment = this.skyTarget.texture;
      if (!this.envSet) { this.scene.environmentIntensity = options.envIntensity; this.envSet = true; } // later grades keep theirs
      this.scene.environmentRotation.y = Math.PI * .35;
    }).catch(() => {});
    // Upload one texture per frame before the first draw, so no single frame carries all of them (the view wakes a
    // screen ahead of the viewport, so this finishes before it is on screen).
    const uploadTextures = async () => {
      const textures = new Set();
      this.scene.traverse(n => { if (n.material) [].concat(n.material).forEach(m => { for (const k of ['map', 'aoMap', 'roughnessMap']) if (m[k]) textures.add(m[k]); }); });
      for (const texture of textures) {
        if (this.renderer !== renderer) return;
        renderer.initTexture(texture);
        await new Promise(resolve => requestAnimationFrame(resolve));
      }
    };
    this.ready = Promise.all([sky, this.loaded])
      .then(() => this.renderer === renderer ? renderer.compileAsync(this.scene, this.camera) : null).catch(() => {})
      .then(uploadTextures)
      .then(() => { if (this.renderer !== renderer) return; this.compiled = true; this.needsRender = true; this.tick(); requestAnimationFrame(() => this.options.onDraw?.(true)); });
    return this.ready;
  }

  /** Compile shaders for objects added after wake() started (wake's own pass only saw the scene as it was then). */
  precompile() {
    const renderer = this.renderer;
    if (!renderer) return Promise.resolve(); // asleep: the next wake() compiles the whole scene, these objects included
    // After ready, so the environment map is in place and the variants match what the first real frame will use.
    return this.ready.then(() => this.renderer === renderer ? renderer.compileAsync(this.scene, this.camera) : null).catch(() => {});
  }

  /** Release this view's WebGL context and every GPU resource in it; wake() restores it. */
  sleep() {
    const renderer = this.renderer;
    if (!renderer || this.manual) return;
    this.renderer = null; this.compiled = false; this.ready = null;
    this.controls?.disconnect();
    if (!this.ownEnv) this.scene.environment = null;
    this.skyTarget?.dispose(); this.skyTarget = null;
    // Materials are this view's own: disposing them frees their programs and drops the old context's listeners.
    // (Geometry and AO textures are shared with the other views, so they are left alone; the lost context frees their copies.)
    this.scene.traverse(n => { if (n.material) [].concat(n.material).forEach(m => m.dispose()); });
    renderer.dispose(); renderer.forceContextLoss();
    this.canvas.remove();
    this.options.onDraw?.(false);
  }

  async load(onProgress) {
    const { gltf, pins, ao } = await loadModel(undefined, onProgress);
    if (this.disposed) return;
    const scene = gltf.scene.clone(true);
    // A glTF mesh with several primitives arrives as a Group of child Meshes, so the assembly
    // node is whichever object carries the group name; its meshes are itself or its children.
    scene.traverse(node => {
      const spec = GROUPS[node.name];
      if (!spec) return;
      const meshes = []; node.traverse(child => { if (child.isMesh) meshes.push(child); });
      const materials = [];
      meshes.forEach(mesh => {
        mesh.castShadow = true; mesh.receiveShadow = true;
        mesh.material = Array.isArray(mesh.material) ? mesh.material.map(buildMaterial) : buildMaterial(mesh.material);
        [].concat(mesh.material).forEach(m => { if (ao[node.name]) { m.aoMap = ao[node.name]; m.aoMapIntensity = 1; } });
        materials.push(...[].concat(mesh.material));
        mesh.userData.group = node.name;
      });
      const box = new THREE.Box3().setFromObject(node);
      this.groups.set(node.name, { key: node.name, mesh: node, meshes, base: node.position.clone(), explode: new THREE.Vector3(...spec.explode), center: box.getCenter(new THREE.Vector3()), materials, label: spec.label });
    });
    this.root.add(scene);
    if (pins) this.buildRig(scene, pins);
    if (pins && this.options.payload) this.buildPayload();
    if (this.options.trackMotion) this.buildTrackLoops();
    if (this.options.labels) this.buildLabels();
    this.applyExplode();
    this.container.classList.add('is-ready');
    this.markLoaded();
    this.tick();
    return this;
  }

  // Hinge hierarchy: frame → arm hinge → bucket hinge, with a pivot group for each cylinder half.
  buildRig(scene, pins) {
    const P = k => new THREE.Vector3(...pins[k]);
    const armPivot = P('armPivot'), bucketPivot = P('bucketPivot');
    const reparent = (node, parent, pivot) => { parent.add(node); node.position.copy(node.userData.base ?? node.position).sub(pivot); };
    const armGroup = new THREE.Group(); armGroup.name = 'rig:arm'; armGroup.position.copy(armPivot); scene.add(armGroup);
    const bucketGroup = new THREE.Group(); bucketGroup.name = 'rig:bucket'; bucketGroup.position.copy(bucketPivot).sub(armPivot); armGroup.add(bucketGroup);
    const pivotGroup = (name, parent, worldPos, parentWorld) => { const g = new THREE.Group(); g.name = name; g.position.copy(worldPos).sub(parentWorld); parent.add(g); return g; };
    const liftBarrelPivot = pivotGroup('rig:liftBarrel', scene, P('liftBase'), new THREE.Vector3());
    const liftRodPivot = pivotGroup('rig:liftRod', armGroup, P('liftRodEnd'), armPivot);
    const tiltBarrelPivot = pivotGroup('rig:tiltBarrel', armGroup, P('tiltBase'), armPivot);
    const tiltRodPivot = pivotGroup('rig:tiltRod', bucketGroup, P('tiltRodEnd'), bucketPivot);
    const parentFor = { liftBarrel: [liftBarrelPivot, P('liftBase')], liftRod: [liftRodPivot, P('liftRodEnd')], tiltBarrel: [tiltBarrelPivot, P('tiltBase')], tiltRod: [tiltRodPivot, P('tiltRodEnd')] };
    for (const g of this.groups.values()) {
      const [parent, pivot] = parentFor[g.key] || (ON_BUCKET.includes(g.key) ? [bucketGroup, bucketPivot] : ON_ARM.includes(g.key) ? [armGroup, armPivot] : [null]);
      if (!parent) continue;
      parent.add(g.mesh); g.mesh.position.copy(g.base).sub(pivot); g.base.copy(g.mesh.position);
    }
    // The chrome rod is a plain cylinder, so it can stretch along its axis (anchored at the rod eye)
    // to follow the changing pin distance; the eye and pin primitives keep their size.
    const stretchable = (key, pivotGroup, base, end) => {
      const group = this.groups.get(key); if (!group) return null;
      const axis = base.clone().sub(end).normalize(); // rod eye → barrel, in pivot-local (rest) space
      const q = new THREE.Quaternion().setFromUnitVectors(new THREE.Vector3(0, 0, 1), axis);
      const wrapper = new THREE.Group(); wrapper.name = 'rig:' + key + ':stretch'; wrapper.quaternion.copy(q);
      const chrome = group.meshes.filter(m => [].concat(m.material).some(mat => mat.name === '液压杆镀铬'));
      if (!chrome.length) return null;
      if (chrome.includes(group.mesh)) { // the whole group is the rod: the wrapper takes the group's place under the pivot
        pivotGroup.add(wrapper); wrapper.position.copy(group.mesh.position); group.mesh = wrapper; group.base.copy(wrapper.position);
      } else group.mesh.add(wrapper);
      let length = 0;
      chrome.forEach(m => {
        const box = new THREE.Box3().setFromObject(m); // rest pose: world axes coincide with pivot-local axes
        const proj = [];
        for (const x of [box.min.x, box.max.x]) for (const y of [box.min.y, box.max.y]) for (const z of [box.min.z, box.max.z]) proj.push(new THREE.Vector3(x, y, z).dot(axis));
        length = Math.max(length, Math.max(...proj) - Math.min(...proj)); // rod extent along its own axis (both sides share one primitive)
        wrapper.add(m); m.position.set(0, 0, 0); m.quaternion.copy(q).invert();
      });
      return { wrapper, length, rest: base.distanceTo(end) };
    };
    this.rig = { armGroup, bucketGroup, liftBarrelPivot, liftRodPivot, tiltBarrelPivot, tiltRodPivot, armPivot, bucketPivot, liftBase: P('liftBase'), liftRodEnd: P('liftRodEnd'), tiltBase: P('tiltBase'), tiltRodEnd: P('tiltRodEnd') };
    this.rig.liftStretch = stretchable('liftRod', liftRodPivot, P('liftBase'), P('liftRodEnd'));
    this.rig.tiltStretch = stretchable('tiltRod', tiltRodPivot, P('tiltBase'), P('tiltRodEnd'));
    this.rigLimits = { lift: THREE.MathUtils.degToRad(70), dump: THREE.MathUtils.degToRad(-48), curl: THREE.MathUtils.degToRad(18) };
    this.setPose(0, 0);
  }

  // lift: 0 (rest, boom lowered) → 1 (full height). tilt: −1 (dump forward) … 0 (level) … 1 (curl back).
  /** The CAD rest pose has the bucket 16 cm below the tracks' contact plane; a real machine stops when the cutting edge meets the ground. */
  groundLift(tilt) {
    if (!this.rig) return 0;
    this.groundTable ??= new Map();
    const key = Math.round(tilt * 20);
    if (this.groundTable.has(key)) return this.groundTable.get(key);
    const bucket = this.groups.get('bucket'), v = new THREE.Vector3();
    const minY = lift => { this.applyPose(lift, key / 20); this.rig.armGroup.updateWorldMatrix(true, true); let mn = Infinity;
      bucket.meshes.forEach(m => { const b = m.geometry.boundingBox || (m.geometry.computeBoundingBox(), m.geometry.boundingBox); for (const x of [b.min.x, b.max.x]) for (const y of [b.min.y, b.max.y]) for (const z of [b.min.z, b.max.z]) mn = Math.min(mn, m.localToWorld(v.set(x, y, z)).y); });
      return mn - this.root.position.y; };
    let lo = 0, hi = .5; if (minY(lo) >= -.005) hi = 0; else for (let i = 0; i < 14; i++) { const mid = (lo + hi) / 2; if (minY(mid) < -.005) lo = mid; else hi = mid; }
    this.groundTable.set(key, hi); return hi;
  }
  setPose(lift, tilt) { this.applyPose(Math.max(lift, this.groundLift(tilt)), tilt); }
  applyPose(lift, tilt) {
    if (!this.rig) return;
    const r = this.rig, L = this.rigLimits;
    const liftAngle = lift * L.lift;
    const tiltAngle = tilt < 0 ? -tilt * L.dump : tilt * L.curl;
    r.armGroup.rotation.x = liftAngle;
    r.bucketGroup.rotation.x = -liftAngle * .9 + tiltAngle; // partial self-levelling keeps the bucket roughly level while lifting
    // Lift cylinder: barrel on the frame aims at the rod eye on the boom; rod aims back at the barrel.
    const rodEnd = r.liftRodEnd.clone().sub(r.armPivot).applyAxisAngle(new THREE.Vector3(1, 0, 0), liftAngle).add(r.armPivot);
    const d0 = r.liftRodEnd.clone().sub(r.liftBase), d = rodEnd.clone().sub(r.liftBase);
    r.liftBarrelPivot.rotation.x = yz(d) - yz(d0);
    r.liftRodPivot.rotation.x = (yz(d.clone().negate()) - yz(d0.clone().negate())) - liftAngle;
    if (r.liftStretch) r.liftStretch.wrapper.scale.z = 1 + (d.length() - r.liftStretch.rest) / r.liftStretch.length;
    // Tilt cylinder lives in boom space: barrel base on the boom, rod eye on the quick-attach.
    const tiltEndLocal = r.tiltRodEnd.clone().sub(r.bucketPivot).applyAxisAngle(new THREE.Vector3(1, 0, 0), r.bucketGroup.rotation.x).add(r.bucketPivot);
    const t0 = r.tiltRodEnd.clone().sub(r.tiltBase), t = tiltEndLocal.clone().sub(r.tiltBase);
    r.tiltBarrelPivot.rotation.x = yz(t) - yz(t0);
    r.tiltRodPivot.rotation.x = (yz(t.clone().negate()) - yz(t0.clone().negate())) - r.bucketGroup.rotation.x;
    if (r.tiltStretch) r.tiltStretch.wrapper.scale.z = 1 + (t.length() - r.tiltStretch.rest) / r.tiltStretch.length;
    this.pose = { lift, tilt };
    this.needsRender = true;
  }

  buildLabels() {
    const layer = document.createElement('div'); layer.className = 'exp-3d-labels'; this.container.appendChild(layer); this.labelLayer = layer;
    const seen = new Set();
    for (const group of this.groups.values()) {
      if (group.key === 'trackR' || seen.has(group.label)) continue;
      seen.add(group.label);
      const el = document.createElement('button'); el.type = 'button'; el.className = 'exp-3d-label'; el.dataset.group = group.key;
      el.innerHTML = `<i></i><span>${t(group.label)}</span>`;
      el.addEventListener('click', () => this.onSelect?.(group.key));
      layer.appendChild(el); this.labelElements.set(group.key, el);
    }
  }

  pick(clientX, clientY) {
    const rect = this.canvas.getBoundingClientRect();
    this.pointer.set(((clientX - rect.left) / rect.width) * 2 - 1, -((clientY - rect.top) / rect.height) * 2 + 1);
    this.raycaster.setFromCamera(this.pointer, this.camera);
    const meshes = [...this.groups.values()].filter(g => !g.ghost).flatMap(g => g.meshes);
    const hit = this.raycaster.intersectObjects(meshes, false)[0];
    return hit ? hit.object.userData.group : null;
  }

  setHover(key) {
    if (this.hoverKey === key) return;
    this.hoverKey = key;
    for (const group of this.groups.values()) {
      const on = !!key && group.key === key;
      group.materials.forEach(m => { m.emissive.setHex(on ? 0x5a1a10 : 0x000000); m.emissiveIntensity = on ? .35 : 0; });
    }
    this.labelElements.forEach((el, k) => el.classList.toggle('is-hover', k === key));
    this.needsRender = true;
  }

  resize() {
    const w = this.container.clientWidth, h = this.container.clientHeight;
    if (!w || !h) return;
    this.renderer?.setSize(w, h, false);
    const frame = this.options.frame?.getBoundingClientRect();
    if (frame?.width && frame.height) {
      // Compose the shot on the frame box but paint the whole container, so the machine can move past the box unclipped.
      // A view shift (setViewShift) is left out: the picture moves with the container.
      const box = this.container.getBoundingClientRect();
      this.camera.aspect = frame.width / frame.height;
      this.camera.setViewOffset(frame.width, frame.height, box.left - frame.left, box.top - frame.top + (this.viewShift || 0), w, h);
    } else this.camera.aspect = w / h;
    this.camera.updateProjectionMatrix();
    this.needsRender = true;
  }

  /** Move the container (and the picture with it) up by `px` from where it is laid out, e.g. to keep it on screen while its section is still scrolling in. */
  setViewShift(px) {
    if (px === (this.viewShift || 0)) return;
    this.viewShift = px;
    this.container.style.transform = px ? `translate3d(0, ${-px}px, 0)` : '';
  }

  setExplode(value) { this.explodeValue = Math.max(0, Math.min(1, value)); this.applyExplode(); }
  applyExplode() {
    const t = easeOut(this.explodeValue);
    for (const group of this.groups.values()) group.mesh.position.copy(group.base).addScaledVector(group.explode, t);
    if (this.ground) this.ground.position.y = -.005 - .82 * t; // keep the exploded undercarriage above the shadow catcher
    this.needsRender = true;
  }

  setFocus(keys) {
    this.focusKey = keys;
    const set = keys ? new Set([].concat(keys)) : null;
    for (const group of this.groups.values()) {
      const ghost = !!set && !set.has(group.key);
      group.ghost = ghost;
      group.materials.forEach(m => { m.transparent = ghost; m.opacity = ghost ? this.options.ghostOpacity : 1; m.depthWrite = !ghost; m.needsUpdate = true; });
      group.meshes.forEach(m => { m.castShadow = !ghost; m.renderOrder = ghost ? 1 : 0; });
    }
    this.labelElements.forEach((el, k) => el.classList.toggle('is-active', !!set && set.has(k)));
    this.needsRender = true;
  }

  flyTo(keys, { distanceScale = 1, azimuth = null, elevation = null, duration = 1.1 } = {}) {
    let box;
    if (keys) { box = new THREE.Box3(); [].concat(keys).forEach(k => { const g = this.groups.get(k); if (g) box.union(new THREE.Box3().setFromObject(g.mesh)); }); }
    else box = new THREE.Box3().setFromObject(this.root);
    if (box.isEmpty()) return;
    const center = box.getCenter(new THREE.Vector3());
    const size = box.getSize(new THREE.Vector3()).length();
    const fov = THREE.MathUtils.degToRad(this.camera.fov);
    const distance = Math.max(this.controls?.minDistance ?? 1, 1.6, size / (2 * Math.tan(fov / 2)) * 1.15 * distanceScale);
    const current = new THREE.Spherical().setFromVector3(this.camera.position.clone().sub(this.controls ? this.controls.target : this.target));
    const end = new THREE.Vector3().setFromSphericalCoords(distance, elevation ?? current.phi, azimuth ?? current.theta).add(center);
    this.cameraTween = { from: this.camera.position.clone(), to: end, targetFrom: (this.controls ? this.controls.target : this.target).clone(), targetTo: center, start: this.clock(), duration: duration * 1000 };
    if (this.controls) this.controls.autoRotate = false;
    this.needsRender = true;
  }

  setAutoRotate(on) { if (this.controls) this.controls.autoRotate = on; }

  /** Recolour the body paint (the finale's colour picker). Only the colour uniform changes: no shader recompiles, no shadow redraw. */
  setPaint(hex, duration = .6) {
    const materials = [];
    this.groups.forEach(g => g.materials.forEach(m => { if (m.name === PAINT) materials.push(m); }));
    this.paintTween = { materials, from: materials.map(m => m.color.clone()), to: new THREE.Color(hex), start: this.clock(), duration: Math.max(1, duration * 1000) };
    this.tick();
  }

  /** Take the camera from OrbitControls for a scripted shot; on release the orbit resumes from wherever the camera was left. */
  holdCamera(on) {
    if (!this.controls || !!this.cameraHeld === on) return;
    this.cameraHeld = on; this.cameraTween = null;
    this.controls.enabled = !on;
    this.controls.autoRotate = !on && this.options.autoRotate && !this.userInteracted;
    this.needsRender = true;
  }

  /**
   * Drive the machine forward: body and belts advance by the same distance. The sun's shadow frustum is centred on
   * `shadowZ` with half-size `shadowHalf` (by default it follows the machine), so anything towed stays covered too.
   */
  setDrive(metres, shadowZ = -metres, shadowHalf = 2.4) {
    this.root.position.z = -metres; // -Z is the machine's forward direction
    this.setTravel(metres);
    this.sun.position.set(this.sunBase.x, this.sunBase.y, this.sunBase.z + shadowZ);
    this.sun.target.position.set(0, 0, shadowZ);
    const cam = this.sun.shadow.camera;
    if (cam.right !== shadowHalf) { cam.left = cam.bottom = -shadowHalf; cam.right = cam.top = shadowHalf; cam.updateProjectionMatrix(); }
    this.needsRender = true;
  }

  // A looping working cycle: fn(seconds) → { lift, tilt, advance } where advance moves the whole
  // machine along its travel axis (metres). Used by the scene chapter; null stops the loop.
  /** Keep the continuous belt in place; transport each tread block as one rigid component. */
  buildTrackLoops() {
    this.belts = [];
    for (const key of ['trackL', 'trackR']) {
      const group = this.groups.get(key); if (!group) continue;
      const src = group.meshes.find(m => [].concat(m.material).some(mat => mat.name === '细纹橡胶'));
      if (!src) continue;
      const source = src.geometry, count = source.attributes.position.count;
      // glTF primitives are quantised and shared by the five viewers. Only this scene
      // owns the float buffers being animated; never mutate the cached GLB geometry.
      const geo = new THREE.BufferGeometry();
      geo.setIndex(source.index.clone());
      for (const [name, attribute] of Object.entries(source.attributes)) {
        const array = new Float32Array(count * attribute.itemSize);
        for (let i = 0; i < count; i++) for (let k = 0; k < attribute.itemSize; k++) array[i * attribute.itemSize + k] = attribute.getComponent(i, k);
        geo.setAttribute(name, new THREE.BufferAttribute(array, attribute.itemSize));
      }
      geo.attributes.position.setUsage(THREE.DynamicDrawUsage);
      geo.attributes.normal.setUsage(THREE.DynamicDrawUsage);
      const scale = src.getWorldScale(new THREE.Vector3()).x;
      const motion = createTrackMotion(geo.attributes.position.array, geo.attributes.normal.array, geo.index.array, scale);
      geo.computeBoundingBox(); geo.computeBoundingSphere(); src.geometry = geo;
      geo.boundingBox.expandByScalar(.05 / scale); geo.boundingSphere.radius += .05 / scale; // 5 cm of slack around the loop
      this.belts.push({ mesh: src, motion, rest: motion.rest, scale });
    }
    this.travel = 0;
  }

  setTravel(metres) {
    if (!this.belts?.length || metres === this.travel) return;
    for (const belt of this.belts) {
      belt.motion.update(metres);
      const geometry = belt.mesh.geometry;
      geometry.attributes.position.needsUpdate = true;
      geometry.attributes.normal.needsUpdate = true;
      // Bounds are not recomputed: tread blocks only travel around the belt's own loop, and the loop's envelope
      // (set once in buildTrackLoops) contains every position. Recomputing both belts cost ~5 ms a frame (4× throttle).
    }
    this.travel = metres;
    this.needsRender = true;
  }

  /** Fit the load to bucket-local CAD surfaces; discharged material lives in world space. */
  buildPayload() {
    const bucket = this.groups.get('bucket'); if (!bucket) return;
    const material = new THREE.MeshStandardMaterial({ color: 0x5a4a3a, roughness: 1, metalness: 0 });
    this.payloadSystem = new BucketPayload({ parent: this.rig.bucketGroup, meshes: bucket.meshes, scene: this.scene, material });
    this.payload = this.payloadSystem.heap;
  }

  setLoad(load, tilt = this.pose.tilt) {
    if (!this.payloadSystem) return;
    this.payloadSystem.setTarget(load);
    this.needsRender = true;
  }

  /** Dust and wear: pull the paint of the parts that touch the ground toward the plate's ground colour and roughen them. */
  weather(amount, color) {
    const factor = { bucket: 1, quickattach: .8, trackL: .95, trackR: .95, trackframe: .85, frame: .4, arm: .35, liftBarrel: .3, tiltBarrel: .3, engine: .2, fueltank: .2, hydtank: .2, controls: .1, battery: .1, liftRod: 0, tiltRod: 0 };
    const dust = new THREE.Color(color);
    for (const g of this.groups.values()) {
      const f = (factor[g.key] ?? .15) * amount;
      g.materials.forEach(m => {
        if (m.name === '液压杆镀铬') return;
        if (!m.userData.base) m.userData.base = { color: m.color.clone(), roughness: m.roughness, clearcoat: m.clearcoat ?? 0 };
        const b = m.userData.base;
        m.color.copy(b.color).lerp(dust, f * .45);
        m.roughness = b.roughness + (1 - b.roughness) * f * .6;
        if (m.clearcoat !== undefined) m.clearcoat = b.clearcoat * (1 - f * .8);
      });
    }
    this.needsRender = true;
  }
  /** Light the machine with the backdrop itself: an equirect built from the plate so reflections and bounce carry its colours. */
  setEnvironmentFromImage(img, { skyColor = '#c9ccd2', groundColor = '#8a7a66' } = {}) {
    const c = document.createElement('canvas'); c.width = 1024; c.height = 512; const g = c.getContext('2d');
    const grad = g.createLinearGradient(0, 0, 0, 512); grad.addColorStop(0, skyColor); grad.addColorStop(.5, skyColor); grad.addColorStop(.62, groundColor); grad.addColorStop(1, groundColor);
    g.fillStyle = grad; g.fillRect(0, 0, 1024, 512);
    const bandH = 300, top = 256 - bandH * .55; // the plate's horizon (55% down) sits on the equator
    g.drawImage(img, 0, top, 512, bandH); g.save(); g.translate(1024, 0); g.scale(-1, 1); g.drawImage(img, 0, top, 512, bandH); g.restore();
    const tex = new THREE.CanvasTexture(c); tex.mapping = THREE.EquirectangularReflectionMapping; tex.colorSpace = THREE.SRGBColorSpace;
    if (!this.renderer) return;
    const pmrem = new THREE.PMREMGenerator(this.renderer); const env = pmrem.fromEquirectangular(tex).texture; pmrem.dispose(); tex.dispose();
    if (this.ownEnv) this.ownEnv.dispose();
    this.ownEnv = env; this.scene.environment = env; this.scene.environmentRotation.y = 0; this.needsRender = true;
  }

  /** Match the lighting to a backdrop: exposure, key/fill balance, colour temperature, shadow density and tint. Tweened. */
  setGrade(grade, duration = 1.2) {
    const cur = this.currentGrade();
    const to = { ...cur, ...grade };
    const col = k => ({ from: new THREE.Color(cur[k]), to: new THREE.Color(to[k]) });
    this.gradeTween = { start: this.clock(), duration: duration * 1000, from: cur, to,
      colors: { sunColor: col('sunColor'), skyColor: col('skyColor'), groundColor: col('groundColor'), shadowColor: col('shadowColor'), payloadColor: col('payloadColor'), dustColor: col('dustColor') } };
    this.needsRender = true;
  }
  currentGrade() {
    return { exposure: this.exposure, envIntensity: this.scene.environmentIntensity ?? this.options.envIntensity,
      sunIntensity: this.sun.intensity, sunColor: this.sun.color.getHex(), hemiIntensity: this.hemi.intensity,
      skyColor: this.hemi.color.getHex(), groundColor: this.hemi.groundColor.getHex(),
      shadowOpacity: this.ground ? this.ground.material.opacity : 0, shadowColor: this.ground ? this.ground.material.color.getHex() : 0,
      contactOpacity: this.contact ? this.contact.material.opacity : 0, payloadColor: this.payload ? this.payload.material.color.getHex() : 0, dust: this.dust ?? 0, dustColor: this.dustColor ?? 0x8a7a66 };
  }
  applyGrade(g) {
    this.exposure = g.exposure; if (this.renderer) this.renderer.toneMappingExposure = g.exposure; this.scene.environmentIntensity = g.envIntensity;
    this.sun.intensity = g.sunIntensity; this.sun.color.set(g.sunColor);
    this.hemi.intensity = g.hemiIntensity; this.hemi.color.set(g.skyColor); this.hemi.groundColor.set(g.groundColor);
    if (this.ground) { this.ground.material.opacity = g.shadowOpacity; this.ground.material.color.set(g.shadowColor); }
    if (this.contact) this.contact.material.opacity = g.contactOpacity;
    if (this.payload) this.payload.material.color.set(g.payloadColor);
    if (g.dust !== undefined) { this.dust = g.dust; this.dustColor = g.dustColor; this.weather(g.dust, g.dustColor); }
    this.needsRender = true;
  }

  /**
   * Fixed photographic camera matching a backdrop plate: eye height, horizon line and where the machine's feet
   * and centre fall in the frame (fractions of the container). Tweened when `duration` > 0.
   */
  placeCamera({ height = 1.2, horizonFrac = .55, feetFrac = .8, xFrac = .62, azimuth = 0, vfov = 40, duration = 0 }) {
    const w = this.container.clientWidth || 1, h = this.container.clientHeight || 1;
    this.camera.fov = vfov; this.camera.aspect = w / h; this.camera.updateProjectionMatrix();
    const tv = Math.tan(THREE.MathUtils.degToRad(vfov) / 2), th = tv * w / h;
    const pitch = Math.atan((.5 - horizonFrac) * 2 * tv);           // >0 looks down (horizon rises above centre)
    const depression = pitch + Math.atan((feetFrac - .5) * 2 * tv); // angle below horizontal to the machine's feet
    const d = height / Math.tan(Math.max(depression, .05));
    const cam = new THREE.Vector3(Math.sin(azimuth) * d, height, Math.cos(azimuth) * d);
    const yaw = Math.atan((xFrac - .5) * 2 * th);                   // machine right of centre → camera yaws left
    const toOrigin = new THREE.Vector3(-cam.x, 0, -cam.z).normalize().applyAxisAngle(new THREE.Vector3(0, 1, 0), yaw);
    const forward = new THREE.Vector3(toOrigin.x * Math.cos(pitch), -Math.sin(pitch), toOrigin.z * Math.cos(pitch));
    const target = cam.clone().add(forward.multiplyScalar(d));
    if (duration > 0) this.cameraTween = { from: this.camera.position.clone(), to: cam, targetFrom: (this.controls ? this.controls.target : this.target).clone(), targetTo: target, start: this.clock(), duration: duration * 1000 };
    else { this.cameraTween = null; this.camera.position.copy(cam); (this.controls ? this.controls.target : this.target).copy(target); }
    this.needsRender = true;
  }

  setLoop(fn) { this.loop = fn ? { fn, start: this.clock() } : null; if (!fn) { this.root.position.set(0, 0, 0); this.setTravel(0); this.payloadSystem?.reset(); } this.needsRender = true; }

  // Ease the rig from its current pose to a new one (scene changes, presets).
  animatePose(lift, tilt, duration = 1.2) {
    if (!this.rig) return;
    this.poseTween = { from: { ...this.pose }, to: { lift, tilt }, start: this.clock(), duration: duration * 1000 };
    this.needsRender = true;
  }

  // One animation loop per view: tick() is called from several places (visibility, load, wake) and must not start
  // a second loop while one is running. (Each extra loop re-ran controls.update(), which sped up auto-rotation and damping.)
  tick = () => {
    if (this.looping || this.disposed || !this.visible || this.manual) return;
    this.looping = true;
    const frame = () => {
      if (this.disposed || !this.visible || this.manual) { this.looping = false; return; }
      requestAnimationFrame(frame);
      this.step();
    };
    frame();
  };

  /** Advance tweens, loop and payload to the current clock and render if anything changed. */
  step() {
    if (!this.root.children.length) return;
    const stepStart = performance.now();
    if (this.cameraTween) {
      const tw = this.cameraTween;
      const t = Math.min(1, (this.clock() - tw.start) / tw.duration), e = t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
      this.camera.position.lerpVectors(tw.from, tw.to, e);
      (this.controls ? this.controls.target : this.target).lerpVectors(tw.targetFrom, tw.targetTo, e);
      if (t >= 1) this.cameraTween = null;
      this.needsRender = true;
    }
    if (this.loop && !this.poseTween) {
      const p = this.loop.fn((this.clock() - this.loop.start) / 1000);
      this.setPose(p.lift, p.tilt);
      this.root.position.z = -(p.advance || 0); // -Z is the machine's forward direction
      this.setTravel(p.advance || 0);            // the belts roll by the same distance
      if (p.load !== undefined) this.setLoad(p.load, p.tilt);
    }
    if (this.gradeTween) {
      const tw = this.gradeTween, t = Math.min(1, (this.clock() - tw.start) / tw.duration), e = 1 - Math.pow(1 - t, 3), g = {};
      for (const k in tw.to) g[k] = typeof tw.to[k] === 'number' && !tw.colors[k] ? tw.from[k] + (tw.to[k] - tw.from[k]) * e : tw.to[k];
      for (const k in tw.colors) g[k] = tw.colors[k].from.clone().lerp(tw.colors[k].to, e).getHex();
      this.applyGrade(g);
      if (t >= 1) this.gradeTween = null;
    }
    if (this.paintTween) {
      const tw = this.paintTween, t = Math.min(1, (this.clock() - tw.start) / tw.duration), e = easeOut(t);
      tw.materials.forEach((m, i) => m.color.copy(tw.from[i]).lerp(tw.to, e));
      if (t >= 1) this.paintTween = null;
      this.cameraMoved = true; // redraw with the existing shadow map
    }
    if (this.poseTween) {
      const tw = this.poseTween;
      const t = Math.min(1, (this.clock() - tw.start) / tw.duration), e = t < .5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
      this.setPose(tw.from.lift + (tw.to.lift - tw.from.lift) * e, tw.from.tilt + (tw.to.tilt - tw.from.tilt) * e);
      if (t >= 1) this.poseTween = null;
    }
    if (this.payloadSystem) {
      const now = this.clock();
      const dt = this.payloadTime === undefined ? 0 : (now - this.payloadTime) / 1000;
      this.payloadTime = now;
      if (this.payloadSystem.step(dt)) this.needsRender = true;
    }
    let moved = false;
    if (this.controls && !this.cameraHeld) moved = this.controls.update(); else this.camera.lookAt(this.controls ? this.controls.target : this.target);
    if (!this.renderer || !this.compiled) return; // asleep, or shaders still compiling: state is kept, drawing waits
    if (!this.needsRender && !moved && !this.cameraMoved) return; // render on demand: idle viewers cost nothing while the page scrolls
    this.cameraMoved = false;
    // Shadows depend on the scene, not the camera: frames where only the orbit moved (auto-rotate, drags) reuse the
    // last shadow map, which halves the geometry drawn per frame. Anything else that changes sets needsRender.
    if (this.needsRender) this.renderer.shadowMap.needsUpdate = true;
    this.needsRender = false;
    this.renderer.render(this.scene, this.camera);
    if (this.labelLayer) this.updateLabels();
    this.adaptQuality(performance.now() - stepStart);
  }

  /**
   * Keep motion smooth on GPUs that can't hold the frame rate at full resolution. When rendered frames arrive more
   * than 22 ms apart (under ~45 fps) for 45 consecutive frames while this view's own CPU work per frame stays small
   * (so the GPU or compositor is the bottleneck, and fewer pixels will help), the pixel ratio drops a quarter step,
   * down to 1; the lower cap survives sleep/wake. Devices that keep up never trigger it and render as designed.
   */
  adaptQuality(cpuMs) {
    const now = performance.now(), last = this.lastFrameAt;
    this.lastFrameAt = now;
    if (last === undefined || now - last > 100) { this.frameSamples = []; return; } // not a continuous animation
    (this.frameSamples ??= []).push([now - last, cpuMs]);
    if (this.frameSamples.length < 45) return;
    const median = i => this.frameSamples.map(f => f[i]).sort((a, b) => a - b)[22];
    const gpuBound = median(0) > 22 && median(1) < 8;
    this.frameSamples = [];
    const ratio = this.renderer.getPixelRatio();
    if (!gpuBound || ratio <= 1) return;
    this.pixelRatioCap = Math.max(1, ratio - .25);
    this.renderer.setPixelRatio(this.pixelRatioCap);
    this.resize();
  }

  updateLabels() {
    const w = this.container.clientWidth, h = this.container.clientHeight, v = new THREE.Vector3();
    for (const [key, el] of this.labelElements) {
      const group = this.groups.get(key);
      group.mesh.getWorldPosition(v); v.add(group.center.clone().sub(group.base)); v.project(this.camera);
      const x = (v.x * .5 + .5) * w, y = (-v.y * .5 + .5) * h;
      const behind = v.z > 1 || x < 0 || x > w || y < 0 || y > h;
      el.style.transform = `translate(${x.toFixed(1)}px, ${y.toFixed(1)}px)`;
      el.classList.toggle('is-visible', this.explodeValue > .55 && !behind && !group.ghost);
    }
  }

  dispose() {
    this.disposed = true;
    this.resizeObserver.disconnect(); this.intersection.disconnect();
    this.controls?.dispose();
    this.payloadSystem?.dispose();
    this.root.traverse(n => { if (n.isMesh) [].concat(n.material).forEach(m => m.dispose()); });
    this.belts?.forEach(belt => belt.mesh.geometry.dispose());
    this.manual = false; this.sleep(); this.labelLayer?.remove();
  }
}

export function webglAvailable() {
  try { const c = document.createElement('canvas'); return !!(c.getContext('webgl2') || c.getContext('webgl')); } catch { return false; }
}
