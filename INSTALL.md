# Установка Aroma Restaurant

## 1. Требования

- PHP 8.2+
- MySQL 8 / MariaDB
- Apache
- Расширения PHP: `pdo_mysql`, `mbstring`, `json`, `fileinfo` (рекомендуется `gd`)

## 2. Файлы

1. Разместите проект в document root (локально: `htdocs/Restarant`).
2. Скопируйте конфиг БД:

```bash
cp config/database.example.php config/database.php
```

Отредактируйте `config/database.php`:

```php
return [
    'host' => '127.0.0.1',
    'port' => '3306',
    'dbname' => 'aroma_restaurant',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
];
```

3. В `config/app.php` при необходимости измените `base_url` (`/Restarant` для XAMPP, `` для корня субдомена).

## 3. Импорт MySQL

Через phpMyAdmin или CLI:

```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

`seed.sql` **не** содержит пароль администратора.

## 4. Права папок

Запись для веб-сервера:

- `uploads/` (и подпапки)
- `storage/logs/`
- `storage/cache/`

## 5. Создание администратора

**Рекомендуется CLI:**

```bash
php database/create-admin.php admin@example.com "YourStrongPassword" "Администратор"
```

**Либо web + одноразовый ключ:**

1. Создайте файл `storage/create-admin.key` со случайной строкой.
2. Откройте  
   `/database/create-admin.php?key=ВАШ_КЛЮЧ`
3. Заполните форму (пароль не показывается после отправки).
4. Удалите `database/create-admin.php` и `storage/create-admin.key`.

## 6. Проверка

- Главная: меню, категории, галерея
- Бронирование: форма → запись в `reservations`
- Админка: вход, dashboard, CRUD

## 7. Production

- `display_errors = Off`
- `log_errors = On`
- HTTPS
- Удалить `create-admin.php`
- Не коммитить `config/database.php`
