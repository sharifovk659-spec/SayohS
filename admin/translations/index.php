<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Переводы';
$adminActive = 'translations';

$lang = (string) ($_GET['lang'] ?? 'en');
$entity = (string) ($_GET['entity'] ?? 'dishes');
$itemId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$allowedLangs = ['ru', 'en', 'de'];
$allowedEntities = ['categories', 'dishes', 'pages'];

if (!in_array($lang, $allowedLangs, true)) {
    $lang = 'en';
}
if (!in_array($entity, $allowedEntities, true)) {
    $entity = 'dishes';
}

$errors = form_errors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $postLang = (string) ($_POST['lang'] ?? '');
    $postEntity = (string) ($_POST['entity'] ?? '');
    $postId = (int) ($_POST['id'] ?? 0);

    if (!in_array($postLang, $allowedLangs, true) || !in_array($postEntity, $allowedEntities, true) || $postId <= 0) {
        flash('error', 'Некорректные параметры.');
        redirect('admin/translations/index.php');
    }

    $errs = [];
    $redirectParams = ['lang' => $postLang, 'entity' => $postEntity, 'id' => $postId];

    try {
        if ($postEntity === 'categories') {
            $name = sanitize_plain($_POST['name'] ?? '');
            $description = sanitize_plain($_POST['description'] ?? '');

            if ($name === '') {
                $errs['name'] = 'Укажите название.';
            } else {
                upsert_translation_row('category_translations', [
                    'category_id' => $postId,
                    'language_code' => $postLang,
                ], [
                    'name' => $name,
                    'description' => $description !== '' ? $description : null,
                ]);
            }
        } elseif ($postEntity === 'dishes') {
            $name = sanitize_plain($_POST['name'] ?? '');
            $shortDescription = sanitize_plain($_POST['short_description'] ?? '');
            $description = sanitize_plain($_POST['description'] ?? '');
            $ingredients = sanitize_plain($_POST['ingredients'] ?? '');

            if ($name === '') {
                $errs['name'] = 'Укажите название.';
            } else {
                upsert_translation_row('dish_translations', [
                    'dish_id' => $postId,
                    'language_code' => $postLang,
                ], [
                    'name' => $name,
                    'short_description' => $shortDescription !== '' ? $shortDescription : null,
                    'description' => $description !== '' ? $description : null,
                    'ingredients' => $ingredients !== '' ? $ingredients : null,
                ]);
            }
        } else {
            $title = sanitize_plain($_POST['title'] ?? '');
            $subtitle = sanitize_plain($_POST['subtitle'] ?? '');
            $content = sanitize_basic_html($_POST['content'] ?? '');
            $metaTitle = sanitize_plain($_POST['meta_title'] ?? '');
            $metaDescription = sanitize_plain($_POST['meta_description'] ?? '');

            if ($title === '') {
                $errs['title'] = 'Укажите заголовок.';
            } else {
                upsert_translation_row('page_translations', [
                    'page_id' => $postId,
                    'language_code' => $postLang,
                ], [
                    'title' => $title,
                    'subtitle' => $subtitle !== '' ? $subtitle : null,
                    'content' => $content !== '' ? $content : null,
                    'meta_title' => $metaTitle !== '' ? $metaTitle : null,
                    'meta_description' => $metaDescription !== '' ? $metaDescription : null,
                ]);
            }
        }

        if ($errs === []) {
            flash('success', 'Перевод сохранён.');
            redirect('admin/translations/index.php?' . http_build_query($redirectParams));
        }

        set_form_state($errs, $_POST);
        redirect('admin/translations/index.php?' . http_build_query($redirectParams));
    } catch (Throwable $e) {
        flash('error', 'Не удалось сохранить: ' . $e->getMessage());
        redirect('admin/translations/index.php?' . http_build_query($redirectParams));
    }
}

$entityLabels = [
    'categories' => 'Категории',
    'dishes' => 'Блюда',
    'pages' => 'Страницы',
];

$langLabels = [
    'ru' => 'RU',
    'en' => 'EN',
    'de' => 'DE',
];

$items = [];
$translation = [];
$ruFallback = [];

if ($entity === 'categories') {
    $items = db()->query('SELECT id, name FROM categories ORDER BY sort_order ASC, name ASC')->fetchAll();
} elseif ($entity === 'dishes') {
    $items = db()->query(
        'SELECT d.id, d.name, c.name AS category_name
         FROM dishes d
         LEFT JOIN categories c ON c.id = d.category_id
         ORDER BY c.sort_order ASC, d.sort_order ASC, d.name ASC'
    )->fetchAll();
} else {
    $items = db()->query('SELECT id, page_key, title FROM pages ORDER BY page_key ASC')->fetchAll();
}

