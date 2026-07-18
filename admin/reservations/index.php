<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Бронирования';
$adminActive = 'reservations';

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = admin_per_page();

$allowedStatus = ['new', 'confirmed', 'completed', 'cancelled'];
if ($status !== '' && !in_array($status, $allowedStatus, true)) {
    $status = '';
}

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(customer_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'reservation_date >= ?';
    $params[] = $dateFrom;
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'reservation_date <= ?';
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM reservations WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT * FROM reservations WHERE {$whereSql}
     ORDER BY reservation_date DESC, reservation_time DESC, id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$queryBase = array_filter([
    'q' => $q !== '' ? $q : null,
    'status' => $status !== '' ? $status : null,
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
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Имя или телефон">
      </div>
      <div class="form-group">
        <label for="status">Статус</label>
        <select id="status" name="status">
          <option value="">Все</option>
          <?php foreach ($allowedStatus as $st): ?>
            <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(reservation_status_label($st)) ?></option>
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
    <a class="btn btn-light" href="<?= e(admin_url('admin/reservations/export.php', $queryBase)) ?>">Экспорт CSV</a>
  </div>

  <?php if (!$rows): ?>
    <p class="admin-empty">Бронирования не найдены.</p>
  <?php else: ?>
    <p class="admin-muted">Найдено: <?= $total ?></p>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Гость</th>
            <th>Дата / время</th>
            <th>Гости</th>
            <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td data-label="Гость">
                <a href="<?= e(base_url('admin/reservations/view.php?id=' . (int) $row['id'])) ?>">
                  <?= e((string) $row['customer_name']) ?>
                </a>
                <br><small><?= e((string) $row['phone']) ?></small>
              </td>
              <td data-label="Дата">
                <?= e((string) $row['reservation_date']) ?>
                <?= e(substr((string) $row['reservation_time'], 0, 5)) ?>
              </td>
              <td data-label="Гости"><?= (int) $row['guests_count'] ?></td>
              <td data-label="Статус">
                <span class="badge badge-<?= e((string) $row['status']) ?>"><?= e(reservation_status_label((string) $row['status'])) ?></span>
              </td>
              <td class="actions" data-label="">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/reservations/view.php?id=' . (int) $row['id'])) ?>">Открыть</a>
                <form method="post" action="<?= e(base_url('admin/reservations/delete.php')) ?>" onsubmit="return confirm('Удалить бронирование?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="btn btn-sm btn-danger" type="submit">Удалить</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= admin_pagination($page, $pages, 'admin/reservations/index.php', $queryBase) ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
