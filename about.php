<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$aboutPage = apply_page_translation(fetch_page('about'));
$aboutTitle = localize_brand_string(
    (string) ($aboutPage['title'] ?? ('Добро пожаловать в ' . site_brand_full()))
);
$aboutSubtitle = localize_brand_string((string) ($aboutPage['subtitle'] ?? 'О нас'));
$aboutContent = localize_brand_string((string) ($aboutPage['content'] ?? ''));
$pageTitle = localize_brand_string((string) ($aboutPage['meta_title'] ?? ('О ресторане — ' . site_brand_full())));
$pageDescription = localize_brand_string(
    (string) ($aboutPage['meta_description'] ?? (string) ($app['description'] ?? ''))
);
$bodyClass = 'page-about';

$aboutParagraph = $aboutContent !== ''
    ? $aboutContent
    : 'Мы объединяем свежие продукты, современную подачу и внимательный сервис.';
$aboutBenefits = ['Свежие продукты', 'Высокое качество', 'Любовь к деталям'];
if ($aboutContent !== '') {
    $parts = preg_split("/\n+/", $aboutContent) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn (string $p): bool => $p !== ''));
    if ($parts !== []) {
        $aboutParagraph = array_shift($parts);
        if (count($parts) >= 3) {
            $aboutBenefits = array_slice($parts, 0, 3);
        }
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow"><?= e($aboutSubtitle) ?></p>
      <h1><?= e($aboutTitle) ?></h1>
      <p><?= e($aboutParagraph) ?></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="about-home" data-reveal>
      <div class="about-home-media">
        <img src="<?= e(hero_image_url('about-preview.webp')) ?>" alt="<?= e(site_brand_full()) ?>" width="900" height="1100" loading="lazy" onerror="this.onerror=null;this.src='<?= e(asset('images/hero/about-preview.svg')) ?>'">
      </div>
      <div class="about-home-copy">
        <h2 class="section-title">Кухня, сервис и атмосфера</h2>
        <p class="section-text"><?= e(site_brand_full()) ?> — место для спокойных ужинов и особенных встреч. Мы готовим из свежих продуктов и следим за каждой деталью сервировки.</p>
        <ul class="about-benefits">
          <?php foreach ($aboutBenefits as $benefit): ?>
          <li><span class="about-benefit-icon" aria-hidden="true">✓</span> <?= e($benefit) ?></li>
          <?php endforeach; ?>
        </ul>
        <a class="btn btn-primary" href="<?= e(base_url('reservation.php')) ?>">Забронировать стол</a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
