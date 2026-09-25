"""Photo-derived equipment details; kept distinct from original STEP geometry."""
import math
import bpy
from mathutils import Vector,Matrix
import detail_geometry as g
import track_details
PIVOT=Vector((0,-.245163891,1.036337642))
ANGLE=math.radians(-25.0)
POSE=Matrix.Translation(PIVOT)@Matrix.Rotation(ANGLE,4,'X')@Matrix.Translation(-PIVOT)

def pose(p):return POSE@Vector(p)

BUCKET_PIVOT=Vector((0,.898355,.581884))
BUCKET_ANGLE=math.atan2(.1291,.3016)
BUCKET_POSE=Matrix.Translation(pose(BUCKET_PIVOT))@Matrix.Rotation(BUCKET_ANGLE,4,'X')@Matrix.Translation(-BUCKET_PIVOT)
def bucket_pose(p):return BUCKET_POSE@Vector(p)

def hydraulic(name,a,b,r=.025):
    a,b=Vector(a),Vector(b);d=b-a
    g.cyl(name+'缸筒',a+d*.055,a+d*.69,r,'black')
    g.cyl(name+'缸底',a+d*.02,a+d*.09,r*1.15,'black')
    g.cyl(name+'导向套',a+d*.66,a+d*.72,r*1.13,'gray')
    g.cyl(name+'镀铬活塞杆',a+d*.70,b-d*.04,r*.49,'chrome')
    for p in [a,b]:
        g.cyl(name+'铰接头',p+Vector((-.019,0,0)),p+Vector((.019,0,0)),r*.8,'gray')
        g.cyl(name+'铰轴',p+Vector((-.032,0,0)),p+Vector((.032,0,0)),r*.43,'steel')

def hydraulics():
    g.collection('液压油缸与管路')
    for side in [-1,1]:
        x=side*.355
        a=Vector((x,-.405,.631));b=pose((x,.469,.824))
        hydraulic('举升油缸',a,b,.025)
        for y,z in [(-.245,1.036),(-.405,.631)]:
            g.cyl('大臂固定销',(side*.305,y,z),(side*.40,y,z),.017,'steel')
            g.bolt('固定销螺栓',(side*.405,y,z),side=side,r=.010)
        g.bolt('举升前销',(side*.397,b.y,b.z),side=side,r=.009)
        # Flexible hose arcs follow photographs, with rigid steel lines along each lift arm.
        for k in range(2):
            xx=side*(.38+k*.022)
            tip=pose((xx,.54,.94))
            pts=[(xx,-.57,.93),(xx,-.50,1.22+k*.015),(xx,-.18,1.24+k*.015),tuple(pose((xx,.02,1.045))),tuple(tip)]
            g.tube('高压软管',pts,.009,'rubber')
            g.cyl('车架软管接头',(side*.302,-.57,.93),(xx,-.57,.93),.012,'steel',12)
            start=pose((xx,-.005,1.035));end=pose((xx,.57,.942))
            g.tube('大臂镀锌硬管',[tuple(start),tuple(pose((xx,.37,.965))),tuple(end)],.005,'steel',False)
            g.cyl('软管压接套',start-Vector((0,.025,0)),start+Vector((0,.025,0)),.012,'steel',12)
        for k in range(2):
            xx=side*(.394+k*.015)
            port=a+(b-a)*(.15 if k==0 else .64)
            nipple=port+Vector((side*.029,0,.009))
            g.tube('举升缸供油管',[(xx,-.49,.84),(xx,-.53,.71),(xx,-.47,.58),tuple(nipple)],.007,'rubber')
            g.cyl('举升缸油口',port,nipple,.010,'steel',12)
        for y,z in [(.07,1.00),(.36,.95)]:
            o=g.box('硬管固定夹',pose((side*.389,y,z)),(.055,.017,.021),'gray',.002);o.rotation_euler.x=ANGLE
    hydraulic('铲斗翻转油缸',pose((0,.562,1.141)),bucket_pose((0,1.025,.664)),.031)
    for i,x in enumerate([-.055,.055]):
        start=pose((x,.49,1.0));a=pose((0,.562,1.141));b=bucket_pose((0,1.025,.664))
        port=a+(b-a)*(.12 if i==0 else .62);tip=port+Vector((x,0,0))
        g.tube('翻斗缸软管',[tuple(start),tuple(start+Vector((x,.035,.06))),tuple(tip)],.007,'rubber')
        g.cyl('翻斗缸油口',port,tip,.010,'steel',12)
    for side in [-1,1]:
        p=pose((side*.25,.898,.582));g.bolt('铲斗连接销',p+Vector((side*.04,0,0)),side=side,r=.012)
    for k in range(3):
        x=.20+k*.044;start=pose((x,.63,.91));end=pose((x,.71,.91))
        g.cyl('液压快接头',start,end,.016,'steel',12)
        g.cyl('快接红色防尘帽',end,pose((x,.735,.91)),.019,'red',24)

