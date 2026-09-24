<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = ($app['full_name'] ?? $app['name']) . ' — ' . __('nav_home');
$pageDescription = $app['description'];
$bodyClass = 'page-home';

$heroPage = apply_page_translation(fetch_page('home_hero'));
$heroTitle = localize_brand_string(
    translated_setting('hero_title')
        ?: (string) ($heroPage['title'] ?? ($app['full_name'] ?? $app['name']))
);
$heroText = localize_brand_string(
    translated_setting('hero_text')
        ?: (string) ($heroPage['content'] ?? __('popular_text'))
);
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

$mobileHeroSlides = [
    [
        'label' => 'Вкусные блюда',
        'title' => 'Восточный плов',
        'sub' => 'Ароматный рис, мясо и специи каждый день',
        'img' => home_banner_image_url('baneri-1.png', 'banner/mobile-hero-salmon.png'),
        'alt' => 'Восточный плов',
        'wide' => true,
        'overlay' => false,
        'theme' => 'light',
        'cta_href' => base_url('menu.php'),
        'cta_label' => __('hero_menu_btn'),
    ],
    [
        'label' => 'Настоящий вкус',
        'title' => 'Шашлык как искусство',
        'sub' => 'Сочное мясо. Ароматный дым. Незабываемый вкус.',
        'img' => home_banner_image_url('baneri-2.png', 'banner/plate-cut.png'),
        'alt' => 'Шашлык как искусство',
        'wide' => true,
        'overlay' => false,
        'theme' => 'dark',
        'cta_href' => base_url('menu.php'),
        'cta_label' => __('hero_menu_btn'),
    ],
    [
        'label' => 'Душевные встречи',
        'title' => 'Чай, который собирает друзей',
        'sub' => 'Ароматный чай и любимые блюда в уютной атмосфере',
        'img' => home_banner_image_url('baneri-3.png', 'banner/mobile-hero-salmon.png'),
        'alt' => 'Чай, который собирает друзей',
        'wide' => true,
        'overlay' => false,
        'theme' => 'light',
        'cta_href' => base_url('reservation.php'),
        'cta_label' => __('hero_book_btn'),
    ],
    [
        'label' => 'Полезно и вкусно',
        'title' => 'Салаты и лёгкие блюда',
        'sub' => 'Забота о вашем здоровье',
        'img' => home_banner_image_url('baneri-4.png', 'banner/mobile-hero-salmon.png'),
        'alt' => 'Салаты и лёгкие блюда',
        'wide' => true,
        'overlay' => false,
        'theme' => 'light',
        'cta_href' => base_url('menu.php'),
        'cta_label' => __('hero_menu_btn'),
    ],
    [
        'label' => 'Любимые десерты',
        'title' => 'Сладкие моменты',
        'sub' => 'Десерты, которые делают день ярче',
        'img' => home_banner_image_url('baneri-5.png', 'banner/mobile-hero-salmon.png'),
        'alt' => 'Сладкие моменты',
        'wide' => true,
        'overlay' => false,
        'theme' => 'light',
        'cta_href' => base_url('menu.php'),
        'cta_label' => __('hero_menu_btn'),
    ],
];

