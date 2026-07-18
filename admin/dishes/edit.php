<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM dishes WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$dish = $stmt->fetch();

if (!$dish) {
    flash('error', 'Блюдо не найдено.');
    redirect('admin/dishes/index.php');
}

$adminPageTitle = 'Редактирование блюда';
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

            $slug = unique_slug('dishes', $slug, $id);
            $image = $dish['image'];

            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = store_upload($_FILES['image'], 'dishes', $dish['image'] ?? null);
            }

            if (!empty($_POST['remove_image']) && $image) {
                delete_upload('dishes', (string) $image);
                $image = null;
            }

            $upd = db()->prepare(
                'UPDATE dishes SET
                    category_id = ?, name = ?, slug = ?, short_description = ?, description = ?, ingredients = ?,
                    price = ?, old_price = ?, image = ?, weight = ?, calories = ?, is_popular = ?, is_available = ?, sort_order = ?
                 WHERE id = ?'
            );
            $upd->execute([
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
                $id,
            ]);

            foreach (['en', 'de'] as $langCode) {
                $trName = sanitize_plain($_POST['tr_' . $langCode . '_name'] ?? '');
                $trShort = sanitize_plain($_POST['tr_' . $langCode . '_short_description'] ?? '');
                $trDesc = sanitize_plain($_POST['tr_' . $langCode . '_description'] ?? '');
                $trIng = sanitize_plain($_POST['tr_' . $langCode . '_ingredients'] ?? '');
                if ($trName !== '') {
                    upsert_translation_row('dish_translations', [
                        'dish_id' => $id,
                        'language_code' => $langCode,
                    ], [
                        'name' => $trName,
                        'short_description' => $trShort !== '' ? $trShort : null,
                        'description' => $trDesc !== '' ? $trDesc : null,
                        'ingredients' => $trIng !== '' ? $trIng : null,
                    ]);
                }
            }

            flash('success', 'Блюдо обновлено.');
            redirect('admin/dishes/index.php');
        } catch (Throwable $e) {
            $errs['form'] = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }

    set_form_state($errs, $input);
    redirect('admin/dishes/edit.php?id=' . $id);
}

$categoryId = (int) old('category_id', $dish['category_id']);
$name = (string) old('name', $dish['name']);
$slug = (string) old('slug', $dish['slug']);
$shortDescription = (string) old('short_description', $dish['short_description'] ?? '');
$description = (string) old('description', $dish['description'] ?? '');
$ingredients = (string) old('ingredients', $dish['ingredients'] ?? '');
$price = (string) old('price', (string) $dish['price']);
$oldPrice = (string) old('old_price', $dish['old_price'] !== null ? (string) $dish['old_price'] : '');
$weight = (string) old('weight', $dish['weight'] ?? '');
$calories = (string) old('calories', $dish['calories'] ?? '');
$sortOrder = (string) old('sort_order', (string) $dish['sort_order']);
$isPopular = (int) old('is_popular', (int) $dish['is_popular']);
$isAvailable = (int) old('is_available', (int) $dish['is_available']);
$imgSrc = admin_image_src('dishes', $dish['image'] ?? null);