def controls():
    g.collection('操纵台与扶手')
    for side in [-1,1]:
        x=-.1598 if side<0 else .1751;y=-.3016;z=1.20
        g.box('操纵杆安装座',(x,y,1.201),(.103,.079,.008),'black',.008)
        for i in range(5):
            g.cyl('操纵杆橡胶防尘套',(x,y,z+i*.009),(x,y,z+(i+1)*.009),.047-i*.006,'rubber',40,r2=.043-i*.006)
        g.cyl('操纵杆',(x,y,1.244),(x,y,1.399),.006,'chrome')
        g.cyl('操纵手柄',(x,y,1.364),(x,y,1.439),.017,'rubber',40,r2=.012)
        g.tube('操纵扶手',[(x+side*.065,-.26,1.18),(x+side*.065,-.26,1.345),(x+side*.068,-.38,1.345),(x+side*.053,-.48,1.345),(x-side*.062,-.48,1.345),(x-side*.067,-.34,1.345)],.012,'gray')
        for dx in [-.045,.045]:g.bolt('操纵台固定螺丝',(x+dx,-.33,1.207),axis='Z',r=.005)
    g.cyl('辅助操纵杆',(0,-.3757,1.19),(0,-.3757,1.365),.005,'steel')
    g.cyl('辅助操纵杆握把',(0,-.3757,1.32),(0,-.3757,1.38),.013,'rubber')
    g.cyl('辅助操纵杆防尘圈',(0,-.3757,1.197),(0,-.3757,1.21),.012,'rubber')
    # Centers and surface normal are measured directly from the STEP hole cylinders.
    normal=Vector((0,-.603,.798));tangent=Vector((0,.798,.603));base=Vector((0,-.4711,1.1348))
    slope=math.atan2(.603,.798)
    for x in [.101,.191]:
        p=base+Vector((x,0,0));g.cyl('仪表黑色边圈',p,p+normal*.017,.032,'black')
        g.cyl('仪表玻璃',p+normal*.017,p+normal*.018,.0275,'glass')
        for j in range(9):
            a=(j/8*1.5+.25)*math.pi
            q=p+normal*.019+Vector((math.cos(a)*.021,0,0))+tangent*(math.sin(a)*.021)
            g.cyl('仪表刻度',q,q+normal*.001,.0014,'white',8)
        g.tube('仪表指针',[tuple(p+normal*.021),tuple(p+normal*.021+Vector((-.012,0,0))-tangent*.011)],.0012,'white',False)
    for x in [-.015,-.050]:
        o=g.box('灯光喇叭开关',base+Vector((x,0,0))+normal*.010,(.030,.046,.018),'black',.003);o.rotation_euler.x=slope
    for x,mat in [(.146,'green'),(.2235,'amber')]:
        p=Vector((x,-.4375,1.1601));g.cyl('指示灯边圈',p,p+normal*.003,.011,'black');g.cyl('指示灯',p+normal*.003,p+normal*.013,.0095,mat)
    p=base+Vector((-.20,0,0));g.cyl('总电源开关',p,p+normal*.025,.041,'black')
    o=g.box('红色电源旋钮',p+normal*.03,(.058,.016,.014),'red',.004);o.rotation_euler.x=slope
    p=base+Vector((-.111,0,0));g.cyl('钥匙座',p,p+normal*.018,.017,'black')
    g.tube('启动钥匙',[tuple(p+normal*.016),tuple(p+normal*.049)],.003,'steel',False)
    g.box('钥匙柄',p+normal*.052,(.016,.006,.015),'black',.003)
    p=Vector((.261,-.4753,1.1404));g.cyl('12V插座防水盖',p,p+normal*.012,.017,'black')

