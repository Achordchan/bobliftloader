// Hero viewer, exploded-view chapter, part-by-part chapter and the photo lightbox.
import { Box3, Plane, Raycaster, Vector2, Vector3 } from 'three';
import { LoaderViewer, GROUPS, webglAvailable } from './viewer.js?v=35';
import { LogoTow } from './logo-tow.js?v=4';
const t = (source, vars) => window.I18N ? window.I18N.t(source, vars) : source; // i18n/i18n.js

const reduced = matchMedia('(prefers-reduced-motion: reduce)');
if ('scrollRestoration' in history) history.scrollRestoration = 'manual'; // sticky chapters must not jump to a restored mid-page offset
const PHOTO = name => `assets/site/${name}.webp`;
const THUMB = name => `assets/site/thumb/${name}.webp`;

export const PHOTO_SETS = {
  overview: { title: '整机实拍', photos: ['IMG_2724', 'IMG_2731', 'IMG_2737', 'IMG_2740', 'IMG_2747', 'IMG_2753', 'IMG_2757', 'IMG_2799', 'IMG_2716', 'IMG_2719', 'IMG_2773', 'IMG_2980', 'IMG_2990', 'IMG_2860', 'IMG_2869'] },
};

export const PARTS = [
  { id: 'bucket', title: '铲斗', groups: ['bucket'], text: '焊接钢制斗体，经快换架与举升臂相连，由前端油缸驱动翻转。', photos: ['IMG_2810', 'IMG_2836', 'IMG_2850'], view: { azimuth: 2.35, elevation: 1.2, distanceScale: 1.35 } },
  { id: 'quickattach', title: '快换架与液压快接', groups: ['quickattach', 'tiltBarrel', 'tiltRod'], text: '快换架把铲斗锁在臂架前端，旁边布置液压快接头，便于更换附具。', photos: ['IMG_2812', 'IMG_2840', 'IMG_2962', 'IMG_2999'], view: { azimuth: 2.55, elevation: 1.1, distanceScale: 1.15 } },
  { id: 'arm', title: '举升臂与油缸', groups: ['arm', 'liftBarrel', 'liftRod'], text: '臂架绕车架后铰点抬升，两侧举升油缸随铰点伸展，管路沿臂架走线。', photos: ['IMG_2787', 'IMG_2789', 'IMG_2791', 'IMG_2806', 'IMG_2967', 'IMG_2969'], view: { azimuth: 1.95, elevation: 1.15, distanceScale: 1.05 } },
  { id: 'controls', title: '操纵台', groups: ['controls'], text: '双侧操纵杆左右布局，握持位置清晰；仪表、钥匙与总电源开关集中在站驾视线内，环形扶手沿操纵区布置。', photos: ['IMG_2768', 'IMG_2767', 'IMG_2804', 'IMG_2824', 'IMG_2826', 'IMG_2783', 'IMG_2964'], view: { azimuth: .55, elevation: .95, distanceScale: 1.5 } },
  { id: 'engine', title: '发动机与散热', groups: ['engine'], text: '发动机位于机身后部，风罩、空气滤清器与油冷却器布置在机罩之下。', photos: ['IMG_2771', 'IMG_2775', 'IMG_2830', 'IMG_2863', 'IMG_2844', 'IMG_2876', 'IMG_2885', 'IMG_2853'], view: { azimuth: .35, elevation: 1.05, distanceScale: 1.3 } },
  { id: 'tanks', title: '油箱与电瓶', groups: ['fueltank', 'hydtank', 'battery'], text: '汽油箱、液压油箱和电瓶安装在车架内部，液压油箱侧面设有油位观察窗。', photos: ['IMG_2814', 'IMG_2779'], view: { azimuth: .95, elevation: 1.05, distanceScale: 1.25 } },
  { id: 'frame', title: '车架、机罩与踏板', groups: ['frame'], text: '焊接车架承载全部总成，开槽机罩可掀开检修；后部站立踏板带防滑凸纹，从表面到两侧边缘都为日常站立而设。', photos: ['IMG_2808', 'IMG_2838', 'IMG_2834', 'IMG_2860', 'IMG_2869', 'IMG_2818'], view: { azimuth: 2.65, elevation: 1.2, distanceScale: 1.05 } },
  { id: 'tracks', title: '履带底盘', groups: ['trackframe', 'trackL', 'trackR'], text: '钢制履带架安装在车架下方，橡胶履带包覆驱动轮与承重轮。', photos: ['IMG_2797', 'IMG_2842', 'IMG_2740'], view: { azimuth: 1.5, elevation: 1.3, distanceScale: 1.15 } },
];
const partForGroup = key => PARTS.find(p => p.groups.includes(key)) || PARTS.find(p => p.id === 'frame');

// ---------- Lightbox ----------
const lightbox = document.querySelector('#lightbox');
const lbImage = lightbox.querySelector('img');
const lbTitle = lightbox.querySelector('#lightbox-title');
const lbCount = lightbox.querySelector('#lightbox-count');
const lbThumbs = lightbox.querySelector('.exp-lightbox-thumbs');
let lbSet = [], lbIndex = 0, lbOpener = null;
function lbShow(i) {
  lbIndex = (i + lbSet.length) % lbSet.length;
  lbImage.src = PHOTO(lbSet[lbIndex]); lbImage.alt = t('{title} 实拍 {n}', { title: lbTitle.textContent, n: lbIndex + 1 });
  lbCount.textContent = `${lbIndex + 1} / ${lbSet.length}`;
  lbThumbs.querySelectorAll('button').forEach((b, k) => { b.setAttribute('aria-pressed', String(k === lbIndex)); if (k === lbIndex) b.scrollIntoView({ block: 'nearest', inline: 'center', behavior: 'smooth' }); });
  const next = new Image(); next.src = PHOTO(lbSet[(lbIndex + 1) % lbSet.length]);
}
export function openLightbox(title, photos, start = 0, opener = null) {
  lbSet = photos; lbOpener = opener; lbTitle.textContent = t(title);
  lbThumbs.innerHTML = photos.map((p, k) => `<button type="button" data-k="${k}" aria-pressed="false"><img src="${THUMB(p)}" alt="" loading="lazy"></button>`).join('');
  lbThumbs.querySelectorAll('button').forEach(b => b.addEventListener('click', () => lbShow(+b.dataset.k)));
  lbShow(start);
  lightbox.showModal(); document.body.style.overflow = 'hidden'; lightbox.querySelector('.exp-close').focus();
}
lightbox.querySelector('.exp-close').addEventListener('click', () => lightbox.close());
lightbox.querySelector('[data-prev]').addEventListener('click', () => lbShow(lbIndex - 1));
lightbox.querySelector('[data-next]').addEventListener('click', () => lbShow(lbIndex + 1));
lightbox.addEventListener('click', e => { if (e.target === lightbox) lightbox.close(); });
lightbox.addEventListener('keydown', e => { if (e.key === 'ArrowLeft') lbShow(lbIndex - 1); if (e.key === 'ArrowRight') lbShow(lbIndex + 1); });
lightbox.addEventListener('close', () => { document.body.style.overflow = ''; lbOpener?.focus({ preventScroll: true }); });
document.querySelectorAll('[data-scene-photo]').forEach(btn => btn.addEventListener('click', () => openLightbox('土方作业实拍', [btn.dataset.scenePhoto], 0, btn)));
document.querySelectorAll('[data-lightbox]').forEach(btn => btn.addEventListener('click', () => { const set = PHOTO_SETS[btn.dataset.lightbox]; openLightbox(set.title, set.photos, 0, btn); }));

// ---------- Viewers ----------
const heroBox = document.querySelector('#hero-3d');
const explodeBox = document.querySelector('#explode-3d');
const partsBox = document.querySelector('#parts-3d');
const motionBox = document.querySelector('#motion-3d');
const scenesBox = document.querySelector('#scenes-3d');
const finaleBox = document.querySelector('#finale-3d');
const explodeRange = document.querySelector('#explode-range');
const explodeStatus = document.querySelector('#explode-status');
const gsap = window.gsap, ScrollTrigger = window.ScrollTrigger;

function setStatus(el, text) { if (!el) return; el.hidden = !text; el.textContent = text || ''; }
if (gsap) bindPartSteps(); // with or without the 3D: the list is there either way

