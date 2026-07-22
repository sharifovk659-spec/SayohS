<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$from = (string) ($_SESSION['google_oauth_from'] ?? 'register');
$back = $from === 'login' ? 'login.php' : 'register.php';

if (user_logged_in()) {
    unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_from']);
    redirect('account/');
}

if (!google_oauth_enabled()) {
    flash('error', __('error_google_oauth_config'));
    redirect($back);
}

$error = (string) ($_GET['error'] ?? '');
if ($error !== '') {
    storage_log('google_oauth error: ' . $error);
    flash('error', __('error_google_oauth'));
    redirect($back);
}

$state = (string) ($_GET['state'] ?? '');
$expected = (string) ($_SESSION['google_oauth_state'] ?? '');
unset($_SESSION['google_oauth_state'], $_SESSION['google_oauth_from']);

if ($state === '' || $expected === '' || !hash_equals($expected, $state)) {
    flash('error', __('error_csrf'));
    redirect($back);
}

$code = (string) ($_GET['code'] ?? '');
if ($code === '') {
    flash('error', __('error_google_oauth'));
    redirect($back);
}

if (auth_rate_limited('google_oauth_callback', 20, 900)) {
    flash('error', __('error_generic'));
    redirect($back);
}

$profile = google_oauth_fetch_profile($code);
if ($profile === null) {
    flash('error', __('error_google_oauth'));
    redirect($back);
}

if (!google_oauth_login_or_register($profile)) {
    flash('error', __('error_register_unavailable'));
    redirect($back);
}

flash('success', __('success_google_auth'));

$target = trim((string) ($_SESSION['redirect_after_login'] ?? ''));
unset($_SESSION['redirect_after_login']);
if ($target === '' || str_contains($target, '://') || str_starts_with($target, '//')) {
    $target = 'account/';
}
redirect(ltrim($target, '/'));
