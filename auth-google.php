<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (user_logged_in()) {
    redirect('account/');
}

if (!google_oauth_enabled()) {
    flash('error', __('error_google_oauth_config'));
    redirect('register.php');
}

if (auth_rate_limited('google_oauth_start', 20, 900)) {
    flash('error', __('error_generic'));
    redirect('register.php');
}

$state = bin2hex(random_bytes(16));
$_SESSION['google_oauth_state'] = $state;
$_SESSION['google_oauth_from'] = (string) ($_GET['from'] ?? 'register');

header('Location: ' . google_oauth_auth_url($state), true, 302);
exit;
