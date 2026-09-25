/* Shared fabrication primitives. Model dimensions are visual estimates, not production measurements. */
'use strict';
window.LoaderGeometry = function (root) {
  const T = THREE;
  const materials = [];
  function material(parameters) { const m = new T.MeshStandardMaterial(parameters); materials.push(m); return m; }
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
  const paint = material({color:0xf04c05, metalness:.36, roughness:.29, bumpMap:grain(128,55,7), bumpScale:.0007});
  const dark = material({color:0x242925, metalness:.62, roughness:.38, bumpMap:grain(128,60,10), bumpScale:.001});
  const rubber = material({color:0x171b18, metalness:.02, roughness:.86, bumpMap:grain(128,95,12), bumpScale:.0018});
  const steel = material({color:0xb6babc, metalness:.92, roughness:.24});
  const black = material({color:0x080d0b, metalness:.18, roughness:.48});
  const orange = material({color:0xef7626, metalness:.2, roughness:.35});
  const red = material({color:0xb51b0d, roughness:.36});
  const light = material({color:0xffedd0, emissive:0xffe9bd, emissiveIntensity:.35, metalness:.25, roughness:.16});
  function mesh(geometry, mat, parent=root) {
    const o = new T.Mesh(geometry, mat); o.castShadow = true; o.receiveShadow = true; parent.add(o); return o;
  }
  function box(w,h,d,x,y,z,mat=dark,parent=root) {
    const o = mesh(new T.BoxGeometry(w,h,d),mat,parent); o.position.set(x,y,z); return o;
  }
  function roundBox(w,h,d,r,x,y,z,mat=dark,parent=root) {
    const s = new T.Shape(), a=-w/2,b=-h/2;
    s.moveTo(a+r,b); s.lineTo(a+w-r,b); s.quadraticCurveTo(a+w,b,a+w,b+r);
    s.lineTo(a+w,b+h-r); s.quadraticCurveTo(a+w,b+h,a+w-r,b+h);
    s.lineTo(a+r,b+h); s.quadraticCurveTo(a,b+h,a,b+h-r); s.lineTo(a,b+r); s.quadraticCurveTo(a,b,a+r,b);
    const g = new T.ExtrudeGeometry(s,{depth:Math.max(.001,d-2*r),bevelEnabled:true,bevelSize:r*.35,bevelThickness:r,bevelSegments:3,steps:1,curveSegments:6});
    g.translate(0,0,-d/2+r); const o=mesh(g,mat,parent); o.position.set(x,y,z); return o;
  }
  function rod(a,b,r,mat=steel,parent=root,r2=r,segments=20) {
    const av=new T.Vector3(...a),bv=new T.Vector3(...b),v=bv.clone().sub(av);
    const o=mesh(new T.CylinderGeometry(r2,r,v.length(),segments),mat,parent);
    o.position.copy(av.add(bv).multiplyScalar(.5)); o.quaternion.setFromUnitVectors(new T.Vector3(0,1,0),v.normalize()); return o;
  }
  function sphere(x,y,z,r,mat,parent=root) {
    const o=mesh(new T.SphereGeometry(r,20,12),mat,parent); o.position.set(x,y,z); return o;
  }
  function path(points) { const s=new T.Shape(); points.forEach(([z,y],i)=>i?s.lineTo(z,y):s.moveTo(z,y)); s.closePath(); return s; }
  function plate(points,width,x,mat=paint,parent=root,bevel=.006,holes=[]) {
    const s=path(points); holes.forEach(h=>s.holes.push(path(h)));
    const g=new T.ExtrudeGeometry(s,{depth:width,bevelEnabled:bevel>0,bevelThickness:bevel,bevelSize:bevel,bevelSegments:3,steps:1,curveSegments:16});
    g.rotateY(-Math.PI/2); g.translate(x+width/2,0,0); return mesh(g,mat,parent);
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
    const g=new T.BufferGeometry(); g.setAttribute('position',new T.Float32BufferAttribute(points.flat(),3));g.setIndex([0,1,2,0,2,3]);g.computeVertexNormals();return mesh(g,mat,parent);
  }
  function group(name,parent=root) {const g=new T.Group();g.name=name;parent.add(g);return g;}
  return {T,root,materials,paint,dark,rubber,steel,black,orange,red,light,material,mesh,box,roundBox,rod,sphere,plate,circle,torus,bolt,hose,quad,group};
};
