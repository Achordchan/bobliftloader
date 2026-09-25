"""Native boom hierarchy, hydraulic fittings and a rolled steel bucket shell."""
import math
import geometry as g


def build():
    g.collection('03 · 举升臂与液压机构')
    boom=g.empty('举升臂总成 · 后铰点',(0,1.86,-.70))
    boom['说明']='围绕本地 X 轴举升；液压系统当前为展示姿态，不是完整动力学绑定。'
    arm=[(-.13,.015),(-.08,.10),(.06,.13),(.18,.085),(.29,-.06),(1.57,-.91),(1.75,-1.05),(1.82,-1.22),(1.77,-1.49),(1.64,-1.49),(1.57,-1.17),(.27,-.33),(.02,-.27),(-.12,-.15)]
    for s in [-1,1]:
        g.profile('橙色举升主臂',arm,.106,s*.535,'paint',boom,bevel=.013)
        g.profile('后部铰链加强板',[(-.12,.015),(-.06,.12),(.12,.10),(.30,-.11),(.29,-.24),(.20,-.31),(.03,-.25),(-.12,-.12)],.02,s*.602,'paint',boom,bevel=.01)
        g.profile('举升缸耳板',[(.02,-.25),(.16,-.26),(.26,-.38),(.17,-.61),(.06,-.54)],.053,s*.525,'paint',boom,bevel=.008)
        skin=g.box('举升臂冲压加强筋',.014,.145,.38,s*.60,-.87,1.28,'paint',boom,bevel=.006)
        skin.rotation_euler.x=-.58
        front=g.profile('前端铰链侧板',[(1.58,-1.12),(1.76,-1.09),(1.81,-1.22),(1.76,-1.49),(1.64,-1.48)],.019,s*.6,'paint',boom,bevel=.006)
        g.boolean_holes(front,[((s*.60,-1.255,1.728),.022,.1)])
        for z,y,r in [(-.015,-.015,.030),(.21,-.26,.022),(.155,-.53,.02),(1.70,-1.435,.022)]:
            g.rod('轴销衬套',(s*.58,y,z),(s*.62,y,z),r*1.42,'steel',boom)
            g.bolt('举升臂轴销',s*.625,y,z,boom,r,s)
        for offset in [0,.044]:
            points=[(s*(.49+offset),-.12,-.18),(s*(.49+offset),.11,.05),(s*(.49+offset),.065,.28),(s*(.49+offset),-.19,.52)]
            g.hose('主油管波纹护套',points,.019,'rubber',boom)
            for i in range(18):
                t=i/17
                y=.108-.04*t;z=.04+.235*t
                ring=g.ring('油管护套环纹',(s*(.49+offset),y,z),.019,.0022,'black',boom,'z')
                ring.rotation_euler.x=-.16
        g.hose('臂内液压软管',[(s*.42,-.31,.21),(s*.43,-.56,.61),(s*.43,-.91,1.12),(s*.37,-1.12,1.51)],.014,'rubber',boom)
        g.rod('外置金属油管',(s*.52,-.51,.63),(s*.52,-.81,1.16),.009,'steel',boom)
        for z in [.71,.98]:g.box('油管固定夹',.027,.024,.032,s*.54,-.51-(z-.63)*.57,z,'black',boom)
    g.box('前部横梁',1.02,.17,.14,0,-1.17,1.62,'paint',boom,bevel=.008)
    for s in [-1,1]:
        g.profile('中央翻斗油缸耳板',[(1.32,-.69),(1.42,-.66),(1.57,-.91),(1.67,-1.26),(1.54,-1.33),(1.39,-1.10)],.035,s*.137,'paint',boom,bevel=.006)
        g.bolt('中央缸上铰销',s*.169,-.745,1.38,boom,.019,s)
    g.box('辅助液压阀块',.11,.35,.044,.34,-.89,1.66,'steel',boom,bevel=.009)
    for i in range(3):
        y=-.77-i*.11
        g.rod('快接六角接头',(.34,y,1.66),(.34,y,1.71),.035,'steel',boom,vertices=6)
        g.rod('快接套筒',(.34,y,1.71),(.34,y,1.785),.029,'steel',boom)
        for j in range(5):g.ring('快接套筒车削纹',(.34,y,1.727+j*.010),.03,.0014,'black',boom,'z')
        g.rod('快接防尘帽',(.34,y,1.781),(.34,y,1.796),.021,'red' if i==1 else 'black',boom)
        g.hose('阀块液压管',[(.34,y,1.63),(.4,y+.015,1.57),(.43,y-.08,1.49),(.39,-1.15,1.4)],.014,'rubber',boom)
    g.hose('防尘帽系绳',[(.34,-.88,1.79),(.42,-.92,1.82),(.43,-1.02,1.78),(.34,-1.01,1.76)],.0035,'red',boom)
    for s in [-1,1]:
        g.rod('快换锁销',(s*.32,-1.18,1.72),(s*.32,-1.06,1.72),.016,'steel',boom)
        g.rod('快换锁销手柄',(s*.32,-1.06,1.72),(s*.42,-1.06,1.72),.015,'paint',boom)
        g.ring('快换锁销拉环',(s*.405,-1.10,1.745),.028,.0035,'paint',boom,'z')
    bucket=build_bucket(boom)
    # Cylinder dimensions follow the displayed lowered pose.
    for s in [-1,1]:
        a=(s*.46,.88,-.82);b=(s*.46,1.33,-.545)
        av,bv=g.v(a),g.v(b);mid=av+(bv-av).normalized()*.43
        m=(mid.x,mid.z,mid.y)
        g.rod('举升油缸缸筒',a,m,.049,'black')
        g.rod('举升油缸活塞杆',m,b,.024,'chrome')
        g.ring('举升缸压盖',m,.049,.005,'steel',axis='y')
    a=(0,1.11,.68);b=(0,.67,1.10)
    av,bv=g.v(a),g.v(b);mid=av+(bv-av).normalized()*.42;m=(mid.x,mid.z,mid.y)
    g.rod('中央翻斗缸缸筒',a,m,.048,'black')
    g.rod('中央翻斗缸活塞杆',m,b,.022,'chrome')
    g.rod('中央翻斗缸端盖',m,(m[0],m[1]-.015,m[2]+.015),.052,'steel')
    guard=g.box('翻斗缸保护板',.19,.15,.04,0,.82,.98,'dark',bevel=.003);guard.rotation_euler.x=-.60
    g.hose('翻斗缸供油管',[(-.05,1.08,.67),(-.14,.90,.84),(-.12,.72,1.02),(-.03,.67,1.10)],.012)
    return boom,bucket


