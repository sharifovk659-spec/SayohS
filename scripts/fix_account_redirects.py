from pathlib import Path

root = Path(r"c:\xampp\htdocs\Restarant")
for rel in ["login.php", "register.php", "forgot-password.php"]:
    p = root / rel
    text = p.read_text(encoding="utf-8")
    new = text.replace("account.php", "account/")
    if new != text:
        p.write_text(new, encoding="utf-8", newline="\n")
        print("fixed", rel)
    else:
        print("no change", rel)
