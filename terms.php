<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Пользовательское соглашение — Aroma';
$pageDescription = 'Пользовательское соглашение сайта Aroma Restaurant.';
$bodyClass = 'page-legal';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">Документы</p>
      <h1>Пользовательское соглашение</h1>
    </div>
  </div>
</section>

<section class="section">
  <div class="container legal-content">
    <p>Используя сайт Aroma Restaurant, вы соглашаетесь с правилами бронирования и публикации информации.</p>
    <h2>Бронирование</h2>
    <p>Заявка на сайте не гарантирует автоматическое подтверждение стола. Окончательное подтверждение делает администратор.</p>
    <h2>Контент</h2>
    <p>Тексты, фотографии и описания меню принадлежат ресторану. Копирование без согласия запрещено.</p>
    <h2>Ответственность</h2>
    <p>Мы стараемся поддерживать актуальность меню и цен, однако отдельные позиции могут временно отсутствовать.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