def build_bucket(boom):
    g.collection('04 · 铲斗与快换架')
    bucket=g.empty('铲斗总成 · 翻转铰点',(0,-1.49,2.06),boom)
    bucket['说明']='原生网格、厚度和倒角修饰器，可单独编辑。'
    p0=(-.11,.46);p1=(-.105,.06);p2=(-.055,-.042);p3=(.17,-.116)
    curve=[]
    for i in range(33):
        t=i/32;u=1-t
        curve.append((u**3*p0[0]+3*u*u*t*p1[0]+3*u*t*t*p2[0]+t**3*p3[0],u**3*p0[1]+3*u*u*t*p1[1]+3*u*t*t*p2[1]+t**3*p3[1]))
    curve.append((.74,-.286))
    vertices=[(x,y,z) for z,y in curve for x in [-.875,.875]]
    faces=[(2*i,2*i+1,2*i+3,2*i+2) for i in range(len(curve)-1)]
    bowl=g.mesh('连续卷弯铲斗壳体',vertices,faces,'dark',bucket,smooth=True)
    mod=bowl.modifiers.new('18mm 钢板厚度','SOLIDIFY');mod.thickness=.018;mod.offset=-1
    mod=bowl.modifiers.new('板材边缘倒角','BEVEL');mod.width=.002;mod.segments=3
    for s in [-1,1]:
        cheek=[(-.16,.03),(-.15,.435),(-.094,.47),(.018,.445),(.77,-.294),(.67,-.322),(.17,-.315),(-.11,-.16)]
        side=g.profile('铲斗侧板',cheek,.025,s*.884,'dark',bucket,bevel=.004)
        g.boolean_holes(side,[((s*.884,-.22,.25),.019,.12),((s*.884,.31,-.085),.015,.12)])
        g.rod('侧板加强折边',(s*.901,.442,-.08),(s*.901,-.285,.75),.008,'dark',bucket)
        g.hose('内侧焊缝',[(s*.866,.425,-.09),(s*.866,.10,-.07),(s*.866,-.10,.14),(s*.866,-.28,.72)],.0038,'dark',bucket)
        ear=g.profile('快换背部耳板',[(-.26,.11),(-.26,.37),(-.15,.38),(-.15,.11)],.074,s*.36,'dark',bucket,bevel=.005)
        g.boolean_holes(ear,[((s*.36,.25,-.205),.025,.15)])
        g.bolt('铲斗快换轴销',s*.405,.25,-.205,bucket,.021,s)
    g.box('快换背板',.72,.34,.060,0,.15,-.233,'dark',bucket)
    for s in [-1,1]:
        g.box('下部锁销座',.12,.07,.095,s*.3,-.028,-.258,'dark',bucket)
        g.rod('锁定插销',(s*.295,-.06,-.24),(s*.295,.07,-.24),.016,'steel',bucket)
    g.box('翻斗缸连接座',.31,.04,.13,0,.31,-.26,'dark',bucket)
    g.rod('翻斗缸铰轴',(-.1,.30,-.26),(.1,.30,-.26),.025,'steel',bucket)
    g.profile('可更换耐磨铲刃',[(.655,-.268),(.785,-.302),(.794,-.315),(.65,-.297)],1.82,0,'wear',bucket,bevel=.001)
    for i in range(11):
        x=-.81+i*.162
        g.rod('铲刃沉头螺钉',(x,-.286,.723),(x,-.28,.723),.006,'black',bucket,vertices=6,bevel=0)
    for x in [-.65,0,.65]:g.profile('铲斗底部耐磨筋',[(-.11,-.12),(.12,-.215),(.66,-.342),(.71,-.32),(.17,-.18)],.060,x,'dark',bucket,bevel=.002)
    g.rod('铲斗后壁上沿',(-.88,.46,-.112),(.88,.46,-.112),.012,'dark',bucket)
    return bucket
