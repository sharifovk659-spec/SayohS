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
    $errs = [];

    if ($title === '') {
        $errs['title'] = 'Укажите заголовок.';
    }

    if ($errs === []) {
        try {
            $currentImage = is_array($row) ? (string) ($row['image'] ?? '') : '';
            $image = $currentImage;

            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = store_upload($_FILES['image'], 'banners', $currentImage !== '' ? $currentImage : null);
            }

            if (!empty($_POST['remove_image']) && $currentImage !== '') {
                delete_upload('banners', $currentImage);
                $image = '';
            }

            if ($id > 0) {
                $stmt = db()->prepare(
                    'UPDATE home_banners SET sort_order = :sort_order, is_active = :is_active,
                     label = :label, title = :title, subtitle = :subtitle, image = :image
                     WHERE id = :id'
                );
                $stmt->execute([
                    'sort_order' => $sortOrder,
                    'is_active' => $isActive,
                    'label' => $label,
                    'title' => $title,
                    'subtitle' => $subtitle,
                    'image' => $image !== '' ? $image : null,
                    'id' => $id,
                ]);
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO home_banners (sort_order, is_active, label, title, subtitle, image)
                     VALUES (:sort_order, :is_active, :label, :title, :subtitle, :image)'
                );
                $stmt->execute([
                    'sort_order' => $sortOrder,
                    'is_active' => $isActive,
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
    ]);
    redirect('admin/banners/edit.php' . ($id > 0 ? '?id=' . $id : ''));
}

$label = (string) old('label', is_array($row) ? ($row['label'] ?? '') : '');
$title = (string) old('title', is_array($row) ? ($row['title'] ?? '') : '');
$subtitle = (string) old('subtitle', is_array($row) ? ($row['subtitle'] ?? '') : '');
$sortOrder = (int) old('sort_order', is_array($row) ? ($row['sort_order'] ?? 0) : 0);
$isActive = old('is_active', is_array($row) ? (int) ($row['is_active'] ?? 1) : 1);
$imgSrc = admin_image_src('banners', is_array($row) ? ($row['image'] ?? null) : null, 'banner');

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <?php if (!empty($errors['form'])): ?>
      <div class="admin-flash admin-flash-error full"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="form-group">
      <label for="label">Верхняя подпись</label>
      <input type="text" id="label" name="label" value="<?= e($label) ?>" placeholder="Вкусные блюда">
    </div>

    <div class="form-group">
      <label for="title">Заголовок *</label>
      <input type="text" id="title" name="title" required value="<?= e($title) ?>" placeholder="Свежие ингредиенты">
      <?= field_error('title', $errors) ?>
    </div>

    <div class="form-group full">
      <label for="subtitle">Подзаголовок</label>
      <textarea id="subtitle" name="subtitle" rows="2"><?= e($subtitle) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Порядок</label>
      <input type="number" id="sort_order" name="sort_order" min="0" max="99" value="<?= (int) $sortOrder ?>">
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" <?= (int) $isActive === 1 ? 'checked' : '' ?>> Показывать на сайте</label>
    </div>

    <div class="form-group full">
      <label for="image">Фото для баннера</label>
      <p class="admin-muted">JPG, PNG, WebP до 8 МБ. PNG/WebP без фона поддерживаются.</p>
      <?php if ($imgSrc): ?>
        <img class="thumb thumb--banner-preview" src="<?= e($imgSrc) ?>" alt="">
        <label><input type="checkbox" name="remove_image" value="1"> Удалить текущее фото</label>
      <?php endif; ?>
      <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/webp,image/*">
    </div>

    <div class="form-actions full">
      <button type="submit" class="btn btn-primary">Сохранить</button>
      <a class="btn btn-light" href="<?= e(base_url('admin/banners/index.php')) ?>">Назад</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
