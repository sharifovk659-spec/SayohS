<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    redirect('menu.php');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash('error', __('error_csrf'));
    redirect('menu.php');
}

$action = (string) ($_POST['action'] ?? 'toggle');
$dishId = (int) ($_POST['dish_id'] ?? 0);

if ($dishId <= 0) {
    flash('error', __('error_generic'));
    redirect('menu.php');
}

match ($action) {
    'add' => favorite_add($dishId),
    'remove' => favorite_remove($dishId),
    default => favorite_toggle($dishId),
};

$back = trim((string) ($_POST['redirect_to'] ?? ''));
if ($back === '' || str_contains($back, '://') || str_starts_with($back, '//')) {
    $referer = (string) ($_SERVER['HTTP_REFERER'] ?? '');
    $back = 'menu.php';
    if ($referer !== '') {
        $host = parse_url($referer, PHP_URL_HOST);
        $own = $_SERVER['HTTP_HOST'] ?? '';
        if ($host && $own && strcasecmp((string) $host, (string) $own) === 0) {
            $path = parse_url($referer, PHP_URL_PATH) ?? 'menu.php';
            $query = parse_url($referer, PHP_URL_QUERY);
            $back = ltrim($path, '/');
            if (is_string($query) && $query !== '') {
                $back .= '?' . $query;
            }
        }
    }
}

flash('success', __('success_saved'));
redirect(ltrim($back, '/'));
