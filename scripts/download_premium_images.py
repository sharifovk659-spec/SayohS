#!/usr/bin/env python3
"""Download premium food/restaurant photos (Unsplash — free license, Pinterest-like quality)."""
from __future__ import annotations

import subprocess
from pathlib import Path

ROOT = Path(r"c:\xampp\htdocs\Restarant\assets\images")

# Direct Unsplash CDN URLs (stable photo IDs, resized)
IMAGES = {
    "hero/hero-salmon.jpg": "https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=1200&q=80",  # salmon plate
    "hero/about-interior.jpg": "https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=1200&q=80",  # restaurant interior
    "categories/cat-pizza.jpg": "https://images.unsplash.com/photo-1513104890138-7c749659a591?auto=format&fit=crop&w=600&q=80",
    "categories/cat-burgers.jpg": "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?auto=format&fit=crop&w=600&q=80",
    "categories/cat-shawarma.jpg": "https://images.unsplash.com/photo-1529006557810-274b9b2fc783?auto=format&fit=crop&w=600&q=80",
    "categories/cat-grill.jpg": "https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=600&q=80",
    "categories/cat-salads.jpg": "https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=600&q=80",
    "categories/cat-drinks.jpg": "https://images.unsplash.com/photo-1551024709-8f23befc0f44?auto=format&fit=crop&w=600&q=80",
    "categories/cat-desserts.jpg": "https://images.unsplash.com/photo-1578985545062-69928b1d9587?auto=format&fit=crop&w=600&q=80",
    "gallery/gal-interior.jpg": "https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=800&q=80",
    "gallery/gal-pizza.jpg": "https://images.unsplash.com/photo-1574071318508-1cdbab80d2f8?auto=format&fit=crop&w=800&q=80",
    "gallery/gal-pasta.jpg": "https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?auto=format&fit=crop&w=800&q=80",
    "gallery/gal-drinks.jpg": "https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=800&q=80",
    "gallery/gal-dessert.jpg": "https://images.unsplash.com/photo-1464349095431-e9a21285b5f3?auto=format&fit=crop&w=800&q=80",
    "gallery/gal-team.jpg": "https://images.unsplash.com/photo-1577219491135-ce391730fb2c?auto=format&fit=crop&w=800&q=80",
    "decor/leaf-photo-1.png": "https://images.unsplash.com/photo-1615485926015-8c4f2c2c0f4e?auto=format&fit=crop&w=400&q=80",  # may not be transparent - skip if bad
    "dishes/salad-hero.jpg": "https://images.unsplash.com/photo-1540189549336-e6e99c3679fe?auto=format&fit=crop&w=1000&q=80",
    "dishes/burger-classic.jpg": "https://images.unsplash.com/photo-1550547660-d9450f859349?auto=format&fit=crop&w=800&q=80",
    "dishes/pizza-margherita.jpg": "https://images.unsplash.com/photo-1574071318508-1cdbab80d2f8?auto=format&fit=crop&w=800&q=80",
    "dishes/grill-steak.jpg": "https://images.unsplash.com/photo-1600891964092-4316c288032e?auto=format&fit=crop&w=800&q=80",
}

# Better basil leaf PNGs from public domain / open sources (transparent-ish cutouts via pngimg style)
LEAVES = {
    "decor/basil-1.png": "https://pngimg.com/uploads/basil/basil_PNG8.png",
    "decor/basil-2.png": "https://pngimg.com/uploads/basil/basil_PNG5.png",
    "decor/mint-1.png": "https://pngimg.com/uploads/mint/mint_PNG9.png",
}


def download(url: str, dest: Path) -> bool:
    dest.parent.mkdir(parents=True, exist_ok=True)
    cmd = [
        "curl",
        "-L",
        "--fail",
        "--silent",
        "--show-error",
        "-A",
        "Mozilla/5.0 AromaRestaurantBot/1.0",
        "-o",
        str(dest),
        url,
    ]
    try:
        subprocess.check_call(cmd, timeout=60)
        size = dest.stat().st_size
        if size < 2000:
            dest.unlink(missing_ok=True)
            print("TOO_SMALL", dest, size)
            return False
        print("OK", dest.name, size)
        return True
    except Exception as exc:  # noqa: BLE001
        print("FAIL", dest, exc)
        dest.unlink(missing_ok=True)
        return False


def maybe_webp(src: Path) -> None:
    if src.suffix.lower() not in {".jpg", ".jpeg", ".png"}:
        return
    out = src.with_suffix(".webp")
    php = f"""<?php
    if (!function_exists('imagecreatefromjpeg')) {{ echo 'no-gd'; exit(1); }}
    $src = {src.as_posix()!r};
    $out = {out.as_posix()!r};
    $ext = strtolower(pathinfo($src, PATHINFO_EXTENSION));
    if ($ext === 'png') {{ $im = @imagecreatefrompng($src); }}
    else {{ $im = @imagecreatefromjpeg($src); }}
    if (!$im) {{ echo 'read-fail'; exit(1); }}
    imagepalettetotruecolor($im);
    imagealphablending($im, true);
    imagesavealpha($im, true);
    if (!imagewebp($im, $out, 82)) {{ echo 'webp-fail'; exit(1); }}
    echo 'webp-ok';
    """
    try:
        r = subprocess.run(["php", "-r", php], capture_output=True, text=True, timeout=30)
        print(src.name, "->", r.stdout.strip() or r.stderr.strip())
    except Exception as exc:  # noqa: BLE001
        print("webp skip", src.name, exc)


def main() -> None:
    # skip broken leaf photo entry
    items = {k: v for k, v in IMAGES.items() if "leaf-photo" not in k}
    items.update(LEAVES)
    for rel, url in items.items():
        dest = ROOT / rel
        if dest.exists() and dest.stat().st_size > 5000:
            print("EXISTS", dest.name)
            maybe_webp(dest)
            continue
        if download(url, dest):
            maybe_webp(dest)


if __name__ == "__main__":
    main()
