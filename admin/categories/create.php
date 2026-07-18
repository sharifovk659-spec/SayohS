<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Новая категория';
$adminActive = 'categories';
$errors = form_errors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $name = sanitize_plain($_POST['name'] ?? '');
    $slug = sanitize_plain($_POST['slug'] ?? '');
    $description = sanitize_plain($_POST['description'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $input = compact('name', 'slug', 'description', 'sortOrder') + ['is_active' => $isActive];
    $errs = [];

    if ($name === '') {
        $errs['name'] = 'Укажите название.';
    }

    if ($slug === '') {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }

    if ($errs === []) {
        try {
            $slug = unique_slug('categories', $slug);
            $image = null;
            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = store_upload($_FILES['image'], 'categories');
            }

            $stmt = db()->prepare(
                'INSERT INTO categories (name, slug, description, image, sort_order, is_active)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$name, $slug, $description !== '' ? $description : null, $image, $sortOrder, $isActive]);

            flash('success', 'Категория создана.');
            redirect('admin/categories/index.php');
        } catch (Throwable $e) {
            $errs['form'] = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }

    set_form_state($errs, [
        'name' => $name,
        'slug' => $slug,
        'description' => $description,
        'sort_order' => $sortOrder,
        'is_active' => $isActive,
    ]);
    redirect('admin/categories/create.php');
}

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <?php if (!empty($errors['form'])): ?>
      <div class="admin-flash admin-flash-error full"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="form-group">
      <label for="name">Название</label>
      <input class="<?= e(field_invalid('name', $errors)) ?>" type="text" id="name" name="name" required
             value="<?= e((string) old('name')) ?>">
      <?= field_error('name', $errors) ?>
    </div>

    <div class="form-group">
      <label for="slug">Slug</label>
      <input type="text" id="slug" name="slug" value="<?= e((string) old('slug')) ?>" placeholder="Авто из названия">
    </div>

    <div class="form-group full">
      <label for="description">Описание</label>
      <textarea id="description" name="description" rows="3"><?= e((string) old('description')) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Порядок</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= e((string) old('sort_order', '0')) ?>">
    </div>

    <div class="form-group">
      <label for="image">Изображение</label>
      <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,image/*">
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" <?= old('is_active', 1) ? 'checked' : '' ?>> Активна</label>
    </div>

    <div class="form-group full actions">
      <button class="btn" type="submit">Сохранить</button>
      <a class="btn btn-light" href="<?= e(base_url('admin/categories/index.php')) ?>">Отмена</a>
    </div>
  </form>
</div>

<?php
clear_old_input();
require __DIR__ . '/../includes/admin-footer.php';
?>
