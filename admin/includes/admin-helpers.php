<?php

declare(strict_types=1);

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        flash('error', 'Метод не поддерживается.');
        redirect('admin/index.php');
    }
}

function require_csrf(): void
{
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Сессия устарела. Обновите страницу и повторите действие.');
        redirect('admin/index.php');
    }
}

function admin_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Новая',
        'confirmed' => 'Подтверждена',
        'cancelled' => 'Отменена',
        'completed' => 'Завершена',
        'read' => 'Прочитано',
        'answered' => 'Отвечено',
        default => $status,
    };
}

function gallery_type_label(string $type): string
{
    return gallery_album_label($type);
}

/**
 * @param array<string, scalar|null> $params
 */
function admin_url(string $path, array $params = []): string
{
    $path = ltrim(str_replace(["\r", "\n"], '', $path), '/');
    if (!str_starts_with($path, 'admin/')) {
        $path = 'admin/' . $path;
    }

    $url = base_url($path);
    if ($params === []) {
        return $url;
    }

    return $url . '?' . http_build_query($params);
}

/**
 * Safe relative redirect inside admin only.
 */
function admin_redirect(string $path): never
{
    $path = ltrim(str_replace(["\r", "\n", '\\'], '', $path), '/');
    if ($path === '' || str_contains($path, '..') || !str_starts_with($path, 'admin/')) {
        redirect('admin/index.php');
    }
    redirect($path);
}

function admin_pagination(int $page, int $pages, string $basePath, array $query = []): string
{
    if ($pages <= 1) {
        return '';
    }

    $html = '<nav class="admin-pagination" aria-label="Страницы">';
    for ($i = 1; $i <= $pages; $i++) {
        $q = $query;
        $q['page'] = $i;
        $cls = $i === $page ? ' is-active' : '';
        $html .= '<a class="btn btn-sm btn-light' . $cls . '" href="' . e(admin_url($basePath, $q)) . '">' . $i . '</a>';
    }
    $html .= '</nav>';

    return $html;
}

/**
 * Protect CSV cells from formula injection.
 */
function csv_safe(?string $value): string
{
    $value = (string) $value;
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }

    return $value;
}

function format_admin_price(float|string|null $price): string
{
    return format_price((float) $price);
}

/**
 * @throws RuntimeException
 */
function store_upload(array $file, string $folder, ?string $oldFile = null): string
{
    $result = upload_image($file, $folder, $oldFile);
    if (empty($result['ok'])) {
        throw new RuntimeException((string) ($result['error'] ?? 'Ошибка загрузки файла.'));
    }

    return (string) $result['file'];
}

function admin_image_src(string $folder, ?string $file, ?string $assetSubdir = null): ?string
{
    if ($file === null || $file === '') {
        return null;
    }

    $file = basename($file);
    $uploadPath = dirname(__DIR__, 2) . '/uploads/' . $folder . '/' . $file;
    if (is_file($uploadPath)) {
        return base_url('uploads/' . $folder . '/' . rawurlencode($file));
    }

    $subdir = $assetSubdir ?? $folder;
    $assetPath = dirname(__DIR__, 2) . '/assets/images/' . $subdir . '/' . $file;
    if (is_file($assetPath)) {
        return asset('images/' . $subdir . '/' . $file);
    }

    return null;
}

function admin_per_page(): int
{
    return max(5, min(100, (int) app_config('admin_per_page', 20)));
}

/**
 * Active categories for admin selects (id ASC).
 *
 * @return list<array<string, mixed>>
 */
function fetch_categories_list(): array
{
    try {
        $stmt = db()->query(
            'SELECT id, name, slug, is_active
             FROM categories
             ORDER BY sort_order ASC, id ASC'
        );
        return $stmt ? $stmt->fetchAll() : [];
    } catch (Throwable $e) {
        storage_log('fetch_categories_list: ' . $e->getMessage());
        return [];
    }
}

function order_status_label(string $status): string
{
    return match ($status) {
        'new' => 'Новый',
        'confirmed' => 'Подтверждён',
        'preparing' => 'Готовится',
        'ready' => 'Готов',
        'delivering' => 'В доставке',
        'completed' => 'Выполнен',
        'cancelled' => 'Отменён',
        default => $status,
    };
}

function payment_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Ожидает оплаты',
        'paid' => 'Оплачен',
        'failed' => 'Ошибка оплаты',
        'refunded' => 'Возврат',
        default => $status,
    };
}

function delivery_type_label(string $type): string
{
    return match ($type) {
        'delivery' => 'Доставка',
        'pickup' => 'Самовывоз',
        default => $type,
    };
}

function payment_method_label(string $method): string
{
    return match ($method) {
        'cash' => 'Наличные',
        'on_receipt' => 'При получении',
        default => $method,
    };
}

function user_status_label(string $status): string
{
    return match ($status) {
        'active' => 'Активен',
        'blocked' => 'Заблокирован',
        default => $status,
    };
}

function admin_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Администратор',
        'manager' => 'Менеджер',
        default => $role,
    };
}

/**
 * @return list<string>
 */
function order_statuses(): array
{
    return ['new', 'confirmed', 'preparing', 'ready', 'delivering', 'completed', 'cancelled'];
}

/**
 * @return list<string>
 */
function payment_statuses(): array
{
    return ['pending', 'paid', 'failed', 'refunded'];
}

/**
 * @param array<string, string|null> $fields
 */
function upsert_translation_row(string $table, array $uniqueKeys, array $fields): void
{
    $allowed = ['category_translations', 'dish_translations', 'page_translations'];
    if (!in_array($table, $allowed, true)) {
        throw new InvalidArgumentException('Invalid translation table.');
    }

    $where = [];
    $params = [];
    foreach ($uniqueKeys as $col => $val) {
        $where[] = "{$col} = ?";
        $params[] = $val;
    }

    $check = db()->prepare('SELECT id FROM ' . $table . ' WHERE ' . implode(' AND ', $where) . ' LIMIT 1');
    $check->execute($params);
    $existingId = $check->fetchColumn();

    if ($existingId) {
        $sets = [];
        $updParams = [];
        foreach ($fields as $col => $val) {
            $sets[] = "{$col} = ?";
            $updParams[] = $val;
        }
        $updParams[] = (int) $existingId;
        $upd = db()->prepare('UPDATE ' . $table . ' SET ' . implode(', ', $sets) . ' WHERE id = ?');
        $upd->execute($updParams);
    } else {
        $all = array_merge($uniqueKeys, $fields);
        $cols = array_keys($all);
        $placeholders = implode(', ', array_fill(0, count($cols), '?'));
        $ins = db()->prepare(
            'INSERT INTO ' . $table . ' (' . implode(', ', $cols) . ') VALUES (' . $placeholders . ')'
        );
        $ins->execute(array_values($all));
    }
}
