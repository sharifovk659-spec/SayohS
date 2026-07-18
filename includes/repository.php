<?php

declare(strict_types=1);

/**
 * Public data access layer. Uses PDO when MySQL is available; otherwise catalog fallbacks.
 */

function ensure_catalog_fallback(): void
{
    if (!function_exists('catalog_data')) {
        require_once __DIR__ . '/catalog.php';
    }
}

/**
 * Map DB dish columns to template-friendly keys.
 *
 * @param array<string, mixed> $row
 * @return array<string, mixed>
 */
function normalize_dish_row(array $row): array
{
    if (array_key_exists('short_description', $row)) {
        $row['full_description'] = $row['full_description'] ?? (string) ($row['description'] ?? '');
        $row['description'] = (string) ($row['short_description'] ?? '');
    } elseif (!array_key_exists('full_description', $row) && array_key_exists('description', $row)) {
        $row['full_description'] = (string) $row['description'];
    }

    $row['id'] = (int) ($row['id'] ?? 0);
    $row['category_id'] = (int) ($row['category_id'] ?? 0);
    $row['price'] = (float) ($row['price'] ?? 0);
    $row['old_price'] = isset($row['old_price']) && $row['old_price'] !== '' && $row['old_price'] !== null
        ? (float) $row['old_price']
        : null;
    $row['is_popular'] = (int) ($row['is_popular'] ?? 0);
    $row['is_available'] = (int) ($row['is_available'] ?? 1);
    $row['sort_order'] = (int) ($row['sort_order'] ?? 0);

    return $row;
}

/**
 * @return list<array<string, mixed>>
 */

function map_dish_public(array $row): array
{
    $row = normalize_dish_row($row);
    if (function_exists('apply_dish_translation')) {
        $row = apply_dish_translation($row);
    }
    if (!empty($row['category_id']) && function_exists('apply_category_translation') && isset($row['category_name'])) {
        $cat = apply_category_translation([
            'id' => (int) $row['category_id'],
            'name' => (string) $row['category_name'],
        ]);
        $row['category_name'] = $cat['name'] ?? $row['category_name'];
    }
    return $row;
}

function map_category_public(array $row): array
{
    if (function_exists('apply_category_translation')) {
        $row = apply_category_translation($row);
    }
    return $row;
}

function cached_active_categories(): array
{
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }

    if (!db_available()) {
        ensure_catalog_fallback();
        $cache = array_map('map_category_public', catalog_data()['categories']);
        return $cache;
    }

    try {
        $stmt = db()->query(
            'SELECT id, name, slug, description, image, sort_order, is_active
             FROM categories
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT 50'
        );
        $cache = array_map('map_category_public', $stmt->fetchAll());
    } catch (Throwable $e) {
        storage_log('cached_active_categories: ' . $e->getMessage());
        ensure_catalog_fallback();
        $cache = array_map('map_category_public', catalog_data()['categories']);
    }

    return $cache;
}

/**
 * @return list<array<string, mixed>>
 */
function get_menu_categories(): array
{
    return cached_active_categories();
}

/**
 * @return list<array<string, mixed>>
 */
