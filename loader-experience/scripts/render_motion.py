"""Render actual mesh/curve motion and bake a separately editable motion-study blend."""
import argparse
import json
from pathlib import Path
import sys
import time
import bpy
from mathutils import Vector
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(ROOT/'scripts'))
from mechanical_rig import MachineRig, pose
args=sys.argv[sys.argv.index('--')+1:] if '--' in sys.argv else []
parser=argparse.ArgumentParser();parser.add_argument('--samples',action='store_true');parser.add_argument('--clip',choices=['operation','tracks']);options=parser.parse_args(args)
rig=MachineRig();scene=bpy.context.scene
scene.render.engine='CYCLES';scene.cycles.samples=12;scene.cycles.use_denoising=True
scene.cycles.adaptive_threshold=.08;scene.cycles.max_bounces=5;scene.render.use_persistent_data=True
prefs=bpy.context.preferences.addons['cycles'].preferences;prefs.compute_device_type='METAL';prefs.get_devices()
for d in prefs.devices:d.use=d.type=='METAL'
scene.cycles.device='GPU';scene.render.film_transparent=True
scene.render.image_settings.file_format='PNG';scene.render.image_settings.color_mode='RGBA'
scene.render.resolution_percentage=100;scene.render.fps=24
floor=bpy.data.objects.get('摄影棚地面')
if floor:floor.is_shadow_catcher=True
cam=scene.camera;cam.data.type='ORTHO'
log=[];start=time.monotonic()
for clip,count,loc,target,scale in [('operation',160,(3.5,5.0,2.7),(0,.12,1.0),3.45),('tracks',64,(4.8,2.0,1.5),(0,.05,.47),2.65)]:
    if options.clip and options.clip!=clip:continue
    folder=ROOT/'design/motion-frames'/clip;folder.mkdir(parents=True,exist_ok=True)
    cam.location=loc;cam.rotation_euler=(Vector(target)-cam.location).to_track_quat('-Z','Y').to_euler();cam.data.ortho_scale=scale
    scene.render.resolution_x=960;scene.render.resolution_y=720
    frames=[0,54,92,159] if options.samples and clip=='operation' else [0,32] if options.samples else range(count)
    for i in frames:
        p=i/(count-1)
        scene.frame_set(i+1)
        rig.apply(p if clip=='operation' else 0,p*2 if clip=='operation' else p)
        if not options.samples and clip=='operation':rig.keyframe(i+1)
        output=folder/f'{i:04d}.png'
        if not output.exists():
            scene.render.filepath=str(output);bpy.ops.render.render(write_still=True)
        print(json.dumps({'clip':clip,'frame':i,'total':count,'seconds':round(time.monotonic()-start)}),flush=True)
    if not options.samples and clip=='operation':
        scene.frame_start=1;scene.frame_end=160;scene.frame_set(1)
        scene['动作参考']='MVI_2897 举升回落、MVI_2905 翻斗；演示角度并非厂家标定行程'
        scene['动画说明']='真实CAD铰点带动臂架、铲斗，油缸端点随动；履带块沿闭合路径运动。轮组速比与软管变形为展示近似。'
        bpy.ops.wm.save_as_mainfile(filepath=str(ROOT.parent/'loader-cad/loader-motion-study.blend'))
        # Keep the next render job free of baked action evaluation.
        for obj in rig.moving:obj.animation_data_clear()
        for obj,_ in rig.hoses:obj.data.animation_data_clear()
    log.append({'clip':clip,'frames':count,'fps':24})
(ROOT/'design/motion-render.json').write_text(json.dumps({'samples_only':options.samples,'clips':log,'moving_objects':len(rig.moving),'reference':['MVI_2897.MP4','MVI_2905.MP4']},indent=2))
