"""Small Blender geometry helpers for clearly attributed photo-based additions."""
import math
import bpy
from mathutils import Vector
COLLECTION=None
MATS={}

def collection(name):
    global COLLECTION
    COLLECTION=bpy.data.collections.new('照片补充 · '+name)
    bpy.context.scene.collection.children.link(COLLECTION)

def finish(o,name,mat):
    o.name=name
    for c in list(o.users_collection):c.objects.unlink(o)
    COLLECTION.objects.link(o)
    o['来源']='产品照片估算；非原厂 CAD'
    o.data.materials.append(MATS[mat])
    return o

def bevel(o,width=.002):
    m=o.modifiers.new('边缘加工圆角','BEVEL');m.width=width;m.segments=3
    m=o.modifiers.new('加权法线','WEIGHTED_NORMAL');m.keep_sharp=True
    return o

def box(name,center,size,mat='gray',radius=.002):
    bpy.ops.mesh.primitive_cube_add(size=1,location=center);o=bpy.context.object;o.scale=size
    bpy.ops.object.transform_apply(location=False,rotation=False,scale=True)
    finish(o,name,mat)
    if radius:bevel(o,radius)
    return o

def mesh(name,vertices,faces,mat):
    data=bpy.data.meshes.new(name);data.from_pydata(vertices,[],faces);data.update()
    o=bpy.data.objects.new(name,data);COLLECTION.objects.link(o);o['来源']='产品照片估算；非原厂 CAD';data.materials.append(MATS[mat]);return o

def cyl(name,a,b,r,mat='steel',vertices=48,r2=None):
    a,b=Vector(a),Vector(b)
    bpy.ops.mesh.primitive_cone_add(vertices=vertices,radius1=r,radius2=r if r2 is None else r2,depth=(b-a).length,location=(a+b)/2)
    o=bpy.context.object;o.rotation_euler=(b-a).to_track_quat('Z','Y').to_euler();finish(o,name,mat)
    for p in o.data.polygons:p.use_smooth=len(p.vertices)==4
    return bevel(o,.0007)

def tube(name,points,r=.009,mat='rubber',smooth=True):
    data=bpy.data.curves.new(name,'CURVE');data.dimensions='3D';data.resolution_u=12;data.bevel_depth=r;data.bevel_resolution=3
    if smooth:
        spline=data.splines.new('BEZIER');spline.bezier_points.add(len(points)-1)
        for p,co in zip(spline.bezier_points,points):p.co=co;p.handle_left_type='AUTO';p.handle_right_type='AUTO'
    else:
        spline=data.splines.new('POLY');spline.points.add(len(points)-1)
        for p,co in zip(spline.points,points):p.co=(*co,1)
    o=bpy.data.objects.new(name,data);COLLECTION.objects.link(o);o['来源']='产品照片估算；非原厂 CAD';data.materials.append(MATS[mat]);return o

def ring(name,center,r,thickness,mat='steel',axis='X'):
    bpy.ops.mesh.primitive_torus_add(major_radius=r,minor_radius=thickness,major_segments=48,minor_segments=10,location=center)
    o=bpy.context.object
    if axis=='X':o.rotation_euler.y=math.pi/2
    elif axis=='Y':o.rotation_euler.x=math.pi/2
    finish(o,name,mat)
    for p in o.data.polygons:p.use_smooth=True
    return o

def bolt(name,center,axis='X',side=1,r=.009):
    c=Vector(center);d=Vector((side*.006,0,0) if axis=='X' else (0,0,.006))
    cyl(name+' · 垫片',c-d*.3,c+d*.2,r*1.4,'steel',32)
    return cyl(name,c,c+d,r,'steel',6)
