// Build the upload folder for static hosting (EdgeOne Pages): the page and its assets only — no design sources,
// scripts, tests or notes — with JavaScript and CSS minified by esbuild (import paths and ?v= stamps unchanged).
// Usage: node scripts/build_dist.mjs [outDir]   (default: ../deploy/bl360 next to this project)
import { cpSync, existsSync, mkdirSync, readdirSync, readFileSync, rmSync, statSync } from 'node:fs';
import { gunzipSync } from 'node:zlib';
import { dirname, join, relative } from 'node:path';
import { fileURLToPath } from 'node:url';
import { spawnSync } from 'node:child_process';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const OUT = process.argv[2] || join(ROOT, '..', 'deploy', 'bl360');
const SKIP = new Set(['design', 'scripts', 'tests', 'README.md', '.DS_Store', '__pycache__', 'edgeone.json',
  'motion']); // assets/motion: the old pre-rendered clips, replaced by the live model (kept in the source for now)

// The .gz copies must match what they were made from, or the page would load a stale model.
for (const f of ['assets/model/loader.glb', 'assets/env/sky.hdr']) {
  const src = join(ROOT, f), gz = src + '.gz';
  if (!existsSync(gz) || !gunzipSync(readFileSync(gz)).equals(readFileSync(src))) throw new Error(`${f}.gz is missing or out of date: run gzip -9kfn ${f}`);
}

rmSync(OUT, { recursive: true, force: true }); mkdirSync(OUT, { recursive: true });
const minify = [];
(function walk(dir) {
  for (const name of readdirSync(join(ROOT, dir))) {
    if (SKIP.has(name) || name.startsWith('.')) continue;
    const rel = join(dir, name), st = statSync(join(ROOT, rel));
    if (st.isDirectory()) { walk(rel); continue; }
    if (/\.(js|css)$/.test(name) && !/\.min\.js$/.test(name)) minify.push(rel);
    else { mkdirSync(join(OUT, dir), { recursive: true }); cpSync(join(ROOT, rel), join(OUT, rel)); }
  }
})('');
// Hosting config (headers, caching) sits at the root of the upload.
if (existsSync(join(ROOT, 'edgeone.json'))) cpSync(join(ROOT, 'edgeone.json'), join(OUT, 'edgeone.json'));

// One esbuild run, no bundling: each file is minified on its own, so modules and classic scripts keep their form.
const run = spawnSync('npx', ['--yes', 'esbuild@0.25', ...minify, '--minify', '--charset=utf8', '--log-level=warning', `--outdir=${OUT}`, `--outbase=${ROOT}`], { cwd: ROOT, stdio: 'inherit' });
if (run.status !== 0) process.exit(run.status ?? 1);

let files = 0, bytes = 0;
(function count(dir) { for (const n of readdirSync(dir)) { const p = join(dir, n), st = statSync(p); if (st.isDirectory()) count(p); else { files++; bytes += st.size; } } })(OUT);
console.log(`${relative(process.cwd(), OUT) || OUT}: ${files} files, ${(bytes / 1e6).toFixed(1)} MB (${minify.length} minified)`);
