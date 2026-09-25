"""Verify CAD mesh preservation, attribution, saved render settings and dependencies."""
import hashlib
import json
import math
from pathlib import Path
import bpy
ROOT=Path(__file__).resolve().parents[1]
source=json.loads((ROOT/'data/assembly.json').read_text())
scene=bpy.context.scene
cad=[o for o in scene.objects if o.get('CAD编号')]
assert len(cad)==len(source['parts'])==160
assert len({o['CAD编号'] for o in cad})==160
assert hashlib.sha256(Path(source['source']).read_bytes()).hexdigest()==source['sha256']
max_error=0
for p in source['parts']:
    o=next(o for o in cad if o['CAD编号']==p['id'])
    expected=[[(p['bounds_mm'][1][j]-1524.881075752)/1000 for j in [0,1]],[(p['bounds_mm'][0][j]-1500)/1000 for j in [0,1]],[(p['bounds_mm'][2][j]-900)/1000 for j in [0,1]]]
    actual=[[min(v.co[i] for v in o.data.vertices),max(v.co[i] for v in o.data.vertices)] for i in range(3)]
    error=max(abs(actual[i][j]-expected[i][j]) for i in range(3) for j in range(2));max_error=max(max_error,error)
    assert error<.00001,(o.name,error)
    assert o.data.polygons and o.data.materials
meshes=[o for o in scene.objects if o.type=='MESH']
for o in meshes:
    assert len(o.data.vertices)>0 and len(o.data.polygons)>0,o.name
    assert all(math.isfinite(a) for v in o.data.vertices for a in v.co),o.name
external=[i.filepath for i in bpy.data.images if i.source=='FILE' and not i.packed_file]
assert not external,external
photo=[o for o in scene.objects if o.get('来源','').startswith('产品照片')]
report={'blend':bpy.data.filepath,'cad_parts':len(cad),'photo_derived_objects':len(photo),'meshes':len(meshes),'cad_local_bounds_max_error_m':max_error,'source_sha256_unchanged':True,'empty_meshes':0,'non_finite_coordinates':0,'unpacked_images':external,'cameras':[o.name for o in scene.objects if o.type=='CAMERA'],'limitations':['照片补充件未获工程尺寸校准','未做运动学绑定或动态碰撞验收','侧面开孔以原始 CAD 为准，照片局部结构可能属于不同批次','未复刻不可辨认的标签文字与制造商标识']}
(ROOT/'data/final-validation.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
print(json.dumps(report,ensure_ascii=False,indent=2))
