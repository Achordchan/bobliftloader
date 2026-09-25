import * as THREE from 'three';
import { MaterialFlow } from './material-flow.js?v=1';

/** Bucket-local fitted fill surface plus world-space, ground-colliding discharge clods. */
export class BucketPayload {
  constructor({ parent, meshes, scene, material }) {
    this.parent = parent; this.scene = scene; this.material = material;
    this.flow = new MaterialFlow(); this.pending = 0; this.lastAmount = -1; this.seed = 19;
    this.capacity = 256; this.quantum = .006;
    this.point = new THREE.Vector3(); this.direction = new THREE.Vector3();
    this.rotation = new THREE.Quaternion(); this.scale = new THREE.Vector3(); this.matrix = new THREE.Matrix4();
    this.fitSurface(meshes);
    this.grainGeometry = new THREE.IcosahedronGeometry(1, 0);
    this.grains = new THREE.InstancedMesh(this.grainGeometry, material, this.capacity);
    this.grains.instanceMatrix.setUsage(THREE.DynamicDrawUsage);
    this.grains.castShadow = true; this.grains.receiveShadow = true;
    this.grains.frustumCulled = false;
    scene.add(this.grains);
    this.particles = Array.from({ length: this.capacity }, () => ({ active: false, settled: false, age: 0, radius: .02, position: new THREE.Vector3(), velocity: new THREE.Vector3(), spin: 0 }));
    this.reset();
  }

  random() { this.seed = this.seed * 16807 % 2147483647; return this.seed / 2147483647; }

