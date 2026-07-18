<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM reservations WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Бронирование не найдено.');
    redirect('admin/reservations/index.php');
}

$adminPageTitle = 'Бронирование #' . $id;
$adminActive = 'reservations';
$statuses = ['new', 'confirmed', 'completed', 'cancelled'];

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-grid-2">
  <section class="admin-panel">
    <h2 class="admin-panel-title">Данные гостя</h2>
    <dl class="detail-list">
      <div>
        <dt>Имя</dt>
        <dd><?= e((string) $row['customer_name']) ?></dd>
      </div>
      <div>
        <dt>Телефон</dt>
        <dd><?= e((string) $row['phone']) ?></dd>
      </div>
      <div>
        <dt>Email</dt>
        <dd><?= e((string) ($row['email'] ?: '—')) ?></dd>
      </div>
      <div>
        <dt>Дата и время</dt>
        <dd><?= e((string) $row['reservation_date']) ?> <?= e(substr((string) $row['reservation_time'], 0, 5)) ?></dd>
      </div>
      <div>
        <dt>Гостей</dt>
        <dd><?= (int) $row['guests_count'] ?></dd>
      </div>
      <div>
        <dt>Сообщение</dt>
        <dd><?= nl2br(e((string) ($row['message'] ?: '—'))) ?></dd>
      </div>
      <div>
        <dt>Создано</dt>
        <dd><?= e((string) $row['created_at']) ?></dd>
      </div>
      <div>
        <dt>Статус</dt>
        <dd><span class="badge badge-<?= e((string) $row['status']) ?>"><?= e(reservation_status_label((string) $row['status'])) ?></span></dd>
      </div>
    </dl>
  </section>

  <section class="admin-panel">
    <h2 class="admin-panel-title">Обновление</h2>
    <form method="post" action="<?= e(base_url('admin/reservations/update.php')) ?>" class="form-grid" style="grid-template-columns:1fr">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="form-group">
        <label for="status">Статус</label>
        <select id="status" name="status" required>
          <?php foreach ($statuses as $st): ?>
            <option value="<?= e($st) ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= e(reservation_status_label($st)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="admin_comment">Комментарий администратора</label>
        <textarea id="admin_comment" name="admin_comment" rows="5"><?= e((string) ($row['admin_comment'] ?? '')) ?></textarea>
      </div>
      <div class="actions">
        <button class="btn" type="submit">Сохранить</button>
        <a class="btn btn-light" href="<?= e(base_url('admin/reservations/index.php')) ?>">К списку</a>
        <button class="btn btn-danger" type="submit" form="delete-reservation" onclick="return confirm('Удалить бронирование?');">Удалить</button>
      </div>
    </form>
    <form id="delete-reservation" method="post" action="<?= e(base_url('admin/reservations/delete.php')) ?>" hidden>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
    </form>
  </section>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
