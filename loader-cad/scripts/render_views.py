"""Render saved scene viewpoints without overwriting the Blender project."""
from pathlib import Path
import bpy
ROOT=Path(__file__).resolve().parents[1]
scene=bpy.context.scene
prefs=bpy.context.preferences.addons['cycles'].preferences
prefs.compute_device_type='METAL';prefs.get_devices()
for device in prefs.devices:device.use=device.type=='METAL'
scene.cycles.device='GPU';scene.cycles.samples=64
for key,name in [('side','整机 · 侧面'),('rear','整机 · 后侧')]:
    scene.camera=bpy.data.objects[name]
    scene.render.filepath=str(ROOT/'renders'/(key+'.png'))
    bpy.ops.render.render(write_still=True)
