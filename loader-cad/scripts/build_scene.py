"""Build an editable, separately saved Blender scene from the supplied CAD assembly."""
import json
import math
import sys
from pathlib import Path
import bpy
import bmesh
from mathutils import Vector
ROOT=Path(__file__).resolve().parents[1]
sys.path.insert(0,str(Path(__file__).resolve().parent))
OFFSET=(1500,1524.881075752,900)

def position(p):
    return ((p[1]-OFFSET[1])/1000,(p[0]-OFFSET[0])/1000,(p[2]-OFFSET[2])/1000)

def material(name,color,metallic,roughness):
    m=bpy.data.materials.new(name);m.diffuse_color=(*color,1);m.use_nodes=True
    p=m.node_tree.nodes.get('Principled BSDF');p.inputs['Base Color'].default_value=(*color,1)
    p.inputs['Metallic'].default_value=metallic;p.inputs['Roughness'].default_value=roughness
    return m

def camera(name,loc,target,scale):
    d=bpy.data.cameras.new(name);o=bpy.data.objects.new(name,d);bpy.context.scene.collection.objects.link(o)
    o.location=loc;o.rotation_euler=(Vector(target)-o.location).to_track_quat('-Z','Y').to_euler()
    d.type='ORTHO';d.ortho_scale=scale;return o

def studio():
    scene=bpy.context.scene
    scene.world.color=(.25,.25,.25);scene.world.use_nodes=True
    scene.world.node_tree.nodes['Background'].inputs['Color'].default_value=(.65,.72,.82,1)
    scene.world.node_tree.nodes['Background'].inputs['Strength'].default_value=.45
    floor=material('摄影棚暖灰',(.33,.35,.38),0,.8)
    bpy.ops.mesh.primitive_plane_add(size=200,location=(0,0,-.025));o=bpy.context.object;o.name='摄影棚地面';o.data.materials.append(floor)
    for name,loc,power,size in [('主柔光',(-3,4,5),900,4),('轮廓光',(3,-2,4),1000,3),('前补光',(2,4,2),400,3)]:
        d=bpy.data.lights.new(name,'AREA');d.energy=power;d.shape='DISK';d.size=size
        o=bpy.data.objects.new(name,d);scene.collection.objects.link(o);o.location=loc;o.rotation_euler=(Vector((0,.1,.6))-o.location).to_track_quat('-Z','Y').to_euler()
    cameras={'hero':camera('整机 · 前侧',(3,4,2.3),(0,.12,.65),3.0),'side':camera('整机 · 侧面',(4,.1,1.3),(0,.1,.65),2.9),'rear':camera('整机 · 后侧',(-3,-4,2.1),(0,-.15,.65),2.9)}
    scene.camera=cameras['hero'];scene.render.engine='CYCLES';scene.cycles.samples=96;scene.cycles.use_denoising=True
    prefs=bpy.context.preferences.addons['cycles'].preferences
    prefs.compute_device_type='METAL';prefs.get_devices()
    for device in prefs.devices:device.use=device.type=='METAL'
    scene.cycles.device='GPU'
    scene.render.resolution_x=1800;scene.render.resolution_y=1500;scene.render.resolution_percentage=100
    scene.view_settings.view_transform='AgX';scene.view_settings.exposure=-.5;scene.render.image_settings.file_format='PNG'
    return cameras

def main():
    bpy.ops.object.select_all(action='SELECT');bpy.ops.object.delete(use_global=False)
    data=json.loads((ROOT/'data/assembly.json').read_text())
    mats={'red':material('朱红 · 喷涂钢板',(.48,.012,.003),.25,.31),'gray':material('石墨灰 · 喷涂钢板',(.047,.065,.095),.38,.34),'black':material('哑黑 · 内部结构',(.025,.029,.033),.3,.44),'steel':material('镀锌钢',(.43,.47,.51),.8,.23)}
    collections={};cad=[]
    for p in data['parts']:
        group=p['path'][1] if len(p['path'])>1 else '其他原厂零件'
        if group not in collections:
            c=bpy.data.collections.new('CAD · '+group);bpy.context.scene.collection.children.link(c);collections[group]=c
        name=f"{p['id']:03d} · {p['name']}"
        mesh=bpy.data.meshes.new(name);mesh.from_pydata([position(v) for v in p['vertices']],[],p['triangles']);mesh.update()
        bm=bmesh.new();bm.from_mesh(mesh);bmesh.ops.remove_doubles(bm,verts=list(bm.verts),dist=.000001);bm.to_mesh(mesh);bm.free()
        mesh.set_sharp_from_angle(angle=math.radians(32))
        for poly in mesh.polygons:poly.use_smooth=True
        o=bpy.data.objects.new(name,mesh);collections[group].objects.link(o);o['来源']='原厂 STEP 几何';o['CAD零件名称']=p['name'];o['CAD编号']=p['id'];o['装配路径']='/'.join(p['path']);cad.append(o)
        gray=any(x in group for x in ['大臂','铲斗','快换','履带架']) or any(x in p['name'] for x in ['盖板','仪表盘','踏板','油散'])
        key='gray' if gray else 'red'
        if any(x in p['name'] for x in ['电瓶','发动机座板']):key='black'
        if any(x in p['name'] for x in ['过滤器','螺母','螺纹']):key='steel'
        mesh.materials.append(mats[key])
    if '--complete' in sys.argv:
        from photo_details import build
        build(mats)
    cameras=studio();scene=bpy.context.scene;scene.unit_settings.system='METRIC'
    scene['CAD源文件']=data['source'];scene['CAD_SHA256']=data['sha256'];scene['说明']='原厂结构保留；照片补充件为外观近似，不作制造依据。'
    for screen in bpy.data.screens:
        for area in screen.areas:
            if area.type=='VIEW_3D':
                area.spaces.active.overlay.show_relationship_lines=False
                area.spaces.active.clip_end=1000
                area.spaces.active.region_3d.view_distance=3.8
                area.spaces.active.region_3d.view_location=(0,.1,.65)
                area.spaces.active.region_3d.view_rotation=cameras['hero'].rotation_euler.to_quaternion()
                area.spaces.active.shading.color_type='MATERIAL'
    bpy.ops.object.select_all(action='DESELECT')
    name='loader-cad-restored' if '--complete' in sys.argv else 'loader-cad-original'
    bpy.ops.wm.save_as_mainfile(filepath=str(ROOT/(name+'.blend')))
    scene.render.filepath=str(ROOT/'renders'/(name+'.png'));bpy.ops.render.render(write_still=True)
    report={'cad_parts':len(cad),'cad_triangles':sum(len(p['triangles']) for p in data['parts']),'blender_meshes':len([o for o in scene.objects if o.type=='MESH']),'source_sha256':data['sha256']}
    (ROOT/'data'/(name+'-validation.json')).write_text(json.dumps(report,ensure_ascii=False,indent=2))
if __name__=='__main__':main()
