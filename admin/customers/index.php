<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Клиенты';
$adminActive = 'customers';

$q = trim((string) ($_GET['q'] ?? ''));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = admin_per_page();

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM users WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT id, name, email, phone, status, last_login_at, created_at
     FROM users WHERE {$whereSql}
     ORDER BY created_at DESC, id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$queryBase = array_filter(['q' => $q !== '' ? $q : null], static fn ($v) => $v !== null);

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <div class="admin-toolbar">
    <form class="admin-filters" method="get" action="">
      <div class="form-group">
        <label for="q">Поиск</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Имя, email или телефон">
      </div>
      <button class="btn btn-light btn-sm" type="submit">Найти</button>
    </form>
  </div>

  <?php if (!$rows): ?>
    <p class="admin-empty">Клиенты не найдены.</p>
  <?php else: ?>
    <p class="admin-muted">Найдено: <?= $total ?></p>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Телефон</th>
            <th>Статус</th>
            <th>Последний вход</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td data-label="ID"><?= (int) $row['id'] ?></td>
              <td data-label="Имя"><?= e((string) $row['name']) ?></td>
              <td data-label="Email"><?= e((string) $row['email']) ?></td>
              <td data-label="Телефон"><?= e((string) ($row['phone'] ?: '—')) ?></td>
              <td data-label="Статус">
                <span class="badge badge-<?= e((string) $row['status']) ?>"><?= e(user_status_label((string) $row['status'])) ?></span>
              </td>
              <td data-label="Вход"><?= e((string) ($row['last_login_at'] ?: '—')) ?></td>
              <td class="actions" data-label="">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/customers/view.php?id=' . (int) $row['id'])) ?>">Открыть</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?= admin_pagination($page, $pages, 'admin/customers/index.php', $queryBase) ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
