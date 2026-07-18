<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$orderStatuses = order_statuses();
$paymentStatuses = payment_statuses();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $postId = (int) ($_POST['id'] ?? 0);
    if ($postId !== $id || $id <= 0) {
        flash('error', 'Некорректный заказ.');
        redirect('admin/orders/index.php');
    }

    $orderStatus = (string) ($_POST['order_status'] ?? '');
    $paymentStatus = (string) ($_POST['payment_status'] ?? '');
    $adminComment = sanitize_plain($_POST['admin_comment'] ?? '');

    if (!in_array($orderStatus, $orderStatuses, true)) {
        flash('error', 'Некорректный статус заказа.');
        redirect('admin/orders/view.php?id=' . $id);
    }
    if (!in_array($paymentStatus, $paymentStatuses, true)) {
        flash('error', 'Некорректный статус оплаты.');
        redirect('admin/orders/view.php?id=' . $id);
    }

    try {
        $upd = db()->prepare(
            'UPDATE orders SET order_status = ?, payment_status = ?, admin_comment = ?, updated_at = NOW() WHERE id = ?'
        );
        $upd->execute([
            $orderStatus,
            $paymentStatus,
            $adminComment !== '' ? $adminComment : null,
            $id,
        ]);
        flash('success', 'Заказ обновлён.');
    } catch (Throwable) {
        flash('error', 'Не удалось сохранить изменения.');
    }

    redirect('admin/orders/view.php?id=' . $id);
}

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Заказ не найден.');
    redirect('admin/orders/index.php');
}

$itemsStmt = db()->prepare(
    'SELECT dish_name, quantity, unit_price, total_price, options_json
     FROM order_items WHERE order_id = ? ORDER BY id ASC'
);
$itemsStmt->execute([$id]);
$items = $itemsStmt->fetchAll();

$adminPageTitle = 'Заказ ' . (string) $row['order_number'];
$adminActive = 'orders';

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="print-friendly">
  <div class="admin-toolbar no-print">
    <a class="btn btn-light btn-sm" href="<?= e(base_url('admin/orders/index.php')) ?>">К списку</a>
    <button class="btn btn-sm" type="button" onclick="window.print()">Печать</button>
  </div>

  <div class="admin-grid-2">
    <section class="admin-panel">
      <h2 class="admin-panel-title">Информация о заказе</h2>
      <dl class="detail-list">
        <div>
          <dt>Номер</dt>
          <dd><?= e((string) $row['order_number']) ?></dd>
        </div>
        <div>
          <dt>Клиент</dt>
          <dd><?= e((string) $row['customer_name']) ?></dd>
        </div>
        <div>
          <dt>Телефон</dt>
          <dd><?= e((string) $row['customer_phone']) ?></dd>
        </div>
        <div>
          <dt>Email</dt>
          <dd><?= e((string) ($row['customer_email'] ?: '—')) ?></dd>
        </div>
        <?php if (!empty($row['user_id'])): ?>
          <div>
            <dt>Аккаунт</dt>
            <dd><a href="<?= e(base_url('admin/customers/view.php?id=' . (int) $row['user_id'])) ?>">Клиент #<?= (int) $row['user_id'] ?></a></dd>
          </div>
        <?php endif; ?>
        <div>
          <dt>Тип</dt>
          <dd><?= e(delivery_type_label((string) $row['delivery_type'])) ?></dd>
        </div>
        <?php if ((string) $row['delivery_type'] === 'delivery'): ?>
          <div>
            <dt>Адрес</dt>
            <dd><?= e((string) ($row['delivery_address'] ?: '—')) ?></dd>
          </div>
          <?php if (!empty($row['landmark'])): ?>
            <div>
              <dt>Ориентир</dt>
              <dd><?= e((string) $row['landmark']) ?></dd>
            </div>
          <?php endif; ?>
        <?php endif; ?>
        <div>
          <dt>Комментарий клиента</dt>
          <dd><?= nl2br(e((string) ($row['comment'] ?: '—'))) ?></dd>
        </div>
        <div>
          <dt>Оплата</dt>
          <dd><?= e(payment_method_label((string) $row['payment_method'])) ?></dd>
        </div>
        <div>
          <dt>Создан</dt>
          <dd><?= e((string) $row['created_at']) ?></dd>
        </div>
        <div>
          <dt>Статус заказа</dt>
          <dd><span class="badge badge-<?= e((string) $row['order_status']) ?>"><?= e(order_status_label((string) $row['order_status'])) ?></span></dd>
        </div>
        <div>
          <dt>Статус оплаты</dt>
          <dd><span class="badge badge-<?= e((string) $row['payment_status']) ?>"><?= e(payment_status_label((string) $row['payment_status'])) ?></span></dd>
        </div>
      </dl>
    </section>

    <section class="admin-panel no-print">
      <h2 class="admin-panel-title">Обновление</h2>
      <form method="post" action="" class="form-grid" style="grid-template-columns:1fr">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="form-group">
          <label for="order_status">Статус заказа</label>
          <select id="order_status" name="order_status" required>
            <?php foreach ($orderStatuses as $st): ?>
              <option value="<?= e($st) ?>" <?= $row['order_status'] === $st ? 'selected' : '' ?>><?= e(order_status_label($st)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="payment_status">Статус оплаты</label>
          <select id="payment_status" name="payment_status" required>
            <?php foreach ($paymentStatuses as $st): ?>
              <option value="<?= e($st) ?>" <?= $row['payment_status'] === $st ? 'selected' : '' ?>><?= e(payment_status_label($st)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="admin_comment">Комментарий администратора</label>
          <textarea id="admin_comment" name="admin_comment" rows="5"><?= e((string) ($row['admin_comment'] ?? '')) ?></textarea>
        </div>
        <div class="actions">
          <button class="btn" type="submit">Сохранить</button>
        </div>
      </form>
    </section>
  </div>

  <section class="admin-panel">
    <h2 class="admin-panel-title">Состав заказа</h2>
    <?php if (!$items): ?>
      <p class="admin-empty">Позиции не найдены.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Блюдо</th>
              <th>Кол-во</th>
              <th>Цена</th>
              <th>Сумма</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <tr>
                <td><?= e((string) $item['dish_name']) ?></td>
                <td><?= (int) $item['quantity'] ?></td>
                <td><?= e(format_admin_price($item['unit_price'])) ?></td>
                <td><?= e(format_admin_price($item['total_price'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td colspan="3"><strong>Подытог</strong></td>
              <td><?= e(format_admin_price($row['subtotal'])) ?></td>
            </tr>
            <tr>
              <td colspan="3"><strong>Доставка</strong></td>
              <td><?= e(format_admin_price($row['delivery_fee'])) ?></td>
            </tr>
            <tr>
              <td colspan="3"><strong>Итого</strong></td>
              <td><strong><?= e(format_admin_price($row['total'])) ?></strong></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
