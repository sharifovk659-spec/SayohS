<?php

declare(strict_types=1);

/**
 * Detect install path from DOCUMENT_ROOT vs project root (e.g. /Restarant or '').
 */
function detect_base_path(): string
{
    static $detected = null;
    if ($detected !== null) {
        return $detected;
    }

    $docRoot = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    $appRoot = realpath(__DIR__ . '/..');
    if ($docRoot && $appRoot && str_starts_with($appRoot, $docRoot)) {
        $rel = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
        $detected = rtrim($rel, '/');
        return $detected;
    }

    $detected = '';
    return $detected;
}

function is_production_host(): bool
{
    $host = strtolower((string) ($_SERVER['HTTP_HOST'] ?? ''));
    return $host === 'aroma.inovaauto.com';
}

function base_url(string $path = ''): string
{
    $fromDb = function_exists('setting') ? setting('base_url') : null;
    if (is_string($fromDb) && $fromDb !== '') {
        $base = rtrim($fromDb, '/');
    } elseif (is_production_host()) {
        // Subdomain document root — never fall back to local /Restarant.
        $base = 'https://aroma.inovaauto.com';
    } else {
        // Local XAMPP: detect /Restarant; otherwise use config/app.php.
        $detected = detect_base_path();
        $base = $detected !== ''
            ? $detected
            : rtrim((string) app_config('base_url', ''), '/');
    }

    $path = ltrim($path, '/');
    if ($path === '') {
        if ($base === '') {
            return '/';
        }
        // Absolute URL base already ends without trailing slash handling
        if (str_starts_with($base, 'http://') || str_starts_with($base, 'https://')) {
            return $base . '/';
        }
        return $base . '/';
    }

    if ($base === '') {
        return '/' . $path;
    }
    return $base . '/' . $path;
}

function asset(string $path): string
{
    $rel = ltrim($path, '/');
    $url = base_url('assets/' . $rel);
    $file = dirname(__DIR__) . '/assets/' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $rel);
    if (is_file($file)) {
        $sep = str_contains($url, '?') ? '&' : '?';
        return $url . $sep . 'v=' . (string) filemtime($file);
    }
    return $url;
}

function upload_url(string $folder, string $file): string
{
    return base_url('uploads/' . trim($folder, '/') . '/' . rawurlencode($file));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Strip all HTML/JS from user content before save.
 */
function sanitize_plain(?string $value): string
{
    return trim(strip_tags((string) $value));
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        $host = parse_url($path, PHP_URL_HOST);
        $own = $_SERVER['HTTP_HOST'] ?? '';
        if ($host && $own && strcasecmp((string) $host, (string) $own) !== 0) {
            header('Location: ' . base_url());
            exit;
        }
        header('Location: ' . $path);
        exit;
    }

    // Prevent open redirects
    $path = ltrim(str_replace(["\r", "\n"], '', $path), '/');
    header('Location: ' . base_url($path));
    exit;
}

function format_price(float|string $price): string
{
    return number_format((float) $price, 0, ',', ' ') . ' ₽';
}

