'use strict';
(() => {
  const host=document.getElementById('viewport');
  try {
    const T=THREE,scene=new T.Scene();scene.background=new T.Color(0xeeede9);
    const camera=new T.PerspectiveCamera(34,1,.1,100);
    const renderer=new T.WebGLRenderer({antialias:true,preserveDrawingBuffer:true});
    renderer.setPixelRatio(Math.min(devicePixelRatio,2));renderer.shadowMap.enabled=true;
    renderer.shadowMap.type=T.PCFSoftShadowMap;renderer.outputColorSpace=T.SRGBColorSpace;
    renderer.toneMapping=T.ACESFilmicToneMapping;renderer.toneMappingExposure=1.08;host.appendChild(renderer.domElement);
    scene.add(new T.HemisphereLight(0xfffaf1,0x858983,1.65));
    const key=new T.DirectionalLight(0xfff4e5,2.7);key.position.set(-3,7,5);key.castShadow=true;
    key.shadow.mapSize.set(2048,2048);Object.assign(key.shadow.camera,{left:-4,right:4,top:4,bottom:-4,near:.5,far:20});key.shadow.bias=-.0003;key.shadow.normalBias=.02;key.shadow.radius=4;scene.add(key);
    const fill=new T.DirectionalLight(0xf5f9ff,1.8);fill.position.set(4,3,-3);scene.add(fill);
    // A local studio reflection map gives painted and machined surfaces broad highlights.
    const studio=new T.Scene();studio.background=new T.Color(0x999b99);
    const room=new T.Mesh(new T.BoxGeometry(18,14,18),new T.MeshBasicMaterial({color:0x8b8e8a,side:T.BackSide}));studio.add(room);
    for(const [x,y,z,w,h,d,power] of [[-5,5,2,3,6,.1,5],[4,4,-3,2,5,.1,3],[0,7,0,6,.1,5,3]]){
      const panel=new T.Mesh(new T.BoxGeometry(w,h,d),new T.MeshBasicMaterial({color:new T.Color(power,power,power)}));panel.position.set(x,y,z);panel.lookAt(0,1,0);studio.add(panel);
    }
    const pmrem=new T.PMREMGenerator(renderer);const environment=pmrem.fromScene(studio,.06);scene.environment=environment.texture;pmrem.dispose();
    studio.traverse(o=>{if(o.isMesh){o.geometry.dispose();o.material.dispose();}});
    const loader=createLoader();scene.add(loader.root);
    const floor=new T.Mesh(new T.PlaneGeometry(200,200),new T.MeshStandardMaterial({color:0xeeede9,roughness:.88}));
    floor.rotation.x=-Math.PI/2;floor.receiveShadow=true;floor.position.y=.024;scene.add(floor);
    const target=new T.Vector3(0,1.10,.18);
    let azimuth=.76,elevation=.30,distance=7.7,auto=false,lastTime=0;
    const pointers=new Map();let pinch=0;
    const lift=document.getElementById('lift'),tilt=document.getElementById('tilt');
    const rotate=document.getElementById('rotate'),wireframe=document.getElementById('wireframe');
    function updatePose(){const pose=loader.update(+lift.value,+tilt.value);tilt.max=pose.maxTilt;tilt.value=pose.tilt;tilt.title=pose.maxTilt<45?'抬起举升臂可增加铲斗下翻角度':'';document.querySelector('.intro').hidden=+lift.value>12;document.getElementById('lift-value').value=lift.value+'°';document.getElementById('tilt-value').value=tilt.value+'°';}
    lift.addEventListener('input',updatePose);tilt.addEventListener('input',updatePose);
    rotate.addEventListener('change',()=>{auto=rotate.checked;});
    wireframe.addEventListener('change',()=>loader.materials.forEach(m=>{m.wireframe=wireframe.checked;}));
    function selectView(name){document.querySelectorAll('[data-view]').forEach(b=>b.classList.toggle('active',b.dataset.view===name));}
    document.querySelectorAll('[data-view]').forEach(button=>button.addEventListener('click',()=>{
      const name=button.dataset.view;azimuth={perspective:.76,side:Math.PI/2,front:0,back:Math.PI}[name];elevation=name==='perspective'?.30:.13;selectView(name);auto=false;rotate.checked=false;
    }));
    function reset(){azimuth=.76;elevation=.30;distance=7.7;lift.value=0;tilt.value=0;auto=false;rotate.checked=false;wireframe.checked=false;loader.materials.forEach(m=>m.wireframe=false);updatePose();selectView('perspective');}
    document.getElementById('reset').addEventListener('click',reset);
    function gap(){const a=[...pointers.values()];return a.length===2?Math.hypot(a[0].x-a[1].x,a[0].y-a[1].y):0;}
    host.addEventListener('pointerdown',e=>{host.setPointerCapture(e.pointerId);pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});pinch=gap();auto=false;rotate.checked=false;});
    host.addEventListener('pointermove',e=>{const old=pointers.get(e.pointerId);if(!old)return;const dx=e.clientX-old.x,dy=e.clientY-old.y;pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});if(pointers.size===2){const next=gap();distance=T.MathUtils.clamp(distance+(pinch-next)*.012,3.8,13);pinch=next;}else{azimuth-=dx*.008;elevation=T.MathUtils.clamp(elevation+dy*.006,.03,1.35);}selectView('');});
    const release=e=>{pointers.delete(e.pointerId);pinch=gap();};host.addEventListener('pointerup',release);host.addEventListener('pointercancel',release);host.addEventListener('lostpointercapture',release);
    host.addEventListener('wheel',e=>{e.preventDefault();distance=T.MathUtils.clamp(distance+e.deltaY*.004,3.8,13);},{passive:false});
    function resize(){renderer.setSize(host.clientWidth,host.clientHeight);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();}
    window.addEventListener('resize',resize);resize();reset();
    function render(time){const dt=Math.min((time-lastTime)/1000,.05);lastTime=time;if(auto)azimuth+=dt*.20;
      const viewDistance=distance*(innerWidth<760?2.15:1);
      camera.position.set(target.x+Math.sin(azimuth)*Math.cos(elevation)*viewDistance,target.y+Math.sin(elevation)*viewDistance,target.z+Math.cos(azimuth)*Math.cos(elevation)*viewDistance);
      camera.lookAt(target);if(innerWidth<760){camera.setViewOffset(host.clientWidth,host.clientHeight,0,host.clientHeight*.10,host.clientWidth,host.clientHeight);}else camera.clearViewOffset();
      renderer.render(scene,camera);requestAnimationFrame(render);
    }
    requestAnimationFrame(render);
    window.loaderStudio={scene,camera,renderer,loader};
  } catch(error){document.getElementById('error').hidden=false;console.error(error);}
})();
