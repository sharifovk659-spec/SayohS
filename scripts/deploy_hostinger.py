#!/usr/bin/env python3
"""Deploy Aroma to Hostinger without overwriting production secrets."""

from __future__ import annotations

import json
import os
import posixpath
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

SKIP_DIRS = {
    ".git",
    "storage/backups",
    "storage/logs",
    "node_modules",
    ".cursor",
}
SKIP_FILES = {
    "deploy.local.json",
    "config/database.php",
    "aroma-restaurant-deploy.zip",
}
SKIP_SUFFIXES = {".bak", ".sql.gz", ".tar.gz"}


def should_skip(rel: str) -> bool:
    rel = rel.replace("\\", "/")
    if rel in SKIP_FILES:
        return True
    if any(rel == d or rel.startswith(d + "/") for d in SKIP_DIRS):
        return True
    if any(rel.endswith(suf) for suf in SKIP_SUFFIXES):
        return True
    if rel.startswith("storage/backups/"):
        return True
    return False


def run(client: paramiko.SSHClient, cmd: str, timeout: int = 300) -> tuple[int, str, str]:
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
        timeout=40,
    )
    sftp = client.open_sftp()

    print("=== Pre-deploy backup ===")
    remote_db = f"/tmp/aroma_predeploy_db_{TS}.sql.gz"
    cmd = (
        f"MYSQL_PWD={shlex.quote(MYSQL['password'])} "
        f"mysqldump -h {shlex.quote(MYSQL['host'])} -u {shlex.quote(MYSQL['username'])} "
        f"--default-character-set=utf8mb4 --single-transaction "
        f"{shlex.quote(MYSQL['database'])} | gzip -c > {remote_db} && "
        f"mkdir -p {shlex.quote(DOCROOT + '/storage/backups')} && "
        f"cp {remote_db} {shlex.quote(DOCROOT + '/storage/backups/')} && "
        f"cp {shlex.quote(DOCROOT + '/config/database.php')} "
        f"{shlex.quote(DOCROOT + '/storage/backups/database.php.' + TS + '.bak')} && "
        f"ls -lh {shlex.quote(DOCROOT + '/storage/backups/')} | tail -5"
    )
    code, out, err = run(client, cmd)
    print(out)
    if code != 0:
        print("BACKUP FAILED", err)
        raise SystemExit(1)

    # Upload files
    uploaded = 0
    for path in ROOT.rglob("*"):
        if not path.is_file():
            continue
        rel = path.relative_to(ROOT).as_posix()
        if should_skip(rel):
            continue
        remote = posixpath.join(DOCROOT, rel)
        remote_dir = posixpath.dirname(remote)
        # ensure dirs
        parts = remote_dir.strip("/").split("/")
        cur = ""
        for part in parts:
            cur += "/" + part
            try:
                sftp.stat(cur)
            except OSError:
                try:
                    sftp.mkdir(cur)
                except OSError:
                    pass
        sftp.put(str(path), remote)
        uploaded += 1
        if uploaded % 50 == 0:
            print(f"uploaded {uploaded}...")

    print(f"=== Uploaded {uploaded} files ===")

    # Apply migrations via mysql
    print("=== Apply migrations ===")
    mig1 = DOCROOT + "/database/migrations/20260712_platform.sql"
    mig2 = DOCROOT + "/database/migrations/20260712_i18n_content.sql"
    for mig in (mig1, mig2):
        cmd = (
            f"MYSQL_PWD={shlex.quote(MYSQL['password'])} "
            f"mysql -h {shlex.quote(MYSQL['host'])} -u {shlex.quote(MYSQL['username'])} "
            f"--default-character-set=utf8mb4 {shlex.quote(MYSQL['database'])} < {shlex.quote(mig)} "
            f"&& echo OK_{posixpath.basename(mig)}"
        )
        code, out, err = run(client, cmd)
        print(out or err or code)

    # Fix mojibake using remote PHP with production DB config
    print("=== Fix UTF-8 mojibake ===")
    # Write a one-off runner that uses production config/database.php
    fixer = DOCROOT + "/database/migrations/fix_utf8_mojibake.php"
    code, out, err = run(client, f"cd {shlex.quote(DOCROOT)} && php {shlex.quote(fixer)}")
    print(out)
    if err:
        print(err[:500])

    # Ensure base_url setting for production
    sql = (
        "UPDATE settings SET setting_value='https://aroma.inovaauto.com' "
        "WHERE setting_key='base_url'; "
        "SHOW TABLES LIKE 'users'; "
        "SELECT COUNT(*) dishes FROM dishes; "
        "SELECT id, name FROM dishes LIMIT 2; "
        "SELECT language_code, name FROM dish_translations WHERE dish_id=1;"
    )
    cmd = (
        f"MYSQL_PWD={shlex.quote(MYSQL['password'])} "
        f"mysql -h {shlex.quote(MYSQL['host'])} -u {shlex.quote(MYSQL['username'])} "
        f"--default-character-set=utf8mb4 {shlex.quote(MYSQL['database'])} "
        f"-e {shlex.quote(sql)}"
    )
    code, out, err = run(client, cmd)
    print("=== DB verify ===")
    print(out)

    # Clear opcache if available / touch
    run(client, f"rm -f {shlex.quote(DOCROOT + '/storage/cache/*')} 2>/dev/null; echo cache_cleared")

    # Protect lang directory
    run(
        client,
        f"printf '%s\\n' 'Require all denied' > {shlex.quote(DOCROOT + '/lang/.htaccess')}",
    )

    sftp.close()
    client.close()
    print("DEPLOY DONE", TS)


if __name__ == "__main__":
    main()
