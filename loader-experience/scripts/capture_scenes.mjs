// Record the scenes chapter's three work loops to video from the real-time scene (explore.js, `?capture-scenes`).
// Each loop is stepped on a virtual clock one frame at a time, so a slow machine never drops frames, and recorded on
// its second pass so material still settling from the first pass makes the clip loop seamlessly.
//
//   node loader-experience/scripts/capture_scenes.mjs [--only desktop|mobile] [--scene garden|site|farm] [--frames <dir>] [--encode-only]
//
// Needs the preview server (scripts/serve.py, port 4175), Google Chrome, ffmpeg and cwebp, and playwright-core
// (resolved normally, or set PLAYWRIGHT_CORE to its directory). Writes assets/scenes/video/<scene>-<variant>.{mp4,webp}.
import { spawnSync } from 'node:child_process';
import { mkdirSync, rmSync, writeFileSync, statSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, resolve, dirname } from 'node:path';
import { fileURLToPath, pathToFileURL } from 'node:url';

const ROOT = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const OUT = join(ROOT, 'assets/scenes/video');
const BASE = process.env.SCENE_URL || 'http://127.0.0.1:4175/';
const CHROME = process.env.CHROME || '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
const FPS = 30;
// Stage shapes: a 1440×900-class desktop viewport minus the header (16:9.2), and a 390×844 phone minus its header.
const VARIANTS = {
  desktop: { viewport: { width: 1600, height: 920 }, deviceScaleFactor: 1.2, crf: 28 },
  mobile: { viewport: { width: 390, height: 782 }, deviceScaleFactor: 2, isMobile: true, hasTouch: true, crf: 29 },
};

const args = process.argv.slice(2), opt = name => { const i = args.indexOf(name); return i >= 0 ? args[i + 1] : null; };
const only = opt('--only'), onlyScene = opt('--scene'), encodeOnly = args.includes('--encode-only');
const FRAMES = resolve(opt('--frames') || join(tmpdir(), 'boblift-scene-frames'));

const run = (cmd, argv) => { const r = spawnSync(cmd, argv, { stdio: ['ignore', 'inherit', 'inherit'] }); if (r.status !== 0) throw new Error(`${cmd} failed (${r.status})`); };

async function capture() {
  const core = process.env.PLAYWRIGHT_CORE ? await import(pathToFileURL(join(process.env.PLAYWRIGHT_CORE, 'index.mjs'))) : await import('playwright-core');
  const browser = await core.chromium.launch({ executablePath: CHROME, headless: true, args: ['--enable-gpu', '--ignore-gpu-blocklist', '--use-angle=metal', '--hide-scrollbars'] });
  try {
    for (const [variant, v] of Object.entries(VARIANTS)) {
      if (only && only !== variant) continue;
      const context = await browser.newContext({ viewport: v.viewport, deviceScaleFactor: v.deviceScaleFactor, isMobile: !!v.isMobile, hasTouch: !!v.hasTouch, reducedMotion: 'no-preference' });
      const page = await context.newPage();
      page.on('console', m => { if (m.type() === 'error') console.error('[page]', m.text()); });
      await page.goto(new URL('?capture-scenes', BASE).href);
      await page.waitForFunction(() => window.__sceneCapture, null, { timeout: 180000 });
      const gl = await page.evaluate(() => { const c = document.createElement('canvas').getContext('webgl2'); const d = c?.getExtension('WEBGL_debug_renderer_info'); return d ? c.getParameter(d.UNMASKED_RENDERER_WEBGL) : 'unknown'; });
      console.log(`${variant}: ${v.viewport.width}×${v.viewport.height} @${v.deviceScaleFactor}x, WebGL ${gl}`);
      const scenes = await page.evaluate(() => window.__sceneCapture.scenes);
      for (const [i, scene] of scenes.entries()) {
        if (onlyScene && onlyScene !== scene.id) continue;
        const dir = join(FRAMES, variant, scene.id);
        rmSync(dir, { recursive: true, force: true }); mkdirSync(dir, { recursive: true });
        const period = await page.evaluate(k => window.__sceneCapture.begin(k), i);
        const frames = Math.round(period * FPS);
        await page.evaluate(([n, fps]) => { for (let f = 0; f < n; f++) window.__sceneCapture.at(f / fps); }, [frames, FPS]); // first pass: settle
        const started = Date.now();
        for (let f = 0; f < frames; f++) {
          await page.evaluate(t => new Promise(r => { window.__sceneCapture.at(t); requestAnimationFrame(() => requestAnimationFrame(r)); }), period + f / FPS);
          writeFileSync(join(dir, `${String(f).padStart(4, '0')}.jpg`), await page.screenshot({ type: 'jpeg', quality: 95 }));
          if (f % 60 === 0) process.stdout.write(`  ${scene.id} ${f}/${frames}\r`);
        }
        console.log(`  ${scene.id}: ${frames} frames (${period}s) in ${((Date.now() - started) / 1000).toFixed(0)}s`);
      }
      await context.close();
    }
  } finally { await browser.close(); }
}

function encode() {
  mkdirSync(OUT, { recursive: true });
  for (const [variant, v] of Object.entries(VARIANTS)) {
    if (only && only !== variant) continue;
    for (const scene of ['garden', 'site', 'farm']) {
      if (onlyScene && onlyScene !== scene) continue;
      const dir = join(FRAMES, variant, scene), mp4 = join(OUT, `${scene}-${variant}.mp4`), poster = join(OUT, `${scene}-${variant}.webp`);
      // H.264 High, BT.709 limited range (what browsers assume), keyframe every 2 s, moov atom first so playback starts while loading.
      run('ffmpeg', ['-y', '-loglevel', 'error', '-framerate', String(FPS), '-i', join(dir, '%04d.jpg'),
        '-vf', 'scale=trunc(iw/2)*2:trunc(ih/2)*2:out_color_matrix=bt709:out_range=tv,format=yuv420p',
        '-c:v', 'libx264', '-preset', 'slow', '-crf', String(v.crf), '-profile:v', 'high', '-g', String(FPS * 2),
        '-colorspace', 'bt709', '-color_primaries', 'bt709', '-color_trc', 'bt709', '-color_range', 'tv',
        '-movflags', '+faststart', '-an', mp4]);
      run('cwebp', ['-quiet', '-q', '80', join(dir, '0000.jpg'), '-o', poster]); // first frame = the loop's first frame
      console.log(`${scene}-${variant}: ${(statSync(mp4).size / 1e6).toFixed(2)} MB video, ${(statSync(poster).size / 1e3).toFixed(0)} KB poster`);
    }
  }
}

if (!encodeOnly) await capture();
encode();
