"""Generate the small, local writing animation used beside tracking numbers."""
from pathlib import Path
from PIL import Image, ImageDraw

output = Path(__file__).resolve().parents[1] / 'public/images/writing-code.gif'
output.parent.mkdir(parents=True, exist_ok=True)
frames = []
for frame in range(48):
    image = Image.new('RGB', (160, 160), '#ecfdf5')
    draw = ImageDraw.Draw(image)
    draw.rounded_rectangle((27, 20, 127, 143), radius=12, fill='white', outline='#a7dfcc', width=2)
    draw.rounded_rectangle((44, 36, 92, 41), radius=2, fill='#a7dfcc')
    progress = min(frame, 35)
    for row in range(3):
        width = max(0, min(64, (progress - row * 12) * 6))
        if width:
            draw.line((44, 65 + row * 23, 44 + width, 65 + row * 23), fill='#059669', width=4)
    row = min(progress // 12, 2)
    x = 44 + min(64, (progress - row * 12) * 6)
    y = 65 + row * 23
    draw.polygon([(x, y), (x + 6, y - 17), (x + 39, y - 50), (x + 49, y - 40), (x + 16, y - 7)], fill='#fbbf24')
    draw.polygon([(x, y), (x + 6, y - 17), (x + 16, y - 7)], fill='#f5d4ab')
    draw.polygon([(x, y), (x + 2, y - 7), (x + 7, y - 2)], fill='#164e3e')
    draw.polygon([(x + 39, y - 50), (x + 44, y - 55), (x + 54, y - 45), (x + 49, y - 40)], fill='#fb7185')
    frames.append(image)
frames[0].save(output, save_all=True, append_images=frames[1:], duration=85, loop=0, optimize=True)