$dishTranslations = ['en' => [], 'de' => []];
try {
    $trStmt = db()->prepare(
        'SELECT language_code, name, short_description, description, ingredients
         FROM dish_translations WHERE dish_id = ? AND language_code IN (\'en\', \'de\')'
    );
    $trStmt->execute([$id]);
    foreach ($trStmt->fetchAll() as $trRow) {
        $dishTranslations[(string) $trRow['language_code']] = $trRow;
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
      <label for="category_id">Категория</label>
      <select id="category_id" name="category_id" required>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int) $cat['id'] ?>" <?= $categoryId === (int) $cat['id'] ? 'selected' : '' ?>>
            <?= e((string) $cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <?= field_error('category_id', $errors) ?>
    </div>

    <div class="form-group">
      <label for="name">Название</label>
      <input type="text" id="name" name="name" required value="<?= e($name) ?>">
      <?= field_error('name', $errors) ?>
    </div>

    <div class="form-group">
      <label for="slug">Slug</label>
      <input type="text" id="slug" name="slug" value="<?= e($slug) ?>">
    </div>

    <div class="form-group">
      <label for="price">Цена</label>
      <input type="text" id="price" name="price" required value="<?= e($price) ?>">
      <?= field_error('price', $errors) ?>
    </div>

    <div class="form-group">
      <label for="old_price">Старая цена</label>
      <input type="text" id="old_price" name="old_price" value="<?= e($oldPrice) ?>">
      <?= field_error('old_price', $errors) ?>
    </div>

    <div class="form-group">
      <label for="weight">Вес / объём</label>
      <input type="text" id="weight" name="weight" value="<?= e($weight) ?>">
    </div>

    <div class="form-group">
      <label for="calories">Калории</label>
      <input type="text" id="calories" name="calories" value="<?= e($calories) ?>">
    </div>

    <div class="form-group full">
      <label for="short_description">Краткое описание</label>
      <input type="text" id="short_description" name="short_description" maxlength="255" value="<?= e($shortDescription) ?>">
    </div>

    <div class="form-group full">
      <label for="description">Описание</label>
      <textarea id="description" name="description" rows="4"><?= e($description) ?></textarea>
    </div>

    <div class="form-group full">
      <label for="ingredients">Ингредиенты</label>
      <textarea id="ingredients" name="ingredients" rows="3"><?= e($ingredients) ?></textarea>
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

    <div class="form-group full checkbox-row">
      <label><input type="checkbox" name="is_popular" value="1" <?= $isPopular ? 'checked' : '' ?>> Популярное</label>
      <label><input type="checkbox" name="is_available" value="1" <?= $isAvailable ? 'checked' : '' ?>> Доступно</label>
    </div>

    <div class="form-group full">
      <h3 class="admin-panel-title">Переводы</h3>
      <p class="admin-muted">Пустые поля на сайте используют русский текст. Полное редактирование — в разделе «Переводы».</p>
      <nav class="admin-tabs" aria-label="Языки перевода">
        <button type="button" class="admin-tab is-active" data-tab="tr-en">EN</button>
        <button type="button" class="admin-tab" data-tab="tr-de">DE</button>
      </nav>
      <?php foreach (['en' => 'EN', 'de' => 'DE'] as $code => $label): ?>
        <div class="admin-tab-panel<?= $code === 'en' ? ' is-active' : '' ?>" id="tr-<?= e($code) ?>"<?= $code !== 'en' ? ' hidden' : '' ?>>
          <?php $tr = $dishTranslations[$code] ?? []; ?>
          <div class="form-group">
            <label for="tr_<?= e($code) ?>_name">Название (<?= e($label) ?>)</label>
            <input type="text" id="tr_<?= e($code) ?>_name" name="tr_<?= e($code) ?>_name"
                   value="<?= e((string) old('tr_' . $code . '_name', $tr['name'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="tr_<?= e($code) ?>_short_description">Краткое описание</label>
            <input type="text" id="tr_<?= e($code) ?>_short_description" name="tr_<?= e($code) ?>_short_description" maxlength="255"
                   value="<?= e((string) old('tr_' . $code . '_short_description', $tr['short_description'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="tr_<?= e($code) ?>_description">Описание</label>
            <textarea id="tr_<?= e($code) ?>_description" name="tr_<?= e($code) ?>_description" rows="3"><?= e((string) old('tr_' . $code . '_description', $tr['description'] ?? '')) ?></textarea>
          </div>
          <div class="form-group">
            <label for="tr_<?= e($code) ?>_ingredients">Ингредиенты</label>
            <textarea id="tr_<?= e($code) ?>_ingredients" name="tr_<?= e($code) ?>_ingredients" rows="2"><?= e((string) old('tr_' . $code . '_ingredients', $tr['ingredients'] ?? '')) ?></textarea>
          </div>
        </div>
      <?php endforeach; ?>
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
