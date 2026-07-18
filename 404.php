<?php

declare(strict_types=1);

http_response_code(404);
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Страница не найдена — Aroma';
$pageDescription = 'Запрашиваемая страница не найдена.';
$bodyClass = 'page-404';

require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container page-empty" data-reveal>
    <p class="eyebrow">Ошибка 404</p>
    <h1>Страница не найдена</h1>
    <p class="section-text">Возможно, ссылка устарела или страница была перемещена.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="<?= e(base_url()) ?>">На главную</a>
      <a class="btn btn-outline" href="<?= e(base_url('menu.php')) ?>">Открыть меню</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
