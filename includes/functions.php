<?php

declare(strict_types=1);

function db_port_open(): bool
{
    static $open = null;

    if ($open !== null) {
        return $open;
    }

    $config = require __DIR__ . '/../config/database.php';
    $host = $config['host'] === 'localhost' ? '127.0.0.1' : $config['host'];
    $errno = 0;
    $errstr = '';
    $socket = @fsockopen($host, (int) $config['port'], $errno, $errstr, 1);

    if (is_resource($socket)) {
        fclose($socket);
        $open = true;
    } else {
        $open = false;
    }

    return $open;
}

/**
 * @return PDO
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (!db_port_open()) {
        throw new RuntimeException('База данных недоступна.');
    }

    $config = require __DIR__ . '/../config/database.php';

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        $config['host'],
        $config['port'],
        $config['dbname'],
        $config['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    if (defined('PDO::MYSQL_ATTR_CONNECT_TIMEOUT')) {
        $options[PDO::MYSQL_ATTR_CONNECT_TIMEOUT] = 2;
    }

    $pdo = new PDO($dsn, $config['username'], $config['password'], $options);

    return $pdo;
}

function app_config(?string $key = null, mixed $default = null): mixed
{
    static $config = null;

    if ($config === null) {
        $config = require __DIR__ . '/../config/app.php';
    }

    if ($key === null) {
        return $config;
    }

    return $config[$key] ?? $default;
}

function base_url(string $path = ''): string
{
    $base = rtrim((string) app_config('base_url', ''), '/');
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }

    return ($base === '' ? '' : $base) . '/' . $path;
}

function asset(string $path): string
{
    return base_url('assets/' . ltrim($path, '/'));
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): never
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        header('Location: ' . $path);
        exit;
    }

    header('Location: ' . base_url($path));
    exit;
}

function format_price(float|string $price): string
{
    return number_format((float) $price, 0, ',', ' ') . ' ₽';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
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

function get_setting(string $key, ?string $default = null): ?string
{
    try {
        $stmt = db()->prepare('SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();

        return $value !== false ? (string) $value : $default;
    } catch (Throwable) {
        return $default;
    }
}

function resolve_image(?string $image, string $folder, string $placeholder): string
{
    $candidates = [];

    if ($image !== null && $image !== '') {
        $candidates[] = $image;
        if (str_ends_with($image, '.webp')) {
            $candidates[] = preg_replace('/\.webp$/', '.svg', $image) ?: $image;
        }
    }

    $candidates[] = $placeholder;
    if (str_ends_with($placeholder, '.webp')) {
        $candidates[] = 'placeholder.svg';
    }

    foreach (array_unique($candidates) as $file) {
        $upload = __DIR__ . '/../uploads/' . $folder . '/' . $file;
        if (is_file($upload)) {
            return base_url('uploads/' . $folder . '/' . rawurlencode($file));
        }

        $assetPath = __DIR__ . '/../assets/images/' . $folder . '/' . $file;
        if (is_file($assetPath)) {
            return asset('images/' . $folder . '/' . $file);
        }
    }

    return asset('images/' . $folder . '/placeholder.svg');
}

function dish_image_url(?string $image): string
{
    return resolve_image($image, 'dishes', 'placeholder.webp');
}

function gallery_image_url(?string $image): string
{
    return resolve_image($image, 'gallery', 'placeholder.webp');
}

function category_image_url(?string $image): string
{
    return resolve_image($image, 'categories', 'placeholder.webp');
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

/**
 * @return list<array<string, mixed>>
 */
