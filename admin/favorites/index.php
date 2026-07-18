<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Избранное';
$adminActive = 'favorites-stats';

$totalFavorites = 0;
$usersWithFavorites = 0;
$topDishes = [];

try {
    $pdo = db();
    $totalFavorites = (int) $pdo->query('SELECT COUNT(*) FROM favorites')->fetchColumn();
    $usersWithFavorites = (int) $pdo->query('SELECT COUNT(DISTINCT user_id) FROM favorites')->fetchColumn();

    $topDishes = $pdo->query(
        'SELECT d.id, d.name, d.image, COUNT(f.id) AS favorite_count
         FROM favorites f
         INNER JOIN dishes d ON d.id = f.dish_id
         GROUP BY d.id, d.name, d.image
         ORDER BY favorite_count DESC, d.name ASC
         LIMIT 20'
    )->fetchAll();
} catch (Throwable) {
    flash('error', 'Не удалось загрузить статистику избранного.');
}

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-cards">
  <div class="admin-card">
    <span>Всего в избранном</span>
    <strong><?= $totalFavorites ?></strong>
  </div>
  <div class="admin-card">
    <span>Пользователей с избранным</span>
    <strong><?= $usersWithFavorites ?></strong>
  </div>
</div>

<section class="admin-panel">
  <h2 class="admin-panel-title">Топ блюд по избранному</h2>
  <?php if (!$topDishes): ?>
    <p class="admin-empty">Данных пока нет.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Блюдо</th>
            <th>В избранном</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($topDishes as $i => $dish): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td>
                <a href="<?= e(base_url('admin/dishes/edit.php?id=' . (int) $dish['id'])) ?>">
                  <?= e((string) $dish['name']) ?>
                </a>
              </td>
              <td><strong><?= (int) $dish['favorite_count'] ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
