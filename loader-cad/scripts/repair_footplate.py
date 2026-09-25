"""Repair only tread transforms in the current scene and save a separate project."""
import hashlib
import json
import struct
import sys
from datetime import datetime
from pathlib import Path
import bpy
ROOT = Path(__file__).resolve().parents[1]
sys.path.insert(0, str(ROOT/'scripts'))
from footplate import PREFIX, fit_treads


def unchanged_objects():
    result = {}
    for obj in bpy.context.scene.objects:
        if obj.name.startswith(PREFIX):
            continue
        digest = hashlib.sha256()
        digest.update(repr(tuple(tuple(row) for row in obj.matrix_world)).encode())
        if obj.type == 'MESH':
            for vertex in obj.data.vertices:
                digest.update(struct.pack('fff', *vertex.co))
            for polygon in obj.data.polygons:
                digest.update(repr(tuple(polygon.vertices)).encode())
        digest.update(repr([slot.material.name if slot.material else None for slot in obj.material_slots]).encode())
        result[obj.name] = digest.hexdigest()
    return result


def main(overwrite=False):
    bpy.context.view_layer.update()
    before = unchanged_objects()
    source = bpy.data.filepath
    stamp = datetime.now().strftime('%Y%m%d-%H%M%S-%f')
    backup = ROOT/'backups'/f'before-footplate-{stamp}.blend'
    backup.parent.mkdir(exist_ok=True)
    bpy.ops.wm.save_as_mainfile(filepath=str(backup), copy=True)
    report = fit_treads()
    after = unchanged_objects()
    if before != after:
        raise RuntimeError('Unexpected change outside the standing-platform treads')
    destination = Path(source) if overwrite and source else ROOT/'loader-cad-footplate-fixed.blend'
    # Return the live editor to its previous 3D view before saving the corrected copy.
    if bpy.context.area and bpy.context.area.type == 'CONSOLE':
        bpy.context.area.type = 'VIEW_3D'
    bpy.ops.wm.save_as_mainfile(filepath=str(destination))
    report.update(source=source, backup=str(backup), output=str(destination),
                  unchanged_other_objects=len(before), original_cad_geometry_unchanged=True)
    (ROOT/'data/footplate-repair.json').write_text(json.dumps(report, ensure_ascii=False, indent=2))
    print(json.dumps(report, ensure_ascii=False, indent=2))

if __name__ == '__main__':
    main()
