<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('account/');
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    flash('error', __('error_csrf'));
    redirect('account/');
}

logout_user();
flash('success', __('nav_logout'));
redirect('login.php');
