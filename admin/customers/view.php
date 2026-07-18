<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $postId = (int) ($_POST['id'] ?? 0);
    $action = (string) ($_POST['action'] ?? '');

    if ($postId !== $id || $id <= 0) {
        flash('error', 'Некорректный клиент.');
        redirect('admin/customers/index.php');
    }

    if ($action === 'block') {
        $newStatus = 'blocked';
    } elseif ($action === 'unblock') {
        $newStatus = 'active';
    } else {
        flash('error', 'Некорректное действие.');
        redirect('admin/customers/view.php?id=' . $id);
    }

    try {
        $upd = db()->prepare('UPDATE users SET status = ?, updated_at = NOW() WHERE id = ?');
        $upd->execute([$newStatus, $id]);
        flash('success', $newStatus === 'blocked' ? 'Клиент заблокирован.' : 'Клиент разблокирован.');
    } catch (Throwable) {
        flash('error', 'Не удалось изменить статус.');
    }

    redirect('admin/customers/view.php?id=' . $id);
}

$stmt = db()->prepare(
    'SELECT id, name, email, phone, status, email_verified_at, last_login_at, created_at, updated_at
     FROM users WHERE id = ? LIMIT 1'
);
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    flash('error', 'Клиент не найден.');
    redirect('admin/customers/index.php');
}

$ordersStmt = db()->prepare(
    'SELECT id, order_number, order_status, payment_status, total, created_at
     FROM orders WHERE user_id = ?
     ORDER BY created_at DESC
     LIMIT 50'
);
$ordersStmt->execute([$id]);
$orders = $ordersStmt->fetchAll();

$adminPageTitle = 'Клиент: ' . (string) $user['name'];
$adminActive = 'customers';

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-grid-2">
  <section class="admin-panel">
    <h2 class="admin-panel-title">Профиль</h2>
    <dl class="detail-list">
      <div>
        <dt>ID</dt>
        <dd><?= (int) $user['id'] ?></dd>
      </div>
      <div>
        <dt>Имя</dt>
        <dd><?= e((string) $user['name']) ?></dd>
      </div>
      <div>
        <dt>Email</dt>
        <dd><?= e((string) $user['email']) ?></dd>
      </div>
      <div>
        <dt>Телефон</dt>
        <dd><?= e((string) ($user['phone'] ?: '—')) ?></dd>
      </div>
      <div>
        <dt>Статус</dt>
        <dd><span class="badge badge-<?= e((string) $user['status']) ?>"><?= e(user_status_label((string) $user['status'])) ?></span></dd>
      </div>
      <div>
        <dt>Email подтверждён</dt>
        <dd><?= e((string) ($user['email_verified_at'] ?: '—')) ?></dd>
      </div>
      <div>
        <dt>Последний вход</dt>
        <dd><?= e((string) ($user['last_login_at'] ?: '—')) ?></dd>
      </div>
      <div>
        <dt>Регистрация</dt>
        <dd><?= e((string) $user['created_at']) ?></dd>
      </div>
    </dl>
  </section>

  <section class="admin-panel">
    <h2 class="admin-panel-title">Действия</h2>
    <form method="post" action="" class="actions">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <?php if ((string) $user['status'] === 'active'): ?>
        <input type="hidden" name="action" value="block">
        <button class="btn btn-danger" type="submit" onclick="return confirm('Заблокировать клиента?');">Заблокировать</button>
      <?php else: ?>
        <input type="hidden" name="action" value="unblock">
        <button class="btn" type="submit">Разблокировать</button>
      <?php endif; ?>
      <a class="btn btn-light" href="<?= e(base_url('admin/customers/index.php')) ?>">К списку</a>
    </form>
  </section>
</div>

<section class="admin-panel">
  <div class="admin-toolbar">
    <h2 class="admin-panel-title">История заказов</h2>
  </div>
  <?php if (!$orders): ?>
    <p class="admin-empty">Заказов пока нет.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>№</th>
            <th>Сумма</th>
            <th>Статус</th>
            <th>Оплата</th>
            <th>Дата</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $order): ?>
            <tr>
              <td>
                <a href="<?= e(base_url('admin/orders/view.php?id=' . (int) $order['id'])) ?>">
                  <?= e((string) $order['order_number']) ?>
                </a>
              </td>
              <td><?= e(format_admin_price($order['total'])) ?></td>
              <td><span class="badge badge-<?= e((string) $order['order_status']) ?>"><?= e(order_status_label((string) $order['order_status'])) ?></span></td>
              <td><span class="badge badge-<?= e((string) $order['payment_status']) ?>"><?= e(payment_status_label((string) $order['payment_status'])) ?></span></td>
              <td><?= e((string) $order['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
