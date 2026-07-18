#!/usr/bin/env python3
"""Test admin login on production without printing hashes/secrets."""
import http.cookiejar
import re
import urllib.parse
import urllib.request

URL = "https://aroma.inovaauto.com/admin/login.php"
EMAIL = "sharifovk659@gmail.com"
PASSWORD = "Aroma!awkGQJZDpv"

cj = http.cookiejar.CookieJar()
opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

html = opener.open(URL, timeout=30).read().decode("utf-8", "replace")
m = re.search(r'name="csrf_token"\s+value="([^"]+)"', html)
if not m:
    m = re.search(r'name="csrf_token"[^>]*value="([^"]+)"', html)
csrf = m.group(1) if m else ""
print("csrf", bool(csrf))

data = urllib.parse.urlencode(
    {"email": EMAIL, "password": PASSWORD, "csrf_token": csrf}
).encode()
req = urllib.request.Request(URL, data=data, method="POST")
try:
    resp = opener.open(req, timeout=30)
    final = resp.geturl()
    body = resp.read().decode("utf-8", "replace")
    code = resp.status
except urllib.error.HTTPError as e:
    final = e.geturl() if hasattr(e, "geturl") else URL
    body = e.read().decode("utf-8", "replace")
    code = e.code

ok = ("admin/index" in final) or ("Обзор" in body) or ("Dashboard" in body) or ("admin-card" in body)
print("status", code)
print("final_url", final)
print("login_ok", ok)
print("has_error_form", "Неверный" in body or "error" in body.lower()[:500])
