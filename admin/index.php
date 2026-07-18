<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Обзор';
$adminActive = 'dashboard';

$stats = [
    'reservations_new' => 0,
    'reservations_confirmed' => 0,
    'dishes' => 0,
    'categories_active' => 0,
    'gallery' => 0,
    'messages_new' => 0,
    'orders_new' => 0,
    'orders_today' => 0,
    'revenue_today' => 0.0,
    'users' => 0,
];

$latest = [];
$upcoming = [];
$latestOrders = [];

try {
    $pdo = db();
    $stats['reservations_new'] = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'new'")->fetchColumn();
    $stats['reservations_confirmed'] = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE status = 'confirmed'")->fetchColumn();
    $stats['dishes'] = (int) $pdo->query('SELECT COUNT(*) FROM dishes')->fetchColumn();
    $stats['categories_active'] = (int) $pdo->query('SELECT COUNT(*) FROM categories WHERE is_active = 1')->fetchColumn();
    $stats['gallery'] = (int) $pdo->query('SELECT COUNT(*) FROM gallery')->fetchColumn();
    $stats['messages_new'] = (int) $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'new'")->fetchColumn();

    $stats['orders_new'] = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'new'")->fetchColumn();
    $stats['orders_today'] = (int) $pdo->query('SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()')->fetchColumn();
    $stats['revenue_today'] = (float) $pdo->query(
        "SELECT COALESCE(SUM(total), 0) FROM orders
         WHERE order_status != 'cancelled' AND DATE(created_at) = CURDATE()"
    )->fetchColumn();
    $stats['users'] = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();

    $latestOrders = $pdo->query(
        'SELECT id, order_number, customer_name, customer_phone, order_status, payment_status, total, created_at
         FROM orders
         ORDER BY created_at DESC
         LIMIT 8'
    )->fetchAll();

    $latest = $pdo->query(
        'SELECT id, customer_name, phone, reservation_date, reservation_time, guests_count, status, created_at
         FROM reservations
         ORDER BY created_at DESC
         LIMIT 10'
    )->fetchAll();

    $upcoming = $pdo->query(
        "SELECT id, customer_name, phone, reservation_date, reservation_time, guests_count, status
         FROM reservations
         WHERE reservation_date >= CURDATE()
           AND status IN ('new', 'confirmed')
         ORDER BY reservation_date ASC, reservation_time ASC
         LIMIT 10"
    )->fetchAll();
} catch (Throwable) {
    flash('error', 'Не удалось загрузить статистику.');
}