// The scenes chapter plays pre-rendered loops (assets/scenes/video/). `?capture-scenes` instead runs the original
// real-time scene on a virtual clock so scripts/capture_scenes.mjs can record those loops frame by frame.
const SCENE_CAPTURE = new URLSearchParams(location.search).has('capture-scenes');
const SCENE_VIEWER = { autoRotate: false, controls: false, shadow: true, shadowOpacity: .28, contactShadow: .45, payload: true, trackMotion: true, sunPosition: [1.6, 8, -1.2], fov: 40, exposure: 1.1, envIntensity: 1.15 };
// Finale arrival (bindFinaleDrive): showcase distance in multiples of the hero's camera offset, the overhead shot, and
// the cues over the pinned span (0 → 1).
// The machine is drawn at the hero's scale (the same pixels per metre, from the two cameras' projections) and driven at
// the hero's pace (its top speed while towing the wordmark in, in metres per viewport height scrolled). Stage timings
// are in viewport heights, like HERO_CUE.
const FINALE_CUE = { park: .46, early: .5, lower: .15, camera: .37, hold: .06 }; // park: where it stops, in stage height; early: how far ahead of the pin it sets off; the rest are durations
const HERO_OFFSET = new Vector3(3.4, 1.5, -3.9), HERO_LOOK = new Vector3(0, .62, 0); // the hero's resting camera

// Opening load (see the script at the top of index.html): the downloads fill the poster with colour up to 92%, the rest
// is the first draw; then the model fades in over the poster and the page lets go of the scroll.
const showLoad = f => window.__heroLoad?.(f);
const endLoad = ok => {
  window.__heroHold?.release();
  document.documentElement.classList.remove('hero-pending'); // no model: show the poster
  setTimeout(() => document.documentElement.classList.remove('hero-loading'), ok ? 900 : 0); // after the poster has faded; at once if there is no model
};

if (!webglAvailable()) {
  endLoad(false);
  document.body.classList.add('no-webgl');
  setStatus(explodeStatus, t('当前浏览器不支持实时三维，显示静态图。'));
} else if (SCENE_CAPTURE) {
  endLoad(false);
  const scenes = new LoaderViewer(scenesBox, { ...SCENE_VIEWER, pixelRatio: devicePixelRatio }); // full device resolution for the recording
  window.__loaderViewers = { scenes };
  scenes.load().then(() => bindSceneCapture(scenes)).catch(err => console.error(err));
} else {
  const progress = e => { if (!e.total) return; const f = e.loaded / e.total; setStatus(explodeStatus, t('正在载入三维模型 {n}%', { n: Math.round(f * 100) })); showLoad(f * .92); };
  // The hero always ran two animation loops, so the rotation and damping people saw were twice the nominal values;
  // now that each view runs one loop, those observed values are set explicitly (0.55 × 2, and 1 − 0.93² per frame).
  const hero = new LoaderViewer(heroBox, { deferRenderer: true, autoRotateSpeed: 1.1, dampingFactor: .135, frame: document.querySelector('.exp-hero-frame'), trackMotion: true, autoRotate: !reduced.matches, zoom: false, shadow: true, shadowOpacity: .45, pixelRatio: 1.5, fov: 24, exposure: 1.12, envIntensity: 1.15 });
  const explode = new LoaderViewer(explodeBox, { deferRenderer: true, pinch: true, autoRotate: false, labels: true, shadow: true, shadowOpacity: .4, fov: 28, pixelRatio: 1.25, onSelect: key => { explode.setHover(null); const part = partForGroup(key); openLightbox(part.title, part.photos, 0, explodeBox); } });
  const motion = new LoaderViewer(motionBox, { deferRenderer: true, pinch: true, autoRotate: false, shadow: true, shadowOpacity: .4, fov: 26, pixelRatio: 1.25 });
  const narrowM = matchMedia('(max-width: 700px)').matches;
  if (narrowM) { motion.camera.position.set(-5.4, 2.1, -5.0); motion.camera.fov = 30; motion.camera.updateProjectionMatrix(); motion.controls.target.set(0, .85, -.35); } else { motion.camera.position.set(-4.6, 1.9, -4.3); motion.controls.target.set(0, .95, -.35); }
  motion.controls.minDistance = 3;
  const parts = new LoaderViewer(partsBox, { deferRenderer: true, pinch: true, autoRotate: false, shadow: true, shadowOpacity: .3, fov: 26, ghostOpacity: .07, pixelRatio: 1.25 });
  hero.camera.position.set(3.4, 1.5, -3.9);
  const narrow = matchMedia('(max-width: 700px)').matches;
  if (narrow) explode.camera.position.set(7.4, 3.2, -7.8); else explode.camera.position.set(5.0, 2.4, -5.3);
  explode.controls.target.set(0, narrow ? .3 : .5, 0); explode.controls.maxDistance = 12;
  parts.camera.position.set(3.2, 1.6, -3.6);
  // Finale: the machine drives back in (bindFinaleDrive), then turns freely, in the hero's lighting and angle (the poster underneath is that shot).
  const finaleProduct = document.querySelector('.exp-finale-product');
  const finale = new LoaderViewer(finaleBox, { deferRenderer: true, autoRotateSpeed: 1.1, dampingFactor: .135, autoRotate: !reduced.matches, zoom: false, shadow: true, shadowOpacity: .4, pixelRatio: 1.5, fov: 24, exposure: 1.12, envIntensity: 1.15,
    frame: document.querySelector('.exp-finale-frame'), trackMotion: true, onDraw: on => finaleProduct.classList.toggle('is-3d', on) });
  finale.camera.position.copy(HERO_OFFSET); // rescaled to the hero's size in bindFinaleDrive
  window.__loaderViewers = { hero, explode, parts, motion, finale };

  Promise.all([hero.load(progress), explode.load(), parts.load(), motion.load(), finale.load()]).then(() => {
    setStatus(explodeStatus, '');
    bindHeroDrive(hero); bindExplode(explode); bindParts(parts); bindMotion(motion); bindPaint(finale); bindFinaleDrive(finale, hero);
    manageContexts([hero, explode, parts, motion, finale]);
    hero.wake().then(() => { // poster stays until the model draws; the colour fill finishes first
      const root = document.documentElement, quick = root.classList.contains('hero-pending'); // drawn before the fill began: the poster never showed
      root.classList.remove('hero-pending');
      showLoad(1);
      setTimeout(() => {
        document.querySelector('.exp-hero-product').classList.add('is-3d'); // the poster fades out…
        setTimeout(() => { document.querySelector('.exp-hero').classList.add('is-live'); endLoad(true); }, quick ? 0 : 420); // …then the model fades in, once it has gone
      }, root.classList.contains('hero-loading') ? 500 : 0);
    });
  }).catch(err => { console.error(err); endLoad(false); setStatus(explodeStatus, t('三维模型未能载入，已显示静态图。')); document.body.classList.add('no-webgl'); });

  reduced.addEventListener('change', () => { hero.setAutoRotate(!reduced.matches); finale.setAutoRotate(!reduced.matches && !finale.userInteracted); });
  window.addEventListener('pagehide', () => { hero.dispose(); explode.dispose(); parts.dispose(); motion.dispose(); finale.dispose(); });
}

