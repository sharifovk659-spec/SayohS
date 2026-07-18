<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-bootstrap.php';

if (admin_logged_in()) {
    redirect('admin/index.php');
}

$error = '';
$loginValue = '';

const ADMIN_MAX_ATTEMPTS = 5;
const ADMIN_LOCK_MINUTES = 15;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $error = 'Сессия устарела. Обновите страницу.';
    } else {
        $login = mb_strtolower(trim((string) ($_POST['login'] ?? $_POST['email'] ?? '')));
        $password = (string) ($_POST['password'] ?? '');
        $loginValue = $login;

        if ($login === '' || $password === '') {
            $error = 'Укажите логин и пароль.';
        } else {
            try {
                $stmt = db()->prepare(
                    'SELECT * FROM admins
                     WHERE LOWER(email) = ? OR LOWER(name) = ?
                     LIMIT 1'
                );
                $stmt->execute([$login, $login]);
                $admin = $stmt->fetch();

                $now = new DateTimeImmutable('now');
                $genericError = 'Неверный логин или пароль.';

                if ($admin && !empty($admin['locked_until'])) {
                    $lockedUntil = new DateTimeImmutable((string) $admin['locked_until']);
                    if ($lockedUntil > $now) {
                        $error = 'Аккаунт временно заблокирован. Попробуйте позже.';
                    }
                }

                if ($error === '') {
                    $valid = $admin
                        && (int) ($admin['status'] ?? 0) === 1
                        && password_verify($password, (string) $admin['password_hash']);

                    if ($valid) {
                        $upd = db()->prepare(
                            'UPDATE admins SET login_attempts = 0, locked_until = NULL, last_login_at = NOW() WHERE id = ?'
                        );
                        $upd->execute([(int) $admin['id']]);

                        $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
                        $uaKey = preg_replace('/\d+/', 'x', $ua) ?? $ua;

                        session_regenerate_id(true);
                        $_SESSION['admin'] = [
                            'id' => (int) $admin['id'],
                            'name' => (string) $admin['name'],
                            'role' => (string) $admin['role'],
                        ];
                        $_SESSION['admin_id'] = (int) $admin['id'];
                        $_SESSION['admin_ua'] = $ua;
                        $_SESSION['admin_ua_key'] = $uaKey;
                        $_SESSION['admin_login_at'] = time();

                        flash('success', 'Добро пожаловать!');
                        redirect('admin/index.php');
                    }

                    if ($admin) {
                        $attempts = (int) ($admin['login_attempts'] ?? 0) + 1;
                        if ($attempts >= ADMIN_MAX_ATTEMPTS) {
                            $lock = $now->modify('+' . ADMIN_LOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s');
                            $upd = db()->prepare(
                                'UPDATE admins SET login_attempts = ?, locked_until = ? WHERE id = ?'
                            );
                            $upd->execute([$attempts, $lock, (int) $admin['id']]);
                            $error = 'Слишком много попыток. Аккаунт заблокирован на ' . ADMIN_LOCK_MINUTES . ' мин.';
                        } else {
                            $upd = db()->prepare('UPDATE admins SET login_attempts = ? WHERE id = ?');
                            $upd->execute([$attempts, (int) $admin['id']]);
                            $error = $genericError;
                        }
                    } else {
                        $error = $genericError;
                    }
                }
            } catch (Throwable) {
                $error = 'Не удалось подключиться к базе данных.';
            }
        }
    }
}

$brand = setting('restaurant_name', (string) app_config('name', 'Сайёҳ')) ?? 'Сайёҳ';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title>Вход — Админ-панель</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="icon" href="<?= e(asset('icons/favicon.png')) ?>" type="image/png">
</head>
<body class="admin-body">
  <div class="login-page">
    <form class="login-card" method="post" action="" autocomplete="on">
      <?php
      $loginLogo = setting('logo') ?: 'sayoh-logo.png';
      $loginLogoPath = dirname(__DIR__) . '/uploads/settings/' . basename((string) $loginLogo);
      $loginLogoUrl = is_file($loginLogoPath)
          ? upload_url('settings', basename((string) $loginLogo))
          : asset('images/brand/sayoh-logo.png');
      ?>
      <img class="login-brand-logo" src="<?= e($loginLogoUrl) ?>" alt="<?= e($brand) ?>" width="88" height="88" decoding="async">
      <h1><?= e($brand) ?></h1>
      <p>Вход в админ-панель</p>
      <?php if ($error !== ''): ?>
        <div class="admin-flash admin-flash-error"><?= e($error) ?></div>
      <?php endif; ?>
      <?= csrf_field() ?>
      <div class="form-grid" style="grid-template-columns:1fr">
        <div class="form-group">
          <label for="login">Логин</label>
          <input type="text" id="login" name="login" required autocomplete="username"
                 value="<?= e($loginValue) ?>" placeholder="admin">
        </div>
        <div class="form-group">
          <label for="password">Пароль</label>
          <input type="password" id="password" name="password" required autocomplete="current-password">
        </div>
        <div class="form-group">
          <button class="btn" type="submit">Войти</button>
        </div>
      </div>
    </form>
  </div>
</body>
</html>
