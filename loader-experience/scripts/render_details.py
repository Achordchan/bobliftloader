"""Create the actual product stills used by the multi-section scroll story."""
from pathlib import Path
import subprocess
import bpy
from mathutils import Vector
ROOT=Path(__file__).resolve().parents[1]
scene=bpy.context.scene
floor=bpy.data.objects.get('摄影棚地面')
if floor:floor.hide_render=True
scene.render.film_transparent=True
scene.render.image_settings.file_format='PNG'
scene.render.image_settings.color_mode='RGBA'
scene.render.resolution_percentage=100
scene.cycles.samples=64
scene.cycles.use_denoising=True
scene.cycles.adaptive_threshold=.035
scene.render.use_persistent_data=True
prefs=bpy.context.preferences.addons['cycles'].preferences
prefs.compute_device_type='METAL';prefs.get_devices()
for device in prefs.devices:device.use=device.type=='METAL'
scene.cycles.device='GPU'
cam=scene.camera;cam.data.type='ORTHO'
raw=ROOT/'design/detail-renders';raw.mkdir(exist_ok=True)
shots=[
 ('hero',(3.5,4.5,2.2),(0,.1,.70),2.65,(2200,1800)),
 ('chassis',(4.5,.15,1.02),(0,.1,.68),2.7,(2200,1600)),
 ('hydraulics',(2.8,2.4,1.45),(.10,.34,.72),1.45,(2200,1500)),
 ('controls',(-1.2,-2.7,2.5),(0,-.36,1.20),1.03,(1800,1400)),
]
for name,position,target,scale,size in shots:
 cam.location=position
 cam.rotation_euler=(Vector(target)-cam.location).to_track_quat('-Z','Y').to_euler()
 cam.data.ortho_scale=scale
 scene.render.resolution_x,scene.render.resolution_y=size
 path=raw/f'{name}.png';scene.render.filepath=str(path)
 bpy.ops.render.render(write_still=True)
 subprocess.run([str(ROOT.parent/'loader-cad/.venv/bin/python'),str(ROOT/'scripts/encode_frame.py'),str(path),str(ROOT/'assets/details'/f'{name}.webp')],check=True)
 print(f'Created {name}',flush=True)
