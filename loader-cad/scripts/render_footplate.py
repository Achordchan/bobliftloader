"""Render a standing-platform close-up for contact inspection without saving scene changes."""
import sys
from pathlib import Path
import bpy
from mathutils import Vector
ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT/'scripts'))
from footplate import validate_treads

scene = bpy.context.scene
before = '--before' in sys.argv
if not before:
    print(validate_treads())
data = bpy.data.cameras.new('踏板贴合检查')
cam = bpy.data.objects.new('踏板贴合检查', data)
scene.collection.objects.link(cam)
cam.location = (-.63, -1.64, .60)
cam.rotation_euler = (Vector((0, -.84, .34))-cam.location).to_track_quat('-Z', 'Y').to_euler()
data.type = 'ORTHO'
data.ortho_scale = .86
scene.camera = cam
light = bpy.data.lights.new('踏板检查柔光', 'AREA')
light.energy = 35
light.size = .8
obj = bpy.data.objects.new('踏板检查柔光', light)
scene.collection.objects.link(obj)
obj.location = (0, -1.7, 1.0)
obj.rotation_euler = (Vector((0, -.84, .31))-obj.location).to_track_quat('-Z', 'Y').to_euler()
prefs = bpy.context.preferences.addons['cycles'].preferences
prefs.compute_device_type = 'METAL'
prefs.get_devices()
for device in prefs.devices:
    device.use = device.type == 'METAL'
scene.cycles.device = 'GPU'
scene.cycles.samples = 64
scene.render.resolution_x = 1500
scene.render.resolution_y = 1000
scene.render.resolution_percentage = 100
suffix = 'before' if before else 'after'
scene.render.filepath = str(ROOT/'renders'/f'footplate-{suffix}.png')
bpy.ops.render.render(write_still=True)