$dbMobileBanners = fetch_home_banners(true);
if ($dbMobileBanners !== []) {
    $mobileHeroSlides = [];
    foreach ($dbMobileBanners as $i => $bannerRow) {
        $fallbackFiles = [
            'baneri-1.png',
            'baneri-2.png',
            'baneri-3.png',
            'baneri-4.png',
            'baneri-5.png',
        ];
        $fallbackFile = $fallbackFiles[$i] ?? $fallbackFiles[0];
        $titleText = trim((string) ($bannerRow['title'] ?? ''));
        $imageFile = (string) ($bannerRow['image'] ?? '');
        $hasUpload = $imageFile !== '' && is_file(__DIR__ . '/uploads/banners/' . basename($imageFile));
        if (!$hasUpload && $imageFile === '') {
            $imageFile = $fallbackFile;
            $hasUpload = is_file(__DIR__ . '/uploads/banners/' . $fallbackFile);
        }
        $sort = (int) ($bannerRow['sort_order'] ?? ($i + 1));
        $theme = (str_contains($imageFile, 'baneri-2') || $sort === 2) ? 'dark' : 'light';
        $ctaBook = str_contains($imageFile, 'baneri-3') || $sort === 3
            || str_contains(mb_strtolower($titleText), 'чай');
        $showText = (int) ($bannerRow['show_text'] ?? 1) === 1;
        $mobileHeroSlides[] = [
            'label' => trim((string) ($bannerRow['label'] ?? '')) ?: 'Вкусные блюда',
            'title' => $titleText !== '' ? $titleText : 'Восточный плов',
            'sub' => trim((string) ($bannerRow['subtitle'] ?? '')) ?: '',
            'img' => home_banner_image_url($imageFile !== '' ? $imageFile : $fallbackFile, 'banner/mobile-hero-salmon.png'),
            'alt' => $titleText !== '' ? $titleText : 'Восточный плов',
            'wide' => $hasUpload || str_starts_with($fallbackFile, 'baneri-'),
            'overlay' => $hasUpload && $showText,
            'theme' => $theme,
            'cta_href' => $ctaBook ? base_url('reservation.php') : base_url('menu.php'),
            'cta_label' => $ctaBook ? __('hero_book_btn') : __('hero_menu_btn'),
            'hide_dots' => false,
        ];
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="hero hero--premium" aria-label="<?= e($heroTitle) ?>">
  <div class="hero-mobile-banner" data-hero-mobile-banner>
    <div class="hero-mobile-banner__viewport" data-hero-mobile-carousel>
      <?php foreach ($mobileHeroSlides as $i => $slide): ?>
      <?php
      $slideWide = !empty($slide['wide']);
      $slideOverlay = !empty($slide['overlay']);
      $slideTheme = (string) ($slide['theme'] ?? 'light');
      $ctaHref = (string) ($slide['cta_href'] ?? base_url('menu.php'));
      $ctaLabel = (string) ($slide['cta_label'] ?? __('hero_menu_btn'));
      ?>
      <article
        class="hero-mobile-banner__slide<?= $i === 0 ? ' is-active' : '' ?><?= $slideWide ? ' hero-mobile-banner__slide--wide' : '' ?> hero-mobile-banner__slide--<?= e($slideTheme) ?>"
        data-hero-slide="<?= (int) $i ?>"
        aria-hidden="<?= $i === 0 ? 'false' : 'true' ?>"
      >
        <?php if ($slideWide): ?>
        <div class="hero-mobile-banner__inner hero-mobile-banner__inner--wide<?= $slideOverlay ? ' hero-mobile-banner__inner--overlay' : '' ?>">
          <div class="hero-mobile-banner__media hero-mobile-banner__media--wide">
            <img
              src="<?= e($slide['img']) ?>"
              alt="<?= e($slide['alt']) ?>"
              width="1080"
              height="502"
              loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
              decoding="async"
            >
          </div>
          <?php if ($slideOverlay): ?>
          <div class="hero-mobile-banner__copy hero-mobile-banner__copy--overlay">
            <p class="hero-mobile-banner__label"><span class="hero-mobile-banner__label-line" aria-hidden="true"></span><?= e($slide['label']) ?></p>
            <h2 class="hero-mobile-banner__title"><?= e($slide['title']) ?></h2>
            <p class="hero-mobile-banner__sub"><?= e($slide['sub']) ?></p>
            <a class="hero-mobile-banner__cta" href="<?= e($ctaHref) ?>">
              <?= e($ctaLabel) ?>
              <span aria-hidden="true">→</span>
            </a>
          </div>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="hero-mobile-banner__inner">
          <div class="hero-mobile-banner__copy">
            <p class="hero-mobile-banner__label"><span class="hero-mobile-banner__label-line" aria-hidden="true"></span><?= e($slide['label']) ?></p>
            <h2 class="hero-mobile-banner__title"><?= e($slide['title']) ?></h2>
            <p class="hero-mobile-banner__sub"><?= e($slide['sub']) ?></p>
            <a class="hero-mobile-banner__cta" href="<?= e($ctaHref) ?>">
              <?= e($ctaLabel) ?>
              <span aria-hidden="true">→</span>
            </a>
          </div>
          <div class="hero-mobile-banner__media">
            <img src="<?= e($slide['img']) ?>" alt="<?= e($slide['alt']) ?>" width="220" height="200" loading="<?= $i === 0 ? 'eager' : 'lazy' ?>" decoding="async">
          </div>
        </div>
        <?php endif; ?>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="hero-mobile-banner__dots" role="tablist" aria-label="<?= e(__('hero_mb_dots_aria')) ?>">
      <?php foreach ($mobileHeroSlides as $i => $_slide): ?>
      <button
        type="button"
        class="hero-mobile-banner__dot<?= $i === 0 ? ' is-active' : '' ?>"
        data-hero-dot="<?= (int) $i ?>"
        role="tab"
        aria-selected="<?= $i === 0 ? 'true' : 'false' ?>"
        aria-label="<?= e(sprintf(__('hero_mb_dot_aria'), $i + 1, count($mobileHeroSlides))) ?>"
      ></button>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="hero-desktop-only">
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
