<?php

declare(strict_types=1);

/**
 * Google Sign-In (OAuth 2.0) helpers.
 */

function google_oauth_config(): array
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }

    $cfg = [
        'client_id' => trim((string) app_config('google_client_id', '')),
        'client_secret' => trim((string) app_config('google_client_secret', '')),
    ];

    $local = dirname(__DIR__) . '/config/google_oauth.php';
    if (is_file($local)) {
        $loaded = require $local;
        if (is_array($loaded)) {
            if (!empty($loaded['client_id'])) {
                $cfg['client_id'] = trim((string) $loaded['client_id']);
            }
            if (!empty($loaded['client_secret'])) {
                $cfg['client_secret'] = trim((string) $loaded['client_secret']);
            }
        }
    }

    if (function_exists('env_value')) {
        $id = env_value('GOOGLE_CLIENT_ID');
        $secret = env_value('GOOGLE_CLIENT_SECRET');
        if ($id !== '') {
            $cfg['client_id'] = $id;
        }
        if ($secret !== '') {
            $cfg['client_secret'] = $secret;
        }
    } else {
        foreach (['GOOGLE_CLIENT_ID' => 'client_id', 'GOOGLE_CLIENT_SECRET' => 'client_secret'] as $env => $key) {
            $val = getenv($env);
            if ($val === false || $val === '') {
                $val = $_ENV[$env] ?? $_SERVER[$env] ?? '';
            }
            if (is_string($val) && trim($val) !== '') {
                $cfg[$key] = trim($val);
            }
        }
    }

    return $cfg;
}

function google_oauth_enabled(): bool
{
    $cfg = google_oauth_config();
    return ($cfg['client_id'] ?? '') !== '' && ($cfg['client_secret'] ?? '') !== '';
}

function google_oauth_client_id(): string
{
    return (string) (google_oauth_config()['client_id'] ?? '');
}

function google_oauth_client_secret(): string
{
    return (string) (google_oauth_config()['client_secret'] ?? '');
}

function google_oauth_redirect_uri(): string
{
    return rtrim(base_url('auth-google-callback.php'), '?&');
}

function google_oauth_http_post(string $url, array $fields): ?array
{
    $body = http_build_query($fields);
    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
        }
    }

    if (!is_string($raw) || $raw === '') {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $body,
                'timeout' => 12,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
    }

    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function google_oauth_http_get(string $url, string $accessToken): ?array
{
    $raw = null;

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken],
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
        }
    }

    if (!is_string($raw) || $raw === '') {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: Bearer {$accessToken}\r\n",
                'timeout' => 12,
            ],
        ]);
        $raw = @file_get_contents($url, false, $ctx);
    }

    if (!is_string($raw) || $raw === '') {
        return null;
    }

    $data = json_decode($raw, true);
    return is_array($data) ? $data : null;
}

function google_oauth_auth_url(string $state): string
{
    $params = [
        'client_id' => google_oauth_client_id(),
        'redirect_uri' => google_oauth_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'access_type' => 'online',
        'prompt' => 'select_account',
        'include_granted_scopes' => 'true',
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/**
 * Exchange code → profile.
 *
 * @return array{email:string,name:string,sub:string,email_verified:bool}|null
 */
function google_oauth_fetch_profile(string $code): ?array
{
    $token = google_oauth_http_post('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => google_oauth_client_id(),
        'client_secret' => google_oauth_client_secret(),
        'redirect_uri' => google_oauth_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]);

    if ($token === null || empty($token['access_token'])) {
        storage_log('google_oauth token exchange failed');
        return null;
    }

    $info = google_oauth_http_get(
        'https://openidconnect.googleapis.com/v1/userinfo',
        (string) $token['access_token']
    );

    if ($info === null) {
        storage_log('google_oauth userinfo failed');
        return null;
    }

    $email = mb_strtolower(trim((string) ($info['email'] ?? '')));
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return null;
    }

    $name = trim((string) ($info['name'] ?? ''));
    if ($name === '') {
        $name = trim((string) (($info['given_name'] ?? '') . ' ' . ($info['family_name'] ?? '')));
    }
    if ($name === '') {
        $name = strstr($email, '@', true) ?: 'Google';
    }

    return [
        'email' => $email,
        'name' => mb_substr($name, 0, 120),
        'sub' => (string) ($info['sub'] ?? ''),
        'email_verified' => !empty($info['email_verified']),
    ];
}

/**
 * Find or create user from Google profile, then log in.
 *
 * @param array{email:string,name:string,sub:string,email_verified:bool} $profile
 */
function google_oauth_login_or_register(array $profile): bool
{
    if (!db_available()) {
        return false;
    }

    $email = $profile['email'];
    $name = $profile['name'];
    $sub = $profile['sub'];

    try {
        // Optional column google_id — ignore if missing
        $user = null;
        if ($sub !== '') {
            try {
                $stmt = db()->prepare(
                    'SELECT id, name, email, phone, status, email_verified_at, last_login_at, created_at
                     FROM users WHERE google_id = ? LIMIT 1'
                );
                $stmt->execute([$sub]);
                $user = $stmt->fetch() ?: null;
            } catch (Throwable $e) {
                // column may not exist yet
            }
        }

        if ($user === null) {
            $stmt = db()->prepare(
                'SELECT id, name, email, phone, status, email_verified_at, last_login_at, created_at
                 FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$email]);
            $user = $stmt->fetch() ?: null;
        }

        if ($user) {
            if (($user['status'] ?? '') !== 'active') {
                return false;
            }

            if ($sub !== '') {
                try {
                    db()->prepare('UPDATE users SET google_id = COALESCE(google_id, ?) WHERE id = ?')
                        ->execute([$sub, (int) $user['id']]);
                } catch (Throwable $e) {
                    // optional column
                }
            }

            if (empty($user['email_verified_at']) && !empty($profile['email_verified'])) {
                try {
                    db()->prepare('UPDATE users SET email_verified_at = NOW() WHERE id = ?')
                        ->execute([(int) $user['id']]);
                } catch (Throwable $e) {
                    // ignore
                }
            }

            login_user($user);
            return true;
        }

        $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        $verified = !empty($profile['email_verified']) ? date('Y-m-d H:i:s') : null;

        try {
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, phone, password_hash, status, email_verified_at, google_id)
                 VALUES (?, ?, NULL, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, 'active', $verified, $sub !== '' ? $sub : null]);
        } catch (Throwable $e) {
            // Fallback without google_id column
            $stmt = db()->prepare(
                'INSERT INTO users (name, email, phone, password_hash, status, email_verified_at)
                 VALUES (?, ?, NULL, ?, ?, ?)'
            );
            $stmt->execute([$name, $email, $hash, 'active', $verified]);
        }

        $userId = (int) db()->lastInsertId();
        $userStmt = db()->prepare(
            'SELECT id, name, email, phone, status, email_verified_at, last_login_at, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $userStmt->execute([$userId]);
        $created = $userStmt->fetch();
        if (!$created) {
            return false;
        }

        login_user($created);
        return true;
    } catch (Throwable $e) {
        storage_log('google_oauth_login_or_register: ' . $e->getMessage());
        return false;
    }
}
