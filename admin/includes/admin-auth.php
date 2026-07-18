<?php

declare(strict_types=1);

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin']['id']) && (int) $_SESSION['admin']['id'] > 0;
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        flash('error', 'Войдите в админ-панель.');
        redirect('admin/login.php');
    }

    // Soft UA fingerprint (Chrome/Google updates change full UA often)
    $ua = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
    $uaKey = preg_replace('/\d+/', 'x', $ua) ?? $ua;
    if (empty($_SESSION['admin_ua_key'])) {
        $_SESSION['admin_ua_key'] = $uaKey;
        $_SESSION['admin_ua'] = $ua;
    } elseif (($_SESSION['admin_ua_key'] ?? '') !== $uaKey) {
        unset($_SESSION['admin'], $_SESSION['admin_ua'], $_SESSION['admin_ua_key'], $_SESSION['admin_login_at'], $_SESSION['admin_id']);
        flash('error', 'Сессия недействительна. Войдите снова.');
        redirect('admin/login.php');
    }

    $user = admin_user();
    if ($user === null || (int) ($user['status'] ?? 0) !== 1) {
        unset($_SESSION['admin'], $_SESSION['admin_ua'], $_SESSION['admin_ua_key'], $_SESSION['admin_login_at'], $_SESSION['admin_id']);
        flash('error', 'Учётная запись недоступна.');
        redirect('admin/login.php');
    }

    // Keep session payload fresh
    $_SESSION['admin'] = [
        'id' => (int) $user['id'],
        'name' => (string) $user['name'],
        'role' => (string) $user['role'],
    ];
}

function admin_role(): string
{
    return (string) ($_SESSION['admin']['role'] ?? '');
}

function admin_is_full_admin(): bool
{
    return admin_role() === 'admin';
}

function require_admin_role(string $role = 'admin'): void
{
    require_admin();
    if (admin_role() !== $role && !admin_is_full_admin()) {
        flash('error', 'Недостаточно прав для этого раздела.');
        redirect('admin/index.php');
    }
}

/**
 * @return array<string, mixed>|null
 */
function admin_user(): ?array
{
    $id = (int) ($_SESSION['admin']['id'] ?? $_SESSION['admin_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }

    static $user = null;
    static $loaded = false;
    static $loadedId = 0;

    if ($loaded && $loadedId === $id) {
        return $user;
    }

    $loaded = true;
    $loadedId = $id;

    try {
        $stmt = db()->prepare(
            'SELECT id, name, email, role, status, last_login_at
             FROM admins WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        $user = $row ?: null;
    } catch (Throwable) {
        $user = null;
    }

    return $user;
}
