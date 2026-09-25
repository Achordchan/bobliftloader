"""Native editable mesh utilities. Input coordinates are (width, height, front)."""
import math
import bpy
import bmesh
from mathutils import Vector

MATS = {}
ROOT = None
COLLECTION = None


def v(point):
    x, height, front = point
    return Vector((x, front, height))


def collection(name):
    global COLLECTION
    COLLECTION = bpy.data.collections.new(name)
    bpy.context.scene.collection.children.link(COLLECTION)
    return COLLECTION


def link(obj, parent=None):
    COLLECTION.objects.link(obj)
    obj.parent = ROOT if parent is None else parent
    return obj


def empty(name, position=(0, 0, 0), parent=None):
    obj = link(bpy.data.objects.new(name, None), parent)
    obj.location = v(position)
    obj.empty_display_type = 'PLAIN_AXES'
    obj.empty_display_size = .15
    return obj


def finish(obj, mat, bevel=0, smooth=False):
    obj.data.materials.append(MATS[mat] if isinstance(mat, str) else mat)
    if smooth:
        for polygon in obj.data.polygons:
            polygon.use_smooth = True
    if bevel > 0:
        mod = obj.modifiers.new('加工倒角', 'BEVEL')
        mod.width = bevel
        mod.segments = 3
        mod.limit_method = 'ANGLE'
        mod.angle_limit = math.radians(25)
        mod.harden_normals = True
        mod = obj.modifiers.new('板件加权法线', 'WEIGHTED_NORMAL')
        mod.keep_sharp = True
        mod.weight = 50
    return obj


def mesh(name, vertices, faces, mat, parent=None, bevel=0, smooth=False):
    data = bpy.data.meshes.new(name)
    data.from_pydata([v(p) for p in vertices], [], faces)
    data.update()
    bm = bmesh.new()
    bm.from_mesh(data)
    bmesh.ops.recalc_face_normals(bm, faces=list(bm.faces))
    bm.to_mesh(data)
    bm.free()
    return finish(link(bpy.data.objects.new(name, data), parent), mat, bevel, smooth)


def box(name, w, h, d, x, y, z, mat='dark', parent=None, bevel=.003):
    vertices = [(a*w/2, b*h/2, c*d/2) for a,b,c in
                [(-1,-1,-1),(1,-1,-1),(1,1,-1),(-1,1,-1),(-1,-1,1),(1,-1,1),(1,1,1),(-1,1,1)]]
    faces = [(0,3,2,1),(4,5,6,7),(0,1,5,4),(3,7,6,2),(0,4,7,3),(1,2,6,5)]
    obj = mesh(name, vertices, faces, mat, parent, min(bevel, min(w,h,d)*.3))
    obj.location = v((x,y,z))
    return obj


def profile(name, points, width, x=0, mat='paint', parent=None, bevel=.005):
    n = len(points)
    vertices = [(x+s*width/2,y,z) for s in [-1,1] for z,y in points]
    faces = [tuple(reversed(range(n))), tuple(range(n,2*n))]
    faces += [(i,(i+1)%n,(i+1)%n+n,i+n) for i in range(n)]
    return mesh(name,vertices,faces,mat,parent,bevel)


def rod(name, a, b, radius, mat='steel', parent=None, vertices=40, r2=None, bevel=.0015):
    av,bv = v(a),v(b)
    length = (bv-av).length
    r2 = radius if r2 is None else r2
    verts = [(radius*math.cos(i*2*math.pi/vertices), radius*math.sin(i*2*math.pi/vertices),-length/2)
             for i in range(vertices)]
    verts += [(r2*math.cos(i*2*math.pi/vertices),r2*math.sin(i*2*math.pi/vertices),length/2)
              for i in range(vertices)]
    data = bpy.data.meshes.new(name)
    faces = [tuple(reversed(range(vertices))),tuple(range(vertices,2*vertices))]
    faces += [(i,(i+1)%vertices,(i+1)%vertices+vertices,i+vertices) for i in range(vertices)]
    data.from_pydata(verts,[],faces)
    data.update()
    obj = link(bpy.data.objects.new(name,data),parent)
    obj.location = (av+bv)*.5
    obj.rotation_mode='QUATERNION'
    obj.rotation_quaternion=(bv-av).to_track_quat('Z','Y')
    for p in data.polygons:
        p.use_smooth = len(p.vertices)==4 and vertices>8
    return finish(obj,mat,min(bevel,radius*.2))


