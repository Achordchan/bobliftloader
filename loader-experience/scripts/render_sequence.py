"""Render the repaired product into a transparent scroll-controlled camera sequence."""
import json
import math
from pathlib import Path
import sys
import time
import subprocess
import bpy
from mathutils import Vector
ROOT=Path(__file__).resolve().parents[1]
CAD=ROOT.parent/'loader-cad'
sys.path.insert(0,str(CAD/'scripts'))
from footplate import validate_treads
validate_treads()
scene=bpy.context.scene
floor=bpy.data.objects.get('摄影棚地面')
if floor: floor.is_shadow_catcher=True
scene.render.film_transparent=True
scene.render.image_settings.file_format='PNG'
scene.render.image_settings.color_mode='RGBA'
scene.render.resolution_x=1080
scene.render.resolution_y=1080
scene.render.resolution_percentage=100
scene.cycles.samples=20
scene.cycles.use_denoising=True
scene.cycles.use_adaptive_sampling=True
scene.cycles.adaptive_threshold=.07
scene.cycles.max_bounces=6
scene.render.use_persistent_data=True
prefs=bpy.context.preferences.addons['cycles'].preferences
prefs.compute_device_type='METAL';prefs.get_devices()
for device in prefs.devices:device.use=device.type=='METAL'
scene.cycles.device='GPU'
cam=scene.camera
cam.data.type='ORTHO'
raw=ROOT/'design'/'rendered-frames';raw.mkdir(exist_ok=True)
# One continuous camera path: front, chassis side, operator console, front again.
# The target elevation and orthographic scale change smoothly at each chapter.
keys=[(0,38,2.3,(0,.1,.68),2.85),(.32,100,1.4,(0,.04,.46),2.15),(.66,185,2.4,(0,-.36,1.02),1.5),(1,398,2.3,(0,.1,.68),2.85)]
def shot(t):
    for a,b in zip(keys,keys[1:]):
        if t<=b[0]:
            u=(t-a[0])/(b[0]-a[0]);u=u*u*(3-2*u)
            angle=math.radians(a[1]+(b[1]-a[1])*u)
            z=a[2]+(b[2]-a[2])*u
            target=Vector(a[3]).lerp(Vector(b[3]),u)
            cam.location=(4.8*math.sin(angle),4.8*math.cos(angle),z)
            cam.rotation_euler=(target-cam.location).to_track_quat('-Z','Y').to_euler()
            cam.data.ortho_scale=a[4]+(b[4]-a[4])*u
            return
frames=120
args=sys.argv[sys.argv.index('--')+1:] if '--' in sys.argv else []
indices=[int(x) for x in args] if args else list(range(frames))
start=time.monotonic()
for i in indices:
    target=raw/f'{i:04d}.png'
    encoded=ROOT/'assets'/'sequence'/f'{i:04d}.webp'
    if target.exists():
        if not encoded.exists():
            subprocess.run([str(CAD/'.venv/bin/python'),str(ROOT/'scripts/encode_frame.py'),str(target),str(encoded)],check=True)
        continue
    shot(i/(frames-1));scene.render.filepath=str(target)
    bpy.ops.render.render(write_still=True)
    subprocess.run([str(CAD/'.venv/bin/python'),str(ROOT/'scripts/encode_frame.py'),str(target),str(encoded)],check=True)
    print(json.dumps({'frame':i,'total':frames,'seconds':round(time.monotonic()-start,1)}),flush=True)
(ROOT/'design'/'render-manifest.json').write_text(json.dumps({'source':str(CAD/'loader-cad-restored.blend'),'frames':frames,'resolution':[1080,1080],'samples':20,'engine':'Cycles Metal','camera_keys':keys},ensure_ascii=False,indent=2))
