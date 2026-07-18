<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM contact_messages WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$row = $stmt->fetch();

if (!$row) {
    flash('error', 'Сообщение не найдено.');
    redirect('admin/messages/index.php');
}

$adminPageTitle = 'Сообщение #' . $id;
$adminActive = 'messages';
$statuses = ['new', 'read', 'answered'];

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-grid-2">
  <section class="admin-panel">
    <h2 class="admin-panel-title">Письмо</h2>
    <dl class="detail-list">
      <div>
        <dt>Имя</dt>
        <dd><?= e((string) $row['customer_name']) ?></dd>
      </div>
      <div>
        <dt>Email</dt>
        <dd><a href="mailto:<?= e((string) $row['email']) ?>"><?= e((string) $row['email']) ?></a></dd>
      </div>
      <div>
        <dt>Телефон</dt>
        <dd><?= e((string) ($row['phone'] ?: '—')) ?></dd>
      </div>
      <div>
        <dt>Тема</dt>
        <dd><?= e((string) ($row['subject'] ?: '—')) ?></dd>
      </div>
      <div>
        <dt>Сообщение</dt>
        <dd><?= nl2br(e((string) $row['message'])) ?></dd>
      </div>
      <div>
        <dt>Создано</dt>
        <dd><?= e((string) $row['created_at']) ?></dd>
      </div>
    </dl>
  </section>

  <section class="admin-panel">
    <h2 class="admin-panel-title">Статус</h2>
    <form method="post" action="<?= e(base_url('admin/messages/update-status.php')) ?>" class="form-grid" style="grid-template-columns:1fr">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
      <div class="form-group">
        <label for="status">Статус</label>
        <select id="status" name="status" required>
          <?php foreach ($statuses as $st): ?>
            <option value="<?= e($st) ?>" <?= $row['status'] === $st ? 'selected' : '' ?>><?= e(message_status_label($st)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="actions">
        <button class="btn" type="submit">Сохранить</button>
        <a class="btn btn-light" href="<?= e(base_url('admin/messages/index.php')) ?>">К списку</a>
        <button class="btn btn-danger" type="submit" form="delete-message" onclick="return confirm('Удалить сообщение?');">Удалить</button>
      </div>
    </form>
    <form id="delete-message" method="post" action="<?= e(base_url('admin/messages/delete.php')) ?>" hidden>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $id ?>">
    </form>
  </section>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
