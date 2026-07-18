<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_user();
$userId = (int) $user['id'];
$accountSection = 'orders';

$orderId = (int) ($_GET['id'] ?? $_POST['order_id'] ?? 0);
if ($orderId <= 0) {
    flash('error', __('error_generic'));
    redirect('account/orders.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'repeat') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', __('error_csrf'));
        redirect('account/order.php?id=' . $orderId);
    }

    $postOrderId = (int) ($_POST['order_id'] ?? 0);
    if ($postOrderId !== $orderId) {
        flash('error', __('error_generic'));
        redirect('account/orders.php');
    }

    $added = 0;
    try {
        $orderStmt = db()->prepare('SELECT id FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
        $orderStmt->execute([$orderId, $userId]);
        if (!$orderStmt->fetch()) {
            flash('error', __('error_generic'));
            redirect('account/orders.php');
        }

        $itemsStmt = db()->prepare(
            'SELECT dish_id, quantity FROM order_items WHERE order_id = ? ORDER BY id ASC'
        );
        $itemsStmt->execute([$orderId]);
        foreach ($itemsStmt->fetchAll() as $item) {
            $dishId = (int) ($item['dish_id'] ?? 0);
            $qty = max(1, (int) ($item['quantity'] ?? 1));
            if ($dishId > 0 && cart_add($dishId, $qty)) {
                $added++;
            }
        }
    } catch (Throwable $e) {
        storage_log('account/order repeat: ' . $e->getMessage());
        flash('error', __('error_generic'));
        redirect('account/order.php?id=' . $orderId);
    }

    if ($added > 0) {
        flash('success', __('btn_repeat_order'));
    } else {
        flash('error', __('error_generic'));
    }
    redirect('account/order.php?id=' . $orderId);
}

$order = null;
$items = [];

try {
    $stmt = db()->prepare(
        'SELECT id, order_number, order_status, payment_status, payment_method, delivery_type,
                delivery_address, landmark, comment, subtotal, delivery_fee, total, created_at
         FROM orders
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    $stmt->execute([$orderId, $userId]);
    $order = $stmt->fetch() ?: null;

    if ($order) {
        $itemsStmt = db()->prepare(
            'SELECT dish_id, dish_name, quantity, unit_price, total_price
             FROM order_items
             WHERE order_id = ?
             ORDER BY id ASC'
        );
        $itemsStmt->execute([$orderId]);
        $items = $itemsStmt->fetchAll();
    }
} catch (Throwable $e) {
    storage_log('account/order detail: ' . $e->getMessage());
}

if ($order === null) {
    flash('error', __('error_generic'));
    redirect('account/orders.php');
}

$pageTitle = __('order_number') . ' ' . ($order['order_number'] ?? '') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('account_orders');
$bodyClass = 'page-account';

$status = (string) ($order['order_status'] ?? 'new');
$statusLabel = __('order_status_' . $status);
$paymentStatus = (string) ($order['payment_status'] ?? 'pending');
$paymentLabel = __('payment_status_' . $paymentStatus);

require __DIR__ . '/../includes/header.php';
?>

<div class="container account-layout">
  <?php require __DIR__ . '/_nav.php'; ?>

  <div class="account-main">
    <div class="account-card" data-reveal>
      <div class="account-card-head">
        <div>
          <p class="eyebrow"><?= e(__('order_number')) ?></p>
          <h1><?= e((string) $order['order_number']) ?></h1>
          <p class="account-muted"><?= e(date('d.m.Y H:i', strtotime((string) $order['created_at']))) ?></p>
        </div>
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('account/orders.php')) ?>"><?= e(__('btn_back')) ?></a>
      </div>

      <div class="account-order-meta">
        <div>
          <span class="account-meta-label">Статус</span>
          <strong><?= e($statusLabel) ?></strong>
        </div>
        <div>
          <span class="account-meta-label"><?= e(__('checkout_payment')) ?></span>
          <strong><?= e($paymentLabel) ?></strong>
        </div>
        <?php if (!empty($order['delivery_address'])): ?>
          <div class="account-order-meta-full">
            <span class="account-meta-label"><?= e(__('checkout_address')) ?></span>
            <strong><?= e((string) $order['delivery_address']) ?></strong>
            <?php if (!empty($order['landmark'])): ?>
              <span class="account-muted"><?= e((string) $order['landmark']) ?></span>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="account-order-items">
        <h2><?= e(__('menu_title')) ?></h2>
        <ul class="account-items-list">
          <?php foreach ($items as $item): ?>
            <li class="account-item-row">
              <span class="account-item-name"><?= e((string) $item['dish_name']) ?></span>
              <span class="account-item-qty"><?= e((string) ($item['quantity'] ?? 1)) ?> × <?= e(format_price((float) $item['unit_price'])) ?></span>
              <span class="account-item-total"><?= e(format_price((float) $item['total_price'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="account-order-totals">
        <div class="account-total-row">
          <span><?= e(__('cart_subtotal')) ?></span>
          <strong><?= e(format_price((float) $order['subtotal'])) ?></strong>
        </div>
        <div class="account-total-row">
          <span><?= e(__('cart_delivery')) ?></span>
          <strong><?= e(format_price((float) $order['delivery_fee'])) ?></strong>
        </div>
        <div class="account-total-row account-total-row--grand">
          <span><?= e(__('cart_total')) ?></span>
          <strong><?= e(format_price((float) $order['total'])) ?></strong>
        </div>
      </div>

      <form class="account-repeat-form" method="post" action="<?= e(base_url('account/order.php?id=' . $orderId)) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="repeat">
        <input type="hidden" name="order_id" value="<?= (int) $orderId ?>">
        <button class="btn btn-primary" type="submit"><?= e(__('btn_repeat_order')) ?></button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
