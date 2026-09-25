"""Export the CAD scene as one GLB node per assembly group, with per-group baked AO textures.

Blender joins each collection into one object (the .blend on disk is not modified), unwraps it,
bakes ambient occlusion, and exports a GLB whose node names drive the web viewer's explode,
focus and kinematics. Hinge pins are written next to the GLB as JSON in glTF coordinates.

Usage: Blender -b loader-cad/loader-cad-restored.blend --python export_glb.py -- out.glb [--no-bake]
"""
import bpy, sys, json, os, mathutils

argv = sys.argv[sys.argv.index('--') + 1:]
out = argv[0]
bake = '--no-bake' not in argv
ao_dir = os.path.join(os.path.dirname(out), 'ao')
os.makedirs(ao_dir, exist_ok=True)
scene = bpy.context.scene

for o in list(bpy.data.objects):  # studio floor is not part of the product
    if o.type == 'MESH' and o.users_collection and o.users_collection[0].name == 'Collection':
        bpy.data.objects.remove(o, do_unlink=True)

group_of = {
    'CAD · SRT23A.03   车架焊接总成': 'frame', 'CAD · SRT23A.01大臂焊接总成': 'arm',
    'CAD · SRT23A.02汽油箱焊接总成': 'fueltank', 'CAD · SRT23A.04履带架': 'trackframe',
    'CAD · SRT23A.05 铲斗焊接总成': 'bucket', 'CAD · SRT23A.06快换焊接总成': 'quickattach',
    'CAD · SRT23A.07液压油箱焊接图': 'hydtank', 'CAD · SRT23A.08电瓶夹': 'battery',
    'CAD · 其他原厂零件': 'battery', '照片补充 · 左橡胶履带与轮组': 'trackL',
    '照片补充 · 右橡胶履带与轮组': 'trackR', '照片补充 · 操纵台与扶手': 'controls',
    '照片补充 · 发动机外观及散热器': 'engine', '照片补充 · 外部固定件与照明': 'frame',
}
# The hydraulic collection is split so cylinders can articulate in the browser.
def hydraulic_group(name):
    n = name.split(' · ', 1)[-1]
    if n.startswith('举升缸供油管'): return 'liftBarrel'  # feed hoses stay attached to the barrel ports
    if n.startswith('举升油缸镀铬活塞杆') or n.startswith('举升前销'): return 'liftRod'
    if n.startswith('举升油缸') or n.startswith('举升缸油口'): return 'liftBarrel'
    if n.startswith('铲斗翻转油缸镀铬活塞杆'): return 'tiltRod'
    if n.startswith('铲斗翻转油缸') or n.startswith('翻斗缸油口'): return 'tiltBarrel'
    if n.startswith('大臂固定销') or n.startswith('固定销螺栓'): return 'frame'
    if n.startswith('铲斗连接销'): return 'bucket'
    return 'arm'  # hoses, clamps, crimp sleeves and quick couplers travel with the boom

buckets = {}
# Hinge pins: several parts share a pin name (both ends of a cylinder), so each pin is picked
# by proximity to the CAD reference position used by scripts/mechanical_rig.py (Blender Y, Z).
PIN_REF = {'armPivot': ('大臂固定销', (-.245, 1.036)), 'liftBase': ('举升油缸铰轴', (-.41, .63)), 'liftRodEnd': ('举升前销', (.31, .54)),
           'tiltBase': ('铲斗翻转油缸铰轴', (.53, .79)), 'bucketPivot': ('铲斗连接销', (.60, .14))}
pins = {k: [] for k in PIN_REF}
tilt_rod = None
def centre(o):
    bb = [o.matrix_world @ mathutils.Vector(c) for c in o.bound_box]
    return sum(bb, mathutils.Vector()) / 8
for col in scene.collection.children:
    for o in col.all_objects:
        if o.type not in ('MESH', 'CURVE'): continue  # handrails, hoses, grilles and needles are bevelled curves
        g = hydraulic_group(o.name) if col.name == '照片补充 · 液压油缸与管路' else group_of.get(col.name)
        if not g: continue
        if '三联阀' in o.name: g = 'frame'
        buckets.setdefault(g, []).append(o)
        base = o.name.split(' · ', 1)[-1].split('.')[0]
        c = centre(o)
        for key, (prefix, (ry, rz)) in PIN_REF.items():
            if base == prefix and abs(c.y - ry) < .08 and abs(c.z - rz) < .08: pins[key].append(c)
        if base == '铲斗翻转油缸镀铬活塞杆': tilt_rod = o

