/* Lift assembly, auxiliary couplers and hollow bucket share real scene-graph pivots. */
'use strict';
window.buildLoaderAttachment = function(g) {
  const {T,root,group,box,roundBox,plate,rod,bolt,torus,hose,paint,dark,black,steel,rubber,red}=g;
  const boom=group('举升臂总成');boom.position.set(0,1.86,-.70);
  const arm=[[-.13,.015],[-.08,.10],[.06,.13],[.18,.085],[.29,-.06],[1.57,-.91],[1.75,-1.05],[1.82,-1.22],[1.77,-1.49],[1.64,-1.49],[1.57,-1.17],[.27,-.33],[.02,-.27],[-.12,-.15]];
  for(const s of [-1,1]){
    plate(arm,.106,s*.535,paint,boom,.017);
    // Rounded reinforcement pressed into the outer face of each long arm.
    const skin=roundBox(.016,.145,.39,.038,s*.603,-.87,1.28,paint,boom);skin.rotation.x=.58;
    plate([[-.12,.015],[-.06,.12],[.12,.10],[.30,-.11],[.29,-.24],[.20,-.31],[.03,-.25],[-.12,-.12]],.022,s*.605,paint,boom,.013);
    plate([[.02,-.25],[.16,-.26],[.26,-.38],[.17,-.61],[.06,-.54]],.056,s*.525,paint,boom,.01);
    const slot=[[1.704,-1.22],[1.714,-1.19],[1.738,-1.195],[1.749,-1.224],[1.739,-1.305],[1.718,-1.32],[1.70,-1.30]];
    plate([[1.58,-1.12],[1.76,-1.09],[1.81,-1.22],[1.76,-1.49],[1.64,-1.48]],.022,s*.604,paint,boom,.007,[slot]);
    for(const [z,y,r] of [[-.015,-.015,.034],[.21,-.26,.024],[.155,-.53,.022],[1.70,-1.435,.027]]){
      rod([s*.58,y,z],[s*.624,y,z],r*1.40,steel,boom);bolt(s*.63,y,z,boom,r,s);
    }
    for(const offset of [0,.042]){
      const points=[[s*(.49+offset),-.12,-.18],[s*(.49+offset),.115,.05],[s*(.49+offset),.07,.28],[s*(.49+offset),-.19,.52]];
      const h=hose(points,.017,boom);const curve=h.geometry.parameters.path;
      for(let j=1;j<23;j++){
        const t=j/24,p=curve.getPoint(t),v=curve.getTangent(t);
        const ring=torus(p.x,p.y,p.z,.018,.0025,dark,boom,'z');ring.quaternion.setFromUnitVectors(new T.Vector3(0,0,1),v);
      }
    }
    hose([[s*.42,-.31,.21],[s*.43,-.56,.61],[s*.43,-.91,1.12],[s*.37,-1.12,1.51]],.013,boom);
    rod([s*.52,-.51,.63],[s*.52,-.81,1.16],.011,steel,boom);
    for(const z of [.71,.98]){const y=-.51-(z-.63)*.57;box(.027,.024,.032,s*.54,y,z,dark,boom);}
  }
  box(1.02,.175,.14,0,-1.17,1.62,paint,boom);
  for(const s of [-1,1]){
    const slots=[[[1.43,-.84],[1.45,-.82],[1.47,-.90],[1.45,-.95]],[[1.49,-1.00],[1.51,-.98],[1.53,-1.06],[1.51,-1.10]]];
    plate([[1.32,-.69],[1.42,-.66],[1.57,-.91],[1.67,-1.26],[1.54,-1.33],[1.39,-1.10]],.035,s*.137,paint,boom,.008,slots);
    bolt(s*.169,-.745,1.38,boom,.023,s);
    rod([s*.17,-.82,1.35],[s*.26,-.83,1.38],.014,steel,boom);
  }
  // Auxiliary three-port hydraulic block, knurled sleeves, dust cap and retained lanyard.
  roundBox(.115,.36,.044,.015,.34,-.89,1.66,steel,boom);
  for(let i=0;i<3;i++){
    const y=-.77-i*.11;
    rod([.34,y,1.66],[.34,y,1.71],.039,steel,boom,.039,6);
    rod([.34,y,1.71],[.34,y,1.786],.031,steel,boom);
    for(let j=0;j<5;j++)torus(.34,y,1.728+j*.010,.031,.0025,dark,boom,'z');
    rod([.34,y,1.781],[.34,y,1.794],.022,i===1?red:dark,boom);
    hose([[.34,y,1.63],[.40,y+.015,1.57],[.43,y-.08,1.49],[.39,-1.15,1.4]],.016,boom);
    bolt(.404,y,1.66,boom,.008,1);
  }
  hose([[.34,-.88,1.79],[.42,-.92,1.82],[.43,-1.02,1.78],[.34,-1.01,1.76]],.004,boom,red);
  for(const s of [-1,1]){
    rod([s*.32,-1.18,1.72],[s*.32,-1.06,1.72],.018,steel,boom);
    rod([s*.32,-1.06,1.72],[s*.42,-1.06,1.72],.016,paint,boom);
    torus(s*.405,-1.10,1.745,.032,.004,paint,boom,'z');
  }
  const bucket=group('铲斗与快换架',boom);bucket.position.set(0,-1.49,2.06);
  const scoop=[[-.155,.06],[-.14,.445],[-.095,.47],[-.055,.455],[-.060,.115],[-.025,.027],[.07,-.057],[.20,-.13],[.74,-.286],[.74,-.316],[.17,-.162],[.025,-.095],[-.065,-.014]];
  plate(scoop,1.75,0,dark,bucket,.005);
  for(const s of [-1,1]){
    const cheek=[[-.16,.03],[-.15,.435],[-.094,.47],[.018,.445],[.77,-.294],[.67,-.322],[.17,-.315],[-.11,-.16]];
    plate(cheek,.026,s*.884,dark,bucket,.005,[g.circle(.25,-.22,.021),g.circle(-.085,.31,.017)]);
    rod([s*.902,.443,-.08],[s*.902,-.285,.75],.009,steel,bucket);
    rod([s*.867,.431,-.095],[s*.867,.08,-.108],.005,dark,bucket);
    plate([[-.24,.12],[-.24,.37],[-.15,.38],[-.15,.11]],.075,s*.36,dark,bucket,.006,[g.circle(-.202,.25,.027)]);
    bolt(s*.405,.25,-.205,bucket,.025,s);
  }
  // Quick-attach plate and lower locking blocks stay behind the hollow scoop.
  box(.72,.34,.063,0,.15,-.233,dark,bucket);
  for(const s of [-1,1]){
    box(.12,.07,.095,s*.30,-.028,-.258,dark,bucket);
    rod([s*.295,-.06,-.24],[s*.295,.07,-.24],.017,steel,bucket);
    bolt(s*.39,.02,-.245,bucket,.016,s);
  }
  box(.31,.04,.13,0,.31,-.26,dark,bucket);
  rod([-.10,.30,-.26],[.10,.30,-.26],.029,steel,bucket);
  // Wear bar has a bevelled cross section and countersunk cutting-edge screws.
  const wear=g.material({color:0x7f8788,metalness:.85,roughness:.33});
  plate([[.655,-.268],[.785,-.302],[.794,-.315],[.65,-.297]],1.82,0,wear,bucket,.001);
  for(let i=0;i<11;i++){
    const x=-.81+i*.162;rod([x,-.286,.723],[x,-.28,.723],.008,dark,bucket,.008,6);
  }
  for(const x of [-.65,0,.65])plate([[-.11,-.12],[.12,-.215],[.66,-.342],[.71,-.32],[.17,-.18]],.066,x,dark,bucket,.002);
  rod([-.88,.46,-.112],[.88,.46,-.112],.013,steel,bucket);
  // Cylinders keep their geometry and update transforms when the articulation changes.
  const hydraulic=group('动态液压缸');
  const cylinderPairs=[];
  for(const s of [-1,1]){
    const barrel=rod([0,0,0],[0,1,0],.052,black,hydraulic);
    const piston=rod([0,0,0],[0,1,0],.025,steel,hydraulic);
    const collar=rod([0,0,0],[0,1,0],.058,steel,hydraulic);
    cylinderPairs.push({s,barrel,piston,collar});
  }
  const tiltBarrel=rod([0,0,0],[0,1,0],.049,black,hydraulic);
  const tiltRod=rod([0,0,0],[0,1,0],.022,steel,hydraulic);
  const tiltCollar=rod([0,0,0],[0,1,0],.055,steel,hydraulic);
  const ramGuard=box(.19,.15,.05,0,0,0,dark,hydraulic);
  const ramHose=hose([[0,0,0],[0,.1,0],[0,.2,0]],.011,hydraulic);
  function connect(o,a,b){const v=b.clone().sub(a);o.position.copy(a).add(b).multiplyScalar(.5);o.scale.y=v.length();o.quaternion.setFromUnitVectors(new T.Vector3(0,1,0),v.normalize());}
  const contactProfile=[[-.24,-.06],[-.155,.47],[.018,.445],[.17,-.315],[.66,-.342],[.794,-.315]];
  function update(lift,tilt){
    boom.rotation.x=-T.MathUtils.degToRad(lift);
    function bottomAt(angle){
      bucket.rotation.x=T.MathUtils.degToRad(angle+lift*.65);root.updateMatrixWorld(true);
      return Math.min(...contactProfile.map(([z,y])=>bucket.localToWorld(new T.Vector3(0,y,z)).y));
    }
    // Restrict downward curl at low lift positions so the cutting edge cannot cross the floor.
    let maxTilt=45;
    if(bottomAt(maxTilt)<.025){
      let low=-12,high=45;
      for(let i=0;i<14;i++){const mid=(low+high)/2;if(bottomAt(mid)<.025)high=mid;else low=mid;}
      maxTilt=Math.floor(low);
    }
    const actualTilt=Math.min(tilt,maxTilt);bottomAt(actualTilt);
    for(const {s,barrel,piston,collar} of cylinderPairs){
      const a=new T.Vector3(s*.46,.88,-.82),b=boom.localToWorld(new T.Vector3(s*.46,-.53,.155));
      const v=b.clone().sub(a).normalize(),mid=a.clone().addScaledVector(v,.49);
      connect(barrel,a,mid);connect(piston,mid,b);connect(collar,mid.clone().addScaledVector(v,-.025),mid.clone().addScaledVector(v,.018));
    }
    const a=boom.localToWorld(new T.Vector3(0,-.75,1.38));
    const b=bucket.localToWorld(new T.Vector3(0,.30,-.26)),v=b.clone().sub(a).normalize(),mid=a.clone().addScaledVector(v,.50);
    connect(tiltBarrel,a,mid);connect(tiltRod,mid,b);connect(tiltCollar,mid.clone().addScaledVector(v,-.021),mid.clone().addScaledVector(v,.014));
    ramGuard.position.copy(a).lerp(mid,.8).add(new T.Vector3(0,0,.065));ramGuard.quaternion.copy(tiltBarrel.quaternion);
    const p1=a.clone().add(new T.Vector3(-.05,-.035,0)),p2=a.clone().lerp(b,.6).add(new T.Vector3(-.13,0,.05)),p3=b.clone().add(new T.Vector3(-.03,0,0));
    const next=new T.TubeGeometry(new T.CatmullRomCurve3([p1,p2,p3]),24,.011,8,false);ramHose.geometry.dispose();ramHose.geometry=next;
    return {tilt:actualTilt,maxTilt};
  }
  update(0,0);
  return {update,boom,bucket};
};
