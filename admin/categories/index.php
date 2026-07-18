<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Категории';
$adminActive = 'categories';

$q = trim((string) ($_GET['q'] ?? ''));
$params = [];
$sql = 'SELECT c.*, (SELECT COUNT(*) FROM dishes d WHERE d.category_id = c.id) AS dishes_count
        FROM categories c';

if ($q !== '') {
    $sql .= ' WHERE c.name LIKE ? OR c.slug LIKE ? OR c.description LIKE ?';
    $like = '%' . $q . '%';
    $params = [$like, $like, $like];
}

$sql .= ' ORDER BY c.sort_order ASC, c.id ASC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <div class="admin-toolbar">
    <form class="admin-filters" method="get" action="">
      <div class="form-group">
        <label for="q">Поиск</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Название или slug">
      </div>
      <button class="btn btn-light btn-sm" type="submit">Найти</button>
      <?php if ($q !== ''): ?>
        <a class="btn btn-light btn-sm" href="<?= e(base_url('admin/categories/index.php')) ?>">Сброс</a>
      <?php endif; ?>
    </form>
    <a class="btn" href="<?= e(base_url('admin/categories/create.php')) ?>">Добавить категорию</a>
  </div>

  <?php if (!$rows): ?>
    <p class="admin-empty">Категорий пока нет. Создайте первую.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Фото</th>
            <th>Название</th>
            <th>Slug</th>
            <th>Блюд</th>
            <th>Порядок</th>
            <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php $img = admin_image_src('categories', $row['image'] ?? null); ?>
            <tr>
              <td data-label="Фото">
                <?php if ($img): ?>
                  <img class="thumb" src="<?= e($img) ?>" alt="">
                <?php else: ?>
                  <span class="thumb thumb-empty"></span>
                <?php endif; ?>
              </td>
              <td data-label="Название"><?= e((string) $row['name']) ?></td>
              <td data-label="Slug"><code><?= e((string) $row['slug']) ?></code></td>
              <td data-label="Блюд"><?= (int) $row['dishes_count'] ?></td>
              <td data-label="Порядок">
                <form method="post" action="<?= e(base_url('admin/categories/reorder.php')) ?>" class="actions">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <input class="sort-input" type="number" name="sort_order" value="<?= (int) $row['sort_order'] ?>">
                  <button class="btn btn-sm btn-light" type="submit">OK</button>
                </form>
              </td>
              <td data-label="Статус">
                <span class="badge <?= (int) $row['is_active'] ? 'badge-confirmed' : 'badge-cancelled' ?>">
                  <?= (int) $row['is_active'] ? 'Активна' : 'Скрыта' ?>
                </span>
              </td>
              <td class="actions" data-label="">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/categories/edit.php?id=' . (int) $row['id'])) ?>">Изменить</a>
                <form method="post" action="<?= e(base_url('admin/categories/toggle.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="btn btn-sm btn-light" type="submit"><?= (int) $row['is_active'] ? 'Скрыть' : 'Показать' ?></button>
                </form>
                <form method="post" action="<?= e(base_url('admin/categories/delete.php')) ?>" onsubmit="return confirm('Удалить категорию?');">
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
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
