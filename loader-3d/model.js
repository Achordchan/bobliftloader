/* Assembles the reference-based model from separately maintained mechanical subsystems. */
'use strict';
window.createLoader = function () {
  const root = new THREE.Group();root.name='履带式迷你装载机';
  const geometry = new LoaderGeometry(root);
  buildLoaderTracks(geometry);
  buildLoaderBody(geometry);
  const assembly = buildLoaderAttachment(geometry);
  return {root, update:assembly.update, materials:geometry.materials, boom:assembly.boom, bucket:assembly.bucket};
};
