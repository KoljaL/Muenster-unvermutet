"""Erzeugt die PWA-Symbole aus der Nachzeichnung des Logos (MS_Rathhaus_Logo.jpg).
Vorläufige Ableitung bis zum finalen Logo der Designabteilung.
Aufruf: python3 tools/make-icons.py  (benötigt Pillow)"""
import math, os
from PIL import Image, ImageDraw

BG = (42, 79, 97)
FG = (255, 255, 255)
SS = 4
# Geometrie in Logo-Koordinaten (236 x 233); Motiv-Box 38..218 x 35..217
CX, CY, BOX = 128, 126, 182


def draw(size, fill, detailed=True):
    n = size * SS
    img = Image.new("RGB", (n, n), BG)
    d = ImageDraw.Draw(img)
    k = n * fill / BOX
    ox, oy = n / 2 - CX * k, n / 2 - CY * k
    P = lambda x, y: (ox + x * k, oy + y * k)

    lx, ly, r, t = 115, 112.5, 77, 14
    if not detailed:
        t = 20
    a, b = P(lx - r, ly - r), P(lx + r, ly + r)
    d.ellipse([*a, *b], outline=FG, width=round(t * k))

    # Griff
    hw = 17 if detailed else 21
    x0, y0, x1, y1 = 167, 168, 209, 210
    d.line([P(x0, y0), P(x1, y1)], fill=FG, width=round(hw * k))
    for x, y in ((x0, y0), (x1, y1)):
        cx, cy = P(x, y)
        rr = hw * k / 2
        d.ellipse([cx - rr, cy - rr, cx + rr, cy + rr], fill=FG)

    # Gebäudelinien
    if detailed:
        bars = [(81, 108), (94, 88), (106, 68), (118, 68), (130, 88), (143, 108)]
        bw = 6.4
    else:
        bars = [(87, 98), (103, 68), (119, 68), (135, 98)]
        bw = 10
    for x, top in bars:
        d.rectangle([*P(x, top), *P(x + bw, 161)], fill=FG)
    return img.resize((size, size), Image.LANCZOS)


out = os.path.join(os.path.dirname(__file__), "..", "assets", "icons")
os.makedirs(out, exist_ok=True)
draw(192, 0.70).save(os.path.join(out, "icon-192.png"), optimize=True)
draw(512, 0.70).save(os.path.join(out, "icon-512.png"), optimize=True)
draw(512, 0.50).save(os.path.join(out, "icon-512-maskable.png"), optimize=True)
draw(180, 0.72).save(os.path.join(out, "apple-touch-icon.png"), optimize=True)
draw(32, 0.90, detailed=False).save(os.path.join(out, "favicon-32.png"), optimize=True)
