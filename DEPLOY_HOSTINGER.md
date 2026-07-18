# Деплой на Hostinger — aroma.inovaauto.com

PHP + MySQL размещаются на Hostinger (или аналоге). GitHub — только для кода.  
**Не** деплойте этот backend на Vercel.

Целевой адрес: **https://aroma.inovaauto.com**

## Шаги

### 1. Создание субдомена
В hPanel → Domains → Subdomains создайте `aroma` для `inovaauto.com`.

### 2. Document root
Убедитесь, что document root субдомена указывает на папку сайта  
(часто `public_html/aroma` или `domains/aroma.inovaauto.com/public_html`).  
Корень проекта = document root (здесь лежат `index.php`, `admin/`, `.htaccess`).

### 3–4. MySQL база и пользователь
Создайте базу и пользователя, выдайте все права на базу.  
Запомните: host (обычно `localhost`), имя БД, логин, пароль.

### 5–6. Импорт SQL
В phpMyAdmin:

1. Импортируйте `database/schema.sql`
2. Импортируйте `database/seed.sql`

### 7. Загрузка проекта
Через Git, File Manager или ZIP (без `.git`, логов, `config/database.php` с продакшен-секретами из чужих сред).

### 8. config/database.php
Скопируйте из `config/database.example.php` и заполните данные Hostinger.

В `config/app.php` и/или настройках админки:

- `base_url` → `` (пустая строка) для корня субдомена
- при необходимости `public_url` → `https://aroma.inovaauto.com`

В корневом `.htaccess` смените:

```apache
ErrorDocument 404 /404.php
```

и `RewriteBase /`.

### 9. PHP 8.2+
hPanel → PHP Configuration → выберите **8.2** или выше.  
Включите `pdo_mysql`, `mbstring`, `fileinfo`, по возможности `gd`.

### 10. Права папок
`uploads/` и `storage/` — запись (обычно 755/775).

### 11. Uploads
Проверьте загрузку изображения блюда в админке.  
Файл `.htaccess` в `uploads/` должен запрещать выполнение PHP.

### 12. HTTPS
Включите SSL (Let's Encrypt) для `aroma.inovaauto.com`.  
При необходимости раскомментируйте HTTPS-редирект в `.htaccess`.

### 13. Администратор
По SSH / Terminal:

```bash
php database/create-admin.php you@email.com "StrongPasswordHere" "Админ"
```

### 14. Удаление create-admin.php
Удалите `database/create-admin.php` и `storage/create-admin.key` если создавали.

### 15–16. Ошибки PHP
В php.ini / hPanel:

- `display_errors = Off`
- `log_errors = On`
- логи смотрите в `storage/logs/app.log` и логах хостинга

### 17. Бронирование
Отправьте тестовую заявку на сайте → проверьте таблицу `reservations` и админку.

### 18. Админ-панель
`https://aroma.inovaauto.com/admin/login.php`

### 19. Изображения
Загрузка, WebP (если GD есть), placeholder при отсутствии файла.

### 20. Backup
Сделайте бэкап БД и файлов в hPanel.

## GitHub → хостинг

```bash
git init
git add .
git commit -m "Initial restaurant website"
git branch -M main
git remote add origin REPOSITORY_URL
git push -u origin main
```

На Hostinger можно клонировать репозиторий и создать локальный `config/database.php` (он в `.gitignore`).

## После деплоя

- Обновите `robots.txt` Sitemap URL при необходимости
- Проверьте canonical / Open Graph на https
- Смените демо-контакты в настройках админки
