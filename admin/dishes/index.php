<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Блюда';
$adminActive = 'dishes';

$q = trim((string) ($_GET['q'] ?? ''));
$categoryId = (int) ($_GET['category_id'] ?? 0);
$sort = (string) ($_GET['sort'] ?? 'sort');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = admin_per_page();

$allowedSort = [
    'sort' => 'd.sort_order ASC, d.id ASC',
    'name' => 'd.name ASC',
    'price_asc' => 'd.price ASC',
    'price_desc' => 'd.price DESC',
    'newest' => 'd.id DESC',
];
$orderBy = $allowedSort[$sort] ?? $allowedSort['sort'];

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(d.name LIKE ? OR d.slug LIKE ? OR d.short_description LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($categoryId > 0) {
    $where[] = 'd.category_id = ?';
    $params[] = $categoryId;
}

$whereSql = implode(' AND ', $where);

$countStmt = db()->prepare("SELECT COUNT(*) FROM dishes d WHERE {$whereSql}");
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$pages = max(1, (int) ceil($total / $perPage));
$page = min($page, $pages);
$offset = ($page - 1) * $perPage;

$sql = "SELECT d.*, c.name AS category_name
        FROM dishes d
        LEFT JOIN categories c ON c.id = d.category_id
        WHERE {$whereSql}
        ORDER BY {$orderBy}
        LIMIT {$perPage} OFFSET {$offset}";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$categories = fetch_categories_list();
$queryBase = array_filter([
    'q' => $q !== '' ? $q : null,
    'category_id' => $categoryId > 0 ? $categoryId : null,
    'sort' => $sort !== 'sort' ? $sort : null,
], static fn ($v) => $v !== null);

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <div class="admin-toolbar">
    <form class="admin-filters" method="get" action="">
      <div class="form-group">
        <label for="q">Поиск</label>
        <input type="search" id="q" name="q" value="<?= e($q) ?>" placeholder="Название">
      </div>
      <div class="form-group">
        <label for="category_id">Категория</label>
        <select id="category_id" name="category_id">
          <option value="0">Все</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
              <?= e((string) $cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="sort">Сортировка</label>
        <select id="sort" name="sort">
          <option value="sort" <?= $sort === 'sort' ? 'selected' : '' ?>>По порядку</option>
          <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>По названию</option>
          <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Цена ↑</option>
          <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Цена ↓</option>
          <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Сначала новые</option>
        </select>
      </div>
      <button class="btn btn-light btn-sm" type="submit">Применить</button>
    </form>
    <a class="btn" href="<?= e(base_url('admin/dishes/create.php')) ?>">Добавить блюдо</a>
  </div>

  <?php if (!$rows): ?>
    <p class="admin-empty">Блюда не найдены.</p>
  <?php else: ?>
    <p class="admin-muted">Найдено: <?= $total ?></p>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Фото</th>
            <th>Название</th>
            <th>Категория</th>
            <th>Цена</th>
            <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php $img = admin_image_src('dishes', $row['image'] ?? null); ?>
            <tr>
              <td data-label="Фото">
                <?php if ($img): ?>
                  <img class="thumb" src="<?= e($img) ?>" alt="">
                <?php else: ?>
                  <span class="thumb thumb-empty"></span>
                <?php endif; ?>
              </td>
              <td data-label="Название">
                <?= e((string) $row['name']) ?>
                <?php if ((int) $row['is_popular']): ?><span class="badge">Хит</span><?php endif; ?>
                <br><small><code><?= e((string) $row['slug']) ?></code></small>
              </td>
              <td data-label="Категория"><?= e((string) ($row['category_name'] ?? '—')) ?></td>
              <td data-label="Цена"><?= e(format_admin_price($row['price'])) ?></td>
              <td data-label="Статус">
                <span class="badge <?= (int) $row['is_available'] ? 'badge-confirmed' : 'badge-cancelled' ?>">
                  <?= (int) $row['is_available'] ? 'В меню' : 'Скрыто' ?>
                </span>
              </td>
              <td class="actions" data-label="">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/dishes/edit.php?id=' . (int) $row['id'])) ?>">Изменить</a>
                <form method="post" action="<?= e(base_url('admin/dishes/toggle.php')) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <button class="btn btn-sm btn-light" type="submit"><?= (int) $row['is_available'] ? 'Скрыть' : 'Показать' ?></button>
                </form>
                <form method="post" action="<?= e(base_url('admin/dishes/delete.php')) ?>" onsubmit="return confirm('Удалить блюдо?');">
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
    <?= admin_pagination($page, $pages, 'admin/dishes/index.php', $queryBase) ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
