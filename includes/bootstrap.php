<?php

declare(strict_types=1);

/**
 * Shared application bootstrap for public site and admin panel.
 */

if (defined('AROMA_BOOTSTRAP_LOADED')) {
    return;
}
define('AROMA_BOOTSTRAP_LOADED', true);

date_default_timezone_set('Asia/Dushanbe');

// Vercel / serverless: writable session & logs in /tmp
if (getenv('VERCEL') || getenv('NOW_REGION')) {
    $tmp = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR);
    if (is_dir($tmp) && is_writable($tmp)) {
        session_save_path($tmp);
        ini_set('session.save_path', $tmp);
        ini_set('error_log', $tmp . DIRECTORY_SEPARATOR . 'sayoh-php-error.log');
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (getenv('VERCEL') !== false);

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => (bool) $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    @session_start();
}

// Production-safe defaults (override in hosting php.ini as needed)
ini_set('display_errors', '0');
ini_set('log_errors', '1');
$errorLog = __DIR__ . '/../storage/logs/php-error.log';
if (is_dir(dirname($errorLog)) && is_writable(dirname($errorLog))) {
    ini_set('error_log', $errorLog);
}

// Fresh import: create config/database.php from example if missing
$dbLocal = __DIR__ . '/../config/database.php';
$dbExample = __DIR__ . '/../config/database.example.php';
if (!is_file($dbLocal) && is_file($dbExample)) {
    @copy($dbExample, $dbLocal);
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/settings.php';

/**
 * App config from config/app.php with optional DB settings overlay.
 *
 * @return ($key is null ? array<string, mixed> : mixed)
 */
function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';

        if (function_exists('all_settings')) {
            $s = all_settings();
            $map = [
                'name' => 'restaurant_name',
                'full_name' => 'restaurant_full_name',
                'tagline' => 'tagline',
                'phone' => 'phone',
                'phone_href' => 'phone_href',
                'email' => 'email',
                'address' => 'address',
                'map_url' => 'map_url',
                'map_embed' => 'map_embed',
                'whatsapp' => 'whatsapp',
                'base_url' => 'base_url',
                'about_video_url' => 'about_video_url',
                'timezone' => 'timezone',
            ];
            foreach ($map as $cfgKey => $setKey) {
                if (isset($s[$setKey]) && $s[$setKey] !== '') {
                    $config[$cfgKey] = $s[$setKey];
                }
            }
            if (!empty($s['meta_description_default'])) {
                $config['description'] = $s['meta_description_default'];
            }
            if (!empty($s['hero_text']) && empty($config['description'])) {
                $config['description'] = $s['hero_text'];
            }
        }

        $tz = (string) ($config['timezone'] ?? 'Europe/Moscow');
        if ($tz !== '' && in_array($tz, timezone_identifiers_list(), true)) {
            date_default_timezone_set($tz);
        }
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

require_once __DIR__ . '/repository.php';
require_once __DIR__ . '/slug.php';
require_once __DIR__ . '/upload.php';
