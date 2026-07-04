"""Genera los recursos de marca (logo Iron Gym) con transparencia real.

Produce dos imágenes en assets/branding:
- logo_mark.png: mancuerna naranja sobre fondo TRANSPARENTE (splash + icono
  adaptativo Android). Se mantiene dentro de la zona segura del icono adaptativo.
- icon.png: mancuerna naranja sobre fondo oscuro opaco de marca (iOS/web/
  Windows/Android legacy).

Uso:
    python tool/generate_branding.py

Requiere Pillow. Se dibuja con supersampling y se reduce con LANCZOS para
obtener bordes suaves (anti-aliasing) y alfa correcto.
"""

import os

from PIL import Image, ImageDraw

# Paleta de marca (coherente con app_theme.dart).
ORANGE = (245, 165, 36, 255)  # #F5A524
DARK = (14, 15, 19, 255)      # #0E0F13

SIZE = 1024   # tamaño final de salida
SS = 4        # factor de supersampling para bordes suaves

BRANDING_DIR = os.path.join(os.path.dirname(__file__), "..", "assets", "branding")


def draw_dumbbell(draw: ImageDraw.ImageDraw, center: float, mult: float, color) -> None:
    """Dibuja una mancuerna simétrica centrada.

    center: coordenada central (x=y) del lienzo ya supersampleado.
    mult: factor de escala del logo respecto a la geometría base.
    """
    scale = SS * mult

    # Barra central (una sola pieza tipo píldora para evitar muescas al centro).
    bar_hw, bar_hh, bar_r = 112, 34, 34
    draw.rounded_rectangle(
        [
            center - bar_hw * scale,
            center - bar_hh * scale,
            center + bar_hw * scale,
            center + bar_hh * scale,
        ],
        radius=bar_r * scale,
        fill=color,
    )

    # Discos por lado (se solapan ligeramente hacia dentro para no dejar costuras).
    # (x_interno, x_externo, media_altura, radio)
    plates = [
        (100, 176, 104, 28),  # disco grande
        (172, 232, 64, 24),   # disco mediano
        (228, 262, 40, 16),   # tope final
    ]
    for x0, x1, hh, r in plates:
        for sign in (1, -1):
            xa = center + sign * x0 * scale
            xb = center + sign * x1 * scale
            left, right = sorted((xa, xb))
            draw.rounded_rectangle(
                [left, center - hh * scale, right, center + hh * scale],
                radius=r * scale,
                fill=color,
            )


def render(background, mult: float) -> Image.Image:
    """Renderiza el logo a tamaño final con supersampling."""
    big = SIZE * SS
    base_color = background if background is not None else (0, 0, 0, 0)
    img = Image.new("RGBA", (big, big), base_color)
    draw = ImageDraw.Draw(img)
    draw_dumbbell(draw, big / 2, mult, ORANGE)
    return img.resize((SIZE, SIZE), Image.LANCZOS)


def main() -> None:
    os.makedirs(BRANDING_DIR, exist_ok=True)

    # Marca transparente (dentro de la zona segura del icono adaptativo Android).
    logo = render(background=None, mult=1.15)
    logo_path = os.path.join(BRANDING_DIR, "logo_mark.png")
    logo.save(logo_path)

    # Icono completo con fondo oscuro opaco (edge-to-edge; las plataformas aplican
    # su propia máscara).
    icon = render(background=DARK, mult=1.5)
    icon_path = os.path.join(BRANDING_DIR, "icon.png")
    icon.save(icon_path)

    print("Generado:", os.path.normpath(logo_path))
    print("Generado:", os.path.normpath(icon_path))


if __name__ == "__main__":
    main()
