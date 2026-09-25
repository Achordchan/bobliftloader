"""Verify every delivered animation frame and local page dependency."""
import hashlib
import json
from html.parser import HTMLParser
from pathlib import Path
from PIL import Image
ROOT=Path(__file__).resolve().parents[1]
frames=sorted((ROOT/'assets/sequence').glob('*.webp'))
assert [p.name for p in frames]==[f'{i:04}.webp' for i in range(120)]
hashes=[]
for path in frames:
    with Image.open(path) as im:
        assert im.size==(1080,1080),(path,im.size)
        assert 'A' in im.getbands(),path
        im.verify()
    hashes.append(hashlib.sha256(path.read_bytes()).hexdigest())
assert len(set(hashes))>=118, 'Unexpected duplicate animation frames'
class Dependencies(HTMLParser):
    def handle_starttag(self,tag,attrs):
        attrs=dict(attrs)
        candidate=attrs.get('src') if tag in ('img','script') else attrs.get('href') if tag=='link' else None
        if candidate and not candidate.startswith(('http','#','data:')):
            assert (ROOT/candidate).is_file(),candidate
Dependencies().feed((ROOT/'index.html').read_text())
report={'frame_count':len(frames),'resolution':[1080,1080],'alpha':True,'unique_frames':len(set(hashes)),'sequence_bytes':sum(p.stat().st_size for p in frames),'photo_count':len(list((ROOT/'assets/photos').glob('*.webp'))),'all_local_dependencies_present':True}
(ROOT/'design/asset-validation.json').write_text(json.dumps(report,indent=2))
print(json.dumps(report,indent=2))
