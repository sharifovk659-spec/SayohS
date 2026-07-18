# SayohS — GitHub + deploy

Репозиторий: https://github.com/sharifovk659-spec/SayohS

## Важно: пуш ба GitHub

Дар папкаи лоиҳа (`Aroma-Restarant`):

```bash
git push -u origin main
```

Агар remote набошад:

```bash
git remote add origin https://github.com/sharifovk659-spec/SayohS.git
git branch -M main
git push -u origin main
```

Баъд дар GitHub ворид шавед — код бояд дида шавад.

## Vercel

Ин лоиҳа **PHP + MySQL** аст (сайт + админ + upload).  
**Vercel** барои чунин лоиҳа мувофиқ нест (MySQL, session, upload файлов).

Барои Vercel Import хато мешавад ё сайт пурра кор намекунад.

**Дуруст:** GitHub → Hostinger / VPS / shared hosting бо PHP 8.2+ ва MySQL.

## Hostinger (тавсия)

1. Кодро аз GitHub clone/upload кунед
2. `config/database.example.php` → `config/database.php` (пароли DB)
3. Import: `database/schema.sql` + `database/seed.sql` (UTF-8!)
4. Админ: `php database/create-admin.php email@mail.com "Password" "Админ"`

Муфассал: [DEPLOY_HOSTINGER.md](DEPLOY_HOSTINGER.md)

## Локалӣ

```bash
php -S 127.0.0.1:8080 -t .
```

- Сайт: http://127.0.0.1:8080/
- Админ: http://127.0.0.1:8080/admin/login.php
