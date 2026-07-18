<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Галерея';
$adminActive = 'gallery';

$type = (string) ($_GET['type'] ?? '');
$allowedTypes = ['interior', 'dishes', 'drinks', 'team', 'events'];
if ($type !== '' && !in_array($type, $allowedTypes, true)) {
    $type = '';
}

$params = [];
$sql = 'SELECT * FROM gallery';
if ($type !== '') {
    $sql .= ' WHERE type = ?';
    $params[] = $type;
}
$sql .= ' ORDER BY sort_order ASC, id DESC';

$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <div class="admin-toolbar">
    <form class="admin-filters" method="get" action="">
      <div class="form-group">
        <label for="type">Тип</label>
        <select id="type" name="type">
          <option value="">Все</option>
          <?php foreach ($allowedTypes as $t): ?>
            <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e(gallery_album_label($t)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button class="btn btn-light btn-sm" type="submit">Фильтр</button>
    </form>
    <a class="btn" href="<?= e(base_url('admin/gallery/create.php')) ?>">Добавить фото</a>
  </div>

  <?php if (!$rows): ?>
    <p class="admin-empty">В галерее пока нет фотографий.</p>
  <?php else: ?>
    <div class="gallery-grid">
      <?php foreach ($rows as $row): ?>
        <?php $img = admin_image_src('gallery', $row['image'] ?? null); ?>
        <article class="gallery-item">
          <?php if ($img): ?>
            <img src="<?= e($img) ?>" alt="<?= e((string) $row['title']) ?>">
          <?php else: ?>
            <div class="thumb-empty" style="width:100%;aspect-ratio:4/3;border:0;border-radius:0"></div>
          <?php endif; ?>
          <div class="gallery-item-body">
            <strong><?= e((string) $row['title']) ?></strong>
            <span class="badge"><?= e(gallery_album_label((string) $row['type'])) ?></span>
            <span class="admin-muted">Порядок: <?= (int) $row['sort_order'] ?> · <?= (int) $row['is_active'] ? 'Активно' : 'Скрыто' ?></span>
            <div class="actions">
              <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/gallery/edit.php?id=' . (int) $row['id'])) ?>">Изменить</a>
              <form method="post" action="<?= e(base_url('admin/gallery/delete.php')) ?>" onsubmit="return confirm('Удалить фото?');">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                <button class="btn btn-sm btn-danger" type="submit">Удалить</button>
              </form>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
