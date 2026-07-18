#!/usr/bin/env python3
import json, shlex
from pathlib import Path
import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]
DOCROOT = SSH["likely_docroot"]
local = ROOT / "database/migrations/20260712_fix_dishes_text.sql"

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SSH["host"], port=int(SSH["port"]), username=SSH["username"], password=SSH["password"], timeout=30)
sftp = client.open_sftp()
remote = f"{DOCROOT}/database/migrations/20260712_fix_dishes_text.sql"
sftp.put(str(local), remote)
sftp.close()
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} < {shlex.quote(remote)} && echo OK"
)
_i, o, e = client.exec_command(cmd, timeout=60)
print(o.read().decode("utf-8", "replace"))
print(e.read().decode("utf-8", "replace")[:300])
client.close()