def engine_and_trim():
    g.collection('发动机外观及散热器')
    g.box('发动机曲轴箱',(0,-.43,.645),(.36,.28,.28),'black',.035)
    for side in [-1,1]:
        o=g.box('发动机缸头',(side*.12,-.40,.79),(.16,.23,.17),'black',.02);o.rotation_euler.y=side*.42
        for i in range(8):
            o=g.box('缸头散热片',(side*.14,-.40,.74+i*.014),(.20,.245,.005),'gray',.002);o.rotation_euler.y=side*.42
    g.cyl('发动机风罩',(0,-.56,.68),(0,-.647,.68),.156,'black')
    for r in [.055,.095,.132]:g.ring('风罩同心环',(0,-.651,.68),r,.004,'gray','Y')
    for i in range(20):
        a=i*math.tau/20;g.tube('风罩格栅',[(.048*math.cos(a),-.653,.68+.048*math.sin(a)),(.14*math.cos(a),-.653,.68+.14*math.sin(a))],.0035,'black',False)
    g.cyl('风罩中心盖',(0,-.65,.68),(0,-.659,.68),.04,'gray')
    g.box('空气滤清器',(0,-.38,.963),(.20,.17,.12),'black',.028)
    g.box('排气消声器',(-.20,-.32,.91),(.11,.21,.12),'steel',.012)
    g.tube('排气口',[(-.22,-.37,.91),(-.245,-.43,.91),(-.245,-.49,.88)],.018,'black')
    g.box('油冷却器',(0,.083,.91),(.47,.055,.34),'black',.008).rotation_euler.x=math.radians(43)
    for i in range(30):
        z=.794+i*.0077;y=.19-(z-.794)*.93
        g.box('冷却器铝制散热片',(0,y,z),(.43,.010,.003),'steel',.0003)
    g.collection('外部固定件与照明')
    for side in [-1,1]:
        for y,z in [(-.63,.94),(-.61,.82),(-.58,.39),(.22,.32)]:g.bolt('车架紧固件',(side*.303,y,z),side=side,r=.006)
        # CAD side openings are retained; protective rails cover the exposed service area.
        g.tube('侧面管路防护',[(side*.318,-.30,.48),(side*.318,-.08,.65),(side*.318,.16,.61),(side*.318,.28,.45)],.008,'black')
        for y,z in [(-.14,1.18),(.30,.772)]:
            p=(side*.275,y,z);g.bolt('机罩紧固件',p,axis='Z',r=.005)
    for x in [-.265,.265]:
        g.box('工作灯安装支架',(x,.313,.756),(.022,.025,.045),'black',.002)
        g.box('LED工作灯外壳',(x,.323,.785),(.075,.032,.047),'black',.008)
        g.box('LED透明灯罩',(x,.342,.785),(.060,.003,.033),'glass',.005)
        for dx in [-.019,0,.019]:
            for dz in [-.008,.008]:g.box('LED反射杯',(x+dx,.344,.785+dz),(.013,.002,.011),'white',.002)
    # Non-branded safety markings: no invented manufacturer or specifications.
    for side in [-1,1]:
        g.box('侧面安全标识底板',(side*.302,-.535,1.025),(.001,.072,.060),'white',.0003)
        g.box('侧面警示底板',(side*.303,-.54,.443),(.001,.072,.026),'amber',.0003)
    for i in range(8):
        for j in range(6):
            o=g.box('踏板防滑纹',(-.22+j*.088,-.97+i*.038,0),(.032,.006,.003),'gray',.001);o.rotation_euler.z=.55 if (i+j)%2 else -.55
    from footplate import fit_treads
    fit_treads()

def build(mats):
    from build_scene import material
    g.MATS=dict(mats)
    for key,name,color,metal,rough in [('rubber','细纹橡胶',(.009,.012,.016),0,.78),('chrome','液压杆镀铬',(.55,.60,.67),1,.15),('white','白色标识与反光',(.68,.7,.72),.05,.3),('glass','仪表与灯具玻璃',(.085,.14,.18),.3,.10),('amber','琥珀色指示',(.8,.38,.005),.15,.25),('green','绿色指示',(.015,.4,.12),.15,.25)]:
        g.MATS[key]=material(name,color,metal,rough)
    rubber=g.MATS['rubber'];nodes=rubber.node_tree.nodes;links=rubber.node_tree.links
    noise=nodes.new('ShaderNodeTexNoise');noise.inputs['Scale'].default_value=230
    bump=nodes.new('ShaderNodeBump');bump.inputs['Strength'].default_value=.15;bump.inputs['Distance'].default_value=.0006
    links.new(noise.outputs['Fac'],bump.inputs['Height']);links.new(bump.outputs['Normal'],nodes.get('Principled BSDF').inputs['Normal'])
    for o in list(bpy.context.scene.objects):
        if o.get('CAD编号') and any(s in o.get('装配路径','') for s in ['大臂焊接','铲斗焊接','快换焊接']):
            o.matrix_world=BUCKET_POSE if any(s in o.get('装配路径','') for s in ['铲斗焊接','快换焊接']) else POSE
            o['展示姿态']='大臂绕原厂后铰点降低 25 度，铲斗绕前铰点调平；原始状态另存'
    track_details.build();hydraulics();controls();engine_and_trim()
