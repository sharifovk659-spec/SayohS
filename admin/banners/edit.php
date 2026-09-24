<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Слайд баннера';
$adminActive = 'banners';
$errors = form_errors();

$id = (int) ($_GET['id'] ?? 0);
$row = $id > 0 ? fetch_home_banner($id) : null;

if ($id > 0 && $row === null) {
    flash('error', 'Слайд не найден.');
    redirect('admin/banners/index.php');
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $label = sanitize_plain($_POST['label'] ?? '');
    $title = sanitize_plain($_POST['title'] ?? '');
    $subtitle = sanitize_plain($_POST['subtitle'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $showText = isset($_POST['show_text']) ? 1 : 0;
    $errs = [];

    if ($title === '') {
        $errs['title'] = 'Укажите заголовок.';
    }

    if ($errs === []) {
        try {
            ensure_home_banners_show_text_column();
            $currentImage = is_array($row) ? (string) ($row['image'] ?? '') : '';
            $image = $currentImage;
            $hasNewUpload = !empty($_FILES['image'])
                && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

            // New photo replaces old (store_upload deletes old file)
            if ($hasNewUpload) {
                $image = store_upload(
                    $_FILES['image'],
                    'banners',
                    $currentImage !== '' ? $currentImage : null
                );
            } elseif (!empty($_POST['remove_image']) && $currentImage !== '') {
                // Only remove when no new file was uploaded
                delete_upload('banners', $currentImage);
                $image = '';
            }

            if ($id > 0) {
                $stmt = db()->prepare(
                    'UPDATE home_banners SET sort_order = :sort_order, is_active = :is_active, show_text = :show_text,
                     label = :label, title = :title, subtitle = :subtitle, image = :image
                     WHERE id = :id'
                );
                $stmt->execute([
                    'sort_order' => $sortOrder,
                    'is_active' => $isActive,
                    'show_text' => $showText,
                    'label' => $label,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'image' => $image !== '' ? $image : null,
                    'id' => $id,
                ]);
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO home_banners (sort_order, is_active, show_text, label, title, subtitle, image)
                     VALUES (:sort_order, :is_active, :show_text, :label, :title, :subtitle, :image)'
                );
                $stmt->execute([
                    'sort_order' => $sortOrder,
                    'is_active' => $isActive,
                    'show_text' => $showText,
                    'label' => $label,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'image' => $image !== '' ? $image : null,
                ]);
            }

            flash('success', 'Баннер сохранён.');
            redirect('admin/banners/index.php');
        } catch (Throwable $e) {
            $errs['form'] = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }

    set_form_state($errs, [
        'label' => $label,
        'title' => $title,
        'subtitle' => $subtitle,
        'sort_order' => $sortOrder,
        'is_active' => $isActive,
        'show_text' => $showText,
    ]);
    redirect('admin/banners/edit.php' . ($id > 0 ? '?id=' . $id : ''));
}

$label = (string) old('label', is_array($row) ? ($row['label'] ?? '') : '');
$title = (string) old('title', is_array($row) ? ($row['title'] ?? '') : '');
$subtitle = (string) old('subtitle', is_array($row) ? ($row['subtitle'] ?? '') : '');
$sortOrder = (int) old('sort_order', is_array($row) ? ($row['sort_order'] ?? 0) : 0);
$isActive = old('is_active', is_array($row) ? (int) ($row['is_active'] ?? 1) : 1);
$showText = old('show_text', is_array($row) ? (int) ($row['show_text'] ?? 1) : 1);
$imgSrc = admin_image_src('banners', is_array($row) ? ($row['image'] ?? null) : null, 'banner');

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <form method="post" enctype="multipart/form-data" class="form-grid" id="banner-edit-form">
    <?= csrf_field() ?>
    <?php if (!empty($errors['form'])): ?>
      <div class="admin-flash admin-flash-error full"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="form-group">
      <label for="label">Верхняя подпись (маленький текст)</label>
      <input type="text" id="label" name="label" value="<?= e($label) ?>" placeholder="Например: Вкусные блюда">
    </div>

    <div class="form-group">
      <label for="title">Заголовок *</label>
      <input type="text" id="title" name="title" required value="<?= e($title) ?>" placeholder="Например: Свежие ингредиенты">
      <?= field_error('title', $errors) ?>
    </div>

    <div class="form-group full">
      <label for="subtitle">Подзаголовок</label>
      <textarea id="subtitle" name="subtitle" rows="2" placeholder="Короткий текст под заголовком"><?= e($subtitle) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Порядок (0 = первый)</label>
      <input type="number" id="sort_order" name="sort_order" min="0" max="99" value="<?= (int) $sortOrder ?>">
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" <?= (int) $isActive === 1 ? 'checked' : '' ?>> Показывать на сайте</label>
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="show_text" value="1" <?= (int) $showText === 1 ? 'checked' : '' ?>> Показывать текст на баннере</label>
      <p class="admin-muted" style="margin:.35rem 0 0;">Снимите галочку (выкл) — на телефоне останется только фото без текста и кнопки.</p>
    </div>

    <div class="form-group full">
      <label for="image">Фото баннера</label>
      <?php if ($imgSrc): ?>
        <div class="banner-preview-wrap" style="margin:.5rem 0 1rem;">
          <img class="thumb thumb--banner-preview" src="<?= e($imgSrc) ?>" alt="Превью баннера" style="max-width:min(100%,420px);width:100%;height:auto;border-radius:12px;display:block;background:#efe6d8;">
          <label style="display:inline-flex;align-items:center;gap:.4rem;margin-top:.55rem;">
            <input type="checkbox" name="remove_image" value="1" id="remove_image">
            Удалить текущее фото (оставить только текст)
          </label>
        </div>
      <?php else: ?>
        <p class="admin-muted">Пока нет фото — выберите файл ниже.</p>
      <?php endif; ?>
      <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif,image/*">
      <p id="banner-file-name" class="admin-muted" style="margin-top:.4rem;"></p>
    </div>

    <div class="form-actions full" style="display:flex;flex-wrap:wrap;gap:.6rem;align-items:center;">
      <button type="submit" class="btn btn-primary">Сохранить</button>
      <a class="btn btn-light" href="<?= e(base_url('admin/banners/index.php')) ?>">Назад к списку</a>
      <a class="btn btn-light" href="<?= e(base_url()) ?>" target="_blank" rel="noopener">Открыть сайт</a>
    </div>
  </form>

  <?php if ($id > 0): ?>
  <form method="post" action="<?= e(base_url('admin/banners/delete.php')) ?>" class="banner-delete-form" style="margin-top:1.5rem;padding-top:1rem;border-top:1px solid rgba(42,24,16,.1);" onsubmit="return confirm('Удалить этот слайд полностью?');">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">
    <button type="submit" class="btn btn-light" style="color:#9b3b2f;">Удалить слайд полностью</button>
  </form>
  <?php endif; ?>
</div>

<script>
(function () {
  var input = document.getElementById('image');
  var nameEl = document.getElementById('banner-file-name');
  var remove = document.getElementById('remove_image');
  if (input && nameEl) {
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      nameEl.textContent = f ? ('Выбрано: ' + f.name + ' (' + Math.round(f.size / 1024) + ' КБ)') : '';
      if (f && remove) remove.checked = false;
    });
  }
})();
</script>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
