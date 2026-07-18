#!/usr/bin/env python3
"""Reset admin password on production. Prints email + new password only (no MySQL/SSH secrets)."""
import json
import secrets
import shlex
import string
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]
ADMIN_EMAIL = (CFG.get("admin") or {}).get("email") or "sharifovk659@gmail.com"
DOCROOT = SSH["likely_docroot"]

alphabet = string.ascii_letters + string.digits
password = "Aroma!" + "".join(secrets.choice(alphabet) for _ in range(10))

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(
    SSH["host"],
    port=int(SSH["port"]),
    username=SSH["username"],
    password=SSH["password"],
    timeout=30,
)

# Generate hash with remote PHP (same runtime as site)
php = (
    "<?php echo password_hash("
    + json.dumps(password)
    + ", PASSWORD_DEFAULT);"
)
cmd = f"php -r {shlex.quote(php)}"
_i, o, e = client.exec_command(cmd, timeout=30)
pwd_hash = o.read().decode("utf-8", "replace").strip()
err = e.read().decode("utf-8", "replace").strip()
if not pwd_hash.startswith("$"):
    raise SystemExit(f"hash failed: {pwd_hash!r} {err!r}")

sql = (
    "UPDATE admins SET password_hash="
    + json.dumps(pwd_hash)
    + ", login_attempts=0, locked_until=NULL, status=1 "
    "WHERE email="
    + json.dumps(ADMIN_EMAIL)
    + "; "
    "SELECT id, email, name, status FROM admins WHERE email="
    + json.dumps(ADMIN_EMAIL)
    + " LIMIT 1;"
)
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql)}"
)
_i, o, e = client.exec_command(cmd, timeout=30)
out = o.read().decode("utf-8", "replace")
err = e.read().decode("utf-8", "replace")
client.close()

print("ADMIN_RESET_OK")
print("URL=https://aroma.inovaauto.com/admin/login.php")
print(f"EMAIL={ADMIN_EMAIL}")
print(f"PASSWORD={password}")
print("DB_ROW:")
print(out)
if err:
    print("ERR:", err[:300])
