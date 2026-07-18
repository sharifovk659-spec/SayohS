<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Политика конфиденциальности — Aroma';
$pageDescription = 'Политика конфиденциальности ресторана Aroma.';
$bodyClass = 'page-legal';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">Документы</p>
      <h1>Политика конфиденциальности</h1>
    </div>
  </div>
</section>

<section class="section">
  <div class="container legal-content">
    <p>Настоящая политика описывает, какие данные мы получаем через формы сайта Aroma Restaurant и как используем их для обработки заявок.</p>
    <h2>Какие данные собираем</h2>
    <p>Имя, телефон, email, дату и время бронирования, комментарии и сообщения из формы обратной связи.</p>
    <h2>Для чего используем</h2>
    <p>Только для связи с гостем, подтверждения брони и ответа на обращения. Мы не продаём персональные данные третьим лицам.</p>
    <h2>Хранение</h2>
    <p>Данные хранятся ограниченный срок, необходимый для обработки обращения и внутренних процессов ресторана.</p>
    <h2>Контакты</h2>
    <p>По вопросам обработки данных: <a href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a>.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