// Finale arrival, the hero's exit in reverse: over the pinned stage the machine drives in from the top edge in the
// hero's overhead shot, across the big "360 / 460", bucket curled; it stops on the product frame, lowers the bucket and
// the camera comes down to the showcase angle, where the orbit (auto-rotate, dragging) takes over. Scrolling back
// takes the camera from wherever the orbit left it. Progress runs over the pinned span, 0 → 1.
function bindFinaleDrive(viewer, hero) {
  const narrow = matchMedia('(max-width: 700px)');
  const product = document.querySelector('.exp-finale-product'), track = document.querySelector('.exp-finale-track');
  const camera = viewer.camera, target = viewer.controls.target;
  const smooth = t => { t = Math.min(1, Math.max(0, t)); return t * t * (3 - 2 * t); };
  const span = (p, a, b) => Math.min(1, Math.max(0, (p - a) / (b - a)));
  const place = (cam, look, radius, phi, theta) => cam.position.set(Math.sin(phi) * Math.sin(theta), Math.cos(phi), Math.sin(phi) * Math.cos(theta)).multiplyScalar(radius).add(look);
  const orbit = () => { const d = camera.position.clone().sub(target), r = d.length(); return { target: target.clone(), radius: r, phi: Math.acos(d.y / r), theta: Math.atan2(d.x, d.z) }; };
  const corners = () => {
    const box = new Box3().setFromObject(viewer.root), shift = -viewer.root.position.z, out = [];
    for (const x of [box.min.x, box.max.x]) for (const y of [box.min.y, box.max.y]) for (const z of [box.min.z, box.max.z]) out.push(new Vector3(x, y, z + shift));
    return out;
  };
  // The hero's size: pixels per metre at the look-at point, compared through each viewer's own projection (frame
  // composition, aspect) with the camera at the same offset; scale distances by the ratio (it goes as 1 / distance).
  const pixelsPerMetre = (source, look, offset) => {
    const cam = source.camera.clone(); cam.position.copy(look).add(offset); cam.lookAt(look); cam.updateMatrixWorld();
    const a = look.clone().project(cam), b = look.clone().add(new Vector3(1, 0, 0)).project(cam), box = source.container;
    return Math.hypot((b.x - a.x) * box.clientWidth / 2, (b.y - a.y) * box.clientHeight / 2);
  };
  const heroScale = (look, offset) => pixelsPerMetre(viewer, look, offset) / pixelsPerMetre(hero, look, offset);

  // Showcase: the hero's resting shot at the hero's size. On phones the finale frame is narrow, so it is pulled back
  // just enough to keep the whole machine within the screen's width.
  const frameShowcase = () => {
    const offset = HERO_OFFSET.clone().sub(HERO_LOOK), dir = offset.clone().normalize();
    let distance = offset.length() * heroScale(HERO_LOOK, offset);
    const cam = camera.clone(); cam.position.copy(dir).multiplyScalar(distance).add(HERO_LOOK); cam.lookAt(HERO_LOOK); cam.updateMatrixWorld();
    const xs = corners().map(c => c.clone().project(cam).x), width = (Math.max(...xs) - Math.min(...xs)) / 2 * finaleBox.clientWidth;
    if (narrow.matches && width > innerWidth * .92) distance *= width / (innerWidth * .92);
    return { target: HERO_LOOK.clone(), radius: distance, phi: Math.acos(dir.y), theta: Math.atan2(dir.x, dir.z) };
  };
  let showcase = frameShowcase();
  place(camera, showcase.target, showcase.radius, showcase.phi, showcase.theta); target.copy(showcase.target);
  if (reduced.matches || !gsap || !ScrollTrigger) return;

  // The overhead shot (the hero's, at the hero's size) and the drive, measured on a copy of the camera parked there and
  // cached per canvas size: the look-at point slides so the parked machine sits at FINALE_CUE.park of the stage, and the
  // machine starts just past the canvas's top edge. The pinned span is then set from the distance and the hero's pace.
  const stage = document.querySelector('.exp-finale-stage');
  let rest = showcase, held = false, plan = null, takeover = false, blend = null;
  const planDrive = () => {
    const pace = hero.drivePace?.() ?? null;
    const frameBox = viewer.options.frame;
    const key = `${finaleBox.clientWidth}x${finaleBox.clientHeight}:${frameBox.offsetWidth}x${frameBox.offsetHeight}:${narrow.matches}:${pace}`;
    if (plan?.key === key) return plan;
    showcase = frameShowcase(); // the frame sets the scale too; the camera itself takes it on the next arrival
    const top = narrow.matches ? HERO_TOP.narrow : HERO_TOP.wide;
    const radius = top.radius * heroScale(top.target, new Vector3(0, Math.cos(top.phi), -Math.sin(top.phi)).multiplyScalar(top.radius));
    const frame = viewer.options.frame.getBoundingClientRect(), stage = finaleBox.getBoundingClientRect();
    const rise = frame.top + frame.height / 2 - (stage.top + (viewer.viewShift || 0) + stage.height * FINALE_CUE.park); // as laid out, without the view shift
    const metresPerPx = 2 * radius * Math.tan(camera.fov * Math.PI / 360) / frame.height; // screen-up is +Z looking down from the front
    const look = top.target.clone().setZ(top.target.z - rise * metresPerPx);
    const cam = camera.clone(); place(cam, look, radius, top.phi, Math.PI); cam.lookAt(look); cam.updateMatrixWorld();
    const points = corners(), v = new Vector3();
    const hidden = back => points.every(c => { v.set(c.x, c.y, c.z + back).applyMatrix4(cam.matrixWorldInverse); return v.z > -cam.near || v.applyMatrix4(cam.projectionMatrix).y > 1.04; });
    let lo = 0, hi = 16;
    for (let i = 0; i < 24; i++) { const mid = (lo + hi) / 2; if (hidden(mid)) hi = mid; else lo = mid; }
    // Eased out (x = 1 − (1 − d)²), its top speed is at the entry: 2 · start / drive metres per viewport height, set so
    // that on screen (pixels per metre where it comes in) it matches the hero's. (Until the hero's wordmark has loaded
    // its pace is unknown; 2200 px per viewport height is what it measures on a 900 px desktop.)
    const y = advance => new Vector3(0, 0, -advance).project(cam).y * finaleBox.clientHeight / 2;
    const drive = 2 * hi * Math.abs(y(-hi + 1) - y(-hi)) / (pace ?? 2200 * innerHeight / 900);
    const cues = { drive, lower: [drive - FINALE_CUE.lower / 2, drive + FINALE_CUE.lower / 2], camera: [drive, drive + FINALE_CUE.camera] };
    const total = cues.camera[1] + FINALE_CUE.hold; // from setting off (FINALE_CUE.early before the pin) to letting go
    const height = `calc(100svh - var(--header-h) + ${(Math.max(.3, total - FINALE_CUE.early) * 100).toFixed(1)}svh)`;
    if (track.style.height !== height) { track.style.height = height; if (plan) requestAnimationFrame(() => ScrollTrigger.refresh()); } // the span changed after the trigger measured it
    plan = { key, radius, phi: top.phi, look, start: hi, cues, total };
    return plan;
  };

  // Dragging takes over once the machine has stopped and lowered its bucket: the orbit gets the camera from wherever
  // the scroll had it. Scrolling back into the drive hands it back, blending over 0.6 s from where the visitor left it.
  const parked = () => { const { cues, total } = planDrive(); return state.p * total >= cues.lower[1]; };
  finaleBox.addEventListener('pointerdown', () => { // capture phase: before OrbitControls sees the same press
    if (!held || !parked()) return;
    takeover = true; held = false; blend = null;
    viewer.setDrive(0); viewer.setPose(0, 0); viewer.holdCamera(false);
    product.classList.remove('is-driving');
  }, { capture: true });
  const reapply = () => { apply(state.p); if (!blend) gsap.ticker.remove(reapply); };
  const state = { p: 0 };

  const apply = p => { // p: 0 → 1 from setting off to letting go
    const { start, radius, phi, look, cues, total } = planDrive(), vh = p * total;
    stage.classList.toggle('is-driving', vh < cues.lower[1]); // no dragging (and no stolen clicks on the section above) while it drives
    if (p >= 1 || (takeover && vh >= cues.lower[1])) {
      if (held) { viewer.setDrive(0); viewer.setPose(0, 0); place(camera, rest.target, rest.radius, rest.phi, rest.theta); target.copy(rest.target); viewer.holdCamera(false); held = false; }
      product.classList.remove('is-driving');
      return;
    }
    if (!held) { // scrolling back: start from wherever the orbit was
      if (takeover) { blend = { position: camera.position.clone(), target: target.clone(), start: performance.now() }; gsap.ticker.add(reapply); takeover = false; }
      rest = orbit(); viewer.holdCamera(true); held = true;
    }
    product.classList.add('is-driving');
    // 1 · drive in from behind the top edge at the hero's pace, easing to a stop.
    const d = span(vh, 0, cues.drive);
    viewer.setDrive(-start * (1 - d) * (1 - d));
    // 2 · bucket carried curled, lowered as it stops.
    const q = 1 - smooth(span(vh, ...cues.lower));
    viewer.setPose(.12 * q, .55 * q);
    // 3 · camera: overhead front view down to the showcase orbit (shortest way round), over the hero's rise time.
    const k = smooth(span(vh, ...cues.camera));
    if (k === 0 && !blend) rest = showcase; // scrolled back to overhead: the next arrival lands on the showcase shot again
    const turn = ((rest.theta - Math.PI) % (2 * Math.PI) + 3 * Math.PI) % (2 * Math.PI) - Math.PI;
    target.lerpVectors(look, rest.target, k);
    place(camera, target, radius + (rest.radius - radius) * k, phi + (rest.phi - phi) * k, Math.PI + turn * k);
    if (blend) {
      const e = smooth((performance.now() - blend.start) / 600);
      camera.position.lerpVectors(blend.position, camera.position, e); target.lerpVectors(blend.target, target, e);
      if (e >= 1) blend = null;
    }
    viewer.needsRender = true;
  };

  viewer.drivePlan = planDrive; // for inspection
  hero.onPace = planDrive; // the hero's pace is known once its wordmark has loaded: re-plan the span right away
  planDrive(); // sets the pinned span before the trigger measures it
  // It sets off while the stage is still scrolling in (FINALE_CUE.early viewport heights before it pins under the
  // header): meanwhile the canvas is held just under the header, so the machine comes in from the top of the screen,
  // over the end of the section above, rather than out of the stage's moving top edge.
  const header = () => document.querySelector('.exp-header').offsetHeight, early = () => FINALE_CUE.early * innerHeight;
  ScrollTrigger.create({ trigger: track, start: () => `top ${header() + early()}px`, end: () => `top ${header()}px`, invalidateOnRefresh: true,
    onUpdate: self => viewer.setViewShift(Math.round(early() * (1 - self.progress))), onRefresh: self => viewer.setViewShift(Math.round(early() * (1 - self.progress))) });
  // From setting off until the stage lets go. On resize the span is re-planned before the trigger re-measures.
  ScrollTrigger.addEventListener('refreshInit', planDrive);
  gsap.to(state, { p: 1, ease: 'none', onUpdate: () => apply(state.p), scrollTrigger: { trigger: track, start: () => `top ${header() + early()}px`, end: 'bottom bottom', scrub: .8, invalidateOnRefresh: true } });
  apply(0);
  ScrollTrigger.refresh();
}

