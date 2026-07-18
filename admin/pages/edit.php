<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = db()->prepare('SELECT * FROM pages WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$page = $stmt->fetch();

if (!$page) {
    flash('error', 'Страница не найдена.');
    redirect('admin/pages/index.php');
}

$adminPageTitle = 'Страница: ' . (string) $page['page_key'];
$adminActive = 'pages';
$errors = form_errors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $title = sanitize_plain($_POST['title'] ?? '');
    $subtitle = sanitize_plain($_POST['subtitle'] ?? '');
    $content = sanitize_basic_html($_POST['content'] ?? '');
    $videoUrl = sanitize_plain($_POST['video_url'] ?? '');
    $metaTitle = sanitize_plain($_POST['meta_title'] ?? '');
    $metaDescription = sanitize_plain($_POST['meta_description'] ?? '');
    $errs = [];

    if ($title === '') {
        $errs['title'] = 'Укажите заголовок.';
    }
    if ($videoUrl !== '' && !filter_var($videoUrl, FILTER_VALIDATE_URL)) {
        $errs['video_url'] = 'Некорректный URL видео.';
    }

    if ($errs === []) {
        try {
            $image = $page['image'];
            if (!empty($_FILES['image']) && ($_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $image = store_upload($_FILES['image'], 'pages', $page['image'] ?? null);
            }
            if (!empty($_POST['remove_image']) && $image) {
                delete_upload('pages', (string) $image);
                $image = null;
            }

            $upd = db()->prepare(
                'UPDATE pages SET title = ?, subtitle = ?, content = ?, image = ?, video_url = ?, meta_title = ?, meta_description = ?
                 WHERE id = ?'
            );
            $upd->execute([
                $title,
                $subtitle !== '' ? $subtitle : null,
                $content !== '' ? $content : null,
                $image,
                $videoUrl !== '' ? $videoUrl : null,
                $metaTitle !== '' ? $metaTitle : null,
                $metaDescription !== '' ? $metaDescription : null,
                $id,
            ]);

            flash('success', 'Страница сохранена.');
            redirect('admin/pages/index.php');
        } catch (Throwable $e) {
            $errs['form'] = 'Не удалось сохранить: ' . $e->getMessage();
        }
    }

    set_form_state($errs, [
        'title' => $title,
        'subtitle' => $subtitle,
        'content' => $content,
        'video_url' => $videoUrl,
        'meta_title' => $metaTitle,
        'meta_description' => $metaDescription,
    ]);
    redirect('admin/pages/edit.php?id=' . $id);
}

$title = (string) old('title', $page['title']);
$subtitle = (string) old('subtitle', $page['subtitle'] ?? '');
$content = (string) old('content', $page['content'] ?? '');
$videoUrl = (string) old('video_url', $page['video_url'] ?? '');
$metaTitle = (string) old('meta_title', $page['meta_title'] ?? '');
$metaDescription = (string) old('meta_description', $page['meta_description'] ?? '');
$imgSrc = admin_image_src('pages', $page['image'] ?? null, 'about');

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <p class="admin-muted">Ключ: <code><?= e((string) $page['page_key']) ?></code>. Разрешены теги: p, br, strong, em, ul, ol, li.</p>
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">
    <?php if (!empty($errors['form'])): ?>
      <div class="admin-flash admin-flash-error full"><?= e($errors['form']) ?></div>
    <?php endif; ?>

    <div class="form-group">
      <label for="title">Заголовок</label>
      <input type="text" id="title" name="title" required value="<?= e($title) ?>">
      <?= field_error('title', $errors) ?>
    </div>

    <div class="form-group">
      <label for="subtitle">Подзаголовок</label>
      <input type="text" id="subtitle" name="subtitle" value="<?= e($subtitle) ?>">
    </div>

    <div class="form-group full">
      <label for="content">Контент</label>
      <textarea id="content" name="content" rows="10"><?= e($content) ?></textarea>
    </div>

    <div class="form-group">
      <label for="video_url">URL видео</label>
      <input type="url" id="video_url" name="video_url" value="<?= e($videoUrl) ?>">
      <?= field_error('video_url', $errors) ?>
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
      <label for="meta_title">Meta title</label>
      <input type="text" id="meta_title" name="meta_title" value="<?= e($metaTitle) ?>">
    </div>

    <div class="form-group">
      <label for="meta_description">Meta description</label>
      <input type="text" id="meta_description" name="meta_description" value="<?= e($metaDescription) ?>">
    </div>

    <div class="form-group full actions">
      <button class="btn" type="submit">Сохранить</button>
      <a class="btn btn-light" href="<?= e(base_url('admin/pages/index.php')) ?>">Отмена</a>
    </div>
  </form>
</div>

<?php
clear_old_input();
require __DIR__ . '/../includes/admin-footer.php';
?>
