"""Check pairwise coplanar overlap on the hood, before and after modifiers."""
import itertools
import bpy

PANELS=('机罩上面折板','机罩上面折板.001','机罩下端面','机罩上端面')


def signed_area(poly):
    return sum(a[0]*b[1]-b[0]*a[1] for a,b in zip(poly,poly[1:]+poly[:1]))*.5


def intersection_area(subject,clip):
    if signed_area(clip)<0:clip=list(reversed(clip))
    result=subject[:]
    for a,b in zip(clip,clip[1:]+clip[:1]):
        if not result:break
        def distance(p):return (b[0]-a[0])*(p[1]-a[1])-(b[1]-a[1])*(p[0]-a[0])
        source=result;result=[];previous=source[-1];dp=distance(previous)
        for current in source:
            dc=distance(current)
            if (dc>=0)!=(dp>=0):
                t=dp/(dp-dc)
                result.append((previous[0]+t*(current[0]-previous[0]),previous[1]+t*(current[1]-previous[1])))
            if dc>=0:result.append(current)
            previous=current;dp=dc
    return abs(signed_area(result)) if len(result)>2 else 0


def check_hood(require_clean=True):
    bpy.context.view_layer.update()
    hood=bpy.data.objects['机罩斜面坐标架']
    inverse=hood.matrix_world.inverted()
    dg=bpy.context.evaluated_depsgraph_get()
    report={}
    for evaluated in (False,True):
        triangles={}
        for name in PANELS:
            obj=bpy.data.objects[name]
            if evaluated:obj=obj.evaluated_get(dg)
            obj.data.calc_loop_triangles()
            transform=inverse@obj.matrix_world
            coords=[transform@p.co for p in obj.data.vertices]
            triangles[name]=[]
            for triangle in obj.data.loop_triangles:
                points=[coords[i] for i in triangle.vertices]
                # The photographed panel faces share local Y=.018 in Blender coordinates.
                if all(abs(p.y-.018)<1e-6 for p in points):
                    triangles[name].append([(p.x,p.z) for p in points])
            assert triangles[name],f'No front surface found: {name}'
        areas={}
        for a,b in itertools.combinations(PANELS,2):
            area=sum(intersection_area(x,y) for x in triangles[a] for y in triangles[b])
            areas[a+' / '+b]=round(area,12)
        report['evaluated' if evaluated else 'base']=areas
    if require_clean:
        assert all(area<1e-9 for values in report.values() for area in values.values()),report
    return report
