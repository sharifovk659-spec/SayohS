<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

header('Content-Type: application/json; charset=utf-8');

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'method_not_allowed'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = $_POST;
$contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
if (str_contains($contentType, 'application/json')) {
    $raw = file_get_contents('php://input');
    $decoded = is_string($raw) ? json_decode($raw, true) : null;
    if (is_array($decoded)) {
        $payload = $decoded;
    }
}

if (!verify_csrf((string) ($payload['csrf_token'] ?? ''))) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'csrf'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = (string) ($payload['action'] ?? '');
$dishId = (int) ($payload['dish_id'] ?? 0);
$qty = (int) ($payload['quantity'] ?? 1);

$ok = match ($action) {
    'add' => cart_add($dishId, max(1, $qty)),
    'update' => cart_set_qty($dishId, $qty),
    'remove' => cart_remove($dishId),
    'clear' => (static function (): bool {
        cart_clear();
        return true;
    })(),
    default => false,
};

$snapshot = cart_snapshot(false);
$full = cart_snapshot(true);

echo json_encode([
    'ok' => $ok,
    'count' => $full['count'],
    'subtotal' => $full['subtotal'],
    'delivery_fee' => $full['delivery_fee'],
    'total' => $full['total'],
    'items' => array_map(static function (array $item): array {
        return [
            'dish_id' => (int) $item['dish_id'],
            'quantity' => (int) $item['quantity'],
            'unit_price' => (float) $item['unit_price'],
            'line_total' => (float) $item['line_total'],
            'name' => (string) ($item['dish']['name'] ?? ''),
        ];
    }, $snapshot['items']),
], JSON_UNESCAPED_UNICODE);
