<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-bootstrap.php';

require_post();
require_csrf();

unset($_SESSION['admin'], $_SESSION['admin_id'], $_SESSION['admin_ua'], $_SESSION['admin_ua_key'], $_SESSION['admin_login_at']);
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'] ?? '', (bool) $params['secure'], (bool) $params['httponly']);
}
session_destroy();

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
flash('success', 'Вы вышли из админ-панели.');
redirect('admin/login.php');
