<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect('cart.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash('error', __('error_csrf'));
    redirect('cart.php');
}

$action = (string) ($_POST['action'] ?? '');
$dishId = (int) ($_POST['dish_id'] ?? 0);
$qty = (int) ($_POST['quantity'] ?? 1);

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

$back = trim((string) ($_POST['redirect_to'] ?? ''));
if ($back === '' || str_contains($back, '://') || str_starts_with($back, '//')) {
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $back = $referer !== '' && !str_contains($referer, '://') ? $referer : 'cart.php';
    if ($referer !== '') {
        $host = parse_url($referer, PHP_URL_HOST);
        $own = $_SERVER['HTTP_HOST'] ?? '';
        if ($host && $own && strcasecmp((string) $host, (string) $own) === 0) {
            $path = parse_url($referer, PHP_URL_PATH) ?? 'cart.php';
            $query = parse_url($referer, PHP_URL_QUERY);
            $back = ltrim($path, '/');
            if (is_string($query) && $query !== '') {
                $back .= '?' . $query;
            }
        } else {
            $back = 'cart.php';
        }
    }
}

flash($ok ? 'success' : 'error', $ok ? __('success_saved') : __('error_generic'));
redirect(ltrim($back, '/'));
