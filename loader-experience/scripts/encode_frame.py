"""Encode actual Blender frames into alpha WebP files for local web delivery."""
import sys
from pathlib import Path
from PIL import Image
source=Path(sys.argv[1]);destination=Path(sys.argv[2]);destination.parent.mkdir(exist_ok=True,parents=True)
with Image.open(source) as image:
    temporary=destination.with_suffix('.part')
    image.save(temporary,format='WEBP',quality=84,method=3)
    temporary.replace(destination)
