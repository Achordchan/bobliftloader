"""Physically differentiated coatings and metals, authored directly as Blender shader nodes."""
import bpy


def srgb(hex_color):
    rgb = [int(hex_color[i:i + 2], 16) / 255 for i in (0, 2, 4)]
    return tuple(c / 12.92 if c <= .04045 else ((c + .055) / 1.055) ** 2.4 for c in rgb) + (1,)


def material(name, color, metallic=0, roughness=.4, coat=0, texture_scale=0, bump=0):
    mat = bpy.data.materials.new(name)
    mat.use_nodes = True
    p = mat.node_tree.nodes.get('Principled BSDF')
    p.inputs['Base Color'].default_value = srgb(color)
    p.inputs['Metallic'].default_value = metallic
    p.inputs['Roughness'].default_value = roughness
    p.inputs['Coat Weight'].default_value = coat
    p.inputs['Coat Roughness'].default_value = .19
    mat.diffuse_color = srgb(color)
    if texture_scale:
        n = mat.node_tree.nodes.new('ShaderNodeTexNoise')
        n.inputs['Scale'].default_value = texture_scale
        n.inputs['Detail'].default_value = 2
        b = mat.node_tree.nodes.new('ShaderNodeBump')
        b.inputs['Strength'].default_value = .22
        b.inputs['Distance'].default_value = bump
        mat.node_tree.links.new(n.outputs['Fac'], b.inputs['Height'])
        mat.node_tree.links.new(b.outputs['Normal'], p.inputs['Normal'])
    return mat


def create_materials():
    mats = {
        'paint': material('01 · 橙色工业烤漆', 'F34C06', .05, .27, .48, 650, .00035),
        'dark': material('02 · 黑色粉末喷涂钢板', '252A27', .20, .43, .12, 430, .0006),
        'black': material('03 · 液压缸黑色涂层', '111513', .32, .24, .3),
        'rubber': material('04 · 履带橡胶', '242622', 0, .83, 0, 310, .0012),
        'steel': material('05 · 镀锌紧固件', 'ADB3B7', .96, .29),
        'chrome': material('06 · 抛光活塞杆', 'C2C7CB', 1, .15),
        'wear': material('07 · 铲刃裸钢', '929A9E', .95, .34, 0, 900, .00012),
        'red': material('08 · 红色防尘帽', 'BD2110', 0, .32, .25),
        'amber': material('09 · 琥珀反光片', 'EB8A14', .05, .25, .7),
        'lens': material('10 · 灯具透明罩', 'F4F8F7', 0, .09, .7),
        'reflector': material('11 · 工作灯反射杯', 'D6D9DC', 1, .14),
        'floor': material('12 · 摄影棚浅灰地面', 'B7B5AF', .05, .60, 0, 110, .0006),
    }
    mats['lens'].node_tree.nodes.get('Principled BSDF').inputs['Transmission Weight'].default_value = .96
    mats['lens'].node_tree.nodes.get('Principled BSDF').inputs['IOR'].default_value = 1.47
    return mats
