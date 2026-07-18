#!/usr/bin/env python3
import json, shlex
from pathlib import Path
import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SSH["host"], port=int(SSH["port"]), username=SSH["username"], password=SSH["password"], timeout=30)

sql = r"""
SELECT 'cat' t, id, HEX(name) h, name FROM categories
UNION ALL
SELECT 'cattr', id, HEX(name), name FROM category_translations WHERE language_code='ru'
UNION ALL
SELECT 'page', id, HEX(IFNULL(subtitle,'')), IFNULL(subtitle,'') FROM pages
UNION ALL
SELECT 'gal', id, HEX(title), title FROM gallery;
"""
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 -N "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql)}"
)
_i, o, e = client.exec_command(cmd, timeout=60)
raw = o.read()
Path(r"c:\xampp\htdocs\Restarant\storage\backups\encoding_probe.txt").write_bytes(raw)
print("wrote probe bytes", len(raw))

# Analyze locally
text = raw.decode("utf-8", errors="replace")
for line in text.splitlines():
    parts = line.split("\t")
    if len(parts) < 4:
        continue
    kind, _id, hx, name = parts[0], parts[1], parts[2], parts[3]
    try:
        b = bytes.fromhex(hx)
        as_utf = b.decode("utf-8")
    except Exception:
        as_utf = "?"
    # try fix
    try:
        fixed = as_utf.encode("cp1251").decode("utf-8")
    except Exception:
        fixed = None
    mark = "OK" if any("\u0400" <= ch <= "\u04FF" for ch in as_utf) and "Р" not in as_utf[:3] else "CHECK"
    # Better: if hex starts with D0A0 (Р) likely mojibake of Cyrillic that started with D0xx
    is_moj = hx.startswith("D0A0") or hx.startswith("D0A1") or "E280" in hx[:20]
    print(f"{kind}\t{_id}\tmoj={is_moj}\thex={hx[:24]}\tname={as_utf!r}\tfixed={fixed!r}")

client.close()