require __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-cards">
  <a class="admin-card" href="<?= e(base_url('admin/reservations/index.php?status=new')) ?>">
    <span>Новые брони</span>
    <strong><?= (int) $stats['reservations_new'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/reservations/index.php?status=confirmed')) ?>">
    <span>Подтверждённые</span>
    <strong><?= (int) $stats['reservations_confirmed'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/dishes/index.php')) ?>">
    <span>Блюд всего</span>
    <strong><?= (int) $stats['dishes'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/categories/index.php')) ?>">
    <span>Активные категории</span>
    <strong><?= (int) $stats['categories_active'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/gallery/index.php')) ?>">
    <span>Фото в галерее</span>
    <strong><?= (int) $stats['gallery'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/messages/index.php?status=new')) ?>">
    <span>Новые сообщения</span>
    <strong><?= (int) $stats['messages_new'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/orders/index.php?order_status=new')) ?>">
    <span>Новые заказы</span>
    <strong><?= (int) $stats['orders_new'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/orders/index.php?date_from=' . date('Y-m-d') . '&date_to=' . date('Y-m-d'))) ?>">
    <span>Заказов сегодня</span>
    <strong><?= (int) $stats['orders_today'] ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/orders/index.php?date_from=' . date('Y-m-d') . '&date_to=' . date('Y-m-d'))) ?>">
    <span>Выручка сегодня</span>
    <strong><?= e(format_admin_price($stats['revenue_today'])) ?></strong>
  </a>
  <a class="admin-card" href="<?= e(base_url('admin/customers/index.php')) ?>">
    <span>Клиентов</span>
    <strong><?= (int) $stats['users'] ?></strong>
  </a>
</div>

<section class="admin-panel admin-quicklinks">
  <h2 class="admin-panel-title">Быстрые ссылки</h2>
  <div class="actions">
    <a class="btn btn-sm" href="<?= e(base_url('admin/dishes/create.php')) ?>">Добавить блюдо</a>
    <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/categories/create.php')) ?>">Категория</a>
    <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/gallery/create.php')) ?>">Фото</a>
    <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/reservations/index.php')) ?>">Бронирования</a>
    <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/orders/index.php')) ?>">Заказы</a>
    <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/settings/index.php')) ?>">Настройки</a>
  </div>
</section>

<div class="admin-grid-2">
  <section class="admin-panel">
    <div class="admin-toolbar">
      <h2 class="admin-panel-title">Последние бронирования</h2>
      <a class="btn btn-light btn-sm" href="<?= e(base_url('admin/reservations/index.php')) ?>">Все</a>
    </div>
    <?php if (!$latest): ?>
      <p class="admin-empty">Пока нет бронирований.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Гость</th>
              <th>Дата</th>
              <th>Гости</th>
              <th>Статус</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($latest as $row): ?>
              <tr>
                <td>
                  <a href="<?= e(base_url('admin/reservations/view.php?id=' . (int) $row['id'])) ?>">
                    <?= e((string) $row['customer_name']) ?>
                  </a>
                  <br><small><?= e((string) $row['phone']) ?></small>
                </td>
                <td>
                  <?= e((string) $row['reservation_date']) ?>
                  <?= e(substr((string) $row['reservation_time'], 0, 5)) ?>
                </td>
                <td><?= (int) $row['guests_count'] ?></td>
                <td><span class="badge badge-<?= e((string) $row['status']) ?>"><?= e(reservation_status_label((string) $row['status'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="admin-panel">
    <div class="admin-toolbar">
      <h2 class="admin-panel-title">Ближайшие брони</h2>
    </div>
    <?php if (!$upcoming): ?>
      <p class="admin-empty">Нет ближайших подтверждённых или новых броней.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Гость</th>
              <th>Дата / время</th>
              <th>Статус</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($upcoming as $row): ?>
              <tr>
                <td>
                  <a href="<?= e(base_url('admin/reservations/view.php?id=' . (int) $row['id'])) ?>">
                    <?= e((string) $row['customer_name']) ?>
                  </a>
                  <br><small><?= (int) $row['guests_count'] ?> гост.</small>
                </td>
                <td>
                  <?= e((string) $row['reservation_date']) ?>
                  <?= e(substr((string) $row['reservation_time'], 0, 5)) ?>
                </td>
                <td><span class="badge badge-<?= e((string) $row['status']) ?>"><?= e(reservation_status_label((string) $row['status'])) ?></span></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>
</div>

<section class="admin-panel">
  <div class="admin-toolbar">
    <h2 class="admin-panel-title">Последние заказы</h2>
    <a class="btn btn-light btn-sm" href="<?= e(base_url('admin/orders/index.php')) ?>">Все</a>
  </div>
  <?php if (!$latestOrders): ?>
    <p class="admin-empty">Пока нет заказов.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>№</th>
            <th>Клиент</th>
            <th>Сумма</th>
            <th>Статус</th>
            <th>Дата</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($latestOrders as $order): ?>
            <tr>
              <td>
                <a href="<?= e(base_url('admin/orders/view.php?id=' . (int) $order['id'])) ?>">
                  <?= e((string) $order['order_number']) ?>
                </a>
              </td>
              <td>
                <?= e((string) $order['customer_name']) ?>
                <br><small><?= e((string) $order['customer_phone']) ?></small>
              </td>
              <td><?= e(format_admin_price($order['total'])) ?></td>
              <td><span class="badge badge-<?= e((string) $order['order_status']) ?>"><?= e(order_status_label((string) $order['order_status'])) ?></span></td>
              <td><?= e((string) $order['created_at']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/admin-footer.php'; ?>