// Finale colour picker: standard body colours, cross-faded on the paint material only.
function bindPaint(viewer) {
  const swatches = [...document.querySelectorAll('[data-paint]')], name = document.querySelector('#finale-paint-name');
  const pick = button => {
    swatches.forEach(b => b.setAttribute('aria-pressed', String(b === button)));
    name.textContent = button.textContent.trim();
    viewer.setPaint(parseInt(button.dataset.paint, 16));
  };
  swatches.forEach(b => b.addEventListener('click', () => pick(b)));
  document.querySelector('.exp-finale-paint').hidden = false;
}

// Each WebGL context holds its own copy of the model, AO maps, environment and shadow map on the GPU, so only the
// chapters near the viewport keep one: a view wakes within one screen of the viewport and sleeps beyond two and a half.
// The gap between the two is hysteresis, so scrolling back and forth at a boundary doesn't rebuild contexts.
function manageContexts(viewers) {
  for (const viewer of viewers) {
    const wake = viewer === viewers[0] ? () => viewer.wake() : () => requestWake(viewer); // the hero is never held back
    new IntersectionObserver(entries => { if (entries.some(e => e.isIntersecting)) wake(); }, { rootMargin: '100% 0px' }).observe(viewer.container);
    new IntersectionObserver(entries => { if (!entries.some(e => e.isIntersecting)) { wakeGate.pending.delete(viewer); viewer.sleep(); } }, { rootMargin: '250% 0px' }).observe(viewer.container);
  }
}

// Waking a view (new WebGL context, environment prefilter, shader compile, texture uploads) costs frames. The next
// chapter's view comes within a screen during the hero drive, and on phones that work stuttered the drive itself; so
// while the drive plays, wakes wait until it has finished (tow-out done) or the scroll rests. The explode view then
// still has about half a screen to get ready before it reaches the viewport.
const wakeGate = { busy: false, pending: new Set(), timer: 0 };
function requestWake(viewer) { if (wakeGate.busy) wakeGate.pending.add(viewer); else viewer.wake(); }
function releaseWakes() {
  wakeGate.busy = false; clearTimeout(wakeGate.timer);
  for (const viewer of wakeGate.pending) viewer.wake();
  wakeGate.pending.clear();
}
function holdWakes() {
  wakeGate.busy = true; clearTimeout(wakeGate.timer);
  wakeGate.timer = setTimeout(releaseWakes, 350); // the scrub has settled: nothing is moving, so wake now
}

// Hero exit, scrubbed by the hero's scroll range: the camera leaves the turntable orbit and rises to an
// overhead shot from the front, the bucket curls up to carry height, then the machine drives forward — down the
// screen, toward the next chapter — towing the BOB-LIFT wordmark in from the top on a short chain. It eases to a stop
// with the wordmark in the middle while a light sweep crosses the letters, then pulls away and tows it out of frame.
// `centre` is where the machine sits in the stage height once overhead (the resting shot is composed low, on the product frame).
const HERO_TOP = { wide: { radius: 6.2, phi: .2, centre: .52, target: new Vector3(0, .45, -.1) }, narrow: { radius: 6.6, phi: .2, centre: .5, target: new Vector3(0, .45, -.1) } };
// Milestones in viewport heights scrolled from the top of the page. The hero is 240svh, so its stage is pinned until
// 1.4 and has scrolled away by 2.4: the tow-out runs across that hand-off, and the light next chapter rises over the
// wordmark as it leaves instead of an empty dark stage scrolling past.
const HERO_CUE = { hold: .03, overhead: .4, curl: [.25, .4], chainIn: [.34, .44], towIn: [.44, .92], sweep: [.94, 1.16], towOut: [1.16, 1.8] };
function bindHeroDrive(viewer) {
  if (reduced.matches || !gsap || !ScrollTrigger) return;
  const narrow = matchMedia('(max-width: 700px)');
  const product = document.querySelector('.exp-hero-product');
  const camera = viewer.camera, target = viewer.controls.target;
  const smooth = t => { t = Math.min(1, Math.max(0, t)); return t * t * (3 - 2 * t); };
  const span = (p, [a, b]) => Math.min(1, Math.max(0, (p - a) / (b - a)));
  let from = null, shot = null, plan = null, tow = null;
  fetch('assets/logo/boblift.json').then(r => r.json()).then(data => { tow = new LogoTow(viewer, data); plan = null; apply(scrolled()); viewer.onPace?.(); }).catch(err => console.error('BOB-LIFT wordmark not loaded', err));

  // The overhead shot, with its look-at point slid forward so the machine rises from the frame's centre to the stage's.
  const topShot = () => {
    const key = `${heroBox.clientWidth}x${heroBox.clientHeight}:${narrow.matches}`;
    if (shot?.key === key) return shot;
    const base = narrow.matches ? HERO_TOP.narrow : HERO_TOP.wide;
    const frame = viewer.options.frame.getBoundingClientRect(), stage = heroBox.getBoundingClientRect();
    const rise = frame.top + frame.height / 2 - (stage.top + stage.height * base.centre);
    const metresPerPx = 2 * base.radius * Math.tan(camera.fov * Math.PI / 360) / frame.height; // screen-up is +Z when looking down from the front
    shot = { key, ...base, target: base.target.clone().setZ(base.target.z - rise * metresPerPx) };
    return shot;
  };
  const place = (cam, look, radius, phi, theta) => cam.position.set(Math.sin(phi) * Math.sin(theta), Math.cos(phi), Math.sin(phi) * Math.cos(theta)).multiplyScalar(radius).add(look);

  // Everything the drive needs, measured from a copy of the camera parked at the overhead shot (so it doesn't matter
  // which phase the scroll is in) and cached per canvas size: where the wordmark comes to rest (the ground under the
  // stage centre), how big it can be (narrow screens), and how far the machine must go to tow it out past the bottom edge.
  const planDrive = () => {
    const top = topShot();
    if (plan?.key === top.key && plan.tow === !!tow) return plan;
    const cam = camera.clone(); place(cam, top.target, top.radius, top.phi, Math.PI); cam.lookAt(top.target); cam.updateMatrixWorld();
    const ground = (x, y) => { const ray = new Raycaster(); ray.setFromCamera(new Vector2(x, y), cam); return ray.ray.intersectPlane(new Plane(new Vector3(0, 1, 0), 0), new Vector3()); };
    const ndcY = 1 - 2 * top.centre, centre = ground(0, ndcY), width = ground(1, ndcY).distanceTo(ground(-1, ndcY)), bottom = ground(0, -1);
    const box = new Box3().setFromObject(viewer.root), shift = -viewer.root.position.z; // rest position, whatever the current drive
    const corners = [];
    for (const x of [box.min.x, box.max.x]) for (const y of [box.min.y, box.max.y]) for (const z of [box.min.z, box.max.z]) corners.push(new Vector3(x, y, z + shift));
    const v = new Vector3();
    const gone = a => corners.every(c => { v.set(c.x, c.y, c.z - a).applyMatrix4(cam.matrixWorldInverse); return v.z > -cam.near || v.applyMatrix4(cam.projectionMatrix).y < -1.04; });
    let lo = 0, hi = 16;
    for (let i = 0; i < 24; i++) { const mid = (lo + hi) / 2; if (gone(mid)) hi = mid; else lo = mid; }
    const marks = tow ? tow.layout({ scale: Math.min(1, .86 * width / tow.plateWidth), centreZ: centre.z, exitZ: bottom.z }) : null;
    plan = { key: top.key, tow: !!tow, exit: hi, marks };
    return plan;
  };

  // Top speed of the tow-in as seen on screen (smoothstep: 1.5 × the average, halfway), in pixels per viewport height
  // scrolled; the finale drives in at it.
  viewer.drivePace = () => {
    const { marks } = planDrive(); if (!marks) return null;
    const top = topShot(), cam = camera.clone(); place(cam, top.target, top.radius, top.phi, Math.PI); cam.lookAt(top.target); cam.updateMatrixWorld();
    const y = advance => new Vector3(0, 0, -advance).project(cam).y * heroBox.clientHeight / 2, mid = marks.centred / 2;
    return 1.5 * marks.centred / (HERO_CUE.towIn[1] - HERO_CUE.towIn[0]) * Math.abs(y(mid + .5) - y(mid - .5));
  };

  const apply = p => { // p: viewport heights scrolled
    if (p > HERO_CUE.hold && p < HERO_CUE.towOut[1]) holdWakes(); else releaseWakes();
    if (p <= HERO_CUE.hold) {
      if (from) { camera.position.copy(from.position); target.copy(from.target); viewer.setPose(0, 0); viewer.setDrive(0); tow?.setActive(false); viewer.holdCamera(false); from = null; }
      product.classList.remove('is-driving');
      return;
    }
    if (!from) {
      viewer.holdCamera(true);
      const d = camera.position.clone().sub(target), r = d.length();
      from = { position: camera.position.clone(), target: target.clone(), radius: r, phi: Math.acos(d.y / r), theta: Math.atan2(d.x, d.z) };
    }
    product.classList.add('is-driving');
    const top = topShot();
    // 1 · camera: spherical blend to the overhead front view (theta = π looks back along +Z, so forward reads as "down").
    const k = smooth((p - HERO_CUE.hold) / (HERO_CUE.overhead - HERO_CUE.hold));
    const turn = ((Math.PI - from.theta) % (2 * Math.PI) + 3 * Math.PI) % (2 * Math.PI) - Math.PI; // shortest way round
    target.lerpVectors(from.target, top.target, k);
    place(camera, target, from.radius + (top.radius - from.radius) * k, from.phi + (top.phi - from.phi) * k, from.theta + turn * k);
    // 2 · bucket to carry position before moving off.
    const q = smooth(span(p, HERO_CUE.curl));
    viewer.setPose(.12 * q, .55 * q);
    // 3 · drive. With the wordmark: ease in and stop with it centred, hold for the sweep, then pull away and tow it out.
    // Without it (still loading): one run from standstill until the machine is out of frame.
    const { exit, marks } = planDrive();
    let advance;
    if (marks) {
      const a = span(p, HERO_CUE.towIn), b = span(p, HERO_CUE.towOut);
      advance = b > 0 ? marks.centred + (marks.gone - marks.centred) * b * b * (2 - b) : marks.centred * a * a * (3 - 2 * a);
    } else {
      const d = span(p, [HERO_CUE.towIn[0], HERO_CUE.towOut[1]]);
      advance = exit * d * d * (2 - d);
    }
    if (tow && p >= HERO_CUE.chainIn[0]) {
      tow.setActive(true);
      const shift = tow.update(advance);
      // The wordmark starts out of frame (or, on tall screens, in the dark) and comes up to full light as it is pulled in.
      tow.setReveal(smooth(shift / 1.1), smooth(span(p, HERO_CUE.chainIn)));
      tow.setSweep(span(p, HERO_CUE.sweep));
      const machineZ = -advance - .1, logoZ = tow.centreZ;
      viewer.setDrive(advance, (machineZ + logoZ) / 2, Math.max(2.4, Math.abs(machineZ - logoZ) / 2 + 1.8));
    } else {
      tow?.setActive(false);
      viewer.setDrive(advance);
    }
    viewer.needsRender = true;
  };

  // Scrubbed over the hero's whole height, pinned part and scroll-away, and handed to apply() in viewport heights.
  const hero = document.querySelector('.exp-hero'), state = { p: 0 };
  const scrolled = () => state.p * hero.offsetHeight / innerHeight;
  gsap.to(state, { p: 1, ease: 'none', onUpdate: () => apply(scrolled()), scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: .8 } });
}

