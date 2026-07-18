<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM categories WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    flash('error', 'Категория не найдена.');
    redirect('admin/categories/index.php');
}

$adminPageTitle = 'Редактирование категории';
$adminActive = 'categories';
$errors = form_errors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $name = sanitize_plain($_POST['name'] ?? '');
    $slug = sanitize_plain($_POST['slug'] ?? '');
    $description = sanitize_plain($_POST['description'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;
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
            $slug = unique_slug('categories', $slug, $id);
            $image = $category['image'];

            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = store_upload($_FILES['image'], 'categories', $category['image'] ?? null);
            }

            if (!empty($_POST['remove_image']) && $image) {
                delete_upload('categories', (string) $image);
                $image = null;
            }

            $upd = db()->prepare(
                'UPDATE categories
                 SET name = ?, slug = ?, description = ?, image = ?, sort_order = ?, is_active = ?
                 WHERE id = ?'
            );
            $upd->execute([$name, $slug, $description !== '' ? $description : null, $image, $sortOrder, $isActive, $id]);

            foreach (['en', 'de'] as $langCode) {
                $trName = sanitize_plain($_POST['tr_' . $langCode . '_name'] ?? '');
                $trDesc = sanitize_plain($_POST['tr_' . $langCode . '_description'] ?? '');
                if ($trName !== '') {
                    upsert_translation_row('category_translations', [
                        'category_id' => $id,
                        'language_code' => $langCode,
                    ], [
                        'name' => $trName,
                        'description' => $trDesc !== '' ? $trDesc : null,
                    ]);
                }
            }

            flash('success', 'Категория обновлена.');
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
    redirect('admin/categories/edit.php?id=' . $id);
}

$name = (string) old('name', $category['name']);
$slug = (string) old('slug', $category['slug']);
$description = (string) old('description', $category['description'] ?? '');
$sortOrder = (string) old('sort_order', (string) $category['sort_order']);
$isActive = (int) old('is_active', (int) $category['is_active']);
$imgSrc = admin_image_src('categories', $category['image'] ?? null);

$categoryTranslations = ['en' => [], 'de' => []];
try {
    $trStmt = db()->prepare(
        'SELECT language_code, name, description
         FROM category_translations WHERE category_id = ? AND language_code IN (\'en\', \'de\')'
    );
    $trStmt->execute([$id]);
    foreach ($trStmt->fetchAll() as $trRow) {
        $categoryTranslations[(string) $trRow['language_code']] = $trRow;
    }
} catch (Throwable) {
    // translation tables may be missing before migration
}

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
      <label for="name">Название</label>
      <input class="<?= e(field_invalid('name', $errors)) ?>" type="text" id="name" name="name" required value="<?= e($name) ?>">
      <?= field_error('name', $errors) ?>
    </div>

    <div class="form-group">
      <label for="slug">Slug</label>
      <input type="text" id="slug" name="slug" value="<?= e($slug) ?>">
    </div>

    <div class="form-group full">
      <label for="description">Описание</label>
      <textarea id="description" name="description" rows="3"><?= e($description) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Порядок</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= e($sortOrder) ?>">
    </div>

    <div class="form-group">
      <label for="image">Изображение</label>
      <?php if ($imgSrc): ?>
        <img class="thumb" src="<?= e($imgSrc) ?>" alt="">
        <label><input type="checkbox" name="remove_image" value="1"> Удалить изображение</label>
      <?php endif; ?>
      <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,image/*">
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" <?= $isActive ? 'checked' : '' ?>> Активна</label>
    </div>

    <div class="form-group full">
      <h3 class="admin-panel-title">Переводы</h3>
      <p class="admin-muted">Пустые поля на сайте используют русский текст.</p>
      <nav class="admin-tabs" aria-label="Языки перевода">
        <button type="button" class="admin-tab is-active" data-tab="tr-en">EN</button>
        <button type="button" class="admin-tab" data-tab="tr-de">DE</button>
      </nav>
      <?php foreach (['en' => 'EN', 'de' => 'DE'] as $code => $label): ?>
        <div class="admin-tab-panel<?= $code === 'en' ? ' is-active' : '' ?>" id="tr-<?= e($code) ?>"<?= $code !== 'en' ? ' hidden' : '' ?>>
          <?php $tr = $categoryTranslations[$code] ?? []; ?>
          <div class="form-group">
            <label for="tr_<?= e($code) ?>_name">Название (<?= e($label) ?>)</label>
            <input type="text" id="tr_<?= e($code) ?>_name" name="tr_<?= e($code) ?>_name"
                   value="<?= e((string) old('tr_' . $code . '_name', $tr['name'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="tr_<?= e($code) ?>_description">Описание</label>
            <textarea id="tr_<?= e($code) ?>_description" name="tr_<?= e($code) ?>_description" rows="3"><?= e((string) old('tr_' . $code . '_description', $tr['description'] ?? '')) ?></textarea>
          </div>
        </div>
      <?php endforeach; ?>
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
<script>
document.querySelectorAll('[data-tab]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var target = btn.getAttribute('data-tab');
    document.querySelectorAll('.admin-tab-panel').forEach(function (p) {
      p.classList.toggle('is-active', p.id === target);
      p.hidden = p.id !== target;
    });
    document.querySelectorAll('[data-tab]').forEach(function (b) {
      b.classList.toggle('is-active', b.getAttribute('data-tab') === target);
    });
  });
});
</script>
