"""Extract the BOB-LIFT wordmark outlines from the Illustrator (PDF-compatible) source into assets/logo/boblift.json.

The AI file draws each letter in two parts split by a curved band: the upper part as a clip path filled with an
axial gradient, the lower part as a solid fill. Every PDF path is written as an outer contour plus hole contours,
with cubic Béziers kept so three.js can tessellate them at any resolution. Units: logo width = 1, v points up.
"""
import json, re, sys, zlib
from pathlib import Path

SRC = Path(sys.argv[1] if len(sys.argv) > 1 else '/Users/a1234/Desktop/工作/滑移装载机(1)/logo(1).ai')
OUT = Path(__file__).resolve().parents[1] / 'assets/logo/boblift.json'

data = SRC.read_bytes()
objs = {int(m.group(1)): m.start() for m in re.finditer(rb'(\d+) 0 obj', data)}
def raw(n): s = objs[n]; return data[s:data.find(b'endobj', s)]
page = next(raw(n) for n in objs if b'/Type/Page' in raw(n) and b'/Kids' not in raw(n))
body = raw(int(re.search(rb'/Contents (\d+) 0 R', page).group(1)))
start = body.find(b'stream') + 6
while body[start:start + 1] in b'\r\n': start += 1
content = zlib.decompress(body[start:body.rfind(b'endstream')]).decode('latin1')

# --- tiny content-stream interpreter: q/Q, translate-only cm, m l c v y h re, W n (clip = upper part), f (lower part)
shapes, path, cur, tx, saved, nums = [], [], None, [0.0, 0.0], [], []
P = lambda x, y: (x + tx[0], y + tx[1])
for t in content.split():
    try: nums.append(float(t)); continue
    except ValueError: pass
    if t == 'q': saved.append(tx[:])
    elif t == 'Q': tx = saved.pop() if saved else [0.0, 0.0]
    elif t == 'cm' and nums[-6:-2] == [1, 0, 0, 1]: tx = [tx[0] + nums[-2], tx[1] + nums[-1]]
    elif t == 'm': cur = P(*nums[-2:]); path.append([cur])
    elif t == 'l': cur = P(*nums[-2:]); path[-1].append(('L', cur))
    elif t == 'c': a, b, cur = P(*nums[-6:-4]), P(*nums[-4:-2]), P(*nums[-2:]); path[-1].append(('C', a, b, cur))
    elif t == 'v': b, end = P(*nums[-4:-2]), P(*nums[-2:]); path[-1].append(('C', cur, b, end)); cur = end
    elif t == 'y': a, end = P(*nums[-4:-2]), P(*nums[-2:]); path[-1].append(('C', a, end, end)); cur = end
    elif t == 're': path = []  # the page-sized clip rectangle
    elif t == 'n':
        if path: shapes.append(('upper', path))
        path = []
    elif t == 'f': shapes.append(('lower', path)); path = []
    nums = []

pts = [p for _, sp in shapes for c in sp for seg in c[1:] for p in seg[1:]] + [c[0] for _, sp in shapes for c in sp]
x0, x1 = min(p[0] for p in pts), max(p[0] for p in pts); y0, y1 = min(p[1] for p in pts), max(p[1] for p in pts)
W = x1 - x0
norm = lambda p: [round((p[0] - x0) / W, 6), round((p[1] - y0) / W, 6)]

def area(contour):  # signed area of the on-curve polygon, enough to rank contours
    ring = [contour[0]] + [seg[-1] for seg in contour[1:]]
    return sum(a[0] * b[1] - b[0] * a[1] for a, b in zip(ring, ring[1:] + ring[:1])) / 2

out = []
for part, subpaths in shapes:
    subpaths = [c for c in subpaths if len(c) > 2 and abs(area(c)) > 1e-3]  # drop Illustrator's degenerate joins
    subpaths.sort(key=lambda c: -abs(area(c)))
    def enc(c): return {'start': norm(c[0]), 'segs': [[s[0]] + [norm(p) for p in s[1:]] for s in c[1:]]}
    outer = subpaths[0]
    ring = [outer[0]] + [s[-1] for s in outer[1:]]
    out.append({'part': part, 'outer': enc(outer), 'holes': [enc(c) for c in subpaths[1:]],
                'bounds': [norm((min(p[0] for p in ring), min(p[1] for p in ring))), norm((max(p[0] for p in ring), max(p[1] for p in ring)))]})

OUT.parent.mkdir(parents=True, exist_ok=True)
OUT.write_text(json.dumps({'source': SRC.name, 'aspect': round((y1 - y0) / W, 6), 'shapes': out}, separators=(',', ':')))
print(f'{len(out)} shapes ({sum(s["part"] == "upper" for s in out)} upper), holes: {[len(s["holes"]) for s in out]}, aspect {(y1 - y0) / W:.4f}, {OUT.stat().st_size} bytes')
