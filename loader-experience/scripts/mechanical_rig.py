"""Visual-study rig using CAD pivots; not a manufacturer kinematic specification."""
import math
import re
import bpy
from mathutils import Matrix, Vector

PIVOT = Vector((0, -.245163891, 1.036337642))
BUCKET_PIVOT = Vector((0, .898355, .581884))
REST_TILT = math.atan2(.1291, .3016)


def rotation_about(point, angle):
    return Matrix.Translation(point) @ Matrix.Rotation(angle, 4, 'X') @ Matrix.Translation(-point)


def smooth(value):
    value = max(0, min(1, value))
    return value * value * (3 - 2 * value)


def pose(progress):
    if progress < .34:
        lift, tilt = -25 + 73*smooth(progress/.34), REST_TILT
    elif progress < .58:
        lift, tilt = 48, REST_TILT + (math.radians(-43)-REST_TILT)*smooth((progress-.34)/.24)
    elif progress < .78:
        lift, tilt = 48, math.radians(-43)+(REST_TILT-math.radians(-43))*smooth((progress-.58)/.20)
    else:
        lift, tilt = 48-73*smooth((progress-.78)/.22), REST_TILT
    arm = rotation_about(PIVOT, math.radians(lift))
    bucket = Matrix.Translation(arm @ BUCKET_PIVOT) @ Matrix.Rotation(tilt, 4, 'X') @ Matrix.Translation(-BUCKET_PIVOT)
    return arm, bucket


def closest(prefix, point, excluded=()):
    candidates = [o for o in bpy.context.scene.objects if o.name.startswith(prefix) and o not in excluded]
    if not candidates:
        raise RuntimeError(f'Missing rig component: {prefix}')
    return min(candidates, key=lambda o: (o.matrix_world.translation-point).length)


def put_cylinder(obj, start, end):
    direction = end-start
    depth = max(v.co.z for v in obj.data.vertices)-min(v.co.z for v in obj.data.vertices)
    obj.location = (start+end)/2
    obj.rotation_mode = 'QUATERNION'
    obj.rotation_quaternion = direction.to_track_quat('Z', 'Y')
    obj.scale.z = direction.length/depth


class CylinderRig:
    def __init__(self, prefix, initial_start, initial_end, minimum_length):
        self.prefix = prefix
        self.initial_length = (initial_end-initial_start).length
        self.barrel_length = min(self.initial_length*.635, minimum_length-.095)
        midpoint = (initial_start+initial_end)/2
        self.pieces = {kind: closest(prefix+kind, midpoint) for kind in ['缸筒', '缸底', '导向套', '镀铬活塞杆']}
        self.ends = []
        used=[]
        for point in [initial_start, initial_end]:
            pair=[closest(prefix+kind,point,used) for kind in ['铰接头','铰轴']]
            used.extend(pair);self.ends.append(pair)
        self.objects = list(self.pieces.values()) + [o for pair in self.ends for o in pair]

    def apply(self, start, end):
        direction = (end-start).normalized()
        base = start + direction*.03
        seal = base + direction*self.barrel_length
        rod_end = end-direction*.025
        if (rod_end-seal).dot(direction)<.02:
            raise RuntimeError('Cylinder rod exceeds visual-study travel')
        put_cylinder(self.pieces['缸筒'],base,seal)
        put_cylinder(self.pieces['缸底'],start+direction*.014,base+direction*.018)
        put_cylinder(self.pieces['导向套'],seal-direction*.012,seal+direction*.012)
        put_cylinder(self.pieces['镀铬活塞杆'],seal,rod_end)
        for point,pair in zip([start,end],self.ends):
            for obj,width in zip(pair,[.019,.032]):
                put_cylinder(obj,point-Vector((width,0,0)),point+Vector((width,0,0)))
        return direction


