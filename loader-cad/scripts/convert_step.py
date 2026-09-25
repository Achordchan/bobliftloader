"""Convert STEP assembly leaves to meshes, preserving names and world placements."""
import argparse
import hashlib
import json
from pathlib import Path
from OCP.XCAFDoc import XCAFDoc_DocumentTool
from OCP.STEPCAFControl import STEPCAFControl_Reader
from OCP.TDocStd import TDocStd_Document
from OCP.TCollection import TCollection_ExtendedString
from OCP.TDF import TDF_LabelSequence, TDF_Label
from OCP.TDataStd import TDataStd_Name
from OCP.TopLoc import TopLoc_Location
from OCP.BRepMesh import BRepMesh_IncrementalMesh
from OCP.BRep import BRep_Tool
from OCP.TopExp import TopExp_Explorer
from OCP.TopAbs import TopAbs_FACE, TopAbs_REVERSED
from OCP.TopoDS import TopoDS
from OCP.IFSelect import IFSelect_RetDone
from OCP.BRepAdaptor import BRepAdaptor_Surface
from OCP.GeomAbs import GeomAbs_Cylinder


def convert(source, destination):
    doc=TDocStd_Document(TCollection_ExtendedString('cad'))
    reader=STEPCAFControl_Reader();reader.SetNameMode(True)
    if reader.ReadFile(str(source)) != IFSelect_RetDone:
        raise RuntimeError('STEP read failed')
    if not reader.Transfer(doc):
        raise RuntimeError('STEP assembly transfer failed')
    tool=XCAFDoc_DocumentTool.ShapeTool_s(doc.Main())
    roots=TDF_LabelSequence();tool.GetFreeShapes(roots)
    parts=[]
    def label_name(label):
        attr=TDataStd_Name()
        return attr.Get().ToExtString() if label.FindAttribute(TDataStd_Name.GetID_s(),attr) else 'Unnamed'
    def walk(label,location,path):
        name=label_name(label)
        if tool.IsReference_s(label):
            reference=TDF_Label();tool.GetReferredShape_s(label,reference)
            walk(reference,location.Multiplied(tool.GetLocation_s(label)),path)
        elif tool.IsAssembly_s(label):
            components=TDF_LabelSequence();tool.GetComponents_s(label,components)
            for i in range(1,components.Length()+1):
                walk(components.Value(i),location,path+[name])
        else:
            shape=tool.GetShape_s(label).Moved(location)
            BRepMesh_IncrementalMesh(shape,0.25,False,0.20,True)
            vertices=[];triangles=[];face_count=0;cylinders=[]
            faces=TopExp_Explorer(shape,TopAbs_FACE)
            while faces.More():
                face=TopoDS.Face_s(faces.Current());loc=TopLoc_Location()
                surface=BRepAdaptor_Surface(face,True)
                if surface.GetType()==GeomAbs_Cylinder:
                    cylinder=surface.Cylinder();origin=cylinder.Location();direction=cylinder.Axis().Direction()
                    cylinders.append(dict(radius_mm=cylinder.Radius(),origin_mm=[origin.X(),origin.Y(),origin.Z()],axis=[direction.X(),direction.Y(),direction.Z()]))
                tri=BRep_Tool.Triangulation_s(face,loc)
                if tri is None:raise RuntimeError(f'Missing triangulation: {name}')
                offset=len(vertices);transform=loc.Transformation()
                for i in range(1,tri.NbNodes()+1):
                    p=tri.Node(i).Transformed(transform)
                    vertices.append([p.X(),p.Y(),p.Z()])
                for i in range(1,tri.NbTriangles()+1):
                    a,b,c=tri.Triangle(i).Get()
                    if face.Orientation()==TopAbs_REVERSED:b,c=c,b
                    triangles.append([offset+a-1,offset+b-1,offset+c-1])
                face_count+=1;faces.Next()
            if not vertices:raise RuntimeError(f'Empty part: {name}')
            bounds=[[min(v[j] for v in vertices),max(v[j] for v in vertices)] for j in range(3)]
            parts.append(dict(id=len(parts)+1,name=name,path=path,vertices=vertices,triangles=triangles,bounds_mm=bounds,cad_faces=face_count,cylinders=cylinders))
            print(f'{len(parts):03d} {name}: {len(triangles)} triangles',flush=True)
    for i in range(1,roots.Length()+1):walk(roots.Value(i),TopLoc_Location(),[])
    result=dict(source=str(source),sha256=hashlib.sha256(source.read_bytes()).hexdigest(),unit='mm',linear_deflection_mm=0.25,parts=parts)
    destination.write_text(json.dumps(result,ensure_ascii=False))
    summary={k:v for k,v in result.items() if k!='parts'}
    summary['parts']=[{k:v for k,v in p.items() if k not in ('vertices','triangles')}|{'triangles':len(p['triangles'])} for p in parts]
    destination.with_name('assembly-report.json').write_text(json.dumps(summary,ensure_ascii=False,indent=2))
    print(f'Exported {len(parts)} assembly leaves',flush=True)

if __name__=='__main__':
    parser=argparse.ArgumentParser();parser.add_argument('source',type=Path);parser.add_argument('destination',type=Path)
    args=parser.parse_args();convert(args.source,args.destination)
