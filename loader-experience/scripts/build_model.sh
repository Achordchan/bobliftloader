#!/bin/sh
# Rebuild assets/model/loader.glb from the Blender CAD project.
# 1) Blender joins each assembly collection into one object and exports GLB.
# 2) gltf-transform applies meshopt compression (needs: npm i @gltf-transform/cli).
set -e
ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
TMP="${TMPDIR:-/tmp}/loader-groups.glb"
/Applications/Blender.app/Contents/MacOS/Blender -b "$ROOT/loader-cad/loader-cad-restored.blend" \
  --python "$ROOT/loader-experience/scripts/export_glb.py" -- "$TMP"
npx --yes @gltf-transform/cli optimize "$TMP" "$ROOT/loader-experience/assets/model/loader.glb" \
  --compress meshopt --texture-compress false --simplify false --join false --flatten false --instance false --palette false --prune-attributes false
cp "$TMP.json" "$ROOT/loader-experience/assets/model/loader.json"
mkdir -p "$ROOT/loader-experience/assets/model/ao"
# Baked at 2048/1024 for quality, shipped at half size: renders compare at 54–61 dB PSNR (see README, 2026-09-23).
for f in "$(dirname "$TMP")/ao"/*.png; do magick "$f" -resize 50% -quality 90 "$ROOT/loader-experience/assets/model/ao/$(basename "$f" .png).webp"; done
# The page loads the gzipped copy (viewer.js fetchPacked); keep it in step with the .glb.
gzip -9kfn "$ROOT/loader-experience/assets/model/loader.glb"
ls -la "$ROOT/loader-experience/assets/model/loader.glb"*
