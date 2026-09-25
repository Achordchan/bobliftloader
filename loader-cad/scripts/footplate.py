"""Seat the photo-derived tread bars against the actual CAD standing platform."""
import math
from collections import defaultdict
import bpy
from mathutils import Matrix, Vector
from mathutils.bvhtree import BVHTree

PREFIX = '踏板防滑纹'
EMBED_DEPTH = 0.0004


def support_geometry():
    platforms = [o for o in bpy.context.scene.objects if o.get('CAD编号') == 158]
    if len(platforms) != 1:
        raise RuntimeError('Expected one original CAD standing platform (158)')
    platform = platforms[0]
    vertices = [platform.matrix_world @ v.co for v in platform.data.vertices]
    faces = [tuple(p.vertices) for p in platform.data.polygons]
    tree = BVHTree.FromPolygons(vertices, faces, all_triangles=False)
    groups = defaultdict(lambda: {'area': 0.0, 'normal': None, 'distance': None})
    for face in faces:
        a, b, c = (vertices[i] for i in face[:3])
        cross = (b-a).cross(c-a)
        if cross.length < 1e-12:
            continue
        normal = cross.normalized()
        if normal.z < 0:
            normal.negate()
        if normal.z < 0.8:
            continue
        distance = normal.dot(a)
        key = tuple(round(v, 4) for v in normal) + (round(distance, 5),)
        group = groups[key]
        group['area'] += cross.length / 2
        group['normal'], group['distance'] = normal, distance
    if not groups:
        raise RuntimeError('No usable walking surface on CAD platform')
    largest = max(group['area'] for group in groups.values())
    # Upper and lower sheet surfaces have the same footprint. Select the upper one.
    candidates = [g for g in groups.values() if g['area'] > largest * 0.8]
    plane = max(candidates, key=lambda g: g['distance'])
    return platform, tree, plane['normal'], plane['distance'], max(v.z for v in vertices)


def fit_treads():
    bpy.context.view_layer.update()
    platform, tree, normal, distance, top = support_geometry()
    treads = sorted((o for o in bpy.context.scene.objects if o.name.startswith(PREFIX)), key=lambda o: o.name)
    if len(treads) != 48:
        raise RuntimeError(f'Expected 48 existing tread bars, got {len(treads)}')
    before = []
    proposals = []
    for obj in treads:
        world = obj.matrix_world.copy()
        before.append(min(normal.dot(world @ v.co)-distance for v in obj.data.vertices))
        center = world.translation
        hit, hit_normal, _, _ = tree.ray_cast(Vector((center.x, center.y, top+.2)), Vector((0, 0, -1)))
        if hit is None or abs(normal.dot(hit)-distance) > 0.00005:
            raise RuntimeError(f'{obj.name}: center is not above the CAD walking surface')
        # Project the existing bar heading onto the deck, retaining its alternating pattern.
        heading = world.to_3x3().col[0].normalized()
        tangent = (heading-normal*heading.dot(normal)).normalized()
        cross_tangent = normal.cross(tangent).normalized()
        rotation = Matrix((tangent, cross_tangent, normal)).transposed().to_quaternion()
        scale = world.to_scale()
        local_bottom = min(v.co.z for v in obj.data.vertices)*scale.z
        location = hit-normal*(local_bottom+EMBED_DEPTH)
        proposed = Matrix.LocRotScale(location, rotation, scale)
        # All four corners and edge midpoints must remain over the supporting sheet.
        xs = [min(v.co.x for v in obj.data.vertices), 0, max(v.co.x for v in obj.data.vertices)]
        ys = [min(v.co.y for v in obj.data.vertices), 0, max(v.co.y for v in obj.data.vertices)]
        for x in xs:
            for y in ys:
                q = proposed @ Vector((x, y, local_bottom/scale.z))
                contact, _, _, _ = tree.ray_cast(q+normal*.03, -normal, .06)
                if contact is None or abs(normal.dot(contact)-distance) > 0.00005:
                    raise RuntimeError(f'{obj.name}: tread footprint leaves walking surface')
        proposals.append((obj, proposed))
    # Validate the entire placement before changing any object.
    for obj, proposed in proposals:
        obj.matrix_world = proposed
        obj['贴合表面'] = platform.name
        obj['嵌入深度_mm'] = EMBED_DEPTH*1000
    bpy.context.view_layer.update()
    report = validate_treads()
    report['before_bottom_gap_mm'] = [min(before)*1000, max(before)*1000]
    report['platform_tilt_degrees'] = math.degrees(math.acos(normal.z))
    return report


def validate_treads():
    bpy.context.view_layer.update()
    _, tree, normal, distance, _ = support_geometry()
    treads = [o for o in bpy.context.scene.objects if o.name.startswith(PREFIX)]
    if len(treads) != 48:
        raise RuntimeError('Tread count changed')
    gaps = []
    depsgraph = bpy.context.evaluated_depsgraph_get()
    for obj in treads:
        evaluated = obj.evaluated_get(depsgraph)
        mesh = evaluated.to_mesh()
        try:
            gap = min(normal.dot(evaluated.matrix_world @ v.co)-distance for v in mesh.vertices)
        finally:
            evaluated.to_mesh_clear()
        if not -0.00045 < gap < -0.00035:
            raise RuntimeError(f'{obj.name}: base is not embedded as expected ({gap} m)')
        alignment = obj.matrix_world.to_3x3().col[2].normalized().dot(normal)
        if alignment < 0.999999:
            raise RuntimeError(f'{obj.name}: not aligned to platform')
        gaps.append(gap*1000)
    return {'tread_count': len(treads), 'evaluated_bottom_gap_mm': [min(gaps), max(gaps)],
            'floating_treads': 0, 'surface_alignment_verified': True}
