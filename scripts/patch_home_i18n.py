from pathlib import Path
import re

KEYS = {
    "ru": {
        "nav_call": "Позвонить",
        "hero_fresh": "Свежее каждый день",
        "hero_guests": "5000+ довольных гостей",
        "hero_reviews": "4.9 · 1200+ отзывов",
        "gallery_all": "В галерею",
        "categories_title": "Популярные категории",
    },
    "en": {
        "nav_call": "Call now",
        "hero_fresh": "Fresh daily",
        "hero_guests": "5000+ happy guests",
        "hero_reviews": "4.9 · 1200+ reviews",
        "gallery_all": "View gallery",
        "categories_title": "Popular categories",
    },
    "de": {
        "nav_call": "Jetzt anrufen",
        "hero_fresh": "Täglich frisch",
        "hero_guests": "5000+ zufriedene Gäste",
        "hero_reviews": "4.9 · 1200+ Bewertungen",
        "gallery_all": "Zur Galerie",
        "categories_title": "Beliebte Kategorien",
    },
}

for lang, add in KEYS.items():
    p = Path(rf"c:\xampp\htdocs\Restarant\lang\{lang}.php")
    t = p.read_text(encoding="utf-8")
    for k, v in add.items():
        pat = rf"'{k}'\s*=>\s*'[^']*'"
        if re.search(pat, t):
            t = re.sub(pat, f"'{k}' => '{v}'", t)
        else:
            t = t.replace("return [", "return [\n    '" + k + "' => '" + v + "',", 1)
    p.write_text(t, encoding="utf-8", newline="\n")
    print(lang, "ok")