function bindExplode(viewer) {
  // Scroll drives the explode. The slider and the two buttons take over only until the visitor scrolls on (a tenth
  // of a screen); the view then eases back onto the scroll position. Rotating the model or clicking a part for its
  // photos changes neither, so they never take the explode away from the scroll.
  const setValue = v => { viewer.setExplode(v); explodeRange.value = Math.round(v * 1000); };
  const scrolling = !reduced.matches && !!gsap;
  const state = { value: 0 };
  let manualAt = null, catching = false;
  const catchUp = () => {
    const d = state.value - viewer.explodeValue;
    if (Math.abs(d) < .002) { setValue(state.value); catching = false; gsap.ticker.remove(catchUp); return; }
    setValue(viewer.explodeValue + d * .14);
  };
  const manual = v => { manualAt = scrollY; if (catching) { catching = false; gsap.ticker.remove(catchUp); } setValue(v); };
  explodeRange.disabled = false;
  explodeRange.addEventListener('input', () => manual(explodeRange.value / 1000));
  document.querySelector('#explode-reset').addEventListener('click', () => { manual(0); viewer.setFocus(null); viewer.flyTo(null, { azimuth: 2.4, elevation: 1.15, distanceScale: matchMedia('(max-width: 700px)').matches ? 1.5 : 1.05 }); });
  document.querySelector('#explode-open').addEventListener('click', () => manual(1));
  if (scrolling) {
    gsap.to(state, { value: 1, ease: 'none', scrollTrigger: { trigger: '.exp-explode', start: 'top 60%', end: 'bottom bottom', scrub: .5 }, onUpdate: () => {
      if (manualAt !== null) {
        if (Math.abs(scrollY - manualAt) < innerHeight * .1) return;
        manualAt = null; catching = true; gsap.ticker.add(catchUp);
      }
      if (!catching) setValue(state.value);
    } });
    gsap.from('.exp-explode-copy h2 span', { opacity: .15, y: 22, stagger: .18, ease: 'none', scrollTrigger: { trigger: '.exp-explode', start: 'top 70%', end: 'top 15%', scrub: .5 } });
  } else setValue(1);
}

function bindParts(viewer) {
  const cards = [...document.querySelectorAll('.exp-part-card')];
  const pinned = document.querySelector('.exp-parts-viewer');
  // The card that owns the model is the one crossing this line: 55% down on wide screens (the model sits beside the
  // list); on phones the model is pinned above the list, so the line sits a little below it, in the visible part.
  // Each card keeps the model until the next one reaches the line (its range covers the gap below it).
  const line = () => {
    if (!matchMedia('(max-width: 700px)').matches) return innerHeight * .55;
    const below = pinned.getBoundingClientRect().height + parseFloat(getComputedStyle(pinned).top);
    return below + (innerHeight - below) * .22;
  };
  let current = null;
  const activate = part => {
    if (current === part) return; current = part;
    viewer.setFocus(part.groups); viewer.flyTo(part.groups, part.view);
    cards.forEach(c => c.classList.toggle('is-active', c.dataset.part === part.id));
    document.querySelector('#parts-current').textContent = t(part.title);
  };
  cards.forEach(card => {
    const part = PARTS.find(p => p.id === card.dataset.part);
    card.querySelectorAll('[data-photo-index]').forEach(btn => btn.addEventListener('click', () => openLightbox(part.title, part.photos, +btn.dataset.photoIndex, btn)));
    card.addEventListener('click', e => { if (!e.target.closest('button')) activate(part); });
    if (ScrollTrigger && !reduced.matches) ScrollTrigger.create({ trigger: card, start: () => `top ${line()}px`, end: () => `bottom+=${parseFloat(getComputedStyle(card.parentNode).rowGap) || 0} ${line()}px`, onToggle: self => { if (self.isActive) activate(part); } });
  });
  if (reduced.matches || !ScrollTrigger) activate(PARTS[0]);
  document.querySelector('#parts-all').addEventListener('click', () => { current = null; viewer.setFocus(null); viewer.flyTo(null, { azimuth: 2.4, elevation: 1.15 }); cards.forEach(c => c.classList.remove('is-active')); document.querySelector('#parts-current').textContent = t('整机'); });
}

