<?php

declare(strict_types=1);

/**
 * Order creation from cart snapshot.
 */

const ORDER_MAX_ATTEMPTS = 10;
const ORDER_RATE_WINDOW = 900;

function generate_order_number(): string
{
    $prefix = 'AR-' . date('Ymd') . '-';

    for ($attempt = 0; $attempt < 12; $attempt++) {
        $suffix = str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $number = $prefix . $suffix;
        try {
            $stmt = db()->prepare('SELECT 1 FROM orders WHERE order_number = ? LIMIT 1');
            $stmt->execute([$number]);
            if (!$stmt->fetchColumn()) {
                return $number;
            }
        } catch (Throwable $e) {
            storage_log('generate_order_number: ' . $e->getMessage());
            break;
        }
    }

    return $prefix . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
}

function order_rate_limited(): bool
{
    return auth_rate_limited('order_submit', ORDER_MAX_ATTEMPTS, ORDER_RATE_WINDOW);
}

/**
 * @return array{ok: bool, order: ?array<string, mixed>, error: ?string}
 */
function create_order_from_cart(array $input): array
{
    if (order_rate_limited()) {
        return ['ok' => false, 'order' => null, 'error' => __('error_generic')];
    }

    $snapshot = cart_snapshot();
    if ($snapshot['count'] <= 0 || $snapshot['items'] === []) {
        return ['ok' => false, 'order' => null, 'error' => __('cart_empty')];
    }

    $name = sanitize_plain((string) ($input['name'] ?? ''));
    $phoneRaw = trim((string) ($input['phone'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $address = sanitize_plain((string) ($input['address'] ?? ''));
    $landmark = sanitize_plain((string) ($input['landmark'] ?? ''));
    $comment = sanitize_plain((string) ($input['comment'] ?? ''));
    $deliveryType = (string) ($input['delivery_type'] ?? 'delivery');
    $paymentMethod = (string) ($input['payment_method'] ?? 'cash');
    $idempotencyKey = trim((string) ($input['idempotency_key'] ?? ''));

    if ($name === '' || mb_strlen($name) > 120) {
        return ['ok' => false, 'order' => null, 'error' => __('error_required')];
    }

    if ($phoneRaw === '' || !is_valid_phone($phoneRaw)) {
        return ['ok' => false, 'order' => null, 'error' => __('error_phone')];
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'order' => null, 'error' => __('error_email')];
    }

    if (!in_array($deliveryType, ['delivery', 'pickup'], true)) {
        $deliveryType = 'delivery';
    }

    if (!in_array($paymentMethod, ['cash', 'on_receipt'], true)) {
        $paymentMethod = 'cash';
    }

    if ($deliveryType === 'delivery' && $address === '') {
        return ['ok' => false, 'order' => null, 'error' => __('error_required')];
    }

    if (mb_strlen($address) > 255) {
        return ['ok' => false, 'order' => null, 'error' => __('error_generic')];
    }

    if (mb_strlen($landmark) > 255 || mb_strlen($comment) > 2000) {
        return ['ok' => false, 'order' => null, 'error' => __('error_generic')];
    }

    $phone = function_exists('normalize_phone_e164')
        ? normalize_phone_e164($phoneRaw)
        : normalize_phone($phoneRaw);

    $subtotal = (float) $snapshot['subtotal'];
    $deliveryFee = $deliveryType === 'pickup' ? 0.0 : (float) $snapshot['delivery_fee'];
    $total = round($subtotal + $deliveryFee, 2);

    $minOrder = (float) (setting('min_order_amount') ?: 0);
    if ($minOrder > 0 && $subtotal < $minOrder) {
        return ['ok' => false, 'order' => null, 'error' => __('error_generic')];
    }

    if ($idempotencyKey !== '') {
        $idempotencyKey = substr($idempotencyKey, 0, 64);
        try {
            $existing = db()->prepare(
                'SELECT id, order_number, total, order_status, created_at
                 FROM orders WHERE idempotency_key = ? LIMIT 1'
            );
            $existing->execute([$idempotencyKey]);
            $row = $existing->fetch();
            if ($row) {
                return ['ok' => true, 'order' => $row, 'error' => null];
            }
        } catch (Throwable $e) {
            storage_log('create_order idempotency: ' . $e->getMessage());
        }
    } else {
        $idempotencyKey = null;
    }

    $userId = user_logged_in() ? (int) $_SESSION['user_id'] : null;
    $lang = current_lang();
    $orderNumber = generate_order_number();

    try {
        $pdo = db();
        $pdo->beginTransaction();

        $stmt = $pdo->prepare(
            'INSERT INTO orders
                (order_number, user_id, customer_name, customer_phone, customer_email,
                 delivery_type, delivery_address, landmark, comment,
                 subtotal, delivery_fee, total, payment_method, language_code,
                 idempotency_key, created_ip_hash)
             VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $orderNumber,
            $userId,
            $name,
            $phone,
            $email !== '' ? mb_strtolower($email) : null,
            $deliveryType,
            $address !== '' ? $address : null,
            $landmark !== '' ? $landmark : null,
            $comment !== '' ? $comment : null,
            $subtotal,
            $deliveryFee,
            $total,
            $paymentMethod,
            $lang,
            $idempotencyKey,
            client_ip_hash(),
        ]);

        $orderId = (int) $pdo->lastInsertId();

        $itemStmt = $pdo->prepare(
            'INSERT INTO order_items (order_id, dish_id, dish_name, quantity, unit_price, total_price)
             VALUES (?, ?, ?, ?, ?, ?)'
        );

        foreach ($snapshot['items'] as $item) {
            $dish = $item['dish'] ?? [];
            $dishName = (string) ($dish['name'] ?? 'Блюдо');
            $qty = (int) $item['quantity'];
            $unitPrice = (float) $item['unit_price'];
            $lineTotal = round($qty * $unitPrice, 2);
            $itemStmt->execute([
                $orderId,
                (int) $item['dish_id'],
                mb_substr($dishName, 0, 160),
                $qty,
                $unitPrice,
                $lineTotal,
            ]);
        }

        $pdo->commit();

        cart_clear();

        $order = [
            'id' => $orderId,
            'order_number' => $orderNumber,
            'total' => $total,
            'order_status' => 'new',
            'created_at' => date('Y-m-d H:i:s'),
        ];

        try {
            notify_admin(
                'Новый заказ ' . $orderNumber,
                "Имя: {$name}\nТелефон: {$phoneRaw}\nСумма: {$total} ₽\nТип: {$deliveryType}\n"
            );
        } catch (Throwable) {
            // Order must succeed even if notification fails
        }

        return ['ok' => true, 'order' => $order, 'error' => null];
    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        storage_log('create_order_from_cart: ' . $e->getMessage());

        if ($idempotencyKey !== null && str_contains($e->getMessage(), 'Duplicate')) {
            try {
                $existing = db()->prepare(
                    'SELECT id, order_number, total, order_status, created_at
                     FROM orders WHERE idempotency_key = ? LIMIT 1'
                );
                $existing->execute([$idempotencyKey]);
                $row = $existing->fetch();
                if ($row) {
                    return ['ok' => true, 'order' => $row, 'error' => null];
                }
            } catch (Throwable) {
                // fall through
            }
        }

        return ['ok' => false, 'order' => null, 'error' => __('error_generic')];
    }
}
