<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM gallery WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    flash('error', 'Фото не найдено.');
    redirect('admin/gallery/index.php');
}

$adminPageTitle = 'Редактирование фото';
$adminActive = 'gallery';
$errors = form_errors();
$types = ['interior', 'dishes', 'drinks', 'team', 'events'];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title = sanitize_plain($_POST['title'] ?? '');
    $type = (string) ($_POST['type'] ?? 'interior');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
    $errs = [];

    if ($title === '') {
        $errs['title'] = 'Укажите название.';
    }
    if (!in_array($type, $types, true)) {
        $errs['type'] = 'Некорректный тип.';
    }

    if ($errs === []) {
        try {
            $image = $item['image'];
            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = store_upload($_FILES['image'], 'gallery', $item['image'] ?? null);
            }

            $upd = db()->prepare(
                'UPDATE gallery SET title = ?, image = ?, type = ?, sort_order = ?, is_active = ? WHERE id = ?'
            );
            $upd->execute([$title, $image, $type, $sortOrder, $isActive, $id]);

            flash('success', 'Фото обновлено.');
            redirect('admin/gallery/index.php');
        } catch (Throwable $e) {
            $errs['form'] = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }

    set_form_state($errs, [
        'title' => $title,
        'type' => $type,
        'sort_order' => $sortOrder,
        'is_active' => $isActive,
    ]);
    redirect('admin/gallery/edit.php?id=' . $id);
}

$title = (string) old('title', $item['title']);
$type = (string) old('type', $item['type']);
$sortOrder = (string) old('sort_order', (string) $item['sort_order']);
$isActive = (int) old('is_active', (int) $item['is_active']);
$imgSrc = admin_image_src('gallery', $item['image'] ?? null);

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php if (!empty($errors['form'])): ?>
      <div class="admin-flash admin-flash-error full"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="form-group">
      <label for="title">Название</label>
      <input type="text" id="title" name="title" required value="<?= e($title) ?>">
      <?= field_error('title', $errors) ?>
    </div>

    <div class="form-group">
      <label for="type">Тип</label>
      <select id="type" name="type">
        <?php foreach ($types as $t): ?>
          <option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e(gallery_album_label($t)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="sort_order">Порядок</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= e($sortOrder) ?>">
    </div>

    <div class="form-group">
      <label for="image">Изображение</label>
      <?php if ($imgSrc): ?>
        <img class="thumb" src="<?= e($imgSrc) ?>" alt="">
      <?php endif; ?>
      <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,image/*">
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>> Активно</label>
    </div>

    <div class="form-group full actions">
      <button class="btn" type="submit">Сохранить</button>
      <a class="btn btn-light" href="<?= e(base_url('admin/gallery/index.php')) ?>">Отмена</a>
    </div>
  </form>
</div>

<?php
clear_old_input();
require __DIR__ . '/../includes/admin-footer.php';
?>
