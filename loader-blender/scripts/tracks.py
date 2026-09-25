"""Hollow rubber track loops, elevated sprockets and service guards."""
import math
import geometry as g


def build():
    wheels=[(.77,.30,.22),(-.37,.78,.215),(-.79,.30,.235)]
    outer=[];inner=[]
    for i in range(128):
        a=i*math.tau/128
        z,y,r=max(wheels,key=lambda q:q[0]*math.cos(a)+q[1]*math.sin(a)+q[2])
        outer.append((z+(r+.033)*math.cos(a),y+(r+.033)*math.sin(a)))
        inner.append((z+(r-.027)*math.cos(a),y+(r-.027)*math.sin(a)))
    lengths=[math.dist(outer[i],outer[(i+1)%len(outer)]) for i in range(len(outer))]
    perimeter=sum(lengths)
    def sample(distance):
        i=0
        while distance>lengths[i] and i<len(lengths)-1:
            distance-=lengths[i];i+=1
        a,b=outer[i],outer[(i+1)%len(outer)];t=distance/max(lengths[i],.000001)
        return a[0]+(b[0]-a[0])*t,a[1]+(b[1]-a[1])*t,math.atan2(b[1]-a[1],b[0]-a[0])
    for side,label in [(-1,'左'),(1,'右')]:
        g.collection('02 · '+label+'履带总成')
        x=side*.65;n=len(outer)
        vertices=[(x+s*.145,y,z) for contour in [outer,inner] for s in [-1,1] for z,y in contour]
        faces=[]
        for i in range(n):
            j=(i+1)%n
            faces += [(i,j,n+j,n+i),(2*n+i,3*n+i,3*n+j,2*n+j),
                      (i,2*n+i,2*n+j,j),(n+i,n+j,3*n+j,3*n+i)]
        g.mesh(label+' · 连续橡胶带',vertices,faces,'rubber')
        for i in range(48):
            z,y,angle=sample(i*perimeter/48)
            pad=g.box(label+f' · 胎纹 {i+1:02d}',.32,.036,.071,x,y,z,'rubber',bevel=.005)
            pad.rotation_euler.x=angle
            for offset in [-.075,.075]:
                lug=g.box(label+' · 斜向抓地块',.135,.024,.040,x+offset,y-math.cos(angle)*.026,z+math.sin(angle)*.026,'rubber',bevel=.004)
                lug.rotation_euler.x=angle
                lug.rotation_euler.z=.16 if offset>0 else -.16
        g.profile(label+' · 内侧暗色支架',[(-.91,.18),(-.84,.45),(-.37,.89),(.76,.40),(.9,.18)],.025,x-side*.135,'black')
        for index,(z,y,r) in enumerate(wheels):
            g.rod(label+' · 轮体',(x-.119,y,z),(x+.119,y,z),r-.02,'dark')
            face=x+side*.139
            g.ring(label+' · 轮缘',(face,y,z),r-.04,.01,'black')
            g.rod(label+' · 轮毂',(face,y,z),(face+side*.028,y,z),.065,'steel')
            g.bolt(label+' · 中心螺栓',face+side*.029,y,z,radius=.022,side=side)
            if index==1:
                for i in range(15):
                    a=i*math.tau/15
                    tooth=g.box(label+' · 驱动齿',.21,.039,.048,x,y+math.sin(a)*r,z+math.cos(a)*r,'wear',bevel=.004)
                    tooth.rotation_euler.x=-a
                disc=g.rod(label+' · 驱动轮盘',(face,y,z),(face+side*.012,y,z),.174,'dark')
                for i in range(7):
                    a=i*math.tau/7
                    g.rod(label+' · 轮盘镂空暗面',(face+side*.013,y+math.sin(a)*.111,z+math.cos(a)*.111),(face+side*.015,y+math.sin(a)*.111,z+math.cos(a)*.111),.03,'black',bevel=0)
            for i in range(6):
                a=i*math.tau/6
                g.bolt(label+' · 轮毂螺钉',face+side*.019,y+math.sin(a)*.088,z+math.cos(a)*.088,radius=.008,side=side)
        for i in range(6):
            z=-.59+i*.24;y=.205
            g.rod(label+' · 承重轮',(x-.10,y,z),(x+.10,y,z),.13,'rubber')
            g.rod(label+' · 承重轮侧盖',(x+side*.102,y,z),(x+side*.115,y,z),.10,'dark')
            g.ring(label+' · 承重轮缘',(x+side*.119,y,z),.088,.006,'steel')
        guard=[(-.90,.2),(-.89,.3),(-.65,.59),(-.51,.49),(-.39,.46),(-.22,.60),(.78,.37),(.90,.26),(.79,.18),(-.72,.16)]
        panel=g.profile(label+' · 钢制履带护板',guard,.026,x+side*.159,'dark',bevel=.006)
        holes=[((x+side*.159,.22,-.66+i*.215),.017,.1) for i in range(7)]
        g.boolean_holes(panel,holes)
        g.profile(label+' · 检修盖',[(-.22,.205),(-.22,.345),(.12,.345),(.19,.29),(.17,.205)],.007,x+side*.177,'dark',bevel=.002)
        for z,y in [(-.78,.29),(-.16,.31),(.12,.29),(.73,.28)]:
            g.bolt(label+' · 护板紧固件',x+side*.182,y,z,radius=.011,side=side)
