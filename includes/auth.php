<?php

declare(strict_types=1);

/**
 * Customer authentication helpers.
 */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }

    static $user = false;
    if ($user !== false) {
        return $user;
    }

    try {
        $stmt = db()->prepare(
            'SELECT id, name, email, phone, status, email_verified_at, last_login_at, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([(int) $_SESSION['user_id']]);
        $row = $stmt->fetch();
        if (!$row || ($row['status'] ?? '') !== 'active') {
            unset($_SESSION['user_id'], $_SESSION['user_name']);
            $user = null;
            return null;
        }
        $user = $row;
        return $user;
    } catch (Throwable $e) {
        storage_log('current_user: ' . $e->getMessage());
        $user = null;
        return null;
    }
}

function user_logged_in(): bool
{
    return current_user() !== null;
}

function require_user(): array
{
    $user = current_user();
    if ($user === null) {
        flash('error', __('error_generic'));
        $_SESSION['redirect_after_login'] = (string) ($_SERVER['REQUEST_URI'] ?? 'account/');
        redirect('login.php');
    }
    return $user;
}

function normalize_phone_e164(string $phone): string
{
    $phone = trim($phone);
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if ($digits === '') {
        return '';
    }
    if (str_starts_with($digits, '8') && strlen($digits) === 11) {
        $digits = '7' . substr($digits, 1);
    }
    if (str_starts_with($digits, '9') && strlen($digits) === 10) {
        $digits = '7' . $digits;
    }
    return '+' . $digits;
}

function auth_rate_limited(string $bucket, int $max = 8, int $windowSeconds = 900): bool
{
    $key = 'auth_rl_' . $bucket;
    $now = time();
    $data = $_SESSION[$key] ?? ['count' => 0, 'start' => $now];
    if (($now - (int) $data['start']) > $windowSeconds) {
        $data = ['count' => 0, 'start' => $now];
    }
    $data['count'] = (int) $data['count'] + 1;
    $_SESSION[$key] = $data;
    return $data['count'] > $max;
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION['user_name'] = (string) $user['name'];
    $_SESSION['user_ua'] = substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 250);
    $_SESSION['user_login_at'] = time();

    try {
        $stmt = db()->prepare(
            'UPDATE users SET last_login_at = NOW(), login_attempts = 0, locked_until = NULL WHERE id = ?'
        );
        $stmt->execute([(int) $user['id']]);
    } catch (Throwable $e) {
        storage_log('login_user update: ' . $e->getMessage());
    }

    if (function_exists('favorites_merge_guest')) {
        favorites_merge_guest((int) $user['id']);
    }
    if (function_exists('cart_merge_guest')) {
        cart_merge_guest((int) $user['id']);
    }
}

function logout_user(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        $_SESSION['user_ua'],
        $_SESSION['user_login_at']
    );
    session_regenerate_id(true);
}

function create_password_reset_token(int $userId): ?string
{
    try {
        $token = bin2hex(random_bytes(32));
        $hash = hash('sha256', $token);
        $expires = (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s');
        $ipHash = hash('sha256', (string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        db()->prepare('DELETE FROM password_resets WHERE user_id = ? OR expires_at < NOW()')->execute([$userId]);
        $stmt = db()->prepare(
            'INSERT INTO password_resets (user_id, token_hash, expires_at, created_ip_hash) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$userId, $hash, $expires, $ipHash]);
        return $token;
    } catch (Throwable $e) {
        storage_log('create_password_reset_token: ' . $e->getMessage());
        return null;
    }
}

function consume_password_reset_token(string $token): ?int
{
    $hash = hash('sha256', $token);
    try {
        $stmt = db()->prepare(
            'SELECT id, user_id FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        if (!$row) {
            return null;
        }
        db()->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')->execute([(int) $row['id']]);
        return (int) $row['user_id'];
    } catch (Throwable $e) {
        storage_log('consume_password_reset_token: ' . $e->getMessage());
        return null;
    }
}

/**
 * Send reset mail or log token in non-production test mode. Never echo token on production host.
 */
function send_password_reset_mail(string $email, string $token): bool
{
    $link = base_url('reset-password.php?token=' . rawurlencode($token));
    $body = "Aroma Restaurant password reset\n\n" . $link . "\n\nValid for 1 hour.\n";

    $driver = (string) (setting('mail_driver') ?: 'log');
    if ($driver === 'log' || !is_production_host()) {
        storage_log('password_reset_link for ' . $email . ' (test mode, not shown publicly)');
        // Store only in server log — never flash token on production.
        if (!is_production_host()) {
            storage_log('DEV reset link: ' . $link);
        }
        return true;
    }

    // SMTP placeholder — ready for later wiring; fall back to log.
    storage_log('SMTP not configured; password reset logged for ' . $email);
    return true;
}
