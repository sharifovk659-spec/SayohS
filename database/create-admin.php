<?php

declare(strict_types=1);

/**
 * Создание администратора.
 *
 * CLI:
 *   php database/create-admin.php email@example.com "Password" "Имя"
 *
 * Web (одноразовый ключ):
 *   Создайте файл storage/create-admin.key с случайной строкой,
 *   откройте /database/create-admin.php?key=... (если доступ не запрещён),
 *   после использования удалите этот скрипт и ключ.
 */

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    header('Content-Type: text/html; charset=UTF-8');
}

$keyFile = __DIR__ . '/../storage/create-admin.key';
$providedKey = $isCli ? '' : (string) ($_GET['key'] ?? $_POST['key'] ?? '');

$webAllowed = false;
if (!$isCli && is_file($keyFile)) {
    $expected = trim((string) file_get_contents($keyFile));
    $webAllowed = $expected !== '' && hash_equals($expected, $providedKey);
}

if (!$isCli && !$webAllowed) {
    http_response_code(403);
    echo 'Доступ запрещён. Используйте CLI или одноразовый ключ.';
    exit(1);
}

require_once __DIR__ . '/../includes/bootstrap.php';

function create_admin_out(string $message, bool $cli): void
{
    if ($cli) {
        echo $message . PHP_EOL;
        return;
    }
    echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Создание администратора</title></head><body>';
    echo '<p>' . htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>';
    echo '<p><strong>Удалите database/create-admin.php и storage/create-admin.key после использования.</strong></p>';
    echo '</body></html>';
}

if ($isCli) {
    $email = mb_strtolower(trim((string) ($argv[1] ?? '')));
    $password = (string) ($argv[2] ?? '');
    $name = sanitize_plain((string) ($argv[3] ?? 'Администратор'));
} else {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        echo '<!DOCTYPE html><html lang="ru"><head><meta charset="UTF-8"><title>Создание администратора</title></head><body>';
        echo '<h1>Создание администратора</h1>';
        echo '<form method="post">';
        echo '<input type="hidden" name="key" value="' . htmlspecialchars($providedKey, ENT_QUOTES, 'UTF-8') . '">';
        echo '<p><label>Имя<br><input name="name" required maxlength="120"></label></p>';
        echo '<p><label>Email<br><input type="email" name="email" required maxlength="190"></label></p>';
        echo '<p><label>Пароль<br><input type="password" name="password" required minlength="8" autocomplete="new-password"></label></p>';
        echo '<button type="submit">Создать</button>';
        echo '</form>';
        echo '<p>Пароль не отображается после отправки. Удалите этот файл после использования.</p>';
        echo '</body></html>';
        exit;
    }
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $name = sanitize_plain((string) ($_POST['name'] ?? 'Администратор'));
}

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    create_admin_out('Укажите корректный email.', $isCli);
    exit(1);
}

if (strlen($password) < 8) {
    create_admin_out('Пароль должен быть не короче 8 символов.', $isCli);
    exit(1);
}

if ($name === '') {
    $name = 'Администратор';
}

try {
    if (!db_available()) {
        create_admin_out('База данных недоступна.', $isCli);
        exit(1);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = db()->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $existing = $stmt->fetchColumn();

    if ($existing) {
        create_admin_out('Администратор с таким email уже существует. Используйте другой email или обновите через CLI.', $isCli);
        exit(1);
    }

    $ins = db()->prepare(
        'INSERT INTO admins (name, email, password_hash, role, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, 1, NOW(), NOW())'
    );
    $ins->execute([$name, $email, $hash, 'admin']);

    // Wipe password from memory as much as possible
    $password = str_repeat('0', strlen($password));
    unset($password);

    create_admin_out('Администратор создан: ' . $email . '. Удалите database/create-admin.php.', $isCli);
    exit(0);
} catch (Throwable $e) {
    storage_log('create-admin: ' . $e->getMessage());
    create_admin_out('Не удалось создать администратора.', $isCli);
    exit(1);
}
