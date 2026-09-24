<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Баннер на главной (моб.)';
$adminActive = 'banners';

ensure_home_banners_show_text_column();
$rows = fetch_home_banners(false);

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <div class="admin-toolbar" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;justify-content:space-between;">
    <div>
      <strong>Баннер телефона</strong>
      <p class="admin-muted" style="margin:.2rem 0 0;">«Текст» выкл = только фото без надписи</p>
    </div>
    <a class="btn btn-sm" href="<?= e(base_url('admin/banners/edit.php')) ?>">+ Добавить слайд</a>
  </div>

  <?php if ($rows === []): ?>
    <p>Нет слайдов. Нажмите «Добавить слайд».</p>
  <?php else: ?>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Фото</th>
            <th>Текст</th>
            <th>Порядок</th>
            <th>На сайте</th>
            <th>Текст на баннере</th>
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
            $id = (int) ($row['id'] ?? 0);
            $imgSrc = admin_image_src('banners', $row['image'] ?? null, 'banner');
            $active = (int) ($row['is_active'] ?? 0) === 1;
            $showText = (int) ($row['show_text'] ?? 1) === 1;
            ?>
            <tr>
              <td><?= $id ?></td>
              <td>
                <?php if ($imgSrc): ?>
                  <img class="thumb thumb--banner" src="<?= e($imgSrc) ?>" alt="" style="width:96px;height:45px;object-fit:cover;border-radius:8px;background:#efe6d8;">
                <?php else: ?>
                  <span class="admin-muted">Нет фото</span>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= e((string) ($row['title'] ?? '')) ?></strong><br>
                <span class="admin-muted"><?= e((string) ($row['label'] ?? '')) ?></span>
              </td>
              <td><?= (int) ($row['sort_order'] ?? 0) ?></td>
              <td>
                <form method="post" action="<?= e(base_url('admin/banners/toggle.php')) ?>" style="margin:0;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <input type="hidden" name="field" value="is_active">
                  <button type="submit" class="btn btn-sm btn-light" title="Вкл/Выкл слайд на сайте">
                    <?= $active ? '✅ Вкл' : '⬜ Выкл' ?>
                  </button>
                </form>
              </td>
              <td>
                <form method="post" action="<?= e(base_url('admin/banners/toggle.php')) ?>" style="margin:0;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <input type="hidden" name="field" value="show_text">
                  <button type="submit" class="btn btn-sm btn-light" title="Скрыть или показать текст поверх фото">
                    <?= $showText ? '✅ Вкл' : '⬜ Выкл' ?>
                  </button>
                </form>
              </td>
              <td style="white-space:nowrap;">
                <a class="btn btn-sm btn-light" href="<?= e(base_url('admin/banners/edit.php?id=' . $id)) ?>">Изменить</a>
                <form method="post" action="<?= e(base_url('admin/banners/delete.php')) ?>" style="display:inline;" onsubmit="return confirm('Удалить слайд #<?= $id ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $id ?>">
                  <button type="submit" class="btn btn-sm btn-light" style="color:#9b3b2f;">Удалить</button>
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