def hose(name, points, radius=.014, mat='rubber', parent=None):
    data=bpy.data.curves.new(name,'CURVE')
    data.dimensions='3D'
    data.resolution_u=10
    data.bevel_depth=radius
    data.bevel_resolution=3
    spline=data.splines.new('BEZIER')
    spline.bezier_points.add(len(points)-1)
    for p,coord in zip(spline.bezier_points,points):
        p.co=v(coord)
        p.handle_left_type=p.handle_right_type='AUTO'
    data.materials.append(MATS[mat])
    return link(bpy.data.objects.new(name,data),parent)


def ring(name, pos, radius, tube, mat='steel', parent=None, axis='x'):
    major,minor=40,8
    verts=[]
    for i in range(major):
        a=i*math.tau/major
        for j in range(minor):
            b=j*math.tau/minor
            c=(radius+tube*math.cos(b))*math.cos(a)
            d=(radius+tube*math.cos(b))*math.sin(a)
            t=tube*math.sin(b)
            verts.append((t,c,d) if axis=='x' else (c,t,d) if axis=='y' else (c,d,t))
    faces=[]
    for i in range(major):
        for j in range(minor):
            faces.append((i*minor+j,((i+1)%major)*minor+j,((i+1)%major)*minor+(j+1)%minor,i*minor+(j+1)%minor))
    obj=mesh(name,verts,faces,mat,parent,smooth=True)
    obj.location=v(pos)
    return obj


def bolt(name, x, y, z, parent=None, radius=.017, side=None):
    side=side or (1 if x>=0 else -1)
    rod(name+' 垫片',(x,y,z),(x+side*.005,y,z),radius*1.4,parent=parent,bevel=.001)
    rod(name+' 六角头',(x+side*.005,y,z),(x+side*.016,y,z),radius,parent=parent,vertices=6,bevel=.001)
    rod(name+' 中心凹槽',(x+side*.016,y,z),(x+side*.017,y,z),radius*.32,'black',parent,vertices=6,bevel=0)


def boolean_holes(target, holes, axis='x', sides=24):
    """Use a single native Boolean cutter for all apertures of one plate."""
    vertices,faces=[],[]
    for center,radius,depth in holes:
        start=len(vertices)
        for sign in [-1,1]:
            for i in range(sides):
                a=i*math.tau/sides
                if axis=='x': p=(center[0]+sign*depth/2,center[1]+math.sin(a)*radius,center[2]+math.cos(a)*radius)
                else: p=(center[0]+math.cos(a)*radius,center[1]+math.sin(a)*radius,center[2]+sign*depth/2)
                vertices.append(p)
        faces.append(tuple(start+i for i in reversed(range(sides))))
        faces.append(tuple(start+sides+i for i in range(sides)))
        faces.extend((start+i,start+(i+1)%sides,start+(i+1)%sides+sides,start+i+sides) for i in range(sides))
    cutter=mesh(target.name+' · 切孔工具',vertices,faces,'dark',target.parent)
    mod=target.modifiers.new('真实开孔','BOOLEAN')
    mod.operation='DIFFERENCE';mod.solver='EXACT';mod.object=cutter
    # Keep cutters editable but excluded from the final image.
    cutter.hide_render=True;cutter.hide_set(True);cutter.display_type='WIRE'
    return cutter
