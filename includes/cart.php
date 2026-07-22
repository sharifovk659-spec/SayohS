<?php

declare(strict_types=1);

/**
 * Cart helpers — session for guests, DB sync for logged-in users.
 * Prices are always taken from the server (dishes table), never from the client.
 */

const CART_MAX_QTY = 20;

function cart_session_token(): string
{
    if (empty($_SESSION['cart_token']) || !is_string($_SESSION['cart_token'])) {
        $_SESSION['cart_token'] = bin2hex(random_bytes(16));
    }
    return (string) $_SESSION['cart_token'];
}

/**
 * @return array{items: list<array<string,mixed>>, subtotal: float, delivery_fee: float, total: float, count: int}
 */
function cart_empty_state(): array
{
    return [
        'items' => [],
        'subtotal' => 0.0,
        'delivery_fee' => 0.0,
        'total' => 0.0,
        'count' => 0,
    ];
}

function cart_max_qty(): int
{
    $v = (int) (setting('cart_max_qty') ?: CART_MAX_QTY);
    return max(1, min(50, $v));
}

function cart_delivery_fee(float $subtotal): float
{
    $fee = (float) (setting('delivery_fee') ?: 50);
    $freeFrom = (float) (setting('delivery_free_from') ?: 1500);
    if ($freeFrom > 0 && $subtotal >= $freeFrom) {
        return 0.0;
    }
    return max(0.0, $fee);
}

function cart_get_or_create_db(?int $userId, ?string $sessionToken): ?int
{
    try {
        if ($userId) {
            $stmt = db()->prepare('SELECT id FROM carts WHERE user_id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int) $id;
            }
            $ins = db()->prepare('INSERT INTO carts (user_id, session_token) VALUES (?, NULL)');
            $ins->execute([$userId]);
            return (int) db()->lastInsertId();
        }
        if ($sessionToken) {
            $stmt = db()->prepare('SELECT id FROM carts WHERE session_token = ? LIMIT 1');
            $stmt->execute([$sessionToken]);
            $id = $stmt->fetchColumn();
            if ($id) {
                return (int) $id;
            }
            $ins = db()->prepare('INSERT INTO carts (user_id, session_token) VALUES (NULL, ?)');
            $ins->execute([$sessionToken]);
            return (int) db()->lastInsertId();
        }
    } catch (Throwable $e) {
        storage_log('cart_get_or_create_db: ' . $e->getMessage());
    }
    return null;
}

function cart_cookie_name(): string
{
    return 'sayoh_cart';
}

/**
 * Normalize cart map: [dish_id => qty]
 *
 * @param mixed $raw
 * @return array<int,int>
 */
function cart_normalize_map(mixed $raw): array
{
    if (!is_array($raw)) {
        return [];
    }
    $out = [];
    foreach ($raw as $dishId => $qty) {
        $id = (int) $dishId;
        $q = (int) $qty;
        if ($id > 0 && $q > 0) {
            $out[$id] = min(cart_max_qty(), $q);
        }
    }
    return $out;
}

/**
 * Session + cookie cart (cookie needed on Vercel where /tmp sessions do not stick).
 *
 * @return array<int,int>
 */
function cart_session_map(): array
{
    $raw = $_SESSION['cart'] ?? null;
    if (!is_array($raw) || $raw === []) {
        $cookie = $_COOKIE[cart_cookie_name()] ?? '';
        if (is_string($cookie) && $cookie !== '') {
            $decoded = json_decode($cookie, true);
            if (is_array($decoded)) {
                $raw = $decoded;
                $_SESSION['cart'] = cart_normalize_map($decoded);
            }
        }
    }
    return cart_normalize_map($raw);
}