if ($itemId > 0) {
    if ($entity === 'categories') {
        $check = db()->prepare('SELECT id, name, description FROM categories WHERE id = ? LIMIT 1');
        $check->execute([$itemId]);
        $base = $check->fetch();
        if (!$base) {
            flash('error', 'Запись не найдена.');
            redirect('admin/translations/index.php?lang=' . urlencode($lang) . '&entity=' . urlencode($entity));
        }
        $ruFallback = ['name' => (string) $base['name'], 'description' => (string) ($base['description'] ?? '')];

        $tr = db()->prepare(
            'SELECT name, description FROM category_translations WHERE category_id = ? AND language_code = ? LIMIT 1'
        );
        $tr->execute([$itemId, $lang]);
        $translation = $tr->fetch() ?: [];
    } elseif ($entity === 'dishes') {
        $check = db()->prepare(
            'SELECT id, name, short_description, description, ingredients FROM dishes WHERE id = ? LIMIT 1'
        );
        $check->execute([$itemId]);
        $base = $check->fetch();
        if (!$base) {
            flash('error', 'Запись не найдена.');
            redirect('admin/translations/index.php?lang=' . urlencode($lang) . '&entity=' . urlencode($entity));
        }
        $ruFallback = [
            'name' => (string) $base['name'],
            'short_description' => (string) ($base['short_description'] ?? ''),
            'description' => (string) ($base['description'] ?? ''),
            'ingredients' => (string) ($base['ingredients'] ?? ''),
        ];

        $tr = db()->prepare(
            'SELECT name, short_description, description, ingredients
             FROM dish_translations WHERE dish_id = ? AND language_code = ? LIMIT 1'
        );
        $tr->execute([$itemId, $lang]);
        $translation = $tr->fetch() ?: [];
    } else {
        $check = db()->prepare(
            'SELECT id, title, subtitle, content, meta_title, meta_description FROM pages WHERE id = ? LIMIT 1'
        );
        $check->execute([$itemId]);
        $base = $check->fetch();
        if (!$base) {
            flash('error', 'Запись не найдена.');
            redirect('admin/translations/index.php?lang=' . urlencode($lang) . '&entity=' . urlencode($entity));
        }
        $ruFallback = [
            'title' => (string) $base['title'],
            'subtitle' => (string) ($base['subtitle'] ?? ''),
            'content' => (string) ($base['content'] ?? ''),
            'meta_title' => (string) ($base['meta_title'] ?? ''),
            'meta_description' => (string) ($base['meta_description'] ?? ''),
        ];

        $tr = db()->prepare(
            'SELECT title, subtitle, content, meta_title, meta_description
             FROM page_translations WHERE page_id = ? AND language_code = ? LIMIT 1'
        );
        $tr->execute([$itemId, $lang]);
        $translation = $tr->fetch() ?: [];
    }
}

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <nav class="admin-tabs" aria-label="Язык">
    <?php foreach ($allowedLangs as $code): ?>
      <a class="admin-tab<?= $lang === $code ? ' is-active' : '' ?>"
         href="<?= e(admin_url('admin/translations/index.php', ['lang' => $code, 'entity' => $entity])) ?>">
        <?= e($langLabels[$code]) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <form class="admin-filters" method="get" action="">
    <input type="hidden" name="lang" value="<?= e($lang) ?>">
    <div class="form-group">
      <label for="entity">Тип</label>
      <select id="entity" name="entity" onchange="this.form.submit()">
        <?php foreach ($allowedEntities as $ent): ?>
          <option value="<?= e($ent) ?>" <?= $entity === $ent ? 'selected' : '' ?>><?= e($entityLabels[$ent]) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <?php if ($lang !== 'ru'): ?>
    <p class="admin-muted">Пустые поля на сайте будут использовать русский текст (RU) как запасной вариант.</p>
  <?php endif; ?>
</div>

