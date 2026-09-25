"""Build and render an editable Blender product scene without operating its UI."""
import argparse
import json
import math
from pathlib import Path
import sys
import time

import bpy
from mathutils import Vector

HERE=Path(__file__).resolve().parent
ROOT=HERE.parent
sys.path.insert(0,str(HERE))
import geometry as g
from materials import create_materials
import tracks
import body
import attachment


def arguments():
    argv=sys.argv[sys.argv.index('--')+1:] if '--' in sys.argv else []
    parser=argparse.ArgumentParser()
    parser.add_argument('--preview',action='store_true')
    parser.add_argument('--project',default='loader-product-fixed.blend')
    parser.add_argument('--render',choices=['hero','side','detail','none'],default='hero')
    return parser.parse_args(argv)


def camera(name,position,target,lens):
    data=bpy.data.cameras.new(name)
    obj=bpy.data.objects.new(name,data)
    bpy.context.scene.collection.objects.link(obj)
    obj.location=position;obj.rotation_euler=(Vector(target)-obj.location).to_track_quat('-Z','Y').to_euler()
    data.lens=lens;data.clip_end=1000;data.dof.use_dof=False
    return obj


def area(name,position,target,power,size,size_y=None):
    data=bpy.data.lights.new(name,'AREA');data.energy=power
    data.shape='RECTANGLE';data.size=size;data.size_y=size_y or size
    obj=bpy.data.objects.new(name,data);bpy.context.scene.collection.objects.link(obj)
    obj.location=position;obj.rotation_euler=(Vector(target)-obj.location).to_track_quat('-Z','Y').to_euler()
    return obj


def studio():
    scene=bpy.context.scene
    g.collection('06 · 摄影棚')
    # Geometry helpers parent product pieces; the floor must stay outside the product hierarchy.
    ground=g.box('摄影棚地面',200,.05,200,0,-.003,0,'floor',bevel=0)
    ground.parent=None
    world=bpy.data.worlds.new('柔和摄影棚环境')
    scene.world=world;world.use_nodes=True
    nodes=world.node_tree.nodes;links=world.node_tree.links
    texture=nodes.new('ShaderNodeTexEnvironment')
    texture.image=bpy.data.images.load(str(ROOT/'assets/studio-small-09.hdr'));texture.image.pack()
    coordinates=nodes.new('ShaderNodeTexCoord');mapping=nodes.new('ShaderNodeMapping')
    mapping.inputs['Rotation'].default_value[2]=math.radians(25)
    links.new(coordinates.outputs['Generated'],mapping.inputs['Vector'])
    links.new(mapping.outputs['Vector'],texture.inputs['Vector'])
    links.new(texture.outputs['Color'],nodes.get('Background').inputs['Color'])
    nodes.get('Background').inputs['Strength'].default_value=.15
    area('主灯 · 大型柔光箱',(-3.4,4.0,6.5),(0,.2,1.0),700,4.0,3.0)
    area('右侧轮廓灯',(4.0,-2.5,5.0),(0,0,1.2),450,2.4,3.5)
    area('左后条形灯',(-3.6,-2.0,3.4),(0,0,1.2),350,1.0,3.0)
    area('正面柔和补光',(1.0,5.0,3.0),(0,.5,1.0),100,3.0,2.0)
    cameras = {
        'hero':camera('相机 · 整机三分之四',(-5.4,7.0,3.8),(0,.20,1.10),66),
        'side':camera('相机 · 低角度侧面',(7.7,.45,2.55),(0,.20,1.16),62),
        'detail':camera('相机 · 机罩与液压细节',(2.4,3.5,2.8),(0,.30,1.25),68),
    }
    cameras['side'].data.type='ORTHO'
    cameras['side'].data.ortho_scale=4.5
    return cameras


def main():
    args=arguments();start=time.monotonic()
    bpy.ops.object.select_all(action='SELECT');bpy.ops.object.delete(use_global=False)
    for c in list(bpy.data.collections):
        if not c.objects:bpy.data.collections.remove(c)
    g.MATS=create_materials();g.collection('00 · 产品总成')
    g.ROOT=g.empty('BOBLIFT · 装载机总成')
    g.ROOT['建模依据']='用户提供的整机、侧面和部件特写；尺寸为视觉估算。'
    g.ROOT['单位']='米；非制造图纸'
    body.build();tracks.build();attachment.build();cameras=studio()
    scene=bpy.context.scene;scene.unit_settings.system='METRIC'
    scene.render.engine='CYCLES'
    pref=bpy.context.preferences.addons['cycles'].preferences
    pref.compute_device_type='METAL';pref.get_devices()
    gpu=[]
    for device in pref.devices:
        device.use=device.type=='METAL'
        if device.use:gpu.append(device.name)
    if not gpu:raise RuntimeError('No Metal GPU found; render device requires review')
    scene.cycles.device='GPU';scene.cycles.samples=24 if args.preview else 160
    scene.cycles.use_adaptive_sampling=True;scene.cycles.adaptive_threshold=.065 if args.preview else .018
    scene.cycles.use_denoising=True;scene.cycles.max_bounces=10
    scene.cycles.diffuse_bounces=4;scene.cycles.glossy_bounces=5;scene.cycles.transmission_bounces=8
    scene.cycles.sample_clamp_indirect=5
    scene.render.resolution_x=800 if args.preview else 1800
    scene.render.resolution_y=740 if args.preview else 1600
    scene.render.resolution_percentage=100
    scene.render.image_settings.file_format='PNG';scene.render.image_settings.color_mode='RGBA'
    scene.render.image_settings.color_depth='8';scene.render.film_transparent=False
    scene.view_settings.view_transform='AgX'
    scene.view_settings.look='AgX - Medium High Contrast'
    scene.view_settings.exposure=-.35
    scene.camera=cameras['hero' if args.render=='none' else args.render]
    scene.render.filepath=str(ROOT/'renders'/f'{args.render}{"-preview" if args.preview else ""}.png')
    # Save a useful opening view in the editable project, with cutters hidden and cameras named.
    for screen in bpy.data.screens:
        for view in screen.areas:
            for space in view.spaces:
                if space.type!='VIEW_3D':continue
                space.region_3d.view_perspective='CAMERA'
                space.overlay.show_extras=False
                space.overlay.show_relationship_lines=False
                space.shading.color_type='MATERIAL'
    for source in HERE.glob('*.py'):
        if source.name not in bpy.data.texts:bpy.data.texts.load(str(source))
    bpy.ops.wm.save_as_mainfile(filepath=str(ROOT/args.project))
    report={'blender':bpy.app.version_string,'gpu':gpu,'engine':scene.render.engine,
            'objects':len(bpy.data.objects),'meshes':len(bpy.data.meshes),'materials':len(bpy.data.materials),
            'build_seconds':round(time.monotonic()-start,2),'preview':args.preview,'camera':args.render}
    print('BUILD_REPORT='+json.dumps(report,ensure_ascii=False),flush=True)
    (ROOT/'logs/build-report.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
    if args.render!='none':
        bpy.ops.render.render(write_still=True)
        print('RENDER_SAVED='+scene.render.filepath,flush=True)
    print('TOTAL_SECONDS='+str(round(time.monotonic()-start,2)),flush=True)


if __name__=='__main__':main()
