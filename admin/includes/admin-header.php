<?php

declare(strict_types=1);

/** @var string $adminPageTitle */
/** @var string $adminActive */

$adminPageTitle = $adminPageTitle ?? 'Админ-панель';
$adminActive = $adminActive ?? '';
$admin = admin_user();
$flash = get_flash();
$brand = setting('restaurant_name', (string) app_config('name', 'Aroma')) ?? 'Aroma';
$adminName = (string) ($_SESSION['admin']['name'] ?? $admin['name'] ?? 'Админ');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex,nofollow">
  <title><?= e($adminPageTitle) ?> — Админ-панель</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700&family=Source+Serif+4:opsz,wght@8..60,600;8..60,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/admin.css')) ?>">
  <link rel="icon" href="<?= e(asset('icons/favicon.svg')) ?>" type="image/svg+xml">
</head>
<body class="admin-body">
  <div class="admin-shell">
    <?php require __DIR__ . '/admin-sidebar.php'; ?>

    <div class="admin-main">
      <header class="admin-top">
        <button type="button" class="admin-menu-btn" data-admin-menu aria-label="Открыть меню" aria-controls="admin-sidebar">☰</button>
        <h1><?= e($adminPageTitle) ?></h1>
        <div class="admin-top-right">
          <span class="admin-user-chip"><?= e($adminName) ?></span>
          <form method="post" action="<?= e(base_url('admin/logout.php')) ?>" class="admin-logout-form">
            <?= csrf_field() ?>
            <button class="btn btn-light btn-sm" type="submit">Выйти</button>
          </form>
        </div>
      </header>

      <?php if ($flash): ?>
        <div class="admin-flash admin-flash-<?= e((string) $flash['type']) ?>" role="status">
          <?= e((string) $flash['message']) ?>
        </div>
      <?php endif; ?>

      <div class="admin-content">
