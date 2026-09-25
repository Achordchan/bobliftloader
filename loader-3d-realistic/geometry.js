/* Shared fabrication primitives. Model dimensions are visual estimates, not production measurements. */
'use strict';
window.LoaderGeometry = function (root) {
  const T = THREE;
  const materials = [];
  function material(parameters) { const m = new T.MeshPhysicalMaterial(parameters); materials.push(m); return m; }
  function grain(size, amplitude, repeat) {
    const canvas = document.createElement('canvas'); canvas.width = canvas.height = size;
    const ctx = canvas.getContext('2d'), data = ctx.createImageData(size, size);
    let seed = 7349;
    for (let i = 0; i < data.data.length; i += 4) {
      seed = (seed * 16807) % 2147483647;
      const c = 128 + (seed / 2147483647 - .5) * amplitude;
      data.data[i] = data.data[i + 1] = data.data[i + 2] = c; data.data[i + 3] = 255;
    }
    ctx.putImageData(data, 0, 0);
    const t = new T.CanvasTexture(canvas); t.wrapS = t.wrapT = T.RepeatWrapping; t.repeat.set(repeat, repeat); return t;
  }
  function brushedTexture() {
    const c=document.createElement('canvas');c.width=512;c.height=512;const ctx=c.getContext('2d');
    ctx.fillStyle='#d4d4d4';ctx.fillRect(0,0,512,512);
    let seed=231;for(let i=0;i<2200;i++){seed=(seed*16807)%2147483647;const x=seed%512,y=(seed/512)%512;ctx.strokeStyle=i%3?'rgba(255,255,255,.08)':'rgba(40,40,40,.08)';ctx.lineWidth=.3;ctx.beginPath();ctx.moveTo(x,y);ctx.lineTo(x+20+seed%150,y);ctx.stroke();}
    const t=new T.CanvasTexture(c);t.wrapS=t.wrapT=T.RepeatWrapping;t.repeat.set(2,2);return t;
  }
  const orangePeel=grain(512,38,4),powder=grain(512,60,6),rubberGrain=grain(512,100,5),brushed=brushedTexture();
  const paint = material({color:0xe64102, metalness:.06, roughness:.30, clearcoat:.65, clearcoatRoughness:.22, ior:1.48, bumpMap:orangePeel, bumpScale:.00048, envMapIntensity:1.0});
  const dark = material({color:0x202521, metalness:.22, roughness:.43, clearcoat:.22, clearcoatRoughness:.33, bumpMap:powder, bumpScale:.0012, envMapIntensity:.85});
  const rubber = material({color:0x1b1d1b, metalness:0, roughness:.92, bumpMap:rubberGrain, bumpScale:.0023, envMapIntensity:.65});
  const steel = material({color:0xadb2b4, metalness:1, roughness:.24, roughnessMap:brushed, bumpMap:brushed, bumpScale:.00010, envMapIntensity:.9});
  const black = material({color:0x111613, metalness:.20, roughness:.30, clearcoat:.45, clearcoatRoughness:.22});
  const orange = material({color:0xe8781e, metalness:.1, roughness:.33, clearcoat:.65});
  const red = material({color:0xb31f0c, roughness:.28, clearcoat:.8});
  const light = material({color:0xcac5b6, emissive:0xffdf9b, emissiveIntensity:.025, metalness:.6, roughness:.17});
  function mesh(geometry, mat, parent=root) {
    const o = new T.Mesh(geometry, mat); o.castShadow = true; o.receiveShadow = true; parent.add(o); return o;
  }
  function box(w,h,d,x,y,z,mat=dark,parent=root) {
    const o = mesh(new StudioAddons.RoundedBoxGeometry(w,h,d,2,Math.min(w,h,d)*.065),mat,parent); o.position.set(x,y,z); return o;
  }
  function roundBox(w,h,d,r,x,y,z,mat=dark,parent=root) {
    const o=mesh(new StudioAddons.RoundedBoxGeometry(w,h,d,4,Math.min(r,w*.45,h*.45,d*.45)),mat,parent);o.position.set(x,y,z);return o;
  }
  function rod(a,b,r,mat=steel,parent=root,r2=r,segments=40) {
    const av=new T.Vector3(...a),bv=new T.Vector3(...b),v=bv.clone().sub(av);
    const o=mesh(new T.CylinderGeometry(r2,r,v.length(),segments),mat,parent);
    o.position.copy(av.add(bv).multiplyScalar(.5)); o.quaternion.setFromUnitVectors(new T.Vector3(0,1,0),v.normalize()); return o;
  }
  function sphere(x,y,z,r,mat,parent=root) {
    const o=mesh(new T.SphereGeometry(r,32,20),mat,parent); o.position.set(x,y,z); return o;
  }
  function path(points,radius=0) {
    const s=new T.Shape();
    if(!radius){points.forEach(([z,y],i)=>i?s.lineTo(z,y):s.moveTo(z,y));}
    else points.forEach((p,i)=>{
      const previous=points[(i+points.length-1)%points.length],next=points[(i+1)%points.length];
      const a=new T.Vector2(...previous).sub(new T.Vector2(...p)),b=new T.Vector2(...next).sub(new T.Vector2(...p));
      const d=Math.min(radius,a.length()*.22,b.length()*.22);a.normalize().multiplyScalar(d).add(new T.Vector2(...p));b.normalize().multiplyScalar(d).add(new T.Vector2(...p));
      i?s.lineTo(a.x,a.y):s.moveTo(a.x,a.y);s.quadraticCurveTo(p[0],p[1],b.x,b.y);
    });
    s.closePath();return s;
  }
  function plate(points,width,x,mat=paint,parent=root,bevel=.006,holes=[]) {
    const s=path(points,mat===paint&&width>.04?.028:bevel>0?.005:0); holes.forEach(h=>s.holes.push(path(h)));
    const g=new T.ExtrudeGeometry(s,{depth:width,bevelEnabled:bevel>0,bevelThickness:bevel,bevelSize:bevel,bevelSegments:5,steps:1,curveSegments:24});
    g.rotateY(-Math.PI/2); g.translate(x+width/2,0,0);
    const source=g.attributes.normal.clone();
    // Millimetre-scale smoothing keeps neighbouring bevel vertices distinct.
    g.scale(1000,1000,1000);
    const smooth=StudioAddons.toCreasedNormals(g,Math.PI*.35);smooth.scale(.001,.001,.001);
    // Keep the broad sheet-metal faces planar; only the bevel strips receive interpolated normals.
    const normal=smooth.attributes.normal;
    for(let i=0;i<source.count;i++)if(Math.abs(source.getX(i))>.9999)normal.setXYZ(i,source.getX(i),0,0);
    return mesh(smooth,mat,parent);
  }
  function circle(z,y,r,n=24) {return Array.from({length:n},(_,i)=>[z+Math.cos(i*Math.PI*2/n)*r,y+Math.sin(i*Math.PI*2/n)*r]);}
  function torus(x,y,z,r,t,mat=steel,parent=root,axis='x') {
    const o=mesh(new T.TorusGeometry(r,t,8,32),mat,parent); if(axis==='x')o.rotation.y=Math.PI/2; if(axis==='y')o.rotation.x=Math.PI/2; o.position.set(x,y,z); return o;
  }
  function bolt(x,y,z,parent=root,r=.021,side=Math.sign(x)||1) {
    rod([x,y,z],[x+side*.009,y,z],r*1.38,steel,parent);
    rod([x+side*.009,y,z],[x+side*.023,y,z],r,steel,parent,r,6);
    rod([x+side*.023,y,z],[x+side*.024,y,z],r*.43,black,parent,r*.43,6);
  }
  function hose(points,r=.013,parent=root,mat=rubber) {
    return mesh(new T.TubeGeometry(new T.CatmullRomCurve3(points.map(v=>new T.Vector3(...v))),40,r,8,false),mat,parent);
  }
  function quad(points,mat=paint,parent=root) {
    const g=new T.BufferGeometry(); g.setAttribute('position',new T.Float32BufferAttribute(points.flat(),3));g.setAttribute('uv',new T.Float32BufferAttribute([0,0,1,0,1,1,0,1],2));g.setIndex([0,1,2,0,2,3]);g.computeVertexNormals();return mesh(g,mat,parent);
  }
  function group(name,parent=root) {const g=new T.Group();g.name=name;parent.add(g);return g;}
  return {T,root,materials,paint,dark,rubber,steel,black,orange,red,light,material,mesh,box,roundBox,rod,sphere,plate,circle,torus,bolt,hose,quad,group};
};