def to_gltf(v): return [0.0, round(v.z, 5), round(-v.y, 5)]  # hinge axes run along X; store the axis line
pin_out = {}
for key, pts in pins.items():
    assert pts, key
    pin_out[key] = to_gltf(sum(pts, mathutils.Vector()) / len(pts))
tb = sum(pins['tiltBase'], mathutils.Vector()) / len(pins['tiltBase'])
axis = (centre(tilt_rod) - tb).normalized()
corners = [tilt_rod.matrix_world @ mathutils.Vector(v) for v in tilt_rod.bound_box]
pin_out['tiltRodEnd'] = to_gltf(tb + axis * max((p - tb).dot(axis) for p in corners))  # rod eye = far extreme of the rod

bpy.ops.object.select_all(action='DESELECT')
result = {}
for g, objs in buckets.items():
    for o in objs: o.hide_set(False); o.hide_viewport = False; o.select_set(True)
    bpy.context.view_layer.objects.active = objs[0]
    bpy.ops.object.convert(target='MESH')  # curves become meshes here, before the join
    bpy.ops.object.join()
    joined = bpy.context.view_layer.objects.active
    joined.name = g; joined.data.name = g
    bpy.ops.object.select_all(action='DESELECT')
    result[g] = {'parts': len(objs), 'polys': len(joined.data.polygons)}

if bake:
    scene.render.engine = 'CYCLES'
    scene.cycles.device = 'GPU'
    scene.cycles.samples = 40
    scene.render.bake.margin = 6
    scene.render.bake.use_clear = True
    for g in result:
        obj = bpy.data.objects[g]
        size = 2048 if result[g]['polys'] > 15000 else 1024
        bpy.ops.object.select_all(action='DESELECT'); obj.select_set(True); bpy.context.view_layer.objects.active = obj
        bpy.ops.object.mode_set(mode='EDIT'); bpy.ops.mesh.select_all(action='SELECT')
        bpy.ops.mesh.normals_make_consistent(inside=False)  # STEP tessellation leaves many shells inside-out; AO rays would hit the interior
        bpy.ops.uv.smart_project(angle_limit=1.15, island_margin=0.004, correct_aspect=True)
        bpy.ops.object.mode_set(mode='OBJECT')
        image = bpy.data.images.new('AO_' + g, size, size, alpha=False)
        image.colorspace_settings.name = 'Non-Color'
        for slot in obj.material_slots:  # materials become single-user so the bake target node does not leak between groups
            mat = slot.material.copy(); slot.material = mat
            node = mat.node_tree.nodes.new('ShaderNodeTexImage'); node.image = image; node.select = True
            mat.node_tree.nodes.active = node
        bpy.ops.object.bake(type='AO')
        path = os.path.join(ao_dir, g + '.png')
        image.filepath_raw = path; image.file_format = 'PNG'; image.save()
        for slot in obj.material_slots:  # restore the original shared material for a clean export
            slot.material = bpy.data.materials[slot.material.name.rsplit('.', 1)[0]] if '.' in slot.material.name else slot.material
        result[g]['ao'] = os.path.basename(path); result[g]['ao_size'] = size
        print('BAKED', g, size, flush=True)

bpy.ops.object.select_all(action='DESELECT')
for g in result: bpy.data.objects[g].select_set(True)
bpy.ops.export_scene.gltf(filepath=out, export_format='GLB', use_selection=True, export_apply=True,
    export_materials='EXPORT', export_yup=True, export_normals=True, export_texcoords=True,
    export_extras=False, export_cameras=False, export_lights=False)
json.dump({'groups': result, 'pins': pin_out}, open(out + '.json', 'w'), ensure_ascii=False, indent=1)
print('RESULT', json.dumps({'groups': list(result), 'pins': pin_out}, ensure_ascii=False))