function get_all_dishes(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!db_available()) {
        ensure_catalog_fallback();
        $cache = array_map('map_dish_public', catalog_data()['dishes']);
        return $cache;
    }

    try {
        $stmt = db()->query(
            'SELECT d.*, c.name AS category_name, c.slug AS category_slug
             FROM dishes d
             INNER JOIN categories c ON c.id = d.category_id
             WHERE d.is_available = 1
             ORDER BY d.sort_order ASC, d.id ASC'
        );
        $cache = array_map('map_dish_public', $stmt->fetchAll());
    } catch (Throwable $e) {
        storage_log('get_all_dishes: ' . $e->getMessage());
        ensure_catalog_fallback();
        $cache = array_map('map_dish_public', catalog_data()['dishes']);
    }

    return $cache;
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_categories(int $limit = 7): array
{
    $items = cached_active_categories();
    if ($limit > 0) {
        return array_slice($items, 0, $limit);
    }
    return $items;
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_popular_dishes(int $limit = 8): array
{
    $limit = max(1, min(50, $limit));

    if (!db_available()) {
        ensure_catalog_fallback();
        $items = array_values(array_filter(
            catalog_data()['dishes'],
            static fn(array $d): bool => (int) ($d['is_popular'] ?? 0) === 1 && (int) ($d['is_available'] ?? 0) === 1
        ));
        return array_map('map_dish_public', array_slice($items, 0, $limit));
    }

    try {
        $stmt = db()->prepare(
            'SELECT d.*, c.name AS category_name, c.slug AS category_slug
             FROM dishes d
             INNER JOIN categories c ON c.id = d.category_id
             WHERE d.is_popular = 1 AND d.is_available = 1 AND c.is_active = 1
             ORDER BY d.sort_order ASC, d.id ASC
             LIMIT :lim'
        );
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('map_dish_public', $stmt->fetchAll());
    } catch (Throwable $e) {
        storage_log('fetch_popular_dishes: ' . $e->getMessage());
        ensure_catalog_fallback();
        $items = array_values(array_filter(
            catalog_data()['dishes'],
            static fn(array $d): bool => (int) ($d['is_popular'] ?? 0) === 1 && (int) ($d['is_available'] ?? 0) === 1
        ));
        return array_map('map_dish_public', array_slice($items, 0, $limit));
    }
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_gallery(?string $type = null, int $limit = 0): array
{
    $fallback = static function (?string $type, int $limit): array {
        $items = [
            ['id' => 1, 'title' => 'Интерьер зала', 'image' => 'gal-interior.webp', 'album' => 'interior', 'type' => 'interior'],
            ['id' => 2, 'title' => 'Пицца из печи', 'image' => 'gal-pizza.webp', 'album' => 'dishes', 'type' => 'dishes'],
            ['id' => 3, 'title' => 'Горячее блюдо', 'image' => 'gal-hot.webp', 'album' => 'dishes', 'type' => 'dishes'],
            ['id' => 4, 'title' => 'Авторские напитки', 'image' => 'gal-drinks.webp', 'album' => 'drinks', 'type' => 'drinks'],
            ['id' => 5, 'title' => 'Десерт дня', 'image' => 'gal-dessert.webp', 'album' => 'dishes', 'type' => 'dishes'],
            ['id' => 6, 'title' => 'Команда ресторана', 'image' => 'gal-team.webp', 'album' => 'team', 'type' => 'team'],
            ['id' => 7, 'title' => 'Вечернее событие', 'image' => 'gal-event.webp', 'album' => 'events', 'type' => 'events'],
            ['id' => 8, 'title' => 'Детали сервировки', 'image' => 'gal-table.webp', 'album' => 'interior', 'type' => 'interior'],
        ];
        if ($type !== null && $type !== '' && $type !== 'all') {
            $items = array_values(array_filter($items, static fn(array $i): bool => ($i['album'] ?? '') === $type));
        }
        return $limit > 0 ? array_slice($items, 0, $limit) : $items;
    };

    if (!db_available()) {
        return $fallback($type, $limit);
    }

    try {
        $sql = 'SELECT id, title, image, type, sort_order, is_active
                FROM gallery
                WHERE is_active = 1';
        $params = [];
        if ($type !== null && $type !== '' && $type !== 'all') {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }
        $sql .= ' ORDER BY sort_order ASC, id ASC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $stmt = db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return array_map(static function (array $row): array {
            $row['album'] = (string) ($row['type'] ?? 'interior');
            return $row;
        }, $rows);
    } catch (Throwable $e) {
        storage_log('fetch_gallery: ' . $e->getMessage());
        return $fallback($type, $limit);
    }
}

/**
 * @return array<string, mixed>|null
 */
