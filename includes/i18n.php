<?php

declare(strict_types=1);

/**
 * Language / i18n helpers. Fallback language is Russian (ru).
 */

const AROMA_LANGS = ['ru', 'tg'];
const AROMA_LANG_DEFAULT = 'ru';

function aroma_supported_lang(?string $code): bool
{
    return is_string($code) && in_array(strtolower($code), AROMA_LANGS, true);
}

function current_lang(): string
{
    static $lang = null;
    if ($lang !== null) {
        return $lang;
    }

    $candidate = null;

    if (isset($_GET['lang']) && aroma_supported_lang((string) $_GET['lang'])) {
        $candidate = strtolower((string) $_GET['lang']);
    } elseif (!empty($_SESSION['lang']) && aroma_supported_lang((string) $_SESSION['lang'])) {
        $candidate = strtolower((string) $_SESSION['lang']);
    } elseif (!empty($_COOKIE['aroma_lang']) && aroma_supported_lang((string) $_COOKIE['aroma_lang'])) {
        $candidate = strtolower((string) $_COOKIE['aroma_lang']);
    } else {
        $fromSettings = function_exists('setting') ? setting('default_language', AROMA_LANG_DEFAULT) : AROMA_LANG_DEFAULT;
        $candidate = aroma_supported_lang((string) $fromSettings) ? strtolower((string) $fromSettings) : AROMA_LANG_DEFAULT;
    }

    $lang = $candidate ?: AROMA_LANG_DEFAULT;
    $_SESSION['lang'] = $lang;

    return $lang;
}

function set_lang(string $code): void
{
    if (!aroma_supported_lang($code)) {
        $code = AROMA_LANG_DEFAULT;
    }
    $code = strtolower($code);
    $_SESSION['lang'] = $code;

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    setcookie('aroma_lang', $code, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => $https,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

/**
 * Switch language and redirect back to current page (no open redirect).
 */
function handle_lang_switch(): void
{
    if (!isset($_GET['lang']) || !aroma_supported_lang((string) $_GET['lang'])) {
        return;
    }

    $code = strtolower((string) $_GET['lang']);
    set_lang($code);

    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    unset($query['lang']);
    $qs = http_build_query($query);
    $target = $path . ($qs !== '' ? '?' . $qs : '');

    // Prevent open redirect: only relative path on same host
    if (str_contains($target, '://') || str_starts_with($target, '//')) {
        $target = base_url();
    }

    header('Location: ' . $target);
    exit;
}

/**
 * @return array<string, string>
 */
function lang_catalog(?string $code = null): array
{
    static $cache = [];
    $code = $code ? strtolower($code) : current_lang();
    if (!aroma_supported_lang($code)) {
        $code = AROMA_LANG_DEFAULT;
    }
    if (isset($cache[$code])) {
        return $cache[$code];
    }

    $file = __DIR__ . '/../lang/' . $code . '.php';
    $fallbackFile = __DIR__ . '/../lang/' . AROMA_LANG_DEFAULT . '.php';
    $data = is_file($file) ? require $file : [];
    if (!is_array($data)) {
        $data = [];
    }
    if ($code !== AROMA_LANG_DEFAULT && is_file($fallbackFile)) {
        $ru = require $fallbackFile;
        if (is_array($ru)) {
            $data = array_merge($ru, $data);
        }
    }
    $cache[$code] = $data;
    return $cache[$code];
}

/**
 * Translate UI string. Supports sprintf placeholders.
 */
function __(string $key, mixed ...$args): string
{
    $catalog = lang_catalog();
    $text = $catalog[$key] ?? null;
    if ($text === null || $text === '') {
        $ru = lang_catalog(AROMA_LANG_DEFAULT);
        $text = $ru[$key] ?? $key;
    }
    if ($args !== []) {
        return sprintf((string) $text, ...$args);
    }
    return (string) $text;
}

function lang_url(string $code): string
{
    $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $parts = parse_url($uri);
    $path = $parts['path'] ?? '/';
    $query = [];
    if (!empty($parts['query'])) {
        parse_str($parts['query'], $query);
    }
    $query['lang'] = strtolower($code);
    return $path . '?' . http_build_query($query);
}

function html_lang_attr(): string
{
    return current_lang();
}

function og_locale(): string
{
    return match (current_lang()) {
        'tg' => 'tg_TJ',
        default => 'ru_RU',
    };
}
