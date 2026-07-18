<?php

declare(strict_types=1);

function hero_image_url(string $file = 'hero-main.webp'): string
{
    return resolve_image($file, 'hero', 'placeholder.webp');
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

/**
 * @return list<array<string, mixed>>
 */
function get_menu_categories(): array
{
    return catalog_data()['categories'];
}

/**
 * @return list<array<string, mixed>>
 */
function get_all_dishes(): array
{
    return catalog_data()['dishes'];
}

/**
 * @return array<string, mixed>|null
 */
function find_dish_by_slug(string $slug): ?array
{
    foreach (get_all_dishes() as $dish) {
        if (($dish['slug'] ?? '') === $slug) {
            return $dish;
        }
    }

    return null;
}

/**
 * @param array<string, mixed> $filters
 * @return array{items: list<array<string,mixed>>, total: int, page: int, pages: int}
 */
function filter_menu_dishes(array $filters): array
{
    $q = mb_strtolower(trim((string) ($filters['q'] ?? '')));
    $category = (string) ($filters['category'] ?? 'all');
    $sort = (string) ($filters['sort'] ?? 'default');
    $popular = !empty($filters['popular']);
    $available = !empty($filters['available']);
    $page = max(1, (int) ($filters['page'] ?? 1));
    $perPage = max(1, (int) ($filters['per_page'] ?? app_config('per_page', 8)));

    $items = array_values(array_filter(get_all_dishes(), static function (array $dish) use ($q, $category, $popular, $available): bool {
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
            if (!str_contains($hay, $q)) {
                return false;
            }
        }
        return true;
    }));

    usort($items, static function (array $a, array $b) use ($sort): int {
        return match ($sort) {
            'price_asc' => ((float) $a['price'] <=> (float) $b['price']) ?: ((int) $a['id'] <=> (int) $b['id']),
            'price_desc' => ((float) $b['price'] <=> (float) $a['price']) ?: ((int) $a['id'] <=> (int) $b['id']),
            default => ((int) ($b['is_popular'] ?? 0) <=> (int) ($a['is_popular'] ?? 0)) ?: ((int) $a['id'] <=> (int) $b['id']),
        };
    });

    $total = count($items);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);

    return [
        'items' => array_slice($items, ($page - 1) * $perPage, $perPage),
        'total' => $total,
        'page' => $page,
        'pages' => $pages,
    ];
}

/**
 * @return list<array<string, mixed>>
 */
function related_dishes(array $dish, int $limit = 4): array
{
    $slug = (string) ($dish['slug'] ?? '');
    $category = (string) ($dish['category_slug'] ?? '');
    $items = [];

    foreach (get_all_dishes() as $item) {
        if (($item['slug'] ?? '') === $slug) {
            continue;
        }
        if (($item['category_slug'] ?? '') === $category) {
            $items[] = $item;
        }
    }

    if (count($items) < $limit) {
        foreach (get_all_dishes() as $item) {
            if (($item['slug'] ?? '') === $slug) {
                continue;
            }
            $exists = false;
            foreach ($items as $existing) {
                if (($existing['slug'] ?? '') === ($item['slug'] ?? '')) {
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $items[] = $item;
            }
            if (count($items) >= $limit) {
                break;
            }
        }
    }

    return array_slice($items, 0, $limit);
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
