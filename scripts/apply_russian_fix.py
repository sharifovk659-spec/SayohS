#!/usr/bin/env python3
import json, shlex
from pathlib import Path
import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]
DOCROOT = SSH["likely_docroot"]
SQL = ROOT / "database/migrations/20260712_fix_russian_text.sql"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SSH["host"], port=int(SSH["port"]), username=SSH["username"], password=SSH["password"], timeout=30)
sftp = client.open_sftp()
remote = f"{DOCROOT}/database/migrations/20260712_fix_russian_text.sql"
sftp.put(str(SQL), remote)
sftp.close()

cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} < {shlex.quote(remote)} && echo APPLY_OK"
)
_i, o, e = client.exec_command(cmd, timeout=60)
print(o.read().decode("utf-8", "replace"))
print(e.read().decode("utf-8", "replace")[:300])

verify = "SELECT HEX(LEFT(name,8)), name FROM categories WHERE slug IN ('burgers','salads','desserts'); SELECT HEX(LEFT(subtitle,12)), subtitle FROM pages WHERE page_key='home_hero';"
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 -N "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(verify)}"
)
_i, o, e = client.exec_command(cmd, timeout=60)
raw = o.read()
print("HEX:", raw[:200])
# burgers should start D091 (Б)
for line in raw.decode("utf-8", "replace").splitlines():
    hx = line.split("\t")[0]
    print("starts", hx[:8], "ok_burgers" if hx.startswith("D091") else ("ok_welcome" if hx.startswith("D094") else "check"), "full", hx[:24])

client.close()
print("DONE")
