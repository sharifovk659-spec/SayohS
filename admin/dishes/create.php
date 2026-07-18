<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Новое блюдо';
$adminActive = 'dishes';
$errors = form_errors();
$categories = fetch_categories_list();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $name = sanitize_plain($_POST['name'] ?? '');
    $slug = sanitize_plain($_POST['slug'] ?? '');
    $shortDescription = sanitize_plain($_POST['short_description'] ?? '');
    $description = sanitize_plain($_POST['description'] ?? '');
    $ingredients = sanitize_plain($_POST['ingredients'] ?? '');
    $price = str_replace(',', '.', trim((string) ($_POST['price'] ?? '0')));
    $oldPriceRaw = trim((string) ($_POST['old_price'] ?? ''));
    $oldPrice = $oldPriceRaw === '' ? null : str_replace(',', '.', $oldPriceRaw);
    $weight = sanitize_plain($_POST['weight'] ?? '');
    $calories = sanitize_plain($_POST['calories'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isPopular = isset($_POST['is_popular']) ? 1 : 0;
    $isAvailable = isset($_POST['is_available']) ? 1 : 0;

    $errs = [];
    if ($categoryId <= 0) {
        $errs['category_id'] = 'Выберите категорию.';
    }
    if ($name === '') {
        $errs['name'] = 'Укажите название.';
    }
    if (!is_numeric($price) || (float) $price < 0) {
        $errs['price'] = 'Укажите корректную цену.';
    }
    if ($oldPrice !== null && (!is_numeric($oldPrice) || (float) $oldPrice < 0)) {
        $errs['old_price'] = 'Некорректная старая цена.';
    }

    if ($slug === '') {
        $slug = slugify($name);
    } else {
        $slug = slugify($slug);
    }

    $input = [
        'category_id' => $categoryId,
        'name' => $name,
        'slug' => $slug,
        'short_description' => $shortDescription,
        'description' => $description,
        'ingredients' => $ingredients,
        'price' => $price,
        'old_price' => $oldPriceRaw,
        'weight' => $weight,
        'calories' => $calories,
        'sort_order' => $sortOrder,
        'is_popular' => $isPopular,
        'is_available' => $isAvailable,
    ];

    if ($errs === []) {
        try {
            $check = db()->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
            $check->execute([$categoryId]);
            if (!$check->fetch()) {
                throw new RuntimeException('Категория не найдена.');
            }

            $slug = unique_slug('dishes', $slug);
            $image = null;
            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = store_upload($_FILES['image'], 'dishes');
            }

            $stmt = db()->prepare(
                'INSERT INTO dishes
                (category_id, name, slug, short_description, description, ingredients, price, old_price, image, weight, calories, is_popular, is_available, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $categoryId,
                $name,
                $slug,
                $shortDescription !== '' ? $shortDescription : null,
                $description !== '' ? $description : null,
                $ingredients !== '' ? $ingredients : null,
                (float) $price,
                $oldPrice !== null ? (float) $oldPrice : null,
                $image,
                $weight !== '' ? $weight : null,
                $calories !== '' ? $calories : null,
                $isPopular,
                $isAvailable,
                $sortOrder,
            ]);

            flash('success', 'Блюдо создано.');
            redirect('admin/dishes/index.php');
        } catch (Throwable $e) {
            $errs['form'] = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }

    set_form_state($errs, $input);
    redirect('admin/dishes/create.php');
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
      <label for="category_id">Категория</label>
      <select id="category_id" name="category_id" class="<?= e(trim(field_invalid('category_id', $errors))) ?>" required>
        <option value="">— Выберите —</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat['id'] ?>" <?= (int) old('category_id') === (int) $cat['id'] ? 'selected' : '' ?>>
            <?= e((string) $cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?= field_error('category_id', $errors) ?>
    </div>

    <div class="form-group">
      <label for="name">Название</label>
      <input class="<?= e(trim(field_invalid('name', $errors))) ?>" type="text" id="name" name="name" required value="<?= e((string) old('name')) ?>">
      <?= field_error('name', $errors) ?>
    </div>

    <div class="form-group">
      <label for="slug">Slug</label>
      <input type="text" id="slug" name="slug" value="<?= e((string) old('slug')) ?>" placeholder="Авто из названия">
    </div>

    <div class="form-group">
      <label for="price">Цена</label>
      <input class="<?= e(trim(field_invalid('price', $errors))) ?>" type="text" id="price" name="price" required value="<?= e((string) old('price', '0')) ?>">
      <?= field_error('price', $errors) ?>
    </div>

    <div class="form-group">
      <label for="old_price">Старая цена</label>
      <input class="<?= e(trim(field_invalid('old_price', $errors))) ?>" type="text" id="old_price" name="old_price" value="<?= e((string) old('old_price')) ?>">
      <?= field_error('old_price', $errors) ?>
    </div>

    <div class="form-group">
      <label for="weight">Вес / объём</label>
      <input type="text" id="weight" name="weight" value="<?= e((string) old('weight')) ?>">
    </div>

    <div class="form-group">
      <label for="calories">Калории</label>
      <input type="text" id="calories" name="calories" value="<?= e((string) old('calories')) ?>">
    </div>

    <div class="form-group full">
      <label for="short_description">Краткое описание</label>
      <input type="text" id="short_description" name="short_description" maxlength="255" value="<?= e((string) old('short_description')) ?>">
    </div>

    <div class="form-group full">
      <label for="description">Описание</label>
      <textarea id="description" name="description" rows="4"><?= e((string) old('description')) ?></textarea>
    </div>

    <div class="form-group full">
      <label for="ingredients">Ингредиенты</label>
      <textarea id="ingredients" name="ingredients" rows="3"><?= e((string) old('ingredients')) ?></textarea>
    </div>

    <div class="form-group">
      <label for="sort_order">Порядок</label>
      <input type="number" id="sort_order" name="sort_order" value="<?= e((string) old('sort_order', '0')) ?>">
    </div>

    <div class="form-group">
      <label for="image">Изображение</label>
      <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.webp,image/*">
    </div>

    <div class="form-group full checkbox-row">
      <label><input type="checkbox" name="is_popular" value="1" <?= old('is_popular') ? 'checked' : '' ?>> Популярное</label>
      <label><input type="checkbox" name="is_available" value="1" <?= old('is_available', 1) ? 'checked' : '' ?>> Доступно</label>
    </div>

    <div class="form-group full actions">
      <button class="btn" type="submit">Сохранить</button>
      <a class="btn btn-light" href="<?= e(base_url('admin/dishes/index.php')) ?>">Отмена</a>
    </div>
  </form>
</div>

<?php
clear_old_input();
require __DIR__ . '/../includes/admin-footer.php';
?>
