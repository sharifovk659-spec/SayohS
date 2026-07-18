#!/usr/bin/env python3
import json, shlex, subprocess
from pathlib import Path
import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]
ADMIN_EMAIL = (CFG.get("admin") or {}).get("email") or "sharifovk659@gmail.com"
DOCROOT = SSH["likely_docroot"]
PASSWORD = "Aroma!awkGQJZDpv"

pwd_hash = subprocess.check_output(
    ["php", "-r", f"echo password_hash({json.dumps(PASSWORD)}, PASSWORD_DEFAULT);"],
    text=True,
).strip()

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SSH["host"], port=int(SSH["port"]), username=SSH["username"], password=SSH["password"], timeout=30)

sql = (
    f"UPDATE admins SET password_hash={json.dumps(pwd_hash)}, "
    f"login_attempts=0, locked_until=NULL, status=1 WHERE email={json.dumps(ADMIN_EMAIL)}; "
    f"SELECT id, email, name, status, LEFT(password_hash,10) AS h FROM admins WHERE email={json.dumps(ADMIN_EMAIL)};"
)
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql)}"
)
_i, o, e = client.exec_command(cmd, timeout=40)
print(o.read().decode("utf-8", "replace"))
print(e.read().decode("utf-8", "replace")[:300])

# verify password_verify remotely
php = f"""<?php
require '{DOCROOT}/config/database.php';
// can't require return easily
"""
# simpler verify with mysql hash fetch + local verify
sql2 = f"SELECT password_hash FROM admins WHERE email={json.dumps(ADMIN_EMAIL)} LIMIT 1;"
cmd2 = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} -N --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql2)}"
)
_i, o, e = client.exec_command(cmd2, timeout=30)
remote_hash = o.read().decode("utf-8", "replace").strip()
client.close()

ok = subprocess.check_output(
    ["php", "-r", f"var_export(password_verify({json.dumps(PASSWORD)}, {json.dumps(remote_hash)}));"],
    text=True,
).strip()
print("VERIFY", ok)
print("EMAIL", ADMIN_EMAIL)
print("PASSWORD", PASSWORD)
print("URL https://aroma.inovaauto.com/admin/login.php")
