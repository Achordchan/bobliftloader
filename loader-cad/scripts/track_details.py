"""Rubber track and wheel approximations fitted around original CAD track frames."""
import math
import detail_geometry as g

def build():
    # Coordinates follow CAD mounting points; radii and tread spacing follow photographs.
    wheels=[(-.42,.15,.12),(-.34,.365,.12),(.43,.15,.12)]
    contour=[];inner=[]
    for i in range(160):
        a=i*math.tau/160
        y,z,r=max(wheels,key=lambda q:q[0]*math.cos(a)+q[1]*math.sin(a)+q[2])
        contour.append((y+(r+.024)*math.cos(a),z+(r+.024)*math.sin(a)))
        inner.append((y+(r-.004)*math.cos(a),z+(r-.004)*math.sin(a)))
    lengths=[math.dist(contour[i],contour[(i+1)%len(contour)]) for i in range(len(contour))]
    perimeter=sum(lengths)
    def sample(distance):
        i=0
        while distance>lengths[i] and i<len(lengths)-1:distance-=lengths[i];i+=1
        a,b=contour[i],contour[(i+1)%len(contour)];t=distance/max(lengths[i],1e-9)
        return a[0]+(b[0]-a[0])*t,a[1]+(b[1]-a[1])*t,math.atan2(b[1]-a[1],b[0]-a[0])
    for side,label in [(-1,'左'),(1,'右')]:
        g.collection(label+'橡胶履带与轮组');x=side*.445;n=len(contour)
        verts=[(x+s*.09,y,z) for c in [contour,inner] for s in [-1,1] for y,z in c]
        faces=[]
        for i in range(n):
            j=(i+1)%n
            faces.extend([(i,j,n+j,n+i),(2*n+i,3*n+i,3*n+j,2*n+j),(i,2*n+i,2*n+j,j),(n+i,n+j,3*n+j,3*n+i)])
        belt=g.mesh(label+'连续履带',verts,faces,'rubber')
        for p in belt.data.polygons:p.use_smooth=True
        for i in range(46):
            y,z,a=sample(i*perimeter/46)
            for offset in [-.047,.047]:
                pad=g.box(label+f'履带块 {i+1:02}',(x+offset,y+math.sin(a)*.009,z-math.cos(a)*.009),(.083,.049,.017),'rubber',.004)
                pad.rotation_euler=(a,0,.20 if offset<0 else -.20)
            g.box(label+'内导齿',(x,y-math.sin(a)*.03,z+math.cos(a)*.03),(.028,.025,.026),'rubber',.003).rotation_euler.x=a
        for index,(y,z,r) in enumerate(wheels):
            g.cyl(label+'轮体',(x-.043,y,z),(x+.043,y,z),r-.008,'black')
            face=x+side*.045
            g.cyl(label+'轮盘',(face,y,z),(face+side*.006,y,z),r-.025,'gray')
            g.ring(label+'轮缘',(face+side*.007,y,z),r-.026,.005,'steel')
            g.cyl(label+'轮毂',(face,y,z),(face+side*.019,y,z),.041,'black')
            g.bolt(label+'轮轴',(face+side*.02,y,z),side=side,r=.014)
            for j in range(6):
                a=j*math.tau/6;yy=y+.064*math.cos(a);zz=z+.064*math.sin(a)
                g.bolt(label+'轮盘螺栓',(face+side*.007,yy,zz),side=side,r=.005)
            if index==1:
                for j in range(14):
                    a=j*math.tau/14
                    o=g.box(label+'驱动齿',(x,y+r*math.cos(a),z+r*math.sin(a)),(.11,.024,.024),'black',.003);o.rotation_euler.x=a
                for j in range(6):
                    a=j*math.tau/6
                    g.cyl(label+'驱动盘凹孔',(face+side*.007,y+.092*math.cos(a),z+.092*math.sin(a)),(face+side*.009,y+.092*math.cos(a),z+.092*math.sin(a)),.02,'black')
        for y in [-.26,-.08,.10,.28]:
            z=.12;g.cyl(label+'承重轮',(x-.039,y,z),(x+.039,y,z),.08,'black')
            g.cyl(label+'承重轮毂',(x+side*.04,y,z),(x+side*.045,y,z),.055,'gray')
        for y in [-.39,-.08,.24,.44]:g.bolt(label+'护板螺栓',(side*.509,y,.165),side=side,r=.009)
