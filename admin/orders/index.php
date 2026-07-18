<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Заказы';
$adminActive = 'orders';

$q = trim((string) ($_GET['q'] ?? ''));
$orderStatus = (string) ($_GET['order_status'] ?? '');
$paymentStatus = (string) ($_GET['payment_status'] ?? '');
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$allowedOrderStatus = order_statuses();
$allowedPaymentStatus = payment_statuses();

if ($orderStatus !== '' && !in_array($orderStatus, $allowedOrderStatus, true)) {
    $orderStatus = '';
}
if ($paymentStatus !== '' && !in_array($paymentStatus, $allowedPaymentStatus, true)) {
    $paymentStatus = '';
}

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(order_number LIKE ? OR customer_phone LIKE ? OR customer_name LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($orderStatus !== '') {
    $where[] = 'order_status = ?';
    $params[] = $orderStatus;
}

if ($paymentStatus !== '') {
    $where[] = 'payment_status = ?';
    $params[] = $paymentStatus;
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $dateFrom;
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM orders WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT id, order_number, customer_name, customer_phone, order_status, payment_status, total, created_at
     FROM orders WHERE {$whereSql}
     ORDER BY created_at DESC, id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$queryBase = array_filter([
    'q' => $q !== '' ? $q : null,
    'order_status' => $orderStatus !== '' ? $orderStatus : null,
    'payment_status' => $paymentStatus !== '' ? $paymentStatus : null,
    'date_from' => $dateFrom !== '' ? $dateFrom : null,
    'date_to' => $dateTo !== '' ? $dateTo : null,
], static fn ($v) => $v !== null);

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <div class="admin-toolbar">
    <form class="admin-filters" method="get" action="">
      <div class="form-group">
        <label for="q">Поиск</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="№ заказа, телефон, имя">
      </div>
      <div class="form-group">
        <label for="order_status">Статус заказа</label>
        <select id="order_status" name="order_status">
          <option value="">Все</option>
          <?php foreach ($allowedOrderStatus as $st): ?>
            <option value="<?= e($st) ?>" <?= $orderStatus === $st ? 'selected' : '' ?>><?= e(order_status_label($st)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="payment_status">Оплата</label>
        <select id="payment_status" name="payment_status">
          <option value="">Все</option>
          <?php foreach ($allowedPaymentStatus as $st): ?>
            <option value="<?= e($st) ?>" <?= $paymentStatus === $st ? 'selected' : '' ?>><?= e(payment_status_label($st)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="date_from">С даты</label>
        <input type="date" id="date_from" name="date_from" value="<?= e($dateFrom) ?>">
      </div>
      <div class="form-group">
        <label for="date_to">По дату</label>
        <input type="date" id="date_to" name="date_to" value="<?= e($dateTo) ?>">
      </div>
      <button class="btn btn-light btn-sm" type="submit">Фильтр</button>
    </form>
    <a class="btn btn-light" href="<?= e(admin_url('admin/orders/export.php', $queryBase)) ?>">Экспорт CSV</a>
  </div>

  <?php if (!$rows): ?>
    <p class="admin-empty">Заказы не найдены.</p>
  <?php else: ?>
    <p class="admin-muted">Найдено: <?= $total ?></p>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>№</th>
            <th>Клиент</th>
            <th>Сумма</th>
            <th>Статус</th>
            <th>Оплата</th>
            <th>Дата</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td data-label="№">
                <a href="<?= e(base_url('admin/orders/view.php?id=' . (int) $row['id'])) ?>">
                  <?= e((string) $row['order_number']) ?>
                </a>
              </td>
              <td data-label="Клиент">
                <?= e((string) $row['customer_name']) ?>
                <br><small><?= e((string) $row['customer_phone']) ?></small>
              </td>
              <td data-label="Сумма"><?= e(format_admin_price($row['total'])) ?></td>
              <td data-label="Статус">
                <span class="badge badge-<?= e((string) $row['order_status']) ?>"><?= e(order_status_label((string) $row['order_status'])) ?></span>
              </td>
              <td data-label="Оплата">
                <span class="badge badge-<?= e((string) $row['payment_status']) ?>"><?= e(payment_status_label((string) $row['payment_status'])) ?></span>
              </td>
              <td data-label="Дата"><?= e((string) $row['created_at']) ?></td>
              <td class="actions" data-label="">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/orders/view.php?id=' . (int) $row['id'])) ?>">Открыть</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= admin_pagination($page, $pages, 'admin/orders/index.php', $queryBase) ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
