<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Сообщения';
$adminActive = 'messages';

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = admin_per_page();
$allowedStatus = ['new', 'read', 'answered'];

if ($status !== '' && !in_array($status, $allowedStatus, true)) {
    $status = '';
}

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(customer_name LIKE ? OR email LIKE ? OR phone LIKE ? OR subject LIKE ? OR message LIKE ?)';
    $like = '%' . $q . '%';
    $params = array_merge($params, [$like, $like, $like, $like, $like]);
}

if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}

$whereSql = implode(' AND ', $where);
$countStmt = db()->prepare("SELECT COUNT(*) FROM contact_messages WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$stmt = db()->prepare(
    "SELECT * FROM contact_messages WHERE {$whereSql}
     ORDER BY created_at DESC, id DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$queryBase = array_filter([
    'q' => $q !== '' ? $q : null,
    'status' => $status !== '' ? $status : null,
], static fn ($v) => $v !== null);

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <form class="admin-filters" method="get" action="">
    <div class="form-group">
      <label for="q">Поиск</label>
      <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Имя, email, текст">
    </div>
    <div class="form-group">
      <label for="status">Статус</label>
      <select id="status" name="status">
        <option value="">Все</option>
        <?php foreach ($allowedStatus as $st): ?>
          <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>><?= e(message_status_label($st)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-light btn-sm" type="submit">Фильтр</button>
  </form>

  <?php if (!$rows): ?>
    <p class="admin-empty">Сообщений нет.</p>
  <?php else: ?>
    <p class="admin-muted">Найдено: <?= $total ?></p>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>От кого</th>
            <th>Тема</th>
            <th>Статус</th>
            <th>Дата</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td data-label="От кого">
                <a href="<?= e(base_url('admin/messages/view.php?id=' . (int) $row['id'])) ?>">
                  <?= e((string) $row['customer_name']) ?>
                </a>
                <br><small><?= e((string) $row['email']) ?></small>
              </td>
              <td data-label="Тема"><?= e((string) ($row['subject'] ?: 'Без темы')) ?></td>
              <td data-label="Статус">
                <span class="badge badge-<?= e((string) $row['status']) ?>"><?= e(message_status_label((string) $row['status'])) ?></span>
              </td>
              <td data-label="Дата"><?= e((string) $row['created_at']) ?></td>
              <td class="actions" data-label="">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/messages/view.php?id=' . (int) $row['id'])) ?>">Открыть</a>
                <form method="post" action="<?= e(base_url('admin/messages/delete.php')) ?>" onsubmit="return confirm('Удалить сообщение?');">
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
    <?= admin_pagination($page, $pages, 'admin/messages/index.php', $queryBase) ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
