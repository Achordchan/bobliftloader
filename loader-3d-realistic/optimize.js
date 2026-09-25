/* Merge only rigid opaque parts. Articulation pivots and dynamic cylinders remain independent. */
'use strict';
window.optimizeLoader = function(loader) {
  const T=THREE;
  function batch(scope,exclude=new Set()) {
    scope.updateWorldMatrix(true,true);
    const inverse=scope.matrixWorld.clone().invert(),buckets=new Map();
    function collect(o){
      if(exclude.has(o))return;
      if(o.isMesh&&!o.isInstancedMesh&&!o.material.transparent){
        const list=buckets.get(o.material)||[];list.push(o);buckets.set(o.material,list);
      }
      o.children.forEach(collect);
    }
    scope.children.forEach(collect);
    for(const [material,objects] of buckets){
      if(objects.length<2)continue;
      const geometries=objects.map(o=>{
        const src=o.geometry,g=src.index?src.toNonIndexed():src.clone();
        for(const name of Object.keys(g.attributes))if(!['position','normal','uv'].includes(name))g.deleteAttribute(name);
        if(!g.attributes.uv)g.setAttribute('uv',new T.BufferAttribute(new Float32Array(g.attributes.position.count*2),2));
        g.applyMatrix4(new T.Matrix4().multiplyMatrices(inverse,o.matrixWorld));return g;
      });
      const merged=StudioAddons.mergeGeometries(geometries,false);geometries.forEach(g=>g.dispose());
      if(!merged)throw new Error('Rigid geometry merge failed');
      const mesh=new T.Mesh(merged,material);mesh.name='合并静态部件';mesh.castShadow=mesh.receiveShadow=true;scope.add(mesh);
      objects.forEach(o=>{o.removeFromParent();o.geometry.dispose();});
    }
  }
  loader.root.children.forEach(part=>{
    if(part===loader.boom){batch(loader.bucket);batch(part,new Set([loader.bucket]));}
    else if(part.name!=='动态液压缸')batch(part);
  });
};
