'use strict';
(() => {
  const host=document.getElementById('viewport');
  try {
    const T=THREE,scene=new T.Scene();
    const camera=new T.PerspectiveCamera(28,1,.08,55);
    const renderer=new T.WebGLRenderer({antialias:true,preserveDrawingBuffer:true,powerPreference:'high-performance'});
    renderer.setPixelRatio(Math.min(devicePixelRatio,1.5));host.appendChild(renderer.domElement);
    const studio=createRealisticStudio(renderer,scene,camera);
    const loader=createLoader();scene.add(loader.root);optimizeLoader(loader);
    const target=new T.Vector3(0,1.10,.18);
    let azimuth=.76,elevation=.30,distance=9.35,auto=false,lastTime=0,dirty=true;
    const pointers=new Map();let pinch=0;
    const lift=document.getElementById('lift'),tilt=document.getElementById('tilt');
    const rotate=document.getElementById('rotate'),wireframe=document.getElementById('wireframe');
    function updatePose(){dirty=true;renderer.shadowMap.needsUpdate=true;const pose=loader.update(+lift.value,+tilt.value);tilt.max=pose.maxTilt;tilt.value=pose.tilt;tilt.title=pose.maxTilt<45?'抬起举升臂可增加铲斗下翻角度':'';document.querySelector('.intro').hidden=+lift.value>12;document.getElementById('lift-value').value=lift.value+'°';document.getElementById('tilt-value').value=tilt.value+'°';}
    lift.addEventListener('input',updatePose);tilt.addEventListener('input',updatePose);
    rotate.addEventListener('change',()=>{auto=rotate.checked;dirty=true;});
    wireframe.addEventListener('change',()=>{loader.materials.forEach(m=>{m.wireframe=wireframe.checked;});studio.ao.enabled=!wireframe.checked;dirty=true;});
    function selectView(name){document.querySelectorAll('[data-view]').forEach(b=>b.classList.toggle('active',b.dataset.view===name));}
    document.querySelectorAll('[data-view]').forEach(button=>button.addEventListener('click',()=>{
      dirty=true;const name=button.dataset.view;azimuth={perspective:.76,side:Math.PI/2,front:0,back:Math.PI}[name];elevation=name==='perspective'?.30:.13;selectView(name);auto=false;rotate.checked=false;
    }));
    function reset(){azimuth=.76;elevation=.30;distance=9.35;lift.value=0;tilt.value=0;auto=false;rotate.checked=false;wireframe.checked=false;studio.ao.enabled=true;loader.materials.forEach(m=>m.wireframe=false);updatePose();selectView('perspective');}
    document.getElementById('reset').addEventListener('click',reset);
    function gap(){const a=[...pointers.values()];return a.length===2?Math.hypot(a[0].x-a[1].x,a[0].y-a[1].y):0;}
    host.addEventListener('pointerdown',e=>{host.setPointerCapture(e.pointerId);pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});pinch=gap();auto=false;rotate.checked=false;});
    host.addEventListener('pointermove',e=>{const old=pointers.get(e.pointerId);if(!old)return;dirty=true;const dx=e.clientX-old.x,dy=e.clientY-old.y;pointers.set(e.pointerId,{x:e.clientX,y:e.clientY});if(pointers.size===2){const next=gap();distance=T.MathUtils.clamp(distance+(pinch-next)*.012,3.8,13);pinch=next;}else{azimuth-=dx*.008;elevation=T.MathUtils.clamp(elevation+dy*.006,.03,1.35);}selectView('');});
    const release=e=>{pointers.delete(e.pointerId);pinch=gap();};host.addEventListener('pointerup',release);host.addEventListener('pointercancel',release);host.addEventListener('lostpointercapture',release);
    host.addEventListener('wheel',e=>{e.preventDefault();dirty=true;distance=T.MathUtils.clamp(distance+e.deltaY*.004,3.8,13);},{passive:false});
    function resize(){dirty=true;renderer.setSize(host.clientWidth,host.clientHeight);camera.aspect=host.clientWidth/host.clientHeight;camera.updateProjectionMatrix();studio.resize(host.clientWidth,host.clientHeight);}
    window.addEventListener('resize',resize);document.addEventListener('visibilitychange',()=>{dirty=true;});resize();reset();
    function render(time){const dt=Math.min((time-lastTime)/1000,.05);lastTime=time;if(document.hidden||(!auto&&!dirty)){requestAnimationFrame(render);return;}dirty=false;if(auto)azimuth+=dt*.20;
      const viewDistance=distance*(innerWidth<760?2.15:1);
      camera.position.set(target.x+Math.sin(azimuth)*Math.cos(elevation)*viewDistance,target.y+Math.sin(elevation)*viewDistance,target.z+Math.cos(azimuth)*Math.cos(elevation)*viewDistance);
      camera.lookAt(target);if(innerWidth<760){camera.setViewOffset(host.clientWidth,host.clientHeight,0,host.clientHeight*.10,host.clientWidth,host.clientHeight);}else camera.clearViewOffset();
      studio.draw();requestAnimationFrame(render);
    }
    requestAnimationFrame(render);
    window.loaderStudio={scene,camera,renderer,loader,studio};
  } catch(error){document.getElementById('error').hidden=false;console.error(error);}
})();
