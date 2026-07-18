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

def run(cmd):
    _i, o, e = client.exec_command(cmd, timeout=120)
    return o.read().decode("utf-8", errors="replace"), e.read().decode("utf-8", errors="replace")

sql = "SELECT id, HEX(LEFT(name,8)), name FROM dishes LIMIT 3; SELECT HEX(LEFT(setting_value,24)), LEFT(setting_value,40) FROM settings WHERE setting_key='hero_text';"
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql)}"
)
out, err = run(cmd)
print("DB HEX:")
print(out)

# Upload late file changes
sftp = client.open_sftp()
for rel in ["components/popular-dishes.php"]:
    local = ROOT / rel
    remote = f"{DOCROOT}/{rel}"
    sftp.put(str(local), remote)
    print("reuploaded", rel)
sftp.close()

out, err = run("curl -s https://aroma.inovaauto.com | head -c 800 | xxd | head -25")
print("HTML sample:")
print(out)

urls = [
    "https://aroma.inovaauto.com/",
    "https://aroma.inovaauto.com/menu.php",
    "https://aroma.inovaauto.com/cart.php",
    "https://aroma.inovaauto.com/checkout.php",
    "https://aroma.inovaauto.com/login.php",
    "https://aroma.inovaauto.com/register.php",
    "https://aroma.inovaauto.com/account/",
    "https://aroma.inovaauto.com/admin/login.php",
    "https://aroma.inovaauto.com/?lang=en",
    "https://aroma.inovaauto.com/?lang=de",
]
for url in urls:
    try:
        req = urllib.request.Request(url, method="GET", headers={"User-Agent": "AromaDeployCheck/1.0"})
        with urllib.request.urlopen(req, timeout=25) as resp:
            body = resp.read(2000)
            code = resp.status
            has_mojibake = ("Рџ" in body.decode("utf-8", "replace")) or ("Рђ" in body.decode("utf-8", "replace") and "Автор" not in body.decode("utf-8", "replace"))
            cyr = any(0x0400 <= ord(ch) <= 0x04FF for ch in body.decode("utf-8", "replace"))
            print(f"{code} {url} cyr={cyr} mojibake_mark={has_mojibake} bytes={len(body)}")
    except Exception as ex:
        print(f"ERR {url} {ex}")

client.close()
print("DONE")
