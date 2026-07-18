#!/usr/bin/env python3
import json, shlex, urllib.request
from pathlib import Path
import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]
DOCROOT = SSH["likely_docroot"]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SSH["host"], port=int(SSH["port"]), username=SSH["username"], password=SSH["password"], timeout=30)

sql = (
    "UPDATE settings SET setting_value='hero-salmon.webp' WHERE setting_key='hero_image'; "
    "SELECT setting_value FROM settings WHERE setting_key='hero_image';"
)
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql)}"
)
_i, o, e = client.exec_command(cmd, timeout=40)
print(o.read().decode("utf-8", "replace"))

# verify remote files exist
checks = [
    f"{DOCROOT}/assets/images/hero/hero-salmon.webp",
    f"{DOCROOT}/assets/images/decor/basil-1.webp",
    f"{DOCROOT}/assets/css/home.css",
    f"{DOCROOT}/assets/images/categories/cat-pizza.webp",
]
for path in checks:
    _i, o, e = client.exec_command(f"test -f {shlex.quote(path)} && ls -lh {shlex.quote(path)}")
    print(o.read().decode("utf-8", "replace").strip() or ("MISSING " + path))

client.close()

html = urllib.request.urlopen("https://aroma.inovaauto.com/", timeout=30).read().decode("utf-8", "replace")
print("hero_salmon", "hero-salmon" in html)
print("basil", "basil-1" in html or "decor/basil" in html)
print("home.css", "home.css" in html)
print("DONE")
