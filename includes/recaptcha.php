<?php

declare(strict_types=1);

/**
 * Google reCAPTCHA v3 (invisible / automatic) helpers.
 */

function env_value(string $key, string $default = ''): string
{
    $val = getenv($key);
    if ($val === false || $val === '') {
        $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    return is_string($val) ? trim($val) : $default;
}

function recaptcha_config(): array
{
    static $cfg = null;
    if (is_array($cfg)) {
        return $cfg;
    }

    $cfg = [
        'site_key' => trim((string) app_config('recaptcha_site_key', '')),
        'secret_key' => trim((string) app_config('recaptcha_secret_key', '')),
        'min_score' => (float) app_config('recaptcha_min_score', 0.4),
    ];

    $local = dirname(__DIR__) . '/config/recaptcha.php';
    if (is_file($local)) {
        $loaded = require $local;
        if (is_array($loaded)) {
            if (!empty($loaded['site_key'])) {
                $cfg['site_key'] = trim((string) $loaded['site_key']);
            }
            if (!empty($loaded['secret_key'])) {
                $cfg['secret_key'] = trim((string) $loaded['secret_key']);
            }
            if (isset($loaded['min_score']) && is_numeric($loaded['min_score'])) {
                $cfg['min_score'] = (float) $loaded['min_score'];
            }
        }
    }

    $envSite = env_value('RECAPTCHA_SITE_KEY');
    $envSecret = env_value('RECAPTCHA_SECRET_KEY');
    $envScore = env_value('RECAPTCHA_MIN_SCORE');
    if ($envSite !== '') {
        $cfg['site_key'] = $envSite;
    }
    if ($envSecret !== '') {
        $cfg['secret_key'] = $envSecret;
    }
    if ($envScore !== '' && is_numeric($envScore)) {
        $cfg['min_score'] = (float) $envScore;
    }

    return $cfg;
}

function recaptcha_site_key(): string
{
    return (string) (recaptcha_config()['site_key'] ?? '');
}

function recaptcha_secret_key(): string
{
    return (string) (recaptcha_config()['secret_key'] ?? '');
}

function recaptcha_enabled(): bool
{
    return recaptcha_site_key() !== '' && recaptcha_secret_key() !== '';
}

function recaptcha_min_score(): float
{
    return max(0.1, min(0.9, (float) (recaptcha_config()['min_score'] ?? 0.4)));
}

/**
 * Verify Google reCAPTCHA v3 token.
 *
 * @return array{ok:bool, score:float, action:string, error:?string}
 */
function recaptcha_verify(?string $token, string $expectedAction = 'register'): array
{
    $empty = ['ok' => false, 'score' => 0.0, 'action' => '', 'error' => 'missing'];

    if (!recaptcha_enabled()) {
        // Not configured → do not block clients
        return ['ok' => true, 'score' => 1.0, 'action' => $expectedAction, 'error' => null];
    }

    $token = trim((string) $token);
    if ($token === '') {
        return $empty;
    }

    $payload = http_build_query([
        'secret' => recaptcha_secret_key(),
        'response' => $token,
        'remoteip' => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
    ]);

    $raw = null;
    if (function_exists('curl_init')) {
        $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
        if ($ch !== false) {
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 8,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            $raw = curl_exec($ch);
            curl_close($ch);
        }
    }

    if ($raw === false || $raw === null || $raw === '') {
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
                'timeout' => 8,
            ],
        ]);
        $raw = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, $ctx);
    }

    if (!is_string($raw) || $raw === '') {
        storage_log('recaptcha: empty response from Google');
        // Network glitch — do not punish real clients
        return ['ok' => true, 'score' => 0.5, 'action' => $expectedAction, 'error' => 'network'];
    }

    /** @var array<string, mixed>|null $data */
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        storage_log('recaptcha: invalid JSON');
        return ['ok' => true, 'score' => 0.5, 'action' => $expectedAction, 'error' => 'json'];
    }

    $ok = !empty($data['success']);
    $score = isset($data['score']) ? (float) $data['score'] : 0.0;
    $action = isset($data['action']) ? (string) $data['action'] : '';

    if (!$ok) {
        $codes = isset($data['error-codes']) && is_array($data['error-codes'])
            ? implode(',', $data['error-codes'])
            : 'failed';
        storage_log('recaptcha fail: ' . $codes);
        return ['ok' => false, 'score' => $score, 'action' => $action, 'error' => $codes];
    }

    if ($expectedAction !== '' && $action !== '' && $action !== $expectedAction) {
        storage_log('recaptcha action mismatch: ' . $action);
        return ['ok' => false, 'score' => $score, 'action' => $action, 'error' => 'action'];
    }

    if ($score < recaptcha_min_score()) {
        storage_log('recaptcha low score: ' . $score);
        return ['ok' => false, 'score' => $score, 'action' => $action, 'error' => 'score'];
    }

    return ['ok' => true, 'score' => $score, 'action' => $action, 'error' => null];
}
