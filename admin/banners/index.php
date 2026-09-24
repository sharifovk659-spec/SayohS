<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Баннер на главной (моб.)';
$adminActive = 'banners';

$rows = fetch_home_banners(false);

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <div class="admin-toolbar">
    <p class="admin-muted">Загрузите PNG или WebP с прозрачным фоном — на сайте размер подстроится автоматически.</p>
    <a class="btn btn-sm" href="<?= e(base_url('admin/banners/edit.php')) ?>">Добавить слайд</a>
  </div>

  <?php if ($rows === []): ?>
    <p>Нет слайдов. Запустите миграцию <code>database/migrations/20260924_home_banners.sql</code> или добавьте слайд.</p>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Превью</th>
            <th>Тексты</th>
            <th>Порядок</th>
            <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
            $id = (int) ($row['id'] ?? 0);
            $imgSrc = admin_image_src('banners', $row['image'] ?? null, 'banner');
            ?>
            <tr>
              <td><?= $id ?></td>
              <td>
                <?php if ($imgSrc): ?>
                  <img class="thumb thumb--banner" src="<?= e($imgSrc) ?>" alt="">
                <?php else: ?>
                  <span class="admin-muted">Нет фото</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= e((string) ($row['title'] ?? '')) ?></strong><br>
                <span class="admin-muted"><?= e((string) ($row['label'] ?? '')) ?></span>
              </td>
              <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
              <td><?= (int) ($row['is_active'] ?? 0) === 1 ? 'Вкл' : 'Выкл' ?></td>
              <td><a class="btn btn-sm btn-light" href="<?= e(base_url('admin/banners/edit.php?id=' . $id)) ?>">Изменить</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
