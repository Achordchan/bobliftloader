/* Rounded triangular track loops reconstructed from the low-angle side reference. */
'use strict';
window.buildLoaderTracks = function (g) {
  const {T,group,plate,rod,bolt,box,torus,dark,black,steel,rubber}=g;
  function hull(points) {
    const sorted=points.slice().sort((a,b)=>a[0]-b[0]||a[1]-b[1]);
    const cross=(o,a,b)=>(a[0]-o[0])*(b[1]-o[1])-(a[1]-o[1])*(b[0]-o[0]);
    const lower=[],upper=[];
    for(const p of sorted){while(lower.length>1&&cross(lower.at(-2),lower.at(-1),p)<=0)lower.pop();lower.push(p);}
    for(const p of sorted.slice().reverse()){while(upper.length>1&&cross(upper.at(-2),upper.at(-1),p)<=0)upper.pop();upper.push(p);}
    return lower.slice(0,-1).concat(upper.slice(0,-1));
  }
  const wheels=[[.77,.30,.22],[-.37,.78,.215],[-.79,.30,.235]];
  const outline=radiusOffset=>hull(wheels.flatMap(([z,y,r])=>g.circle(z,y,r+radiusOffset,64)));
  const outer=outline(.037),inner=outline(-.022);
  const lengths=outer.map((p,i)=>{const q=outer[(i+1)%outer.length];return Math.hypot(q[0]-p[0],q[1]-p[1]);});
  const perimeter=lengths.reduce((a,b)=>a+b,0);
  function sample(distance){let i=0;while(distance>lengths[i]&&i<lengths.length-1)distance-=lengths[i++];const a=outer[i],b=outer[(i+1)%outer.length],t=distance/lengths[i];return {z:a[0]+(b[0]-a[0])*t,y:a[1]+(b[1]-a[1])*t,angle:Math.atan2(b[1]-a[1],b[0]-a[0])};}
  for(const side of [-1,1]){
    const track=group(side>0?'右侧三角履带':'左侧三角履带'),x=side*.65;
    plate(outer,.29,x,rubber,track,0,[inner]);
    plate(inner,.022,x-side*.132,black,track,0);
    const tread=new T.InstancedMesh(new T.BoxGeometry(.318,.043,.071),rubber,64);
    const lugs=new T.InstancedMesh(new T.BoxGeometry(.137,.023,.045),rubber,128);
    tread.castShadow=tread.receiveShadow=lugs.castShadow=lugs.receiveShadow=true;track.add(tread,lugs);
    const dummy=new T.Object3D();
    for(let i=0;i<64;i++){
      const p=sample(i*perimeter/64),normal=new T.Vector3(0,-Math.cos(p.angle),Math.sin(p.angle));
      dummy.position.set(x,p.y,p.z);dummy.rotation.set(-p.angle,0,0);dummy.updateMatrix();tread.setMatrixAt(i,dummy.matrix);
      for(let j=0;j<2;j++){
        dummy.position.set(x+(j?1:-1)*.079,p.y+normal.y*.029,p.z+normal.z*.029);
        dummy.rotation.set(-p.angle,0,0);dummy.rotateY(j?.22:-.22);dummy.updateMatrix();lugs.setMatrixAt(i*2+j,dummy.matrix);
      }
    }
    // Main idlers and the elevated toothed drive sprocket.
    for(const [index,[z,y,r]] of wheels.entries()){
      rod([x-.12,y,z],[x+.12,y,z],r-.02,dark,track);
      for(const s of [-1,1])torus(x+s*.125,y,z,r-.036,.014,black,track);
      const face=x+side*.143;
      rod([face,y,z],[face+side*.016,y,z],r*.70,dark,track);
      if(index===1){
        for(let j=0;j<13;j++){const a=j*Math.PI*2/13;const tooth=box(.23,.047,.054,x,y+Math.sin(a)*r,z+Math.cos(a)*r,steel,track);tooth.rotation.x=-a;}
        for(let j=0;j<7;j++){const a=j*Math.PI*2/7;rod([face+side*.017,y+Math.sin(a)*.115,z+Math.cos(a)*.115],[face+side*.019,y+Math.sin(a)*.115,z+Math.cos(a)*.115],.037,black,track);}
      }
      rod([face,y,z],[face+side*.028,y,z],.066,steel,track);
      torus(face+side*.031,y,z,.048,.007,dark,track);
      bolt(face+side*.032,y,z,track,.026,side);
      for(let j=0;j<6;j++){const a=j*Math.PI/3;bolt(face+side*.022,y+Math.sin(a)*.084,z+Math.cos(a)*.084,track,.010,side);}
    }
    for(let i=0;i<6;i++){
      const z=-.59+i*.24,y=.205,r=.133;
      rod([x-.11,y,z],[x+.11,y,z],r,rubber,track);
      rod([x+side*.113,y,z],[x+side*.125,y,z],r*.72,dark,track);
      torus(x+side*.127,y,z,r*.70,.008,steel,track);
      bolt(x+side*.13,y,z,track,.014,side);
    }
    const guard=[[-.90,.20],[-.89,.30],[-.66,.58],[-.52,.49],[-.39,.46],[-.21,.61],[.78,.37],[.90,.26],[.80,.18],[-.72,.16]];
    plate(guard,.029,x+side*.16,dark,track,.009);
    // Recessed inspection cover and staggered drain apertures.
    plate([[-.22,.205],[-.22,.345],[.12,.345],[.19,.29],[.17,.205]],.008,x+side*.181,dark,track,.002);
    for(let i=0;i<7;i++)rod([x+side*.18,.22,-.67+i*.215],[x+side*.19,.22,-.67+i*.215],.019,black,track);
    [[-.78,.29],[-.16,.31],[.12,.29],[.73,.28]].forEach(([z,y])=>bolt(x+side*.19,y,z,track,.012,side));
    const tension=box(.02,.018,.20,x+side*.20,.33,.60,black,track);tension.rotation.x=.25;
  }
};