function find_dish_by_slug(string $slug): ?array
{
    $slug = trim($slug);
    if ($slug === '') {
        return null;
    }

    if (!db_available()) {
        ensure_catalog_fallback();
        foreach (catalog_data()['dishes'] as $dish) {
            if (($dish['slug'] ?? '') === $slug && (int) ($dish['is_available'] ?? 1) === 1) {
                return map_dish_public($dish);
            }
        }
        return null;
    }

    try {
        $stmt = db()->prepare(
            'SELECT d.*, c.name AS category_name, c.slug AS category_slug
             FROM dishes d
             INNER JOIN categories c ON c.id = d.category_id
             WHERE d.slug = :slug AND d.is_available = 1
             LIMIT 1'
        );
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch();
        return $row ? map_dish_public($row) : null;
    } catch (Throwable $e) {
        storage_log('find_dish_by_slug: ' . $e->getMessage());
        ensure_catalog_fallback();
        foreach (catalog_data()['dishes'] as $dish) {
            if (($dish['slug'] ?? '') === $slug) {
                return map_dish_public($dish);
            }
        }
        return null;
    }
}

/**
 * @param array<string, mixed> $filters
 * @return array{items: list<array<string,mixed>>, total: int, page: int, pages: int}
 */
function filter_menu_dishes(array $filters): array
{
    $q = trim((string) ($filters['q'] ?? ''));
    $category = (string) ($filters['category'] ?? 'all');
    $sort = (string) ($filters['sort'] ?? 'default');
    $popular = !empty($filters['popular']);
    $available = !empty($filters['available']);
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = max(1, (int) ($filters['per_page'] ?? app_config('per_page', 8)));

    $allowedSort = ['default', 'price_asc', 'price_desc'];
    if (!in_array($sort, $allowedSort, true)) {
        $sort = 'default';
    }

    if (!db_available()) {
        ensure_catalog_fallback();
        $items = array_values(array_filter(catalog_data()['dishes'], static function (array $dish) use ($q, $category, $popular, $available): bool {
            if ($category !== 'all' && ($dish['category_slug'] ?? '') !== $category) {
                return false;
            }
            if ($popular && (int) ($dish['is_popular'] ?? 0) !== 1) {
                return false;
            }
            if ($available && (int) ($dish['is_available'] ?? 0) !== 1) {
                return false;
            }
            if ($q !== '') {
                $hay = mb_strtolower(($dish['name'] ?? '') . ' ' . ($dish['description'] ?? '') . ' ' . ($dish['ingredients'] ?? ''));
                if (!str_contains($hay, mb_strtolower($q))) {
                    return false;
                }
            }
            return true;
        }));

        usort($items, static function (array $a, array $b) use ($sort): int {
            return match ($sort) {
                'price_asc' => ((float) $a['price'] <=> (float) $b['price']) ?: ((int) $a['id'] <=> (int) $b['id']),
                'price_desc' => ((float) $b['price'] <=> (float) $a['price']) ?: ((int) $a['id'] <=> (int) $b['id']),
                default => ((int) ($b['is_popular'] ?? 0) <=> (int) ($a['is_popular'] ?? 0))
                    ?: ((int) ($a['sort_order'] ?? 0) <=> (int) ($b['sort_order'] ?? 0))
                    ?: ((int) $a['id'] <=> (int) $b['id']),
            };
        });

        $total = count($items);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);

        return [
            'items' => array_map('map_dish_public', array_slice($items, ($page - 1) * $perPage, $perPage)),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    }

    try {
        $where = ['1=1'];
        $params = [];

        if ($available) {
            $where[] = 'd.is_available = 1';
        }
        if ($popular) {
            $where[] = 'd.is_popular = 1';
        }
        if ($category !== '' && $category !== 'all') {
            $where[] = 'c.slug = :category';
            $params['category'] = $category;
        }
        if ($q !== '') {
            $where[] = '(d.name LIKE :q OR d.short_description LIKE :q OR d.ingredients LIKE :q)';
            $params['q'] = '%' . $q . '%';
        }

        $orderBy = match ($sort) {
            'price_asc' => 'd.price ASC, d.id ASC',
            'price_desc' => 'd.price DESC, d.id ASC',
            default => 'd.is_popular DESC, d.sort_order ASC, d.id ASC',
        };

        $whereSql = implode(' AND ', $where);

        $countStmt = db()->prepare(
            "SELECT COUNT(*) FROM dishes d
             INNER JOIN categories c ON c.id = d.category_id
             WHERE {$whereSql}"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min($page, $pages);
        $offset = ($page - 1) * $perPage;

        $stmt = db()->prepare(
            "SELECT d.*, c.name AS category_name, c.slug AS category_slug
             FROM dishes d
             INNER JOIN categories c ON c.id = d.category_id
             WHERE {$whereSql}
             ORDER BY {$orderBy}
             LIMIT :lim OFFSET :off"
        );
        foreach ($params as $k => $v) {
            $stmt->bindValue(':' . $k, $v);
        }
        $stmt->bindValue(':lim', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => array_map('map_dish_public', $stmt->fetchAll()),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
        ];
    } catch (Throwable $e) {
        storage_log('filter_menu_dishes: ' . $e->getMessage());
        return [
            'items' => [],
            'total' => 0,
            'page' => 1,
            'pages' => 1,
        ];
    }
}

/**
 * @param array<string, mixed> $dish
 * @return list<array<string, mixed>>
 */
function related_dishes(array $dish, int $limit = 4): array
{
    $limit = max(1, min(20, $limit));
    $slug = (string) ($dish['slug'] ?? '');
    $categoryId = (int) ($dish['category_id'] ?? 0);
    $categorySlug = (string) ($dish['category_slug'] ?? '');

    if (!db_available()) {
        ensure_catalog_fallback();
        $items = [];
        foreach (catalog_data()['dishes'] as $item) {
            if (($item['slug'] ?? '') === $slug) {
                continue;
            }
            if ((int) ($item['is_available'] ?? 1) !== 1) {
                continue;
            }
            if ($categorySlug !== '' && ($item['category_slug'] ?? '') === $categorySlug) {
                $items[] = map_dish_public($item);
            }
            if (count($items) >= $limit) {
                break;
            }
        }
        return $items;
    }

    try {
        if ($categoryId > 0) {
            $stmt = db()->prepare(
                'SELECT d.*, c.name AS category_name, c.slug AS category_slug
                 FROM dishes d
                 INNER JOIN categories c ON c.id = d.category_id
                 WHERE d.category_id = :cid AND d.is_available = 1 AND d.slug <> :slug
                 ORDER BY d.sort_order ASC, d.id ASC
                 LIMIT :lim'
            );
            $stmt->bindValue(':cid', $categoryId, PDO::PARAM_INT);
            $stmt->bindValue(':slug', $slug);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return array_map('map_dish_public', $stmt->fetchAll());
        }

        $stmt = db()->prepare(
            'SELECT d.*, c.name AS category_name, c.slug AS category_slug
             FROM dishes d
             INNER JOIN categories c ON c.id = d.category_id
             WHERE c.slug = :cslug AND d.is_available = 1 AND d.slug <> :slug
             ORDER BY d.sort_order ASC, d.id ASC
             LIMIT :lim'
        );
        $stmt->bindValue(':cslug', $categorySlug);
        $stmt->bindValue(':slug', $slug);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return array_map('map_dish_public', $stmt->fetchAll());
    } catch (Throwable $e) {
        storage_log('related_dishes: ' . $e->getMessage());
        return [];
    }
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_opening_hours(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!db_available()) {
        $cache = [
            ['day_number' => 1, 'day_name' => 'Понедельник', 'time_from' => '12:00:00', 'time_to' => '23:00:00', 'is_closed' => 0],
            ['day_number' => 2, 'day_name' => 'Вторник', 'time_from' => '12:00:00', 'time_to' => '23:00:00', 'is_closed' => 0],
            ['day_number' => 3, 'day_name' => 'Среда', 'time_from' => '12:00:00', 'time_to' => '23:00:00', 'is_closed' => 0],
            ['day_number' => 4, 'day_name' => 'Четверг', 'time_from' => '12:00:00', 'time_to' => '23:00:00', 'is_closed' => 0],
            ['day_number' => 5, 'day_name' => 'Пятница', 'time_from' => '12:00:00', 'time_to' => '00:00:00', 'is_closed' => 0],
            ['day_number' => 6, 'day_name' => 'Суббота', 'time_from' => '12:00:00', 'time_to' => '00:00:00', 'is_closed' => 0],
            ['day_number' => 7, 'day_name' => 'Воскресенье', 'time_from' => '12:00:00', 'time_to' => '23:00:00', 'is_closed' => 0],
        ];
        return $cache;
    }

    try {
        $stmt = db()->query(
            'SELECT day_number, day_name, time_from, time_to, is_closed, sort_order
             FROM opening_hours
             ORDER BY sort_order ASC, day_number ASC'
        );
        $cache = $stmt->fetchAll();
    } catch (Throwable $e) {
        storage_log('fetch_opening_hours: ' . $e->getMessage());
        $cache = [];
    }

    return $cache;
}

/**
 * @return list<array<string, mixed>>
 */
function fetch_social_links(): array
{
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    if (!db_available()) {
        $cache = [
            ['platform' => 'WhatsApp', 'url' => (string) app_config('whatsapp', '#'), 'icon' => 'whatsapp', 'is_active' => 1],
            ['platform' => 'Instagram', 'url' => (string) app_config('instagram', '#'), 'icon' => 'instagram', 'is_active' => 1],
            ['platform' => 'Facebook', 'url' => (string) app_config('facebook', '#'), 'icon' => 'facebook', 'is_active' => 1],
            ['platform' => 'TikTok', 'url' => (string) app_config('tiktok', '#'), 'icon' => 'tiktok', 'is_active' => 1],
        ];
        return $cache;
    }

    try {
        $stmt = db()->query(
            'SELECT id, platform, url, icon, is_active, sort_order
             FROM social_links
             WHERE is_active = 1
             ORDER BY sort_order ASC, id ASC'
        );
        $cache = $stmt->fetchAll();
    } catch (Throwable $e) {
        storage_log('fetch_social_links: ' . $e->getMessage());
        $cache = [];
    }

    return $cache;
}

/**
 * @return array<string, mixed>|null
 */
function fetch_page(string $pageKey): ?array
{
    $pageKey = trim($pageKey);
    if ($pageKey === '') {
        return null;
    }

    static $cache = [];
    if (array_key_exists($pageKey, $cache)) {
        return $cache[$pageKey];
    }

    if (!db_available()) {
        $cache[$pageKey] = null;
        return null;
    }

    try {
        $stmt = db()->prepare(
            'SELECT page_key, title, subtitle, content, image, video_url, meta_title, meta_description
             FROM pages WHERE page_key = :k LIMIT 1'
        );
        $stmt->execute(['k' => $pageKey]);
        $row = $stmt->fetch();
        $cache[$pageKey] = $row ?: null;
    } catch (Throwable $e) {
        storage_log('fetch_page: ' . $e->getMessage());
        $cache[$pageKey] = null;
    }

    return $cache[$pageKey];
}

/**
 * Convert TIME / H:i string to minutes from midnight.
 */
function time_to_minutes(string $time): int
{
    $time = trim($time);
    if (preg_match('/^(\d{1,2}):(\d{2})(?::(\d{2}))?$/', $time, $m)) {
        return ((int) $m[1]) * 60 + (int) $m[2];
    }
    return -1;
}

function is_within_opening_hours(string $dateYmd, string $timeHi): bool
{
    $date = DateTime::createFromFormat('Y-m-d', $dateYmd);
    if (!$date || $date->format('Y-m-d') !== $dateYmd) {
        return false;
    }

    $minutes = time_to_minutes($timeHi);
    if ($minutes < 0) {
        return false;
    }

    $dayNumber = (int) $date->format('N'); // 1=Mon .. 7=Sun
    $hours = fetch_opening_hours();

    foreach ($hours as $row) {
        if ((int) ($row['day_number'] ?? 0) !== $dayNumber) {
            continue;
        }

        if ((int) ($row['is_closed'] ?? 0) === 1) {
            return false;
        }

        $from = time_to_minutes((string) ($row['time_from'] ?? ''));
        $toRaw = (string) ($row['time_to'] ?? '');
        $to = time_to_minutes($toRaw);

        if ($from < 0 || $to < 0) {
            return false;
        }

        // 00:00 closing means end of day (24:00)
        if ($to === 0) {
            $to = 24 * 60;
        }

        return $minutes >= $from && $minutes <= $to;
    }

    // No row for this day — allow if fallback hours text exists
    return $hours === [];
}
