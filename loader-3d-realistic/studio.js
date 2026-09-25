/* Offline HDR studio illumination, contact shadows and linear-light postprocessing. */
'use strict';
window.createRealisticStudio = function(renderer,scene,camera) {
  const T=THREE,A=StudioAddons;
  renderer.outputColorSpace=T.SRGBColorSpace;renderer.toneMapping=T.ACESFilmicToneMapping;renderer.toneMappingExposure=.9;
  renderer.shadowMap.enabled=true;renderer.shadowMap.type=T.VSMShadowMap;renderer.shadowMap.autoUpdate=false;renderer.shadowMap.needsUpdate=true;
  const binary=atob(window.LOADER_ENVIRONMENT),bytes=new Uint8Array(binary.length);
  for(let i=0;i<binary.length;i++)bytes[i]=binary.charCodeAt(i);
  const hdr=new A.RGBELoader().parse(bytes.buffer);
  const environment=new T.DataTexture(hdr.data,hdr.width,hdr.height,T.RGBAFormat,hdr.type);
  environment.mapping=T.EquirectangularReflectionMapping;environment.colorSpace=T.LinearSRGBColorSpace;
  environment.minFilter=environment.magFilter=T.LinearFilter;environment.needsUpdate=true;
  const pmrem=new T.PMREMGenerator(renderer);const filtered=pmrem.fromEquirectangular(environment);
  scene.environment=filtered.texture;environment.dispose();pmrem.dispose();
  scene.background=new T.Color(0xf4f2ed);scene.fog=new T.Fog(0xf4f2ed,40,90);
  const ambient=new T.HemisphereLight(0xf3f5ff,0x62594d,.22);scene.add(ambient);
  const key=new T.DirectionalLight(0xfff4e8,2.0);key.position.set(-3.8,6,3.5);key.castShadow=true;
  key.shadow.mapSize.set(1024,1024);Object.assign(key.shadow.camera,{left:-3.5,right:3.5,top:3.5,bottom:-3.5,near:.5,far:14});
  key.shadow.bias=-.00015;key.shadow.normalBias=.007;key.shadow.radius=5;key.shadow.blurSamples=8;scene.add(key);
  const rim=new T.DirectionalLight(0xdfe9ff,.55);rim.position.set(3,4,-4);scene.add(rim);
  const ground=new T.Mesh(new T.PlaneGeometry(200,200),new T.MeshStandardMaterial({color:0xd2cec7,roughness:.72,metalness:.06}));
  ground.rotation.x=-Math.PI/2;ground.position.y=.024;ground.receiveShadow=true;scene.add(ground);
  // Diffuse contact falloff supplements the narrow real-time shadow map below the treads.
  const c=document.createElement('canvas');c.width=c.height=256;const ctx=c.getContext('2d');
  const grad=ctx.createRadialGradient(128,128,10,128,128,128);grad.addColorStop(0,'rgba(0,0,0,.43)');grad.addColorStop(.4,'rgba(0,0,0,.26)');grad.addColorStop(1,'rgba(0,0,0,0)');ctx.fillStyle=grad;ctx.fillRect(0,0,256,256);
  const contactTexture=new T.CanvasTexture(c),contacts=new T.Group();scene.add(contacts);
  for(const x of [-.65,.65]){
    const shadow=new T.Mesh(new T.PlaneGeometry(.92,2.55),new T.MeshBasicMaterial({map:contactTexture,transparent:true,depthWrite:false,opacity:.55}));shadow.rotation.x=-Math.PI/2;shadow.position.set(x,.025,0);contacts.add(shadow);
  }
  const target=new T.WebGLRenderTarget(1,1,{type:T.HalfFloatType,samples:0});
  const composer=new A.EffectComposer(renderer,target);composer.addPass(new A.RenderPass(scene,camera));
  const ao=new A.SSAOPass(scene,camera,1,1,16);ao.kernelRadius=.14;ao.minDistance=.0005;ao.maxDistance=.06;composer.addPass(ao);
  const output=new A.OutputPass();composer.addPass(output);
  const fxaa=new A.ShaderPass(A.FXAAShader);composer.addPass(fxaa);
  function resize(w,h){const ratio=renderer.getPixelRatio();composer.setSize(w,h);ao.setSize(Math.round(w*ratio*.6),Math.round(h*ratio*.6));fxaa.material.uniforms.resolution.value.set(1/(w*ratio),1/(h*ratio));}
  function draw(){composer.render();}
  return {composer,ao,resize,draw,contacts,environment:filtered};
};
