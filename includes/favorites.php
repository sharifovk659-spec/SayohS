<?php

declare(strict_types=1);

/**
 * Favorites: DB for logged-in users, session for guests.
 */

function favorites_session_ids(): array
{
    $ids = $_SESSION['favorites'] ?? [];
    if (!is_array($ids)) {
        return [];
    }
    return array_values(array_unique(array_map('intval', $ids)));
}

function favorites_count(): int
{
    if (user_logged_in()) {
        try {
            $stmt = db()->prepare('SELECT COUNT(*) FROM favorites WHERE user_id = ?');
            $stmt->execute([(int) $_SESSION['user_id']]);
            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            storage_log('favorites_count: ' . $e->getMessage());
            return 0;
        }
    }
    return count(favorites_session_ids());
}

function is_favorite(int $dishId): bool
{
    if ($dishId <= 0) {
        return false;
    }
    if (user_logged_in()) {
        try {
            $stmt = db()->prepare('SELECT 1 FROM favorites WHERE user_id = ? AND dish_id = ? LIMIT 1');
            $stmt->execute([(int) $_SESSION['user_id'], $dishId]);
            return (bool) $stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }
    return in_array($dishId, favorites_session_ids(), true);
}

function favorite_add(int $dishId): bool
{
    if ($dishId <= 0) {
        return false;
    }
    if (user_logged_in()) {
        try {
            $stmt = db()->prepare('INSERT IGNORE INTO favorites (user_id, dish_id) VALUES (?, ?)');
            $stmt->execute([(int) $_SESSION['user_id'], $dishId]);
            return true;
        } catch (Throwable $e) {
            storage_log('favorite_add: ' . $e->getMessage());
            return false;
        }
    }
    $ids = favorites_session_ids();
    if (!in_array($dishId, $ids, true)) {
        $ids[] = $dishId;
        $_SESSION['favorites'] = $ids;
    }
    return true;
}

function favorite_remove(int $dishId): bool
{
    if ($dishId <= 0) {
        return false;
    }
    if (user_logged_in()) {
        try {
            $stmt = db()->prepare('DELETE FROM favorites WHERE user_id = ? AND dish_id = ?');
            $stmt->execute([(int) $_SESSION['user_id'], $dishId]);
            return true;
        } catch (Throwable $e) {
            storage_log('favorite_remove: ' . $e->getMessage());
            return false;
        }
    }
    $_SESSION['favorites'] = array_values(array_filter(
        favorites_session_ids(),
        static fn(int $id): bool => $id !== $dishId
    ));
    return true;
}

function favorite_toggle(int $dishId): bool
{
    if (is_favorite($dishId)) {
        favorite_remove($dishId);
        return false;
    }
    favorite_add($dishId);
    return true;
}

function favorites_merge_guest(int $userId): void
{
    $ids = favorites_session_ids();
    if ($ids === []) {
        return;
    }
    try {
        $stmt = db()->prepare('INSERT IGNORE INTO favorites (user_id, dish_id) VALUES (?, ?)');
        foreach ($ids as $dishId) {
            $stmt->execute([$userId, $dishId]);
        }
        unset($_SESSION['favorites']);
    } catch (Throwable $e) {
        storage_log('favorites_merge_guest: ' . $e->getMessage());
    }
}

/**
 * @return list<array<string, mixed>>
 */
function favorites_list(int $limit = 100): array
{
    $limit = max(1, min(200, $limit));
    if (user_logged_in()) {
        try {
            $sql = 'SELECT d.*, c.name AS category_name, c.slug AS category_slug
                    FROM favorites f
                    INNER JOIN dishes d ON d.id = f.dish_id
                    LEFT JOIN categories c ON c.id = d.category_id
                    WHERE f.user_id = ?
                    ORDER BY f.created_at DESC
                    LIMIT ' . (int) $limit;
            $stmt = db()->prepare($sql);
            $stmt->execute([(int) $_SESSION['user_id']]);
            $rows = $stmt->fetchAll();
            return array_map(static function (array $row): array {
                $row = normalize_dish_row($row);
                return apply_dish_translation($row);
            }, $rows);
        } catch (Throwable $e) {
            storage_log('favorites_list: ' . $e->getMessage());
            return [];
        }
    }

    $ids = favorites_session_ids();
    if ($ids === []) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    try {
        $stmt = db()->prepare(
            "SELECT d.*, c.name AS category_name, c.slug AS category_slug
             FROM dishes d
             LEFT JOIN categories c ON c.id = d.category_id
             WHERE d.id IN ($placeholders)
             LIMIT $limit"
        );
        $stmt->execute($ids);
        $rows = $stmt->fetchAll();
        $byId = [];
        foreach ($rows as $row) {
            $row = apply_dish_translation(normalize_dish_row($row));
            $byId[(int) $row['id']] = $row;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    } catch (Throwable $e) {
        return [];
    }
}
