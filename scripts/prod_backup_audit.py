#!/usr/bin/env python3
"""Production SSH audit + backup. Does not print secrets."""

from __future__ import annotations

import json
import os
import shlex
import time
from pathlib import Path

import paramiko

ROOT = Path(__file__).resolve().parents[1]
CFG = json.loads((ROOT / "deploy.local.json").read_text(encoding="utf-8"))
SSH = CFG["ssh"]
MYSQL = CFG["mysql"]
DOCROOT = SSH["likely_docroot"]
TS = time.strftime("%Y%m%d_%H%M%S")
LOCAL_BACKUP = ROOT / "storage" / "backups" / f"prod_{TS}"
LOCAL_BACKUP.mkdir(parents=True, exist_ok=True)


def run(client: paramiko.SSHClient, cmd: str, timeout: int = 180) -> tuple[int, str, str]:
    _stdin, stdout, stderr = client.exec_command(cmd, timeout=timeout)
    out = stdout.read().decode("utf-8", errors="replace")
    err = stderr.read().decode("utf-8", errors="replace")
    code = stdout.channel.recv_exit_status()
    return code, out, err


def main() -> None:
    client = paramiko.SSHClient()
    client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
    client.connect(
        SSH["host"],
        port=int(SSH["port"]),
        username=SSH["username"],
        password=SSH["password"],
        timeout=30,
    )

    print("=== SSH OK ===")
    code, out, err = run(client, f"php -v | head -1; ls -la {shlex.quote(DOCROOT)} | head -30")
    print(out)
    if err:
        print("ERR:", err[:500])

    audit_sql = (
        "SHOW TABLES; "
        "SELECT @@character_set_database AS cs, @@collation_database AS coll; "
        "SELECT COUNT(*) AS dishes FROM dishes; "
        "SELECT COUNT(*) AS reservations FROM reservations; "
        "SELECT COUNT(*) AS admins FROM admins; "
        "SELECT id, HEX(LEFT(name,16)) AS name_hex, name FROM dishes LIMIT 3; "
        "SELECT setting_key, HEX(LEFT(setting_value,24)) AS val_hex, LEFT(setting_value,48) AS val "
        "FROM settings WHERE setting_key IN ('hero_text','base_url','restaurant_name');"
    )
    cmd = (
        f"MYSQL_PWD={shlex.quote(MYSQL['password'])} "
        f"mysql -h {shlex.quote(MYSQL['host'])} -u {shlex.quote(MYSQL['username'])} "
        f"{shlex.quote(MYSQL['database'])} --default-character-set=utf8mb4 "
        f"-e {shlex.quote(audit_sql)}"
    )
    code, out, err = run(client, cmd)
    print("=== DB AUDIT ===")
    print(out)
    if err:
        print("ERR:", err[:800])
    print("exit", code)

    remote_db = f"/tmp/aroma_db_{TS}.sql.gz"
    cmd = (
        f"MYSQL_PWD={shlex.quote(MYSQL['password'])} "
        f"mysqldump -h {shlex.quote(MYSQL['host'])} -u {shlex.quote(MYSQL['username'])} "
        f"--default-character-set=utf8mb4 --single-transaction --routines --triggers "
        f"{shlex.quote(MYSQL['database'])} | gzip -c > {remote_db} && ls -lh {remote_db}"
    )
    code, out, err = run(client, cmd, timeout=240)
    print("=== DB BACKUP ===")
    print(out)
    if err:
        print("ERR:", err[:400])
    print("exit", code)

    remote_files = f"/tmp/aroma_files_{TS}.tar.gz"
    cmd = (
        f"tar -czf {remote_files} -C {shlex.quote(DOCROOT)} "
        f"--exclude=storage/backups --exclude='storage/logs/*.log' . "
        f"&& ls -lh {remote_files}"
    )
    code, out, err = run(client, cmd, timeout=360)
    print("=== FILES BACKUP ===")
    print(out)
    if err:
        print("ERR:", err[:400])
    print("exit", code)

    code, out, err = run(
        client,
        f"test -f {shlex.quote(DOCROOT + '/config/database.php')} && "
        f"wc -c {shlex.quote(DOCROOT + '/config/database.php')}",
    )
    print("=== CONFIG ===")
    print(out.strip())

    code, out, err = run(client, "php -r \"echo 'default_charset='.ini_get('default_charset').PHP_EOL;\"")
    print(out)

    code, out, err = run(client, "curl -sI https://aroma.inovaauto.com | head -25")
    print("=== HEADERS ===")
    print(out)

    # Sample page bytes for encoding diagnosis
    code, out, err = run(
        client,
        "curl -s https://aroma.inovaauto.com | head -c 1200 | xxd | head -40",
    )
    print("=== HTML HEX SAMPLE ===")
    print(out)

    sftp = client.open_sftp()
    for remote, name in (
        (remote_db, f"aroma_db_{TS}.sql.gz"),
        (remote_files, f"aroma_files_{TS}.tar.gz"),
    ):
        try:
            sftp.stat(remote)
            local_path = LOCAL_BACKUP / name
            print("Downloading", name)
            sftp.get(remote, str(local_path))
            print("OK bytes", local_path.stat().st_size)
        except Exception as exc:  # noqa: BLE001
            print("Download fail", name, exc)

    try:
        sftp.get(
            f"{DOCROOT}/config/database.php",
            str(LOCAL_BACKUP / "database.php.production.bak"),
        )
        print("database.php backed up")
    except Exception as exc:  # noqa: BLE001
        print("config bak fail", exc)

    # Keep remote backups too
    run(
        client,
        f"mkdir -p {shlex.quote(DOCROOT + '/storage/backups')} && "
        f"cp {remote_db} {shlex.quote(DOCROOT + '/storage/backups/')} && "
        f"cp {remote_files} {shlex.quote(DOCROOT + '/storage/backups/')} && "
        f"cp {shlex.quote(DOCROOT + '/config/database.php')} "
        f"{shlex.quote(DOCROOT + '/storage/backups/database.php.' + TS + '.bak')}",
    )

    sftp.close()
    client.close()
    print("DONE", LOCAL_BACKUP)


if __name__ == "__main__":
    main()
