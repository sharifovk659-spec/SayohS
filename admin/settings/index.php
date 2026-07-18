<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Настройки';
$adminActive = 'settings';

$keys = [
    'restaurant_name' => 'Название ресторана',
    'restaurant_full_name' => 'Полное название',
    'tagline' => 'Слоган',
    'phone' => 'Телефон',
    'phone_href' => 'Телефон (ссылка)',
    'whatsapp' => 'WhatsApp URL',
    'email' => 'Email',
    'address' => 'Адрес',
    'map_url' => 'Ссылка на карту',
    'map_embed' => 'Embed карты',
    'rating' => 'Рейтинг',
    'guests_count_label' => 'Подпись гостей',
    'reviews_count_label' => 'Подпись отзывов',
    'hero_title' => 'Hero: заголовок',
    'hero_text' => 'Hero: текст',
    'footer_text' => 'Текст в футере',
    'notify_email' => 'Email уведомлений',
    'meta_title_default' => 'Meta title по умолчанию',
    'meta_description_default' => 'Meta description по умолчанию',
    'about_video_url' => 'Видео «О нас»',
    'base_url' => 'Base URL',
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    try {
        $pairs = [];
        foreach (array_keys($keys) as $key) {
            $pairs[$key] = sanitize_plain($_POST[$key] ?? '');
        }

        $currentLogo = setting('logo', '');
        $currentFavicon = setting('favicon', '');
        $currentHero = setting('hero_image', '');

        if (!empty($_FILES['logo']) && ($_FILES['logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $pairs['logo'] = store_upload($_FILES['logo'], 'settings', $currentLogo ?: null);
        }
        if (!empty($_FILES['favicon']) && ($_FILES['favicon']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $pairs['favicon'] = store_upload($_FILES['favicon'], 'settings', $currentFavicon ?: null);
        }
        if (!empty($_FILES['hero_image']) && ($_FILES['hero_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $pairs['hero_image'] = store_upload($_FILES['hero_image'], 'settings', $currentHero ?: null);
        }

        if (!empty($_POST['remove_logo']) && $currentLogo) {
            delete_upload('settings', $currentLogo);
            $pairs['logo'] = '';
        }
        if (!empty($_POST['remove_favicon']) && $currentFavicon) {
            delete_upload('settings', $currentFavicon);
            $pairs['favicon'] = '';
        }

        save_settings($pairs);
        flash('success', 'Настройки сохранены.');
        redirect('admin/settings/index.php');
    } catch (Throwable $e) {
        flash('error', 'Не удалось сохранить настройки: ' . $e->getMessage());
        redirect('admin/settings/index.php');
    }
}

$logoSrc = admin_image_src('settings', setting('logo'), 'brand');
$faviconSrc = admin_image_src('settings', setting('favicon'), 'icons');
$heroSrc = admin_image_src('settings', setting('hero_image'), 'hero');

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <form method="post" enctype="multipart/form-data" class="form-grid">
    <?= csrf_field() ?>

    <?php foreach ($keys as $key => $label): ?>
      <div class="form-group <?= in_array($key, ['tagline', 'address', 'map_embed', 'hero_text', 'footer_text', 'meta_description_default'], true) ? 'full' : '' ?>">
        <label for="<?= e($key) ?>"><?= e($label) ?></label>
        <?php if (in_array($key, ['hero_text', 'footer_text', 'map_embed', 'meta_description_default'], true)): ?>
          <textarea id="<?= e($key) ?>" name="<?= e($key) ?>" rows="3"><?= e((string) setting($key, '')) ?></textarea>
        <?php else: ?>
          <input type="text" id="<?= e($key) ?>" name="<?= e($key) ?>" value="<?= e((string) setting($key, '')) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div class="form-group">
      <label for="logo">Логотип</label>
      <?php if ($logoSrc): ?>
        <img class="thumb" src="<?= e($logoSrc) ?>" alt="">
        <label><input type="checkbox" name="remove_logo" value="1"> Удалить</label>
      <?php endif; ?>
      <input type="file" id="logo" name="logo" accept=".jpg,.jpeg,.png,.webp,image/*">
    </div>

    <div class="form-group">
      <label for="favicon">Favicon</label>
      <?php if ($faviconSrc): ?>
        <img class="thumb" src="<?= e($faviconSrc) ?>" alt="">
        <label><input type="checkbox" name="remove_favicon" value="1"> Удалить</label>
      <?php endif; ?>
      <input type="file" id="favicon" name="favicon" accept=".jpg,.jpeg,.png,.webp,image/*">
    </div>

    <div class="form-group">
      <label for="hero_image">Hero-изображение</label>
      <?php if ($heroSrc): ?>
        <img class="thumb" src="<?= e($heroSrc) ?>" alt="">
      <?php endif; ?>
      <input type="file" id="hero_image" name="hero_image" accept=".jpg,.jpeg,.png,.webp,image/*">
    </div>

    <div class="form-group full actions">
      <button class="btn" type="submit">Сохранить настройки</button>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
