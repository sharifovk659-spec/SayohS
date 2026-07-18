<?php

declare(strict_types=1);

/** @var string $adminActive */
/** @var string $brand */

$adminActive = $adminActive ?? '';
$brand = $brand ?? (setting('restaurant_name', (string) app_config('name', 'Aroma')) ?? 'Aroma');

$nav = [
    'dashboard' => ['Обзор', 'admin/index.php'],
    'categories' => ['Категории', 'admin/categories/index.php'],
    'dishes' => ['Блюда', 'admin/dishes/index.php'],
    'orders' => ['Заказы', 'admin/orders/index.php'],
    'reservations' => ['Бронирования', 'admin/reservations/index.php'],
    'customers' => ['Клиенты', 'admin/customers/index.php'],
    'favorites-stats' => ['Избранное', 'admin/favorites/index.php'],
    'gallery' => ['Галерея', 'admin/gallery/index.php'],
    'pages' => ['Страницы', 'admin/pages/index.php'],
    'translations' => ['Переводы', 'admin/translations/index.php'],
    'messages' => ['Сообщения', 'admin/messages/index.php'],
    'admins' => ['Администраторы', 'admin/admins/index.php'],
    'settings' => ['Настройки', 'admin/settings/index.php'],
    'opening-hours' => ['Часы работы', 'admin/opening-hours/index.php'],
    'social-links' => ['Соцсети', 'admin/social-links/index.php'],
];

if (!admin_is_full_admin()) {
    unset($nav['admins']);
}
?>
<aside class="admin-sidebar" id="admin-sidebar">
  <a class="admin-brand" href="<?= e(base_url('admin/index.php')) ?>">
    <span><?= e($brand) ?></span>
    <small>Админ-панель</small>
  </a>

  <nav class="admin-nav" aria-label="Разделы">
    <?php foreach ($nav as $key => [$label, $href]): ?>
      <a class="<?= $adminActive === $key ? 'is-active' : '' ?>" href="<?= e(base_url($href)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="admin-sidebar-foot">
    <a href="<?= e(base_url()) ?>" target="_blank" rel="noopener">Открыть сайт</a>
    <form method="post" action="<?= e(base_url('admin/logout.php')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-light btn-sm" type="submit">Выйти</button>
    </form>
  </div>
</aside>
<div class="admin-overlay" data-admin-overlay hidden></div>
