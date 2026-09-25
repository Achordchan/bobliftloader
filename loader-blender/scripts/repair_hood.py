"""Targeted repair of the saved project. Keep the source project untouched."""
import hashlib
import json
from pathlib import Path
import sys
import bpy

ROOT=Path(__file__).resolve().parent.parent
sys.path.insert(0,str(ROOT/'scripts'))
from hood_checks import check_hood

source=Path(bpy.data.filepath)
output=ROOT/'loader-product-fixed.blend'
if output.exists():raise RuntimeError('Refusing to overwrite an existing repaired project')
source_hash=hashlib.sha256(source.read_bytes()).hexdigest()
before=check_hood(require_clean=False)

# Update only the two overlapping closure faces, retaining the rest of the scene.
lower=bpy.data.objects['机罩下端面']
upper=bpy.data.objects['机罩上端面']
assert len(lower.data.vertices)==4 and len(upper.data.vertices)==4
for obj,expected,new in [
    (lower,[-.35,.35,.155,-.155],[-.18,.18,.155,-.155]),
    (upper,[-.335,.335,.335,-.335],[-.192,.192,.195,-.195]),
]:
    assert all(abs(v.co.x-x)<1e-6 for v,x in zip(obj.data.vertices,expected)),f'Panel was edited: {obj.name}'
    for vertex,x in zip(obj.data.vertices,new):vertex.co.x=x
    obj.data.update()

viewports=0
for screen in bpy.data.screens:
    for area in screen.areas:
        for space in area.spaces:
            if space.type=='VIEW_3D':
                space.overlay.show_relationship_lines=False
                viewports+=1

after=check_hood()
for name in ['body.py','build.py','validate.py','hood_checks.py','repair_hood.py']:
    path=ROOT/'scripts'/name
    text=bpy.data.texts.get(name) or bpy.data.texts.new(name)
    text.clear();text.write(path.read_text())
bpy.ops.wm.save_as_mainfile(filepath=str(output))
assert hashlib.sha256(source.read_bytes()).hexdigest()==source_hash
report={'source':str(source),'source_sha256':source_hash,'output':str(output),
        'disabled_relationship_line_viewports':viewports,'before_overlap_m2':before,'after_overlap_m2':after}
(ROOT/'logs/hood-repair.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
print('HOOD_REPAIR='+json.dumps(report,ensure_ascii=False),flush=True)