<div class="admin-grid-2">
  <section class="admin-panel">
    <h2 class="admin-panel-title"><?= e($entityLabels[$entity]) ?></h2>
    <?php if (!$items): ?>
      <p class="admin-empty">Записей нет.</p>
    <?php else: ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Название (RU)</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($items as $item): ?>
              <?php
              $label = $entity === 'pages'
                  ? (string) $item['page_key'] . ' — ' . (string) $item['title']
                  : (string) $item['name'];
              if ($entity === 'dishes' && !empty($item['category_name'])) {
                  $label = (string) $item['category_name'] . ' / ' . $label;
              }
              ?>
              <tr class="<?= $itemId === (int) $item['id'] ? 'is-selected' : '' ?>">
                <td><?= (int) $item['id'] ?></td>
                <td>
                  <a href="<?= e(admin_url('admin/translations/index.php', ['lang' => $lang, 'entity' => $entity, 'id' => (int) $item['id']])) ?>">
                    <?= e($label) ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </section>

  <section class="admin-panel">
    <?php if ($itemId <= 0): ?>
      <p class="admin-empty">Выберите запись для редактирования перевода.</p>
    <?php else: ?>
      <h2 class="admin-panel-title">Перевод (<?= e($langLabels[$lang]) ?>)</h2>

      <?php if ($lang !== 'ru' && $ruFallback !== []): ?>
        <details class="admin-fallback">
          <summary>Русский оригинал (RU)</summary>
          <dl class="detail-list">
            <?php foreach ($ruFallback as $key => $val): ?>
              <?php if ($val !== ''): ?>
                <div>
                  <dt><?= e($key) ?></dt>
                  <dd><?= nl2br(e($val)) ?></dd>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </dl>
        </details>
      <?php endif; ?>

      <form method="post" class="form-grid" style="grid-template-columns:1fr">
        <?= csrf_field() ?>
        <input type="hidden" name="lang" value="<?= e($lang) ?>">
        <input type="hidden" name="entity" value="<?= e($entity) ?>">
        <input type="hidden" name="id" value="<?= $itemId ?>">

        <?php if ($entity === 'categories'): ?>
          <div class="form-group">
            <label for="name">Название *</label>
            <input class="<?= e(field_invalid('name', $errors)) ?>" type="text" id="name" name="name" required
                   value="<?= e((string) old('name', $translation['name'] ?? '')) ?>">
            <?= field_error('name', $errors) ?>
          </div>
          <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="4"><?= e((string) old('description', $translation['description'] ?? '')) ?></textarea>
          </div>
        <?php elseif ($entity === 'dishes'): ?>
          <div class="form-group">
            <label for="name">Название *</label>
            <input class="<?= e(field_invalid('name', $errors)) ?>" type="text" id="name" name="name" required
                   value="<?= e((string) old('name', $translation['name'] ?? '')) ?>">
            <?= field_error('name', $errors) ?>
          </div>
          <div class="form-group">
            <label for="short_description">Краткое описание</label>
            <input type="text" id="short_description" name="short_description" maxlength="255"
                   value="<?= e((string) old('short_description', $translation['short_description'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="description">Описание</label>
            <textarea id="description" name="description" rows="4"><?= e((string) old('description', $translation['description'] ?? '')) ?></textarea>
          </div>
          <div class="form-group">
            <label for="ingredients">Ингредиенты</label>
            <textarea id="ingredients" name="ingredients" rows="3"><?= e((string) old('ingredients', $translation['ingredients'] ?? '')) ?></textarea>
          </div>
        <?php else: ?>
          <div class="form-group">
            <label for="title">Заголовок *</label>
            <input class="<?= e(field_invalid('title', $errors)) ?>" type="text" id="title" name="title" required
                   value="<?= e((string) old('title', $translation['title'] ?? '')) ?>">
            <?= field_error('title', $errors) ?>
          </div>
          <div class="form-group">
            <label for="subtitle">Подзаголовок</label>
            <input type="text" id="subtitle" name="subtitle"
                   value="<?= e((string) old('subtitle', $translation['subtitle'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="content">Контент</label>
            <textarea id="content" name="content" rows="6"><?= e((string) old('content', $translation['content'] ?? '')) ?></textarea>
          </div>
          <div class="form-group">
            <label for="meta_title">Meta title</label>
            <input type="text" id="meta_title" name="meta_title"
                   value="<?= e((string) old('meta_title', $translation['meta_title'] ?? '')) ?>">
          </div>
          <div class="form-group">
            <label for="meta_description">Meta description</label>
            <textarea id="meta_description" name="meta_description" rows="2"><?= e((string) old('meta_description', $translation['meta_description'] ?? '')) ?></textarea>
          </div>
        <?php endif; ?>

        <div class="actions">
          <button class="btn" type="submit">Сохранить</button>
        </div>
      </form>
    <?php endif; ?>
  </section>
</div>

<?php
clear_old_input();
require __DIR__ . '/../includes/admin-footer.php';
?>
