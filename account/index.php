<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_user();
$userId = (int) $user['id'];
$accountSection = 'index';

$pageTitle = __('account_title') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('account_welcome', (string) $user['name']);
$bodyClass = 'page-account';

$recentOrders = [];
try {
    $stmt = db()->prepare(
        'SELECT id, order_number, order_status, total, created_at
         FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC
         LIMIT 3'
    );
    $stmt->execute([$userId]);
    $recentOrders = $stmt->fetchAll();
} catch (Throwable $e) {
    storage_log('account/index orders: ' . $e->getMessage());
}

$quickLinks = [
    ['section' => 'profile', 'href' => base_url('account/profile.php'), 'label' => __('account_profile')],
    ['section' => 'orders', 'href' => base_url('account/orders.php'), 'label' => __('account_orders')],
    ['section' => 'favorites', 'href' => base_url('account/favorites.php'), 'label' => __('account_favorites')],
    ['section' => 'addresses', 'href' => base_url('account/addresses.php'), 'label' => __('account_addresses')],
    ['section' => 'security', 'href' => base_url('account/security.php'), 'label' => __('account_security')],
];

require __DIR__ . '/../includes/header.php';
?>

<div class="container account-layout">
  <?php require __DIR__ . '/_nav.php'; ?>

  <div class="account-main">
    <div class="account-card account-card--hero" data-reveal>
      <p class="eyebrow"><?= e(__('account_title')) ?></p>
      <h1><?= e(__('account_welcome', (string) $user['name'])) ?></h1>
      <p class="account-muted"><?= e((string) ($user['email'] ?? '')) ?></p>
    </div>

    <div class="account-quick-links" data-reveal>
      <?php foreach ($quickLinks as $link): ?>
        <a class="account-quick-link" href="<?= e($link['href']) ?>">
          <span><?= e($link['label']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="account-card" data-reveal>
      <div class="account-card-head">
        <h2><?= e(__('account_orders')) ?></h2>
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('account/orders.php')) ?>"><?= e(__('btn_details')) ?></a>
      </div>

      <?php if ($recentOrders === []): ?>
        <p class="account-muted"><?= e(__('no_orders')) ?></p>
      <?php else: ?>
        <div class="account-orders-list">
          <?php foreach ($recentOrders as $order): ?>
            <?php
            $status = (string) ($order['order_status'] ?? 'new');
            $statusLabel = __('order_status_' . $status);
            ?>
            <a class="account-order-row" href="<?= e(base_url('account/order.php?id=' . (int) $order['id'])) ?>">
              <span class="account-order-number"><?= e((string) $order['order_number']) ?></span>
              <span class="account-order-date"><?= e(date('d.m.Y H:i', strtotime((string) $order['created_at']))) ?></span>
              <span class="account-order-status"><?= e($statusLabel) ?></span>
              <span class="account-order-total"><?= e(format_price((float) $order['total'])) ?></span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