// The parts list scrolls natively, so a hard flick ran through all eight cards and out of the chapter. Each card is a
// stop now: a wheel turn, a swipe or a page key moves one card, however hard, and the rest of that gesture is absorbed;
// beyond the first or the last card the page scrolls on as usual. A flick that carries into the list from outside is
// caught on the first (or last) card. Only input is steered: in-page links and the tour cancel whatever is in flight
// (window.__partSteps.cancel, called from app.js) and scroll straight through, as do Home and End.
function bindPartSteps() {
  const cards = [...document.querySelectorAll('.exp-part-card')], pinned = document.querySelector('.exp-parts-viewer'), root = document.documentElement;
  // Where a card rests: its top 28% down the screen beside the model; on phones just under the pinned model. Both are
  // above the line that hands the model to it (bindParts).
  const rest = () => matchMedia('(max-width: 700px)').matches ? pinned.getBoundingClientRect().height + parseFloat(getComputedStyle(pinned).top) + 14 : innerHeight * .28;
  const stops = () => { const r = rest(); return cards.map(c => c.getBoundingClientRect().top + scrollY - r); };
  const inside = () => { const s = stops(); return scrollY >= s[0] - 2 && scrollY <= s[s.length - 1] + 2; };
  const nearest = () => stops().reduce((a, v) => Math.abs(v - scrollY) < Math.abs(a - scrollY) ? v : a);
  // A card taller than the room under its resting place (a phone on its side, a very short window) has to be read by
  // scrolling through it, so then the list scrolls natively.
  const fits = () => { const room = innerHeight - rest() - 12; return cards.every(c => c.offsetHeight <= room); };
  const off = () => reduced.matches || document.body.style.overflow === 'hidden' || !fits(); // also: a dialog is open
  // The stop a gesture going dir (1 down, -1 up) by delta px leads to, or null to let the page scroll natively.
  const next = (dir, delta) => {
    const s = stops(), y = scrollY, first = s[0], last = s[s.length - 1];
    if (dir > 0) return y < first - 2 ? (y + delta >= first ? first : null) : s.find(v => v > y + 2) ?? null;
    return y > last + 2 ? (y + delta <= last ? last : null) : [...s].reverse().find(v => v < y - 2) ?? null;
  };
  let tween = null, ours = false, coastUntil = 0;
  const go = (y, duration = .65, ease = 'power2.inOut') => {
    tween?.kill();
    const pos = { y: scrollY };
    tween = gsap.to(pos, { y, duration, ease, onComplete: () => { tween = null; root.style.overflow = ''; },
      onUpdate: () => { if (document.body.style.overflow === 'hidden') cancel(); else scrollTo(0, pos.y); } }); // a dialog opened meanwhile: stop behind it
  };
  const cancel = () => { tween?.kill(); tween = null; root.style.overflow = ''; ours = false; coastUntil = 0; };
  window.__partSteps = { cancel };

  // Wheel: one card per turn. A turn ends after 180 ms without events; a trackpad's momentum tail keeps it going, while
  // a new swipe shows as a delta rising again. A turn that moved a card is absorbed to its end; one that the page
  // scrolled natively stays native (it may start with a 1 px event on the last card), unless it carries into the list.
  let quietAt = 0, lastAbs = 0;
  addEventListener('wheel', e => {
    if (off() || e.ctrlKey || Math.abs(e.deltaX) > Math.abs(e.deltaY)) return;
    const now = performance.now(), delta = e.deltaY * (e.deltaMode ? 40 : 1), abs = Math.abs(delta);
    const fresh = now > quietAt || abs > lastAbs * 1.6 + 4;
    quietAt = now + 180; lastAbs = abs;
    if (tween) { e.preventDefault(); return; }
    if (fresh) ours = false;
    if (ours) { e.preventDefault(); return; }
    const y = fresh || !inside() ? next(Math.sign(delta), delta) : null;
    if (y != null) { e.preventDefault(); ours = true; go(y); }
  }, { passive: false });

  addEventListener('keydown', e => {
    if (e.key === 'Home' || e.key === 'End') { cancel(); return; } // the page's own jump, not pulled back to a card
    if (off() || e.altKey || e.ctrlKey || e.metaKey || /INPUT|TEXTAREA|SELECT|BUTTON/.test(e.target.tagName)) return;
    const dir = e.key === ' ' ? (e.shiftKey ? -1 : 1) : { ArrowDown: 1, PageDown: 1, ArrowUp: -1, PageUp: -1 }[e.key];
    if (!dir) return;
    if (tween) { e.preventDefault(); return; }
    const y = next(dir, dir * (e.key.startsWith('Arrow') ? 40 : innerHeight * .85));
    if (y != null) { e.preventDefault(); go(y); }
  });

  // Touch. A gesture that starts in the list or less than a screen from it is ours from its very first move (a browser
  // may commit to native scrolling on the first move it is not stopped from making), and a new touch stops whatever
  // is still moving. Once it has gone 6 px it is decided:
  // - inside the list, it steps one card once it has gone 30 px;
  // - otherwise the page follows the finger, and a flick glides on after release; heading into the list it stops at
  //   the first (or last) card, and a glide that ends in the list settles on the nearest card.
  // A gesture from further away scrolls natively; if its momentum carries into the list, the momentum is stopped
  // (overflow: hidden, which also halts iOS) and the page settles on the nearest card.
  let touch = null, lastY = scrollY;
  const settle = () => { root.style.overflow = 'hidden'; go(nearest()); };
  const near = () => { const s = stops(); return scrollY > s[0] - innerHeight && scrollY < s[s.length - 1] + innerHeight; };
  addEventListener('touchstart', e => {
    coastUntil = 0;
    const stopped = !!tween; if (stopped) cancel();
    const t = e.touches[0];
    touch = e.touches.length === 1 && !off() ? { x: t.clientX, y: t.clientY, ours: near(), stopped, dir: 0, mode: '', stepped: false, from: 0, edge: null, last: t.clientY, at: 0, v: 0 } : null;
  }, { passive: true });
  addEventListener('touchmove', e => {
    if (!touch) return;
    const t = e.touches[0], dx = t.clientX - touch.x, dy = touch.y - t.clientY; // dy > 0: towards the end of the page
    if (!touch.dir) {
      if (Math.abs(dx) > 6 && Math.abs(dx) > Math.abs(dy)) { touch = null; return; } // sideways: rotating the model
      if (touch.ours && e.cancelable) e.preventDefault();
      if (Math.abs(dy) < 6) return;
      const dir = touch.dir = Math.sign(dy);
      if (!touch.ours) { touch.mode = 'native'; return; }
      const s = stops(), edge = dir > 0 ? s[0] : s[s.length - 1];
      touch.mode = inside() && next(dir, 0) != null ? 'step' : 'drag';
      if (dir * (edge - scrollY) > 2) touch.edge = edge; // heading into the list: that card is as far as it goes
      touch.from = scrollY - dy;
    }
    if (touch.mode === 'native') return;
    if (e.cancelable) e.preventDefault();
    if (touch.mode === 'drag') {
      const now = performance.now(), y = touch.from + dy;
      if (touch.at) touch.v = .8 * (touch.last - t.clientY) / Math.max(1, now - touch.at) + .2 * touch.v; // px/ms, page direction
      touch.last = t.clientY; touch.at = now;
      scrollTo(0, touch.edge == null ? y : touch.dir > 0 ? Math.min(touch.edge, y) : Math.max(touch.edge, y));
      return;
    }
    if (!touch.stepped && !tween && Math.abs(dy) > 30) { touch.stepped = true; const y = next(touch.dir, 0); if (y != null) go(y); }
  }, { passive: false });
  const release = () => {
    if (touch?.mode === 'drag') {
      const v = performance.now() - touch.at < 80 ? touch.v : 0; // a flick glides on
      let to = scrollY + v * 320;
      if (touch.edge != null) to = touch.dir > 0 ? Math.min(touch.edge, to) : Math.max(touch.edge, to);
      const s = stops();
      if (to > s[0] - 2 && to < s[s.length - 1] + 2) to = s.reduce((a, b) => Math.abs(b - to) < Math.abs(a - to) ? b : a); // ends in the list: on a card
      if (Math.abs(to - scrollY) > 2) go(to, .7, 'power3.out');
    } else if (touch?.mode === 'native' && !off()) { if (inside()) settle(); else coastUntil = performance.now() + 2500; }
    else if (touch && !touch.dir && touch.stopped && inside()) settle(); // a tap that stopped a step between two cards
    touch = null;
  };
  addEventListener('touchend', release, { passive: true });
  addEventListener('touchcancel', release, { passive: true });
  addEventListener('scroll', () => {
    const y = scrollY, prev = lastY; lastY = y;
    if (tween || performance.now() > coastUntil || off()) return;
    const s = stops(), first = s[0] - 2, last = s[s.length - 1] + 2;
    if ((prev < first && y >= first) || (prev > last && y <= last)) { coastUntil = 0; settle(); }
  }, { passive: true });
}

