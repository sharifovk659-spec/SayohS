#!/usr/bin/env python3
"""Set production admin to admin / admin123 using local PHP hash."""
from __future__ import annotations

import json
import shlex
import subprocess
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]

LOGIN = "admin"
PASSWORD = "admin123"

pwd_hash = subprocess.check_output(
    ["php", "-r", f"echo password_hash({json.dumps(PASSWORD)}, PASSWORD_DEFAULT);"],
    text=True,
).strip()
if not pwd_hash.startswith("$2"):
    raise SystemExit(f"bad hash: {pwd_hash!r}")

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(
    SSH["host"],
    port=int(SSH["port"]),
    username=SSH["username"],
    password=SSH["password"],
    timeout=30,
)

sql = f"""
UPDATE admins
SET email='admin', name='admin', password_hash={json.dumps(pwd_hash)}, role='admin', status=1,
    login_attempts=0, locked_until=NULL, updated_at=NOW()
WHERE id=(SELECT id FROM (SELECT MIN(id) AS id FROM admins) t);

INSERT INTO admins (name, email, password_hash, role, status, created_at, updated_at)
SELECT 'admin', 'admin', {json.dumps(pwd_hash)}, 'admin', 1, NOW(), NOW()
FROM DUAL
WHERE (SELECT COUNT(*) FROM admins WHERE LOWER(email)='admin' OR LOWER(name)='admin') = 0;

UPDATE admins
SET email='admin', name='admin', password_hash={json.dumps(pwd_hash)}, role='admin', status=1,
    login_attempts=0, locked_until=NULL, updated_at=NOW()
WHERE LOWER(email)='admin' OR LOWER(name)='admin';

SELECT id, email, name, role, status FROM admins WHERE LOWER(email)='admin' OR LOWER(name)='admin';
"""

cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql)}"
)
_i, o, e = client.exec_command(cmd, timeout=40)
out = o.read().decode("utf-8", "replace")
err = e.read().decode("utf-8", "replace")
client.close()

print("ADMIN_CREDENTIALS_SET")
print("URL=https://aroma.inovaauto.com/admin/login.php")
print(f"LOGIN={LOGIN}")
print(f"PASSWORD={PASSWORD}")
print(out)
if err.strip():
    print("ERR:", err[:400])
