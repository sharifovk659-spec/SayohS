<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Страницы';
$adminActive = 'pages';

$rows = db()->query('SELECT id, page_key, title, subtitle, updated_at FROM pages ORDER BY id ASC')->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <?php if (!$rows): ?>
    <p class="admin-empty">Страницы не найдены. Загрузите seed-данные.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Ключ</th>
            <th>Заголовок</th>
            <th>Подзаголовок</th>
            <th>Обновлено</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td data-label="Ключ"><code><?= e((string) $row['page_key']) ?></code></td>
              <td data-label="Заголовок"><?= e((string) $row['title']) ?></td>
              <td data-label="Подзаголовок"><?= e((string) ($row['subtitle'] ?? '—')) ?></td>
              <td data-label="Обновлено"><?= e((string) $row['updated_at']) ?></td>
              <td class="actions" data-label="">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/pages/edit.php?id=' . (int) $row['id'])) ?>">Редактировать</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