function fallback_categories(): array
{
    return [
        ['id' => 1, 'name' => 'Пицца', 'slug' => 'pizza', 'image' => 'cat-pizza.svg', 'sort_order' => 1],
        ['id' => 2, 'name' => 'Бургеры', 'slug' => 'burgers', 'image' => 'cat-burgers.svg', 'sort_order' => 2],
        ['id' => 3, 'name' => 'Шаурма', 'slug' => 'shawarma', 'image' => 'cat-shawarma.svg', 'sort_order' => 3],
        ['id' => 4, 'name' => 'Гриль', 'slug' => 'grill', 'image' => 'cat-grill.svg', 'sort_order' => 4],
        ['id' => 5, 'name' => 'Салаты', 'slug' => 'salads', 'image' => 'cat-salads.svg', 'sort_order' => 5],
        ['id' => 6, 'name' => 'Напитки', 'slug' => 'drinks', 'image' => 'cat-drinks.svg', 'sort_order' => 6],
        ['id' => 7, 'name' => 'Десерты', 'slug' => 'desserts', 'image' => 'cat-desserts.svg', 'sort_order' => 7],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function fallback_popular_dishes(): array
{
    return [
        ['id' => 1, 'category_id' => 1, 'category_name' => 'Пицца', 'category_slug' => 'pizza', 'name' => 'Пицца Маргарита', 'description' => 'Томатный соус, моцарелла, базилик и оливковое масло', 'price' => 690, 'old_price' => 790, 'weight' => '450 г', 'image' => 'pizza-margherita.svg', 'is_popular' => 1],
        ['id' => 2, 'category_id' => 1, 'category_name' => 'Пицца', 'category_slug' => 'pizza', 'name' => 'Пицца Пепперони', 'description' => 'Острая пепперони, сыр и фирменный томатный соус', 'price' => 820, 'old_price' => null, 'weight' => '480 г', 'image' => 'pizza-pepperoni.svg', 'is_popular' => 1],
        ['id' => 3, 'category_id' => 2, 'category_name' => 'Бургеры', 'category_slug' => 'burgers', 'name' => 'Классический бургер', 'description' => 'Говяжья котлета, сыр чеддер, соус и свежие овощи', 'price' => 640, 'old_price' => 720, 'weight' => '320 г', 'image' => 'burger-classic.svg', 'is_popular' => 1],
        ['id' => 4, 'category_id' => 2, 'category_name' => 'Бургеры', 'category_slug' => 'burgers', 'name' => 'Бургер с беконом', 'description' => 'Котлета, бекон, карамелизированный лук и соус BBQ', 'price' => 740, 'old_price' => null, 'weight' => '350 г', 'image' => 'burger-bacon.svg', 'is_popular' => 1],
        ['id' => 5, 'category_id' => 3, 'category_name' => 'Шаурма', 'category_slug' => 'shawarma', 'name' => 'Шаурма классическая', 'description' => 'Курица, овощи, соус и тонкий лаваш', 'price' => 420, 'old_price' => null, 'weight' => '380 г', 'image' => 'shawarma.svg', 'is_popular' => 1],
        ['id' => 6, 'category_id' => 4, 'category_name' => 'Гриль', 'category_slug' => 'grill', 'name' => 'Стейк на гриле', 'description' => 'Сочный стейк с овощами гриль и соусом', 'price' => 1890, 'old_price' => 2100, 'weight' => '280 г', 'image' => 'grill-steak.svg', 'is_popular' => 1],
        ['id' => 7, 'category_id' => 5, 'category_name' => 'Салаты', 'category_slug' => 'salads', 'name' => 'Салат Цезарь', 'description' => 'Курица, романо, пармезан и соус цезарь', 'price' => 560, 'old_price' => null, 'weight' => '260 г', 'image' => 'salad-caesar.svg', 'is_popular' => 1],
        ['id' => 8, 'category_id' => 7, 'category_name' => 'Десерты', 'category_slug' => 'desserts', 'name' => 'Шоколадный фондан', 'description' => 'Тёплый кекс с жидкой сердцевиной', 'price' => 590, 'old_price' => 650, 'weight' => '140 г', 'image' => 'fondant.svg', 'is_popular' => 1],
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function fallback_gallery(int $limit = 0): array
{
    $items = [
        ['id' => 1, 'title' => 'Интерьер зала', 'image' => 'gal-interior.svg', 'album' => 'interior'],
        ['id' => 2, 'title' => 'Пицца из печи', 'image' => 'gal-pizza.svg', 'album' => 'dishes'],
        ['id' => 3, 'title' => 'Горячее блюдо', 'image' => 'gal-hot.svg', 'album' => 'dishes'],
        ['id' => 4, 'title' => 'Авторские напитки', 'image' => 'gal-drinks.svg', 'album' => 'drinks'],
        ['id' => 5, 'title' => 'Десерт дня', 'image' => 'gal-dessert.svg', 'album' => 'dishes'],
        ['id' => 6, 'title' => 'Команда ресторана', 'image' => 'gal-team.svg', 'album' => 'team'],
        ['id' => 7, 'title' => 'Вечернее событие', 'image' => 'gal-event.svg', 'album' => 'events'],
        ['id' => 8, 'title' => 'Детали сервировки', 'image' => 'gal-table.svg', 'album' => 'interior'],
    ];

    return $limit > 0 ? array_slice($items, 0, $limit) : $items;
}

function gallery_album_label(string $album): string
{
    return match ($album) {
        'interior' => 'Интерьер',
        'dishes' => 'Блюда',
        'drinks' => 'Напитки',
        'team' => 'Команда',
        'events' => 'События',
        default => 'Все',
    };
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_categories(): array
{
    return get_menu_categories();
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_popular_dishes(int $limit = 8): array
{
    $items = array_values(array_filter(
        get_all_dishes(),
        static fn(array $d): bool => (int) ($d['is_popular'] ?? 0) === 1 && (int) ($d['is_available'] ?? 0) === 1
    ));

    return array_slice($items, 0, $limit);
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_gallery(?string $album = null, int $limit = 0): array
{
    $items = [
        ['id' => 1, 'title' => 'Интерьер зала', 'image' => 'gal-interior.webp', 'album' => 'interior'],
        ['id' => 2, 'title' => 'Пицца из печи', 'image' => 'gal-pizza.webp', 'album' => 'dishes'],
        ['id' => 3, 'title' => 'Горячее блюдо', 'image' => 'gal-hot.webp', 'album' => 'dishes'],
        ['id' => 4, 'title' => 'Авторские напитки', 'image' => 'gal-drinks.webp', 'album' => 'drinks'],
        ['id' => 5, 'title' => 'Десерт дня', 'image' => 'gal-dessert.webp', 'album' => 'dishes'],
        ['id' => 6, 'title' => 'Команда ресторана', 'image' => 'gal-team.webp', 'album' => 'team'],
        ['id' => 7, 'title' => 'Вечернее событие', 'image' => 'gal-event.webp', 'album' => 'events'],
        ['id' => 8, 'title' => 'Детали сервировки', 'image' => 'gal-table.webp', 'album' => 'interior'],
    ];

    if ($album && $album !== 'all') {
        $items = array_values(array_filter($items, static fn(array $item): bool => $item['album'] === $album));
    }

    return $limit > 0 ? array_slice($items, 0, $limit) : $items;
}
