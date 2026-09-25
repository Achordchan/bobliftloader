"""Downsample a Radiance .hdr (RLE RGBE) by exact area averaging, so the total light stays the same.
Usage: python3 downsample_hdr.py in.hdr out.hdr WIDTH HEIGHT   (writes flat RGBE; prints the gzip size)
Tried on assets/env/sky.hdr (768×384 → 512×256: 575 → 318 KB gzipped; three.js prefilters both into a 128px cube).
Not shipped: renders differed by ~3/255 on the machine, and the page keeps the original lighting exactly.
"""
import math, sys, gzip
def read(path):
    b = open(path, 'rb').read(); i = b.index(b'\n\n') + 2; j = b.index(b'\n', i)
    dims = b[i:j].split(); h, w = int(dims[1]), int(dims[3]); p = j + 1
    px = [[None]*w for _ in range(h)]
    for y in range(h):
        assert b[p] == 2 and b[p+1] == 2; p += 4
        ch = [bytearray() for _ in range(4)]
        for c in range(4):
            row = ch[c]
            while len(row) < w:
                n = b[p]; p += 1
                if n > 128: row.extend(bytes([b[p]]) * (n - 128)); p += 1
                else: row.extend(b[p:p+n]); p += n
        for x in range(w):
            e = ch[3][x]
            f = 0.0 if e == 0 else math.ldexp(1.0, e - 136)
            px[y][x] = (ch[0][x] * f, ch[1][x] * f, ch[2][x] * f)
    return w, h, px, b[:i]
def encode(r, g, bl):
    v = max(r, g, bl)
    if v < 1e-32: return bytes(4)
    m, e = math.frexp(v); s = m * 256.0 / v
    return bytes([min(255, int(r * s)), min(255, int(g * s)), min(255, int(bl * s)), e + 128])
w, h, px, head = read(sys.argv[1]); W, H = int(sys.argv[3]), int(sys.argv[4])
sx, sy = w / W, h / H
out = bytearray(head.replace(f'-Y {h} +X {w}'.encode(), b'')); out += f'-Y {H} +X {W}\n'.encode()
tot_in = sum(sum(c) for row in px for c in row) / (w * h)
acc = 0
for Y in range(H):
    y0, y1 = Y * sy, (Y + 1) * sy
    for X in range(W):
        x0, x1 = X * sx, (X + 1) * sx; s = [0, 0, 0]; area = 0
        for y in range(int(y0), math.ceil(y1)):
            wy = min(y1, y + 1) - max(y0, y)
            for x in range(int(x0), math.ceil(x1)):
                wx = min(x1, x + 1) - max(x0, x); a = wx * wy; p = px[y][x]
                s[0] += p[0] * a; s[1] += p[1] * a; s[2] += p[2] * a; area += a
        c = [v / area for v in s]; acc += sum(c); out += encode(*c)
print('mean in', tot_in, 'mean out', acc / (W * H))
open(sys.argv[2], 'wb').write(out)
print('raw', len(out), 'gz', len(gzip.compress(bytes(out), 9)))