class MachineRig:
    def __init__(self):
        self.base = {o: o.matrix_world.copy() for o in bpy.context.scene.objects}
        self.arm_rest,self.bucket_rest = pose(0)
        self.arm_objects=[];self.bucket_objects=[]
        accessories=('大臂镀锌硬管','软管压接套','硬管固定夹','液压快接头','快接红色防尘帽','举升前销','铲斗连接销')
        for obj in bpy.context.scene.objects:
            path=obj.get('装配路径','')
            if '大臂焊接' in path or obj.name.startswith(accessories):self.arm_objects.append(obj)
            elif '铲斗焊接' in path or '快换焊接' in path:self.bucket_objects.append(obj)
        sampled=[pose(i/100) for i in range(101)]
        self.lift=[]
        for side in [-1,1]:
            a=Vector((side*.355,-.405,.631));b=self.arm_rest@Vector((side*.355,.469,.824))
            length=min((arm@Vector((side*.355,.469,.824))-a).length for arm,_ in sampled)
            self.lift.append((side,CylinderRig('举升油缸',a,b,length)))
        a=self.arm_rest@Vector((0,.562,1.141));b=self.bucket_rest@Vector((0,1.025,.664))
        length=min((bucket@Vector((0,1.025,.664))-arm@Vector((0,.562,1.141))).length for arm,bucket in sampled)
        self.tilt=CylinderRig('铲斗翻转油缸',a,b,length)
        self.hoses=[]
        for obj in bpy.context.scene.objects:
            if obj.type=='CURVE' and obj.name.startswith(('高压软管','举升缸供油管','翻斗缸软管')):
                points=[p.co.copy() for p in obj.data.splines[0].bezier_points]
                self.hoses.append((obj,points))
        self.ports=[o for o in bpy.context.scene.objects if o.name.startswith(('举升缸油口','翻斗缸油口'))]
        self.moving=set(self.arm_objects+self.bucket_objects+self.ports+self.tilt.objects)
        for _,rig in self.lift:self.moving.update(rig.objects)
        self.prepare_tracks()

    def apply(self, progress, track_progress):
        arm,bucket=pose(progress)
        delta=arm@self.arm_rest.inverted()
        bucket_delta=bucket@self.bucket_rest.inverted()
        for o in self.arm_objects:o.matrix_world=delta@self.base[o]
        for o in self.bucket_objects:o.matrix_world=bucket_delta@self.base[o]
        ports=[]
        for side,rig in self.lift:
            a=Vector((side*.355,-.405,.631));b=arm@Vector((side*.355,.469,.824))
            direction=rig.apply(a,b)
            for fraction in [.15,.64]:
                p=a+direction*(rig.initial_length*fraction);tip=p+Vector((side*.029,0,.009))
                ports.append((side,fraction,p,tip))
        a=arm@Vector((0,.562,1.141));b=bucket@Vector((0,1.025,.664))
        direction=self.tilt.apply(a,b)
        tilt_ports=[]
        for fraction,x in [(.12,-.055),(.62,.055)]:
            p=a+direction*(self.tilt.initial_length*fraction);tip=p+Vector((x,0,0));tilt_ports.append((p,tip))
        for obj,original in self.hoses:
            points=obj.data.splines[0].bezier_points
            if obj.name.startswith('高压软管'):
                for i,p in enumerate(points):
                    weight=0 if i<2 else .35 if i==2 else 1
                    p.co=original[i].lerp(delta@original[i],weight)
            elif obj.name.startswith('举升缸供油管'):
                side=-1 if original[0].x<0 else 1
                rest_a=Vector((side*.355,-.405,.631));rest_b=self.arm_rest@Vector((side*.355,.469,.824))
                fraction=min([.15,.64],key=lambda f:(original[-1]-(rest_a+(rest_b-rest_a)*f+Vector((side*.029,0,.009)))).length)
                port=next(p for p in ports if p[0]==side and p[1]==fraction)
                for i,p in enumerate(points):p.co=original[i] if i<len(points)-1 else port[3]
            else:
                index=0 if original[-1].x<0 else 1
                for i,p in enumerate(points):p.co=delta@original[i] if i<len(points)-1 else tilt_ports[index][1]
        # Match inlet fittings once by their stable source locations.
        for obj in self.ports:
            original=self.base[obj].translation
            if obj.name.startswith('举升缸油口'):
                side=-1 if original.x<0 else 1
                a0=Vector((side*.355,-.405,.631));b0=self.arm_rest@Vector((side*.355,.469,.824))
                fraction=min([.15,.64],key=lambda f:(original-(a0+(b0-a0)*f+Vector((side*.0145,0,.0045)))).length)
                p=next(p for p in ports if p[0]==side and p[1]==fraction);put_cylinder(obj,p[2],p[3])
            else:
                put_cylinder(obj,*tilt_ports[0 if original.x<0 else 1])
        self.apply_tracks(track_progress)
        bpy.context.view_layer.update()

    def prepare_tracks(self):
        wheels=[(-.42,.15,.12),(-.34,.365,.12),(.43,.15,.12)]
        self.contour=[]
        for i in range(160):
            a=i*math.tau/160
            y,z,r=max(wheels,key=lambda q:q[0]*math.cos(a)+q[1]*math.sin(a)+q[2])
            self.contour.append((y+(r+.024)*math.cos(a),z+(r+.024)*math.sin(a)))
        self.lengths=[math.dist(self.contour[i],self.contour[(i+1)%160]) for i in range(160)]
        self.perimeter=sum(self.lengths)
        self.pads=[];self.teeth=[];self.wheels=[]
        for obj in bpy.context.scene.objects:
            match=re.match(r'([左右])履带块 (\d+)',obj.name)
            if match:
                self.pads.append((obj,int(match[2])-1,obj.location.x));self.moving.add(obj)
            elif re.match(r'[左右]内导齿',obj.name):
                y,z=obj.location.y,obj.location.z
                index=min(range(46),key=lambda i:math.dist((y,z),self.sample(i*self.perimeter/46)[:2]))
                self.teeth.append((obj,index,obj.location.x));self.moving.add(obj)
            elif re.match(r'[左右](轮体|轮盘|轮缘|轮毂|轮轴|驱动齿|驱动盘|承重轮)',obj.name):
                centers=[(y,.12,.08) for y in [-.26,-.08,.10,.28]] if '承重轮' in obj.name else wheels
                y,z,r=min(centers,key=lambda w:math.dist((obj.location.y,obj.location.z),w[:2]))
                self.wheels.append((obj,Vector((obj.location.x,y,z)),r));self.moving.add(obj)

    def sample(self, distance):
        distance%=self.perimeter;i=0
        while distance>self.lengths[i] and i<159:distance-=self.lengths[i];i+=1
        a,b=self.contour[i],self.contour[(i+1)%160];t=distance/max(self.lengths[i],1e-9)
        return a[0]+(b[0]-a[0])*t,a[1]+(b[1]-a[1])*t,math.atan2(b[1]-a[1],b[0]-a[0])

    def apply_tracks(self, progress):
        travel=progress*self.perimeter/46*12
        for obj,index,x in self.pads:
            y,z,a=self.sample(index*self.perimeter/46+travel)
            obj.location=(x,y+math.sin(a)*.009,z-math.cos(a)*.009)
            offset=x-(-.445 if x<0 else .445)
            obj.rotation_euler=(a,0,.20 if offset<0 else -.20)
        for obj,index,x in self.teeth:
            y,z,a=self.sample(index*self.perimeter/46+travel)
            obj.location=(x,y-math.sin(a)*.03,z+math.cos(a)*.03);obj.rotation_euler.x=a
        for obj,center,r in self.wheels:
            obj.matrix_world=rotation_about(center,-math.tau*progress*.12/r)@self.base[obj]

    def keyframe(self, frame):
        for obj in self.moving:
            for path in ['location','rotation_quaternion' if obj.rotation_mode=='QUATERNION' else 'rotation_euler','scale']:
                obj.keyframe_insert(data_path=path,frame=frame)
        for obj,_ in self.hoses:
            for i,_ in enumerate(obj.data.splines[0].bezier_points):
                obj.data.keyframe_insert(data_path=f'splines[0].bezier_points[{i}].co',frame=frame)
