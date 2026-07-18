# Чайхана Сайёҳ (SayohS)

Сайти чайхона **Сайёҳ** — PHP + MySQL: меню, бронь, галерея, админ-панель.

## Репозиторий

https://github.com/sharifovk659-spec/SayohS

## Технологии

- PHP 8.2+
- MySQL 8 / MariaDB (utf8mb4)
- HTML/CSS/JS (без тяжёлых фреймворков)

## Быстрый старт (локально)

1. Скопируйте `config/database.example.php` → `config/database.php`
2. Импортируйте `database/schema.sql` и `database/seed.sql` (**UTF-8**)
3. Создайте админа:  
   `php database/create-admin.php admin@example.com "Password123!" "Админ"`
4. Запуск: `php -S 127.0.0.1:8080 -t .`

Подробнее: [INSTALL.md](INSTALL.md) · [GITHUB.md](GITHUB.md)

## Важно про кодировку

Импортируйте SQL только так (иначе кириллица станет `?????`):

```bash
mysql -u root --default-character-set=utf8mb4 aroma_restaurant < database/seed.sql
```

Не используйте PowerShell `Get-Content | mysql` для seed.

## Если «Загрузка» вместо сайта

См. [IMPORT.md](IMPORT.md) — не открывайте `index.php` напрямую.
Локально: запустите `start-local.bat` → http://127.0.0.1:8080/

## Деплой

- **GitHub** — готово для push/import
- **Vercel** — нужен `vercel.json` (уже в репо); без MySQL сайт на fallback
- **Hostinger** — PHP+MySQL ([DEPLOY_HOSTINGER.md](DEPLOY_HOSTINGER.md))

## Админка

`/admin/login.php`