// Lift → dump → curl back and lower. Progress 0…1 is driven by scroll, the slider or the play button.
export function poseAt(p) {
  const e = t => t < .5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
  if (p < .38) return { lift: e(p / .38), tilt: 0, phase: 0 };
  if (p < .66) return { lift: 1, tilt: -e((p - .38) / .28), phase: 1 };
  const k = e((p - .66) / .34);
  return { lift: 1 - k, tilt: -1 + k, phase: 2 };
}
// TODO(性能·待讨论 2026-09-23): 机械动作章节可以像应用场景那样改为预渲染（随滚动逐帧播放），代价是去掉拖动换视角；
// 用户要求本轮保持不变，之后再讨论。见 README「待讨论」。
function bindMotion(viewer) {
  const range = document.querySelector('#motion-range'), play = document.querySelector('#motion-play');
  const points = [...document.querySelectorAll('.exp-motion-points li')];
  let scrollDriven = !reduced.matches && !!gsap, playing = null;
  const apply = p => { const { lift, tilt, phase } = poseAt(p); viewer.setPose(lift, tilt); range.value = Math.round(p * 1000); points.forEach((li, i) => li.classList.toggle('is-current', i === phase)); };
  range.disabled = false;
  range.addEventListener('input', () => { stop(); scrollDriven = false; apply(range.value / 1000); });
  const stop = () => { playing?.kill(); playing = null; play.setAttribute('aria-pressed', 'false'); play.querySelector('span:last-child').textContent = t('播放动作'); };
  play.addEventListener('click', () => {
    if (playing) { stop(); return; }
    scrollDriven = false;
    const state = { p: range.value >= 990 ? 0 : range.value / 1000 };
    play.setAttribute('aria-pressed', 'true'); play.querySelector('span:last-child').textContent = t('暂停动作');
    playing = gsap ? gsap.to(state, { p: 1, duration: 7 * (1 - state.p), ease: 'none', onUpdate: () => apply(state.p), onComplete: stop }) : null;
    if (!playing) apply(1);
  });
  if (scrollDriven) {
    const state = { value: 0 };
    gsap.to(state, { value: 1, ease: 'none', onUpdate: () => { if (scrollDriven) apply(state.value); }, scrollTrigger: { trigger: '.exp-motion', start: 'top 55%', end: 'bottom bottom', scrub: .5, onLeave: () => { scrollDriven = true; }, onLeaveBack: () => { scrollDriven = true; } } });
  } else apply(0);
  window.addEventListener('wheel', stop, { passive: true });
}

// Three working contexts. Each scene has a camera, a rig pose and a backdrop tint; copy is in the HTML.
const ease = t => t < .5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2;
const cycle = (t, period) => ((t % period) + period) % period / period;
// Three working contexts. Each scene has a camera, a resting pose, a looping work cycle and a backdrop.
export // Hydraulic sequences: each step is [seconds, target values]; ramps ease in and out like a valve opening and closing,
// Equal-value steps are dwells; advance drives the chassis and rigid tread loops together.
const smooth = t => t * t * (3 - 2 * t);
const seq = (start, steps) => {
  const total = steps.reduce((a, s) => a + s[0], 0);
  const fn = t => {
    let u = t % total, from = start;
    for (const [dur, to] of steps) {
      if (u <= dur) { const k = smooth(u / dur), r = {}; for (const key in from) r[key] = from[key] + ((to[key] ?? from[key]) - from[key]) * k; return r; }
      u -= dur; from = { ...from, ...to };
    }
    return from;
  };
  fn.period = total;
  return fn;
};
const SCENES = [
  { id: 'garden', pose: { lift: .15, tilt: -.3, load: 0, advance: 0 }, cam: { azimuth: 1.9, xFrac: .63, feetFrac: .8 },
    grade: { exposure: 1.0, envIntensity: 1.0, sunIntensity: 1.1, sunColor: 0xf2f0ec, hemiIntensity: 1.0, skyColor: 0xd6dbe2, groundColor: 0x5c4d3e, shadowOpacity: .5, shadowColor: 0x1a1410, contactOpacity: .55, payloadColor: 0x4a3b2c, dust: .8, dustColor: 0x6b5a48 },
    env: { skyColor: '#c6ccd3', groundColor: '#5f5040' },
    // Grading: blade on the ground, drive in as the bucket fills and curls, lift, back out, tip, back to the blade.
    loop: seq({ lift: .15, tilt: -.3, load: 0, advance: 0 }, [[1.6, {}], [3.4, { advance: .7, load: .6, tilt: .05 }], [1.4, { tilt: .5, load: .75 }], [2.0, { lift: .35 }], [3.0, { advance: 0 }], [1.0, {}], [1.6, { tilt: -.5, load: 0 }], [1.2, {}], [1.6, { tilt: -.3, lift: .15 }]]) },
  { id: 'site', pose: { lift: .15, tilt: -.25, load: 0, advance: 0 }, cam: { azimuth: 2.4, xFrac: .62, feetFrac: .8 },
    grade: { exposure: 1.06, envIntensity: 1.05, sunIntensity: 1.4, sunColor: 0xf5f3ee, hemiIntensity: .95, skyColor: 0xdadde2, groundColor: 0x9a938a, shadowOpacity: .42, shadowColor: 0x1c1c1e, contactOpacity: .5, payloadColor: 0x8d8780, dust: .9, dustColor: 0xa89e90 },
    env: { skyColor: '#cfd3d8', groundColor: '#9b9184' },
    // Loading: dig into the heap, curl, lift high while driving up to the truck, tip, reverse and lower.
    loop: seq({ lift: .15, tilt: -.25, load: 0, advance: 0 }, [[1.4, {}], [3.0, { advance: .6, load: .85, tilt: .1 }], [1.2, { tilt: .4 }], [3.4, { lift: .95, advance: 1.0 }], [.8, {}], [1.8, { tilt: -.7, load: 0 }], [1.2, {}], [1.2, { tilt: .1 }], [3.4, { lift: .15, advance: 0 }], [1.0, { tilt: -.25 }]]) },
  { id: 'farm', pose: { lift: .15, tilt: -.2, load: 0, advance: 0 }, cam: { azimuth: 2.05, xFrac: .62, feetFrac: .8 },
    grade: { exposure: 1.03, envIntensity: 1.0, sunIntensity: 1.5, sunColor: 0xf7f1e4, hemiIntensity: .95, skyColor: 0xd9dce0, groundColor: 0xa38d64, shadowOpacity: .4, shadowColor: 0x3a2a14, contactOpacity: .5, payloadColor: 0xc9a75c, dust: .7, dustColor: 0xb59c6a },
    env: { skyColor: '#cdd1d6', groundColor: '#b39a68' },
    // Bedding: push into the straw, scoop a full bucket, carry it forward, tip it out gently, reverse to the pile.
    loop: seq({ lift: .15, tilt: -.2, load: 0, advance: 0 }, [[1.8, {}], [3.0, { advance: .6, load: 1, tilt: .5 }], [1.2, { tilt: .9, lift: .3 }], [3.0, { advance: 1.2 }], [1.6, { lift: .55 }], [1.6, { tilt: .25, load: .3 }], [1.2, { tilt: -.3, load: 0 }], [1.0, {}], [1.2, { tilt: .2 }], [4.0, { advance: 0, lift: .15 }], [1.0, { tilt: -.2 }]]) },
];
// The plate is 3:2, cover-fitted at 50% 62%, horizon at 55% of its height, roughly a 40° vertical field of view.
// Phones show the plate bottom-aligned at 140% height (see viewer.css), which lifts the horizon above the stacked copy.
function placeSceneCamera(viewer, stage, scene, duration) {
  const W = stage.clientWidth, H = stage.clientHeight, phone = innerWidth <= 700;
  const rh = phone ? 1.4 * H : Math.max(W / 2400, H / 1600) * 1600, off = phone ? H - rh : (H - rh) * .62;
  const frame = { horizonFrac: (off + .55 * rh) / H, vfov: 2 * Math.atan(Math.tan(20 * Math.PI / 180) * H / rh) * 180 / Math.PI, ...(phone ? { xFrac: .3, feetFrac: .55, height: 1.25 } : { height: 1.2 }) };
  viewer.placeCamera({ ...frame, ...scene.cam, ...(phone ? { xFrac: frame.xFrac, feetFrac: frame.feetFrac } : {}), duration });
}

