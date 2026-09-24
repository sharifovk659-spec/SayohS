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
  <div class="admin-toolbar" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;justify-content:space-between;">
    <div>
      <strong>Баннер телефона</strong>
      <p class="admin-muted" style="margin:.2rem 0 0;">Сурат аз телефон ё ПК → матн аз ин ҷо → дар сайт намоиш</p>
    </div>
    <a class="btn btn-sm" href="<?= e(base_url('admin/banners/edit.php')) ?>">+ Добавить слайд</a>
  </div>

  <div class="banner-help" style="margin:1rem 0;padding:1rem 1.1rem;border-radius:14px;background:#f7f1e8;border:1px solid rgba(42,24,16,.08);">
    <strong style="display:block;margin-bottom:.4rem;">Қабул мешавад</strong>
    <ul class="admin-muted" style="margin:0;padding-left:1.1rem;line-height:1.55;">
      <li><strong>JPG, PNG, WebP, GIF</strong> — аз телефон ё компютер (то 12 МБ)</li>
      <li>Ҳар андоза: дароз, баланд, майда, квадрат — сайт <strong>пур</strong> мекунад</li>
      <li>Матн дар сурат набошад — матнро дар «Изменить» нависед</li>
      <li>Тавсияи идеалӣ: <strong>1080×502 px</strong> (2.15:1)</li>
      <li>iPhone HEIC → аввал ба JPG табдил диҳед</li>
    </ul>
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
            <th>Действия</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <?php
            $id = (int) ($row['id'] ?? 0);
            $imgSrc = admin_image_src('banners', $row['image'] ?? null, 'banner');
            $active = (int) ($row['is_active'] ?? 0) === 1;
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
              <td><?= $active ? '✅ Да' : '— Нет' ?></td>
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
