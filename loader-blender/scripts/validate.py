"""Read-only scene checks for the generated project; does not save the open file."""
import json
import math
import sys
from pathlib import Path
import bpy
from mathutils import Vector

ROOT=Path(__file__).resolve().parent.parent
sys.path.insert(0,str(ROOT/'scripts'))
from hood_checks import check_hood
hood_overlap=check_hood()
assert all(not space.overlay.show_relationship_lines
           for screen in bpy.data.screens for area in screen.areas
           for space in area.spaces if space.type=='VIEW_3D')
scene=bpy.context.scene
dg=bpy.context.evaluated_depsgraph_get()
product=bpy.data.objects.get('BOBLIFT · 装载机总成')
assert product is not None

def part_of_product(obj):
    parent=obj.parent
    while parent:
        if parent==product:return True
        parent=parent.parent
    return False

empty=[];invalid=[];corners=[];visible_meshes=0
for obj in scene.objects:
    if obj.type!='MESH' or obj.hide_render or not part_of_product(obj):continue
    evaluated=obj.evaluated_get(dg);visible_meshes+=1
    if not evaluated.data.polygons:empty.append(obj.name)
    if any(not all(math.isfinite(c) for c in vertex.co) for vertex in evaluated.data.vertices):invalid.append(obj.name)
    corners.extend(evaluated.matrix_world@Vector(c) for c in evaluated.bound_box)
assert not empty,empty
assert not invalid,invalid
assert bpy.data.objects['真实镂空蜂窝格栅'].evaluated_get(dg).data.polygons
assert all(image.packed_file for image in bpy.data.images if image.source=='FILE')
assert scene.render.engine=='CYCLES'
assert len([o for o in scene.objects if o.type=='CAMERA'])==3
assert scene.render.resolution_x==1800 and scene.render.resolution_y==1600
report={
 'hood_overlap_m2':hood_overlap,
 'relationship_lines_disabled':True,
 'blender':bpy.app.version_string,'render_engine':scene.render.engine,
 'visible_product_meshes':visible_meshes,'empty_meshes':empty,'invalid_meshes':invalid,
 'packed_images':[image.name for image in bpy.data.images if image.packed_file],
 'cameras':[o.name for o in scene.objects if o.type=='CAMERA'],
 'dimensions_m':[round(max(c[i] for c in corners)-min(c[i] for c in corners),4) for i in range(3)],
 'image_size':[scene.render.resolution_x,scene.render.resolution_y],
 'max_samples':scene.cycles.samples,
 'grille_polygons':len(bpy.data.objects['真实镂空蜂窝格栅'].evaluated_get(dg).data.polygons),
 'embedded_scripts':[t.name for t in bpy.data.texts],
}
(ROOT/'logs/validation.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
print('VALIDATION='+json.dumps(report,ensure_ascii=False),flush=True)
