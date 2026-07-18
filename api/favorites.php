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

$action = (string) ($payload['action'] ?? 'toggle');
$dishId = (int) ($payload['dish_id'] ?? 0);

if ($dishId <= 0) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid_dish'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($action === 'add') {
    favorite_add($dishId);
    $favorited = is_favorite($dishId);
} elseif ($action === 'remove') {
    favorite_remove($dishId);
    $favorited = is_favorite($dishId);
} elseif ($action === 'toggle') {
    $favorited = favorite_toggle($dishId);
} else {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'invalid_action'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => true,
    'favorited' => $favorited,
    'count' => favorites_count(),
], JSON_UNESCAPED_UNICODE);