function cart_persist_session(array $map): void
{
    $map = cart_normalize_map($map);
    $_SESSION['cart'] = $map;
    $payload = json_encode($map, JSON_UNESCAPED_UNICODE);
    if ($payload === false) {
        $payload = '{}';
    }
    $https = function_exists('request_is_https') ? request_is_https() : false;
    @setcookie(cart_cookie_name(), $payload, [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    $_COOKIE[cart_cookie_name()] = $payload;
}

function cart_fetch_dish(int $dishId): ?array
{
    if ($dishId <= 0) {
        return null;
    }

    if (db_available()) {
        try {
            $stmt = db()->prepare(
                'SELECT id, name, slug, price, is_available, image, short_description
                 FROM dishes WHERE id = ? LIMIT 1'
            );
            $stmt->execute([$dishId]);
            $row = $stmt->fetch();
            if ($row) {
                return $row;
            }
        } catch (Throwable $e) {
            storage_log('cart_fetch_dish db: ' . $e->getMessage());
        }
    }

    // Fallback catalog (Vercel / no MySQL) so add-to-cart works on phone
    if (!function_exists('catalog_data')) {
        require_once __DIR__ . '/catalog.php';
    }
    foreach (catalog_data()['dishes'] as $dish) {
        if ((int) ($dish['id'] ?? 0) !== $dishId) {
            continue;
        }
        return [
            'id' => (int) $dish['id'],
            'name' => (string) ($dish['name'] ?? ''),
            'slug' => (string) ($dish['slug'] ?? ''),
            'price' => (float) ($dish['price'] ?? 0),
            'is_available' => (int) ($dish['is_available'] ?? 1),
            'image' => $dish['image'] ?? null,
            'short_description' => (string) ($dish['description'] ?? $dish['short_description'] ?? ''),
        ];
    }

    return null;
}

function cart_add(int $dishId, int $qty = 1): bool
{
    $qty = max(1, min(cart_max_qty(), $qty));
    $dish = cart_fetch_dish($dishId);
    if (!$dish || (int) $dish['is_available'] !== 1) {
        return false;
    }

    if (user_logged_in() && db_available()) {
        $cartId = cart_get_or_create_db((int) $_SESSION['user_id'], null);
        if ($cartId) {
            try {
                $stmt = db()->prepare('SELECT id, quantity FROM cart_items WHERE cart_id = ? AND dish_id = ? LIMIT 1');
                $stmt->execute([$cartId, $dishId]);
                $existing = $stmt->fetch();
                $price = (float) $dish['price'];
                if ($existing) {
                    $newQty = min(cart_max_qty(), (int) $existing['quantity'] + $qty);
                    db()->prepare('UPDATE cart_items SET quantity = ?, unit_price = ?, updated_at = NOW() WHERE id = ?')
                        ->execute([$newQty, $price, (int) $existing['id']]);
                } else {
                    db()->prepare(
                        'INSERT INTO cart_items (cart_id, dish_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
                    )->execute([$cartId, $dishId, $qty, $price]);
                }
                db()->prepare('UPDATE carts SET updated_at = NOW() WHERE id = ?')->execute([$cartId]);
                return true;
            } catch (Throwable $e) {
                storage_log('cart_add db: ' . $e->getMessage());
                // fall through to session/cookie cart
            }
        }
    }

    $map = cart_session_map();
    $map[$dishId] = min(cart_max_qty(), ($map[$dishId] ?? 0) + $qty);
    cart_persist_session($map);
    return true;
}

function cart_set_qty(int $dishId, int $qty): bool
{
    $dish = cart_fetch_dish($dishId);
    if (!$dish) {
        return false;
    }
    if ($qty <= 0) {
        return cart_remove($dishId);
    }
    $qty = min(cart_max_qty(), $qty);
    if ((int) $dish['is_available'] !== 1) {
        return false;
    }

    if (user_logged_in() && db_available()) {
        $cartId = cart_get_or_create_db((int) $_SESSION['user_id'], null);
        if ($cartId) {
            try {
                $price = (float) $dish['price'];
                db()->prepare(
                    'INSERT INTO cart_items (cart_id, dish_id, quantity, unit_price) VALUES (?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE quantity = VALUES(quantity), unit_price = VALUES(unit_price), updated_at = NOW()'
                )->execute([$cartId, $dishId, $qty, $price]);
                return true;
            } catch (Throwable $e) {
                storage_log('cart_set_qty: ' . $e->getMessage());
            }
        }
    }

    $map = cart_session_map();
    $map[$dishId] = $qty;
    cart_persist_session($map);
    return true;
}

function cart_remove(int $dishId): bool
{
    if (user_logged_in() && db_available()) {
        try {
            $cartId = cart_get_or_create_db((int) $_SESSION['user_id'], null);
            if ($cartId) {
                db()->prepare('DELETE FROM cart_items WHERE cart_id = ? AND dish_id = ?')->execute([$cartId, $dishId]);
                return true;
            }
        } catch (Throwable $e) {
            // fall through
        }
    }
    $map = cart_session_map();
    unset($map[$dishId]);
    cart_persist_session($map);
    return true;
}

function cart_clear(): void
{
    if (user_logged_in() && db_available()) {
        try {
            $cartId = cart_get_or_create_db((int) $_SESSION['user_id'], null);
            if ($cartId) {
                db()->prepare('DELETE FROM cart_items WHERE cart_id = ?')->execute([$cartId]);
            }
        } catch (Throwable $e) {
            storage_log('cart_clear: ' . $e->getMessage());
        }
    }
    unset($_SESSION['cart']);
    cart_persist_session([]);
}

/**
 * @return array{items: list<array<string,mixed>>, subtotal: float, delivery_fee: float, total: float, count: int}
 */
function cart_snapshot(bool $includeDelivery = true): array
{
    $items = [];
    $subtotal = 0.0;
    $count = 0;

    try {
        $usedDb = false;
        if (user_logged_in() && db_available()) {
            $cartId = cart_get_or_create_db((int) $_SESSION['user_id'], null);
            if ($cartId) {
                $stmt = db()->prepare(
                    'SELECT ci.dish_id, ci.quantity, d.name, d.slug, d.price, d.is_available, d.image, d.short_description
                     FROM cart_items ci
                     INNER JOIN dishes d ON d.id = ci.dish_id
                     WHERE ci.cart_id = ?
                     ORDER BY ci.id ASC'
                );
                $stmt->execute([$cartId]);
                $rows = $stmt->fetchAll();
                foreach ($rows as $row) {
                    if ((int) $row['is_available'] !== 1) {
                        continue;
                    }
                    $qty = (int) $row['quantity'];
                    $price = (float) $row['price']; // authoritative
                    $line = $qty * $price;
                    $dish = apply_dish_translation(normalize_dish_row([
                        'id' => (int) $row['dish_id'],
                        'name' => $row['name'],
                        'slug' => $row['slug'],
                        'short_description' => $row['short_description'],
                        'price' => $price,
                        'image' => $row['image'],
                        'is_available' => 1,
                    ]));
                    $items[] = [
                        'dish_id' => (int) $row['dish_id'],
                        'quantity' => $qty,
                        'unit_price' => $price,
                        'line_total' => $line,
                        'dish' => $dish,
                    ];
                    $subtotal += $line;
                    $count += $qty;
                }
                $usedDb = true;
            }
        }
        if (!$usedDb) {
            foreach (cart_session_map() as $dishId => $qty) {
                $dish = cart_fetch_dish($dishId);
                if (!$dish || (int) $dish['is_available'] !== 1) {
                    continue;
                }
                $price = (float) $dish['price'];
                $line = $qty * $price;
                $dishN = apply_dish_translation(normalize_dish_row($dish));
                $items[] = [
                    'dish_id' => $dishId,
                    'quantity' => $qty,
                    'unit_price' => $price,
                    'line_total' => $line,
                    'dish' => $dishN,
                ];
                $subtotal += $line;
                $count += $qty;
            }
        }
    } catch (Throwable $e) {
        storage_log('cart_snapshot: ' . $e->getMessage());
        return cart_empty_state();
    }

    $delivery = $includeDelivery ? cart_delivery_fee($subtotal) : 0.0;
    return [
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'delivery_fee' => round($delivery, 2),
        'total' => round($subtotal + $delivery, 2),
        'count' => $count,
    ];
}

function cart_count(): int
{
    return cart_snapshot(false)['count'];
}

function cart_merge_guest(int $userId): void
{
    $map = cart_session_map();
    if ($map === []) {
        // Still try attach session cart from DB if any
        $token = cart_session_token();
        try {
            $stmt = db()->prepare('SELECT id FROM carts WHERE session_token = ? LIMIT 1');
            $stmt->execute([$token]);
            $guestCartId = $stmt->fetchColumn();
            $userCartId = cart_get_or_create_db($userId, null);
            if ($guestCartId && $userCartId && (int) $guestCartId !== (int) $userCartId) {
                $items = db()->prepare('SELECT dish_id, quantity, unit_price FROM cart_items WHERE cart_id = ?');
                $items->execute([(int) $guestCartId]);
                foreach ($items->fetchAll() as $row) {
                    cart_add((int) $row['dish_id'], (int) $row['quantity']);
                }
                db()->prepare('DELETE FROM carts WHERE id = ?')->execute([(int) $guestCartId]);
            }
        } catch (Throwable $e) {
            storage_log('cart_merge_guest token: ' . $e->getMessage());
        }
        unset($_SESSION['cart']);
        return;
    }

    foreach ($map as $dishId => $qty) {
        // temporarily ensure user context
        $_SESSION['user_id'] = $userId;
        cart_add((int) $dishId, (int) $qty);
    }
    unset($_SESSION['cart']);
}
