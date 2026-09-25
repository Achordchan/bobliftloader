/* Sheet-metal body, recessed ventilation well and operator station from the detail photographs. */
'use strict';
window.buildLoaderBody = function(g) {
  const {T,group,mesh,box,roundBox,plate,quad,rod,bolt,torus,hose,sphere,paint,dark,black,rubber,steel,orange,red,light}=g;
  const body=group('车架与钣金机罩');
  box(.94,.22,1.74,0,.60,-.03,dark,body);
  plate([[-1.02,.50],[-1.07,1.41],[-.77,1.90],[-.46,1.75],[.67,.92],[.68,.62]],.89,0,black,body,.02);
  // Cowling uses an actual recessed opening with folded walls and a lowered floor.
  const hood=group('带凹槽的橙色机罩',body);hood.position.set(0,1.36,.43);hood.rotation.x=-Math.PI/3;
  const shape=new T.Shape();shape.moveTo(-.35,0);shape.lineTo(.35,0);shape.lineTo(.335,1.15);shape.lineTo(-.335,1.15);shape.closePath();
  const opening=new T.Path();opening.moveTo(-.155,.45);opening.lineTo(-.192,1.01);opening.lineTo(.192,1.01);opening.lineTo(.155,.45);opening.closePath();shape.holes.push(opening);
  mesh(new T.ExtrudeGeometry(shape,{depth:.018,bevelEnabled:false}),paint,hood);
  for(const s of [-1,1]){
    const q=quad([[s*.35,0,.018],[s*.335,1.15,.018],[s*.422,1.15,-.10],[s*.45,0,-.12]],paint,hood);q.material.side=T.DoubleSide;
    rod([s*.45,0,-.12],[s*.422,1.15,-.10],.013,black,hood);
    quad([[s*.155,.45,.018],[s*.192,1.01,.018],[s*.157,.98,-.103],[s*.123,.49,-.103]],paint,hood);
  }
  quad([[-.155,.45,.018],[.155,.45,.018],[.123,.49,-.103],[-.123,.49,-.103]],paint,hood);
  quad([[-.192,1.01,.018],[-.157,.98,-.103],[.157,.98,-.103],[.192,1.01,.018]],paint,hood);
  quad([[-.123,.49,-.103],[.123,.49,-.103],[.157,.98,-.103],[-.157,.98,-.103]],paint,hood);
  plate([[.43,1.36],[.54,1.15],[.77,1.08],[.77,.92],[.51,.95]],.84,0,paint,body,.009);
  // A perforated honeycomb face, black plenum behind it and an orange folded lip.
  const vent=group('蜂窝通风格栅',hood);vent.position.set(0,.855,-.047);vent.rotation.x=.80;
  const grille=new T.Shape();grille.moveTo(-.173,-.07);grille.lineTo(.173,-.07);grille.lineTo(.193,.076);grille.lineTo(-.193,.076);grille.closePath();
  for(let row=0;row<5;row++)for(let col=0;col<12;col++){
    const cx=-.154+col*.028+(row%2)*.014,cy=-.048+row*.025;
    if(cx>.16)continue;
    const hole=new T.Path();for(let j=0;j<6;j++){const a=Math.PI/6+j*Math.PI/3;const x=cx+Math.cos(a)*.014,y=cy+Math.sin(a)*.014;j?hole.lineTo(x,y):hole.moveTo(x,y);}hole.closePath();grille.holes.push(hole);
  }
  box(.365,.14,.012,0,0,-.03,black,vent);
  mesh(new T.ExtrudeGeometry(grille,{depth:.009,bevelEnabled:false}),paint,vent);
  box(.394,.019,.075,0,.079,-.018,paint,vent);
  // Recessed LED bar with six reflector cups, lens rims, mounting ears and fasteners.
  const lamp=group('六灯珠工作灯',hood);lamp.position.set(0,1.074,.058);lamp.rotation.x=.90;
  roundBox(.365,.125,.076,.018,0,0,-.016,paint,lamp);
  roundBox(.317,.089,.043,.014,0,0,.025,black,lamp);
  roundBox(.276,.064,.012,.006,0,0,.051,steel,lamp);
  roundBox(.262,.053,.012,.004,0,0,.060,black,lamp);
  for(let i=0;i<6;i++){
    const x=-.107+i*.043;
    torus(x,0,.07,.019,.0034,steel,lamp,'z');
    const reflector=sphere(x,0,.067,.016,steel,lamp);reflector.scale.z=.35;
    const led=sphere(x,0,.074,.009,light,lamp);led.scale.z=.32;
    torus(x,0,.073,.012,.0019,orange,lamp,'z');
  }
  for(const s of [-1,1])rod([s*.146,0,.046],[s*.146,0,.058],.008,steel,lamp,.008,6);
  // Layered side plates and rear shell follow the new low-angle image.
  for(const s of [-1,1]){
    const side=group(s>0?'右侧护板':'左侧护板',body);
    plate([[-1.01,.49],[-1.07,1.40],[-.85,1.72],[-.59,1.60],[-.24,1.10],[-.40,.96],[-.49,.54]],.044,s*.48,dark,side,.013);
    plate([[-.99,.48],[-1.10,.79],[-1.13,1.44],[-.95,1.66],[-.86,1.49],[-.90,.72]],.03,s*.43,black,side,.012);
    [[-.98,.62],[-1.01,1.15],[-.79,1.49],[-.60,1.26],[-.55,.72],[-.88,1.57]].forEach(([z,y])=>bolt(s*.507,y,z,side,.021,s));
    for(const [z,y] of [[-.58,1.06],[-.77,.94],[-.88,.74]])rod([s*.507,y,z],[s*.514,y,z],.015,black,side);
    const reflector=roundBox(.019,.19,.055,.007,s*.52,1.03,-.94,orange,side);reflector.rotation.x=-.10;
    for(let i=0;i<8;i++)for(let j=0;j<3;j++)sphere(s*.534,.955+i*.021,-.958+j*.017,.004,orange,side);
    // Service latch: zinc-plated backing and quarter-turn centre.
    plate([[-.71,.60],[-.70,.70],[-.68,.735],[-.65,.70],[-.65,.60]],.007,s*.517,steel,side,.008);
    bolt(s*.53,.655,-.68,side,.016,s);
  }
  roundBox(.75,.67,.19,.06,0,1.20,-1.09,dark,body);
  for(let i=0;i<9;i++)roundBox(.54,.017,.014,.003,0,.94+i*.052,-1.194,black,body);
  box(.78,.065,.37,0,.46,-1.25,dark,body);
  for(let i=0;i<13;i++)for(let j=0;j<5;j++){
    const tooth=box(.028,.010,.022,-.35+i*.058,.497,-1.39+j*.065,steel,body);tooth.rotation.y=.25;
  }
  rod([-.37,.49,-1.43],[.37,.49,-1.43],.016,steel,body);
  for(const s of [-1,1])roundBox(.11,.065,.018,.01,s*.29,.66,-1.2,red,body);
  // Console deck, tapered bellows and asymmetric ergonomic joysticks.
  const controls=group('双操纵杆控制台');
  plate([[-.97,1.98],[-.95,2.12],[-.60,2.12],[-.49,1.98]],.84,0,dark,controls,.015);
  roundBox(.76,.036,.35,.012,0,2.139,-.77,black,controls);
  for(const s of [-1,1]){
    const joystick=group(s>0?'右操纵杆':'左操纵杆',controls);joystick.position.set(s*.255,2.166,-.76);
    const points=[new T.Vector2(.088,0)];
    for(let i=0;i<7;i++){const y=i*.020,r=.09-i*.0063;points.push(new T.Vector2(r,y+.003),new T.Vector2(r+.004,y+.008),new T.Vector2(r-.01,y+.017));}
    points.push(new T.Vector2(.044,.148));
    const boot=mesh(new T.LatheGeometry(points,40),rubber,joystick);boot.scale.z=.85;
    rod([0,.136,0],[s*.020,.298,-.026],.028,black,joystick,.037);
    const grip=roundBox(.091,.102,.064,.018,s*.023,.318,-.029,dark,joystick);grip.rotation.x=-.30;grip.rotation.z=-s*.10;
    const cap=roundBox(.071,.061,.018,.014,s*.023,.344,-.005,black,joystick);cap.rotation.x=-.6;
    sphere(s*.023-.017,.354,.003,.011,orange,joystick);
    sphere(s*.023+.016,.331,.015,.009,s>0?red:orange,joystick);
    hose([[s*.105,-.025,.115],[s*.11,.19,.115],[s*.11,.205,-.08],[s*.09,.19,-.17]],.017,controls,dark).position.set(s*.255,2.15,-.76);
  }
  hose([[-.41,2.18,-.59],[-.40,2.31,-.57],[0,2.32,-.56],[.40,2.31,-.57],[.41,2.18,-.59]],.019,controls,dark);
  rod([-.35,2.30,-.98],[.35,2.30,-.98],.023,dark,controls);
  for(const s of [-1,1])rod([s*.35,2.14,-.98],[s*.35,2.30,-.98],.016,steel,controls);
  rod([0,2.16,-.87],[0,2.18,-.87],.074,dark,controls);
  rod([0,2.18,-.87],[0,2.184,-.87],.058,black,controls);
  torus(0,2.186,-.87,.058,.005,steel,controls,'y');
  for(let i=0;i<9;i++){const a=i*Math.PI*.20;rod([Math.sin(a)*.043,2.189,-.87+Math.cos(a)*.043],[Math.sin(a)*.051,2.189,-.87+Math.cos(a)*.051],.0017,light,controls);}
  rod([0,2.19,-.87],[.024,2.19,-.899],.0025,orange,controls);
  rod([-.115,2.15,-.86],[-.115,2.18,-.86],.027,steel,controls);
  sphere(-.115,2.194,-.86,.025,red,controls);
  rod([.13,2.15,-.91],[.13,2.177,-.91],.022,steel,controls);
  torus(.13,2.177,-.93,.02,.0025,steel,controls,'z');
  for(const s of [-1,1])rod([s*.14,2.15,-.67],[s*.14,2.163,-.67],.016,black,controls);
};
