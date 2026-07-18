#!/usr/bin/env python3
import json, shlex
from pathlib import Path
import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH, MYSQL = CFG["ssh"], CFG["mysql"]
DOCROOT = SSH["likely_docroot"]

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(SSH["host"], port=int(SSH["port"]), username=SSH["username"], password=SSH["password"], timeout=30)
sftp = client.open_sftp()
sftp.put(str(ROOT / "database/migrations/fix_utf8_mojibake.php"), f"{DOCROOT}/database/migrations/fix_utf8_mojibake.php")
sftp.close()

def run(cmd):
    _i, o, e = client.exec_command(cmd, timeout=180)
    return o.read().decode("utf-8", errors="replace"), e.read().decode("utf-8", errors="replace")

out, err = run(f"cd {shlex.quote(DOCROOT)} && php database/migrations/fix_utf8_mojibake.php")
print(out)
print(err[:400] if err else "")

# Run twice to catch nested issues
out, err = run(f"cd {shlex.quote(DOCROOT)} && php database/migrations/fix_utf8_mojibake.php")
print("second pass:", out)

sql = (
    "SELECT id, HEX(LEFT(name,10)), name FROM categories; "
    "SELECT id, HEX(LEFT(subtitle,16)), subtitle FROM pages; "
    "SELECT id, HEX(LEFT(title,16)), title FROM gallery LIMIT 4;"
)
cmd = (
    f"MYSQL_PWD={shlex.quote(MYSQL['password'])} mysql -h {shlex.quote(MYSQL['host'])} "
    f"-u {shlex.quote(MYSQL['username'])} --default-character-set=utf8mb4 "
    f"{shlex.quote(MYSQL['database'])} -e {shlex.quote(sql)}"
)
out, err = run(cmd)
print(out)
client.close()