function request_is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443')
        || (getenv('VERCEL') !== false);
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        // Restore from cookie on serverless (Vercel /tmp sessions are ephemeral)
        $fromCookie = $_COOKIE['csrf_token'] ?? '';
        if (is_string($fromCookie) && preg_match('/^[a-f0-9]{64}$/', $fromCookie)) {
            $_SESSION['csrf_token'] = $fromCookie;
        } else {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }
    $token = (string) $_SESSION['csrf_token'];
    if (!isset($_COOKIE['csrf_token']) || $_COOKIE['csrf_token'] !== $token) {
        @setcookie('csrf_token', $token, [
            'expires' => 0,
            'path' => '/',
            'secure' => request_is_https(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        $_COOKIE['csrf_token'] = $token;
    }
    return $token;
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    if (!is_string($token) || $token === '') {
        return false;
    }
    $expected = '';
    if (!empty($_SESSION['csrf_token']) && is_string($_SESSION['csrf_token'])) {
        $expected = $_SESSION['csrf_token'];
    } elseif (!empty($_COOKIE['csrf_token']) && is_string($_COOKIE['csrf_token'])) {
        $expected = $_COOKIE['csrf_token'];
        $_SESSION['csrf_token'] = $expected;
    }
    return $expected !== '' && hash_equals($expected, $token);
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function old(string $key, mixed $default = ''): mixed
{
    return $_SESSION['old_input'][$key] ?? $default;
}

function clear_old_input(): void
{
    unset($_SESSION['old_input'], $_SESSION['form_errors']);
}

/**
 * @param array<string, string> $errors
 * @param array<string, mixed> $input
 */
function set_form_state(array $errors, array $input): void
{
    $_SESSION['form_errors'] = $errors;
    $_SESSION['old_input'] = $input;
}

/**
 * @return array<string, string>
 */
function form_errors(): array
{
    $errors = $_SESSION['form_errors'] ?? [];
    unset($_SESSION['form_errors']);
    return is_array($errors) ? $errors : [];
}

function field_error(string $key, ?array $errors = null): string
{
    $bag = $errors ?? ($_SESSION['form_errors'] ?? []);
    if (empty($bag[$key])) {
        return '';
    }
    return '<p class="field-error" id="error-' . e($key) . '">' . e((string) $bag[$key]) . '</p>';
}

function field_invalid(string $key, ?array $errors = null): string
{
    $bag = $errors ?? ($_SESSION['form_errors'] ?? []);
    return empty($bag[$key]) ? '' : ' is-invalid';
}

function current_page(): string
{
    $script = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
    return pathinfo($script, PATHINFO_FILENAME);
}

function is_active_page(string $page): bool
{
    return current_page() === $page;
}

function nav_class(string $page): string
{
    return is_active_page($page) ? 'is-active' : '';
}

function honeypot_ok(array $post): bool
{
    return trim((string) ($post['website'] ?? '')) === '';
}

function rate_limit(string $key, int $max, int $seconds): bool
{
    $bucket = $_SESSION['rate_limit'][$key] ?? ['count' => 0, 'start' => time()];
    if (time() - (int) $bucket['start'] > $seconds) {
        $bucket = ['count' => 0, 'start' => time()];
    }
    $bucket['count']++;
    $_SESSION['rate_limit'][$key] = $bucket;
    return $bucket['count'] <= $max;
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    if (str_starts_with($digits, '8') && strlen($digits) === 11) {
        $digits = '7' . substr($digits, 1);
    }
    return $digits;
}

function is_valid_phone(string $phone): bool
{
    $digits = normalize_phone($phone);
    return strlen($digits) >= 10 && strlen($digits) <= 15;
}

function client_ip_hash(): string
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    return hash('sha256', $ip . '|' . ($_SERVER['HTTP_USER_AGENT'] ?? ''));
}

function notify_admin(string $subject, string $body): void
{
    $to = setting('notify_email', '');
    if ($to === null || $to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return;
    }
    $headers = 'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "\r\n" .
        'Content-Type: text/plain; charset=UTF-8';
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}

function resolve_media(?string $file, string $folder, string $assetFolder, string $placeholder): string
{
    if ($file === null || $file === '') {
        return asset('images/' . $assetFolder . '/' . $placeholder);
    }

    $upload = __DIR__ . '/../uploads/' . $folder . '/' . $file;
    if (is_file($upload)) {
        return upload_url($folder, $file);
    }

    $assetDir = __DIR__ . '/../assets/images/' . $assetFolder . '/';
    $asset = $assetDir . $file;
    if (is_file($asset)) {
        return asset('images/' . $assetFolder . '/' . $file);
    }

    // Prefer real photos: webp / jpg / png siblings before SVG placeholders
    $base = preg_replace('/\.(webp|jpe?g|png|svg)$/i', '', $file);
    if (is_string($base) && $base !== '') {
        foreach (['webp', 'jpg', 'jpeg', 'png'] as $ext) {
            $candidate = $base . '.' . $ext;
            if (is_file($assetDir . $candidate)) {
                return asset('images/' . $assetFolder . '/' . $candidate);
            }
        }
        $svgSibling = $base . '.svg';
        if (is_file($assetDir . $svgSibling)) {
            return asset('images/' . $assetFolder . '/' . $svgSibling);
        }
    }

    // Placeholder SVG fallback
    if (str_ends_with($placeholder, '.webp')) {
        $svg = preg_replace('/\.webp$/', '.svg', $placeholder) ?: 'placeholder.svg';
        $svgPath = $assetDir . $svg;
        if (is_file($svgPath)) {
            return asset('images/' . $assetFolder . '/' . $svg);
        }
    }

    return asset('images/' . $assetFolder . '/' . $placeholder);
}

function dish_image_url(?string $image): string
{
    return resolve_media($image, 'dishes', 'dishes', 'placeholder.webp');
}

function category_image_url(?string $image): string
{
    return resolve_media($image, 'categories', 'categories', 'placeholder.webp');
}

function gallery_image_url(?string $image): string
{
    return resolve_media($image, 'gallery', 'gallery', 'placeholder.webp');
}

function hero_image_url(?string $file = null): string
{
    $file = $file ?: setting('hero_image', 'hero-main.webp');
    return resolve_media($file, 'settings', 'hero', 'hero-main.webp');
}

function gallery_album_label(string $type): string
{
    return match ($type) {
        'interior' => 'Интерьер',
        'dishes' => 'Блюда',
        'drinks' => 'Напитки',
        'team' => 'Команда',
        'events' => 'События',
        default => 'Все',
    };
}

function reservation_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Новая',
        'confirmed' => 'Подтверждена',
        'completed' => 'Завершена',
        'cancelled' => 'Отменена',
        default => $status,
    };
}

function message_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Новое',
        'read' => 'Прочитано',
        'answered' => 'Отвечено',
        default => $status,
    };
}

function menu_query(array $overrides = []): string
{
    $q = array_key_exists('q', $overrides) ? (string) $overrides['q'] : trim((string) ($_GET['q'] ?? ''));
    $category = array_key_exists('category', $overrides) ? (string) $overrides['category'] : (string) ($_GET['category'] ?? 'all');
    $sort = array_key_exists('sort', $overrides) ? (string) $overrides['sort'] : (string) ($_GET['sort'] ?? 'default');
    $popular = array_key_exists('popular', $overrides) ? $overrides['popular'] : (isset($_GET['popular']) ? '1' : null);
    $available = array_key_exists('available', $overrides) ? $overrides['available'] : (isset($_GET['available']) ? '1' : null);
    $page = (int) (array_key_exists('page', $overrides) ? $overrides['page'] : ($_GET['page'] ?? 1));

    $clean = [];
    if ($q !== '') {
        $clean['q'] = $q;
    }
    if ($category !== '' && $category !== 'all') {
        $clean['category'] = $category;
    }
    if ($sort !== '' && $sort !== 'default') {
        $clean['sort'] = $sort;
    }
    if ($popular === '1' || $popular === 1 || $popular === true) {
        $clean['popular'] = '1';
    }
    if ($available === '1' || $available === 1 || $available === true) {
        $clean['available'] = '1';
    }
    if ($page > 1) {
        $clean['page'] = $page;
    }

    $query = http_build_query($clean);
    return $query === '' ? '' : '?' . $query;
}
