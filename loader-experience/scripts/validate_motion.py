"""Check baked pose changes and tread displacement in the delivered study file."""
import json
from pathlib import Path
import bpy
from mathutils import Vector
scene=bpy.context.scene
arm=next(o for o in scene.objects if o.get('CAD编号')==42)
bucket=next(o for o in scene.objects if o.get('CAD编号')==108)
pad=next(o for o in scene.objects if o.name.startswith('右履带块 01'))
frames=[]
for frame in [1,25,55,93,125,160]:
 scene.frame_set(frame);bpy.context.view_layer.update()
 center=sum((bucket.matrix_world@Vector(v) for v in bucket.bound_box),Vector())/8
 frames.append({'frame':frame,'arm_angle':arm.matrix_world.to_euler().x,'bucket_center':list(center),'tread_position':list(pad.location)})
assert abs(frames[2]['arm_angle']-frames[0]['arm_angle'])>.7
assert Vector(frames[2]['bucket_center']).z>Vector(frames[0]['bucket_center']).z+.5
assert (Vector(frames[1]['tread_position'])-Vector(frames[0]['tread_position'])).length>.005
assert abs(frames[-1]['arm_angle']-frames[0]['arm_angle'])<.001
report={'baked_frames':scene.frame_end,'pose_checks':frames,'passed':True,'limitations':['Not manufacturer-calibrated travel','No full collision validation','Approximate wheel ratios and flexible hoses']}
Path(__file__).resolve().parents[1].joinpath('design/motion-validation.json').write_text(json.dumps(report,indent=2))
print(json.dumps(report,indent=2))