  fitSurface(meshes) {
    this.parent.updateWorldMatrix(true, true);
    const inverse = this.parent.matrixWorld.clone().invert();
    const bounds = new THREE.Box3(), point = new THREE.Vector3();
    const collisionMaterial = new THREE.MeshBasicMaterial({ side: THREE.DoubleSide });
    const colliders = meshes.map(source => {
      const mesh = new THREE.Mesh(source.geometry, collisionMaterial);
      mesh.matrixAutoUpdate = false;
      mesh.matrix.copy(inverse.clone().multiply(source.matrixWorld)); mesh.matrixWorld.copy(mesh.matrix);
      const a = source.geometry.attributes.position;
      for (let i = 0; i < a.count; i++) bounds.expandByPoint(point.fromBufferAttribute(a, i).applyMatrix4(mesh.matrix));
      return mesh;
    });
    const ray = new THREE.Raycaster(), down = new THREE.Vector3(0, -1, 0);
    const floorAt = (x, z) => {
      ray.set(point.set(x, bounds.max.y + .1, z), down);
      return ray.intersectObjects(colliders, false)[0]?.point.y;
    };
    // Find the long, low cavity floor. High rear brackets and the rim must not become
    // the base of the load, as happens when a world-axis bounding box is used.
    const scan = Array.from({ length: 64 }, (_, i) => {
      const z = bounds.min.z + (i + .5) / 64 * (bounds.max.z - bounds.min.z);
      return { z, y: floorAt(0, z) };
    });
    const low = Math.min(...scan.filter(p => p.y !== undefined).map(p => p.y));
    let best = [], run = [];
    for (const p of scan) {
      if (p.y !== undefined && p.y < low + .035) run.push(p); else run = [];
      if (run.length > best.length) best = [...run];
    }
    if (best.length < 8) throw new Error('Cannot locate the CAD bucket cavity floor');
    this.front = best[0].z + .015; this.back = best.at(-1).z - .018;
    this.width = (bounds.max.x - bounds.min.x) / 2 - .045;
    this.centerX = (bounds.max.x + bounds.min.x) / 2;
    this.height = Math.min((bounds.max.y - low) * .52, (this.back - this.front) * .48);
    this.outlet = new THREE.Vector3(this.centerX, floorAt(0, best[0].z) + .012, bounds.min.z - .045);
    const nx = 24, nz = 12, count = (nx + 1) * (nz + 1);
    const vertices = [], indices = []; this.surface = [];
    for (let j = 0; j <= nz; j++) for (let i = 0; i <= nx; i++) {
      const u = i / nx, v = j / nz, x = this.centerX + (u * 2 - 1) * this.width, z = this.front + v * (this.back - this.front);
      const floor = floorAt(x, z);
      if (floor === undefined || floor > low + .06) throw new Error('Material surface extends outside the bucket cavity');
      const profile = Math.pow(Math.sin(Math.PI * u) * Math.sin(Math.PI * v), .8);
      const height = this.height * profile * (1 + .07 * Math.sin(i * 2.3 + j * 1.7));
      this.surface.push({ x, z, floor: floor + .003, height, v });
      vertices.push(x, floor + .003 + height, z);
    }
    for (const p of this.surface) vertices.push(p.x, p.floor, p.z);
    for (let j = 0; j < nz; j++) for (let i = 0; i < nx; i++) {
      const a = j * (nx + 1) + i, b = a + 1, c = a + nx + 1, d = c + 1;
      indices.push(a, c, b, b, c, d, a + count, b + count, c + count, b + count, d + count, c + count);
    }
    const border = [];
    for (let i = 0; i <= nx; i++) border.push(i);
    for (let j = 1; j <= nz; j++) border.push(j * (nx + 1) + nx);
    for (let i = nx - 1; i >= 0; i--) border.push(nz * (nx + 1) + i);
    for (let j = nz - 1; j > 0; j--) border.push(j * (nx + 1));
    for (let i = 0; i < border.length; i++) {
      const a = border[i], b = border[(i + 1) % border.length];
      indices.push(a, b, b + count, a, b + count, a + count);
    }
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3)); geometry.setIndex(indices);
    geometry.computeVertexNormals(); geometry.computeBoundingSphere();
    this.heap = new THREE.Mesh(geometry, this.material); this.heap.castShadow = true; this.heap.receiveShadow = true;
    this.heap.visible = false; this.parent.add(this.heap);
    collisionMaterial.dispose();
  }

  setTarget(value) { this.flow.setTarget(value); }

  updateSurface() {
    const amount = this.flow.amount, attribute = this.heap.geometry.attributes.position;
    this.heap.visible = amount > 1e-5;
    // Lower and bias the upper surface toward the mouth; the base and footprint stay
    // inside the measured cavity. No translation through the bucket floor or walls.
    for (let i = 0; i < this.surface.length; i++) {
      const p = this.surface[i];
      const bias = 1 - .25 * this.slide * p.v;
      attribute.setY(i, p.floor + p.height * amount * bias);
    }
    attribute.needsUpdate = true; this.heap.geometry.computeVertexNormals(); this.lastAmount = amount;
  }

  emit(amount) {
    this.pending += amount;
    while (this.pending >= this.quantum || (this.flow.amount < 1e-5 && this.pending > 1e-6)) {
      const particle = this.particles.find(p => !p.active);
      if (!particle) break;
      const portion = Math.min(this.pending, this.quantum);
      this.pending -= portion;
      particle.active = true; particle.settled = false; particle.age = 0;
      particle.radius = (.015 + this.random() * .014) * Math.cbrt(portion / this.quantum); particle.spin = this.random() * Math.PI;
      this.point.copy(this.outlet); this.point.x += (this.random() * 2 - 1) * this.width * .85;
      this.point.z -= particle.radius;
      particle.position.copy(this.point).applyMatrix4(this.parent.matrixWorld);
      particle.position.y = Math.max(particle.position.y, particle.radius + .007);
      particle.velocity.set((this.random() - .5) * .25, -.15, -.65 - this.random() * .3).transformDirection(this.parent.matrixWorld).multiplyScalar(.7 + this.random() * .35);
    }
  }

  step(seconds) {
    const dt = Math.min(Math.max(seconds, 0), .05);
    this.parent.updateWorldMatrix(true, false);
    this.direction.set(0, 0, -1).transformDirection(this.parent.matrixWorld);
    const released = this.flow.step(dt, this.direction.y < -.06);
    const previousSlide = this.slide;
    this.slide += ((released > 0 ? 1 : 0) - this.slide) * (1 - Math.exp(-6 * dt));
    if (released > 0) this.emit(released);
    if (this.pending >= this.quantum) this.emit(0);
    let changed = false;
    if (Math.abs(this.flow.amount - this.lastAmount) > 1e-6 || Math.abs(this.slide - previousSlide) > 1e-4) { this.updateSurface(); changed = true; }
    let active = 0;
    for (let i = 0; i < this.capacity; i++) {
      const p = this.particles[i];
      let size = 0;
      if (p.active) {
        changed = true; active++; p.age += dt;
        if (!p.settled) {
          p.velocity.y -= 9.81 * dt; p.position.addScaledVector(p.velocity, dt); p.spin += dt * 3;
          if (p.position.y <= p.radius + .004) {
            p.position.y = p.radius + .004; p.velocity.set(0, 0, 0); p.settled = true; p.age = 0;
          }
        }
        // Deposits stay at world-ground positions, then clear gradually for the next
        // display cycle. They never disappear in mid-flight or follow the chassis.
        size = p.radius * (p.settled ? Math.max(0, 1 - Math.max(0, p.age - 4) / 2) : 1);
        if (p.settled) p.position.y = size + .004;
        if (size < .0001) p.active = false;
      }
      this.rotation.setFromAxisAngle(this.direction.set(1, 0, .4).normalize(), p.spin);
      this.scale.set(size, size * .75, size * .9);
      this.matrix.compose(p.position, this.rotation, this.scale); this.grains.setMatrixAt(i, this.matrix);
    }
    this.grains.visible = active > 0;
    if (changed) this.grains.instanceMatrix.needsUpdate = true;
    return changed;
  }

  reset() {
    this.flow.reset(); this.pending = 0; this.lastAmount = -1; this.slide = 0;
    this.heap.visible = false;
    for (let i = 0; i < this.capacity; i++) {
      this.particles[i].active = false;
      this.grains.setMatrixAt(i, this.matrix.makeScale(0, 0, 0));
    }
    this.grains.instanceMatrix.needsUpdate = true; this.grains.visible = false;
  }

  dispose() {
    this.heap.removeFromParent(); this.grains.removeFromParent();
    this.heap.geometry.dispose(); this.grainGeometry.dispose(); this.material.dispose();
  }
}
