"""Render a saved project without rebuilding or saving over the user's scene."""
import argparse
from pathlib import Path
import sys
import bpy

ROOT=Path(__file__).resolve().parent.parent
argv=sys.argv[sys.argv.index('--')+1:] if '--' in sys.argv else []
p=argparse.ArgumentParser()
p.add_argument('--camera',choices=['hero','side','detail'],default='hero')
p.add_argument('--preview',action='store_true')
p.add_argument('--output',type=Path)
a=p.parse_args(argv)
scene=bpy.context.scene
names={'hero':'相机 · 整机三分之四','side':'相机 · 低角度侧面','detail':'相机 · 机罩与液压细节'}
scene.camera=bpy.data.objects[names[a.camera]]
scene.render.engine='CYCLES';scene.cycles.device='GPU'
prefs=bpy.context.preferences.addons['cycles'].preferences
prefs.compute_device_type='METAL';prefs.get_devices()
for d in prefs.devices:d.use=d.type=='METAL'
scene.cycles.samples=24 if a.preview else 160
scene.cycles.adaptive_threshold=.065 if a.preview else .018
scene.render.resolution_x=900 if a.preview else 1800
scene.render.resolution_y=800 if a.preview else 1600
scene.render.resolution_percentage=100
scene.render.filepath=str((a.output or ROOT/'renders'/f'{a.camera}{"-preview" if a.preview else ""}.png').resolve())
bpy.ops.render.render(write_still=True)
print('RENDER_SAVED='+scene.render.filepath,flush=True)
