<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_user();
$userId = (int) $user['id'];
$accountSection = 'orders';

$pageTitle = __('account_orders') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('account_orders');
$bodyClass = 'page-account';

$orders = [];
try {
    $stmt = db()->prepare(
        'SELECT id, order_number, order_status, payment_status, total, created_at
         FROM orders
         WHERE user_id = ?
         ORDER BY created_at DESC'
    );
    $stmt->execute([$userId]);
    $orders = $stmt->fetchAll();
} catch (Throwable $e) {
    storage_log('account/orders list: ' . $e->getMessage());
}

require __DIR__ . '/../includes/header.php';
?>

<div class="container account-layout">
  <?php require __DIR__ . '/_nav.php'; ?>

  <div class="account-main">
    <div class="account-card" data-reveal>
      <h1><?= e(__('account_orders')) ?></h1>

      <?php if ($orders === []): ?>
        <p class="account-muted"><?= e(__('no_orders')) ?></p>
        <a class="btn btn-primary" href="<?= e(base_url('menu.php')) ?>"><?= e(__('hero_menu_btn')) ?></a>
      <?php else: ?>
        <div class="account-orders-table-wrap">
          <table class="account-orders-table">
            <thead>
              <tr>
                <th><?= e(__('order_number')) ?></th>
                <th>Дата / статус</th>
                <th><?= e(__('cart_total')) ?></th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $order): ?>
                <?php
                $status = (string) ($order['order_status'] ?? 'new');
                $statusLabel = __('order_status_' . $status);
                ?>
                <tr>
                  <td data-label="<?= e(__('order_number')) ?>"><?= e((string) $order['order_number']) ?></td>
                  <td data-label="Дата / статус">
                    <span class="account-order-date"><?= e(date('d.m.Y H:i', strtotime((string) $order['created_at']))) ?></span>
                    <span class="account-order-status"><?= e($statusLabel) ?></span>
                  </td>
                  <td data-label="<?= e(__('cart_total')) ?>"><?= e(format_price((float) $order['total'])) ?></td>
                  <td>
                    <a class="btn btn-outline btn-sm" href="<?= e(base_url('account/order.php?id=' . (int) $order['id'])) ?>"><?= e(__('btn_details')) ?></a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
