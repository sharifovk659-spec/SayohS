<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = ($app['full_name'] ?? $app['name']) . ' — ' . __('nav_home');
$pageDescription = $app['description'];
$bodyClass = 'page-home';

$heroPage = apply_page_translation(fetch_page('home_hero'));
$heroTitle = translated_setting('hero_title')
    ?: (string) ($heroPage['title'] ?? ($app['full_name'] ?? $app['name']));
$heroText = translated_setting('hero_text')
    ?: (string) ($heroPage['content'] ?? __('popular_text'));
$heroEyebrow = __('hero_welcome');

$bannerDir = __DIR__ . '/assets/images/banner/';
$pickBanner = static function (string $base) use ($bannerDir): string {
    if (is_file($bannerDir . $base . '.webp')) {
        return 'banner/' . $base . '.webp';
    }
    if (is_file($bannerDir . $base . '.png')) {
        return 'banner/' . $base . '.png';
    }
    return 'banner/' . $base . '.png';
};

$heroPlateSrc = asset('images/' . $pickBanner('plate-cut'));
$decorBasil = asset('images/' . $pickBanner('basil-large'));
$decorLeaf = asset('images/' . $pickBanner('leaf-cluster'));
$decorPepperTrail = asset('images/' . $pickBanner('pepper-trail'));
$decorPepperScatter = asset('images/' . $pickBanner('pepper-scatter'));

$categories = fetch_categories(5);
$popularDishes = fetch_popular_dishes(8);
$galleryPreview = fetch_gallery(null, 6);
$redirectTo = 'index.php';
$formId = 'home-reservation';

require __DIR__ . '/includes/header.php';
?>

<section class="hero hero--premium" aria-label="<?= e($heroTitle) ?>">
  <div class="hero-ambient" aria-hidden="true"></div>

  <div class="hero-decor" aria-hidden="true">
    <img class="hero-decor__img hero-decor__basil--tl" src="<?= e($decorBasil) ?>" alt="" width="220" height="220" loading="eager">
    <img class="hero-decor__img hero-decor__leaf--tr" src="<?= e($decorLeaf) ?>" alt="" width="200" height="140" loading="eager">
    <img class="hero-decor__img hero-decor__pepper--r" src="<?= e($decorPepperTrail) ?>" alt="" width="280" height="190" loading="lazy">
    <img class="hero-decor__img hero-decor__pepper--br" src="<?= e($decorPepperScatter) ?>" alt="" width="200" height="140" loading="lazy">
    <img class="hero-decor__img hero-decor__leaf--bl" src="<?= e($decorLeaf) ?>" alt="" width="160" height="110" loading="lazy">
    <img class="hero-decor__img hero-decor__basil--br" src="<?= e($decorBasil) ?>" alt="" width="150" height="150" loading="lazy">
  </div>

  <div class="container hero-layout">
    <div class="hero-glass" data-reveal>
      <p class="eyebrow"><?= e($heroEyebrow) ?></p>
      <h1 class="hero-brand"><?= e($heroTitle) ?></h1>
      <p class="hero-text"><?= e($heroText) ?></p>
      <div class="hero-actions">
        <a class="btn btn-primary btn-with-arrow" href="<?= e(base_url('menu.php')) ?>">
          <?= e(__('hero_menu_btn')) ?>
          <span aria-hidden="true">→</span>
        </a>
        <a class="btn btn-outline" href="<?= e(base_url('reservation.php')) ?>"><?= e(__('hero_book_btn')) ?></a>
      </div>
      <div class="hero-social-proof">
        <div class="hero-avatars" aria-hidden="true">
          <span></span><span></span><span></span>
        </div>
        <div class="hero-proof-text">
          <strong><?= e(__('hero_guests')) ?></strong>
          <span class="hero-stars" aria-hidden="true">★★★★★</span>
          <span><?= e(__('hero_reviews')) ?></span>
        </div>
      </div>
    </div>

    <div class="hero-stage" data-reveal>
      <div class="hero-plate">
        <img
          src="<?= e($heroPlateSrc) ?>"
          alt="<?= e($heroTitle) ?>"
          width="790"
          height="784"
          fetchpriority="high"
        >
      </div>

      <aside class="hero-fresh-card">
        <span class="hero-fresh-icon" aria-hidden="true">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M8 10.5c0-2.2 1.6-4.2 4-5.5 2.4 1.3 4 3.3 4 5.5a4 4 0 1 1-8 0Z" stroke="currentColor" stroke-width="1.5"/>
            <path d="M7 20h10M9.5 14.5V20M14.5 14.5V20" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
            <path d="M12 5V3.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
          </svg>
        </span>
        <span class="hero-fresh-copy">
          <strong><?= e(__('hero_fresh')) ?></strong>
          <span><?= e(__('hero_fresh_sub')) ?></span>
        </span>
      </aside>

      <img class="hero-stage-leaf hero-stage-leaf--1" src="<?= e($decorLeaf) ?>" alt="" width="140" height="100" aria-hidden="true">
      <img class="hero-stage-leaf hero-stage-leaf--2" src="<?= e($decorBasil) ?>" alt="" width="120" height="120" aria-hidden="true">
      <img class="hero-stage-pepper" src="<?= e($decorPepperScatter) ?>" alt="" width="160" height="110" aria-hidden="true">
    </div>
  </div>
</section>

<?php
require __DIR__ . '/components/categories-section.php';
require __DIR__ . '/components/popular-dishes.php';
require __DIR__ . '/components/about-home.php';
require __DIR__ . '/components/gallery-preview.php';
require __DIR__ . '/components/reservation-form.php';
require __DIR__ . '/includes/footer.php';
?>
