<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'О ресторане — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = 'История и философия ресторана Aroma.';
$bodyClass = 'page-about';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">О нас</p>
      <h1>Добро пожаловать в Aroma</h1>
      <p>Мы объединяем свежие продукты, современную подачу и внимательный сервис.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="about-home" data-reveal>
      <div class="about-home-media">
        <img src="<?= e(hero_image_url('about-preview.webp')) ?>" alt="Интерьер Aroma" width="900" height="1100" loading="lazy" onerror="this.onerror=null;this.src='<?= e(asset('images/hero/about-preview.svg')) ?>'">
      </div>
      <div class="about-home-copy">
        <h2 class="section-title">Кухня, сервис и атмосфера</h2>
        <p class="section-text">Aroma — место для спокойных ужинов и особенных встреч. Мы готовим из свежих продуктов и следим за каждой деталью сервировки.</p>
        <ul class="about-benefits">
          <li><span class="about-benefit-icon" aria-hidden="true">✓</span> Свежие продукты</li>
          <li><span class="about-benefit-icon" aria-hidden="true">✓</span> Высокое качество</li>
          <li><span class="about-benefit-icon" aria-hidden="true">✓</span> Любовь к деталям</li>
        </ul>
        <a class="btn btn-primary" href="<?= e(base_url('reservation.php')) ?>">Забронировать стол</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