// Recording mode: plate, machine and payload for one scene, advanced by an explicit clock. Each loop is recorded on
// its second pass, so material dumped at the end of the first pass is still settling at the start: the clip loops seamlessly.
function bindSceneCapture(viewer) {
  document.body.classList.add('is-scene-capture');
  const stage = document.querySelector('.exp-scenes-stage');
  const plates = [...document.querySelectorAll('.exp-scene-plate')];
  let now = 0;
  viewer.clock = () => now; viewer.manual = true;
  Promise.all(plates.map(pl => new Promise((resolve, reject) => {
    const img = new Image(); img.onload = () => resolve([pl.dataset.plate, img]); img.onerror = reject; img.src = `assets/scenes/${pl.dataset.plate}.webp`;
    pl.style.backgroundImage = `url(assets/scenes/${pl.dataset.plate}.webp)`;
  }))).then(pairs => {
    const images = Object.fromEntries(pairs);
    window.__sceneCapture = {
      scenes: SCENES.map(s => ({ id: s.id, period: s.loop.period })),
      begin(i) {
        const scene = SCENES[i];
        plates.forEach(pl => pl.classList.toggle('is-on', pl.dataset.plate === scene.id));
        stage.dataset.scene = scene.id;
        now = 0; viewer.setLoop(null); viewer.payloadTime = undefined; viewer.gradeTween = viewer.poseTween = null;
        viewer.applyGrade({ ...viewer.currentGrade(), ...scene.grade });
        viewer.setEnvironmentFromImage(images[scene.id], scene.env);
        placeSceneCamera(viewer, stage, scene, 0);
        viewer.setLoop(scene.loop);
        viewer.step();
        return scene.loop.period;
      },
      /** Advance to `seconds` into the loop (monotonic, in frame steps) and render. */
      at(seconds) { now = seconds * 1000; viewer.needsRender = true; viewer.step(); },
    };
  });
}

// Scenes chapter: one pre-rendered loop per scene, all played through a single <video>. A scene change dips the
// clip to the page's light grey (0.42 s), swaps the source while fully covered, and lifts the veil (0.6 s) once the
// new clip has drawn its first frame, so only one scene is ever on screen: a cross-fade of two different shots read
// as a double exposure (two machines), and swapping a visible still showed half-loaded images as a hard seam.
// One decoder only: with several <video>s Chrome was seen dropping the whole page to 30 fps for the rest of the
// visit, and phones limit concurrent decoders anyway.
// Loading and playing are separate: the clip loads (and rests on its first frame) while the chapter is within
// ~60% of a screen, and plays from its first frame only once the chapter is actually on screen and the tab is visible.
// Phones get the portrait renders; crossing the 700 px breakpoint re-settles the current scene. Reduced motion shows
// the posters and switches without the dip.
function bindSceneVideos() {
  const section = document.querySelector('.exp-scenes');
  const stage = document.querySelector('.exp-scenes-stage');
  const cards = [...document.querySelectorAll('.exp-scene-card')];
  const tabs = [...document.querySelectorAll('.exp-scene-tab')];
  const video = document.querySelector('.exp-scene-video'), veil = document.querySelector('.exp-scene-veil');
  const phone = matchMedia('(max-width: 700px)');
  const base = id => `assets/scenes/video/${id}-${phone.matches ? 'mobile' : 'desktop'}`;
  let current = -1, near = false, onScreen = false, token = 0, dipTimer = 0, liftTimer = 0;
  const prefetched = new Set(), posters = [];
  const prefetch = id => { const href = `${base(id)}.mp4`; if (prefetched.has(href)) return; prefetched.add(href); const link = document.createElement('link'); link.rel = 'prefetch'; link.as = 'video'; link.href = href; document.head.append(link); };
  const warmPosters = () => SCENES.forEach(s => { const img = new Image(); img.src = `${base(s.id)}.webp`; posters.push(img); }); // a swap under the veil never waits on a download
  const canLoad = () => near && !reduced.matches;
  const canPlay = () => onScreen && !document.hidden && !reduced.matches;
  const lift = () => { clearTimeout(liftTimer); veil.classList.add('is-out'); veil.classList.remove('is-on'); };
  // Put scene `id` on the video (poster, and source when allowed) at its first frame, and play it if it may play.
  // `ready` runs once the new picture is showing: the first decoded frame, the poster when not playing, or after 0.8 s.
  const show = (id, { fromStart = true, ready = () => {} } = {}) => {
    const src = `${base(id)}.mp4`;
    if (near) video.poster = `${base(id)}.webp`; // not before the chapter is close: the poster is 70–170 KB the opening does not need
    if (!canLoad()) { if (video.dataset.src) { video.pause(); video.removeAttribute('src'); delete video.dataset.src; video.load(); } ready(); return; }
    if (video.dataset.src !== src) { video.dataset.src = src; video.preload = 'auto'; video.src = src; } else if (fromStart) video.currentTime = 0;
    if (!canPlay()) { video.pause(); ready(); return; } // resting on the first frame, which is the poster
    const mine = token;
    let done = false; const once = () => { if (done || mine !== token) return; done = true; ready(); };
    if (video.requestVideoFrameCallback) video.requestVideoFrameCallback(once); else video.addEventListener('playing', once, { once: true });
    clearTimeout(liftTimer); liftTimer = setTimeout(once, 800);
    video.play().catch(() => {});
    const next = SCENES[current + 1]; if (next) prefetch(next.id); // the next scene is usually the next one scrolled to
  };
  const activate = i => {
    if (current === i) return;
    const first = current < 0; current = i; const mine = ++token; clearTimeout(dipTimer);
    const id = SCENES[i].id;
    cards.forEach((c, k) => c.classList.toggle('is-active', k === i));
    tabs.forEach((t, k) => t.setAttribute('aria-selected', String(k === i)));
    stage.dataset.scene = id;
    if (first || !canPlay()) { lift(); show(id); return; } // nothing moving on screen: switch directly
    veil.classList.remove('is-out'); veil.classList.add('is-on');
    dipTimer = setTimeout(() => { if (mine === token) show(id, { ready: () => { if (mine === token) lift(); } }); }, 420);
  };
  // Visibility or variant changed: settle the current scene again (from its first frame when it comes on screen),
  // cancelling any dip in flight.
  const settle = ({ restart }) => {
    if (current < 0) return;
    token++; clearTimeout(dipTimer); lift();
    show(SCENES[current].id, { fromStart: restart });
  };
  phone.addEventListener('change', () => { warmPosters(); settle({ restart: true }); });
  document.addEventListener('visibilitychange', () => { if (document.hidden) video.pause(); else if (canPlay()) video.play().catch(() => {}); });
  new IntersectionObserver(entries => { const was = near; near = entries.some(e => e.isIntersecting); if (near && !was) warmPosters(); if (near !== was) settle({ restart: true }); }, { rootMargin: '60% 0px' }).observe(stage);
  new IntersectionObserver(entries => { const was = onScreen; onScreen = entries.some(e => e.isIntersecting); if (onScreen !== was) settle({ restart: onScreen }); }, { threshold: .2 }).observe(stage);
  const scrollToScene = i => { const y = section.offsetTop + (i + .35) / SCENES.length * (section.offsetHeight - innerHeight); window.scrollTo({ top: y, behavior: reduced.matches ? 'auto' : 'smooth' }); };
  tabs.forEach((t, k) => t.addEventListener('click', () => { if (ScrollTrigger && !reduced.matches) scrollToScene(k); else activate(k); }));
  if (ScrollTrigger && !reduced.matches) {
    SCENES.forEach((_, i) => ScrollTrigger.create({ trigger: section, start: () => `top+=${(section.offsetHeight - innerHeight) * i / SCENES.length} top`, end: () => `top+=${(section.offsetHeight - innerHeight) * (i + 1) / SCENES.length} top`, onToggle: self => { if (self.isActive) activate(i); } }));
  }
  activate(0);
}
if (!SCENE_CAPTURE) bindSceneVideos();
