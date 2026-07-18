<?php

declare(strict_types=1);

/** @var array $app */
$pageTitle = $pageTitle ?? (setting('meta_title_default') ?: ($app['full_name'] ?? $app['name']));
$pageDescription = $pageDescription ?? (setting('meta_description_default') ?: ($app['description'] ?? ''));
$bodyClass = $bodyClass ?? '';
$flash = get_flash();
$ogImage = $pageOgImage ?? hero_image_url(setting('hero_image'));
$canonicalPath = $pageCanonical ?? ltrim((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'), '/');
if (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' && str_contains($canonicalPath, 'dish.php')) {
    $slug = trim((string) ($_GET['slug'] ?? ''));
    $canonicalUrl = base_url('dish.php' . ($slug !== '' ? '?slug=' . rawurlencode($slug) : ''));
} elseif (($pageCanonicalUrl ?? null) !== null) {
    $canonicalUrl = (string) $pageCanonicalUrl;
} else {
    $script = basename((string) ($_SERVER['SCRIPT_NAME'] ?? 'index.php'));
    $canonicalUrl = $script === 'index.php' ? base_url() : base_url($script);
}
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (!str_starts_with($canonicalUrl, 'http')) {
    $canonicalUrl = $scheme . '://' . $host . $canonicalUrl;
}
if (!str_starts_with($ogImage, 'http')) {
    $ogImageAbs = $scheme . '://' . $host . $ogImage;
} else {
    $ogImageAbs = $ogImage;
}
$favicon = setting('favicon') ?: null;
if ($favicon) {
    $faviconUpload = __DIR__ . '/../uploads/settings/' . basename($favicon);
    $faviconUrl = is_file($faviconUpload)
        ? upload_url('settings', basename($favicon))
        : asset('icons/favicon.png');
} else {
    $faviconUrl = is_file(__DIR__ . '/../assets/icons/favicon.png')
        ? asset('icons/favicon.png')
        : asset('icons/favicon.svg');
}

$logoFile = setting('logo') ?: 'sayoh-logo.png';
$logoUpload = __DIR__ . '/../uploads/settings/' . basename((string) $logoFile);
$logoAsset = __DIR__ . '/../assets/images/brand/sayoh-logo.png';
// Prefer tracked brand asset (Git/deploy), then uploaded logo
if (is_file($logoAsset)) {
    $logoUrl = asset('images/brand/sayoh-logo.png');
} elseif ($logoFile !== '' && is_file($logoUpload)) {
    $logoUrl = upload_url('settings', basename((string) $logoFile));
} else {
    $logoUrl = '';
}

$currentUser = function_exists('current_user') ? current_user() : null;
$cartCount = function_exists('cart_count') ? cart_count() : 0;
$favCount = function_exists('favorites_count') ? favorites_count() : 0;
$lang = function_exists('current_lang') ? current_lang() : 'ru';
$csrf = function_exists('csrf_token') ? csrf_token() : '';
$phoneHref = (string) (setting('phone_href') ?: ($app['phone_href'] ?? ''));
$phoneTel = $phoneHref !== '' ? 'tel:' . preg_replace('/[^\d+]/', '', $phoneHref) : base_url('contacts.php');
?>
<!DOCTYPE html>
<html lang="<?= e(function_exists('html_lang_attr') ? html_lang_attr() : 'ru') ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($pageDescription) ?>">
  <link rel="canonical" href="<?= e($canonicalUrl) ?>">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="<?= e(function_exists('og_locale') ? og_locale() : 'ru_RU') ?>">
  <meta property="og:title" content="<?= e($pageTitle) ?>">
  <meta property="og:description" content="<?= e($pageDescription) ?>">
  <meta property="og:url" content="<?= e($canonicalUrl) ?>">
  <meta property="og:image" content="<?= e($ogImageAbs) ?>">
  <meta property="og:site_name" content="<?= e((string) ($app['full_name'] ?? $app['name'])) ?>">
  <meta name="csrf-token" content="<?= e($csrf) ?>">
  <title><?= e($pageTitle) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/main.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/home.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/glass.css')) ?>">
  <link rel="stylesheet" href="<?= e(asset('css/mobile-wow.css')) ?>">
  <link rel="icon" href="<?= e($faviconUrl) ?>" type="<?= str_ends_with($faviconUrl, '.svg') ? 'image/svg+xml' : 'image/png' ?>">
</head>
<body class="<?= e($bodyClass) ?>" data-base="<?= e(rtrim(base_url(), '/')) ?>">
  <a class="skip-link" href="#main"><?= e(__('skip_to_content')) ?></a>

  <header class="site-header site-header--compact" data-header>
    <div class="container header-inner">
      <a class="brand" href="<?= e(base_url()) ?>" aria-label="<?= e($app['full_name'] ?? $app['name']) ?>">
        <?php if ($logoUrl !== ''): ?>
          <img class="brand-logo" src="<?= e($logoUrl) ?>" alt="<?= e((string) ($app['full_name'] ?? $app['name'])) ?>" width="72" height="72" decoding="async">
        <?php else: ?>
          <span class="brand-mark" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <circle cx="14" cy="14" r="13" stroke="currentColor" stroke-width="1.5"/>
              <path d="M9 17.5c1.8-3.2 3.2-6.8 5-10.2 1.7 3.4 3.1 7 5 10.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
              <circle cx="14" cy="18.5" r="1.4" fill="currentColor"/>
            </svg>
          </span>
        <?php endif; ?>
      </a>

      <nav class="nav-desktop" aria-label="<?= e(__('nav_home')) ?>">
        <a class="<?= e(nav_class('index')) ?>" href="<?= e(base_url()) ?>"><?= e(__('nav_home')) ?></a>
        <a class="<?= e(nav_class('menu')) ?>" href="<?= e(base_url('menu.php')) ?>"><?= e(__('nav_menu')) ?></a>
        <a class="<?= e(nav_class('gallery')) ?>" href="<?= e(base_url('gallery.php')) ?>"><?= e(__('nav_gallery')) ?></a>
        <a class="<?= e(nav_class('reservation')) ?>" href="<?= e(base_url('reservation.php')) ?>"><?= e(__('nav_reservation')) ?></a>
        <a class="<?= e(nav_class('contacts')) ?>" href="<?= e(base_url('contacts.php')) ?>"><?= e(__('nav_contacts')) ?></a>
      </nav>

      <div class="header-actions">
        <div class="lang-switch" data-lang-switch>
          <button
            type="button"
            class="lang-switch-toggle"
            data-lang-toggle
            aria-expanded="false"
            aria-haspopup="listbox"
            aria-label="<?= e(__('lang_label')) ?>"
          >
            <span data-lang-current><?= e(strtoupper($lang)) ?></span>
            <svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M3 4.5 6 7.5 9 4.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" fill="none"/></svg>
          </button>
          <div class="lang-switch-menu" data-lang-menu hidden role="listbox">
            <?php foreach (['ru' => 'RU', 'en' => 'EN', 'de' => 'DE'] as $code => $label): ?>
              <a
                class="lang-link<?= $lang === $code ? ' is-active' : '' ?>"
                href="<?= e(lang_url($code)) ?>"
                hreflang="<?= e($code) ?>"
                role="option"
                <?= $lang === $code ? 'aria-selected="true"' : 'aria-selected="false"' ?>
                data-lang-option
              ><?= e($label) ?></a>
            <?php endforeach; ?>
          </div>
        </div>

        <a class="header-icon-link" href="<?= e(base_url($currentUser ? 'account/favorites.php' : 'login.php')) ?>" aria-label="<?= e(__('nav_favorites')) ?>">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          <span class="header-count" data-fav-count <?= $favCount > 0 ? '' : 'hidden' ?>><?= (int) $favCount ?></span>
        </a>

        <a class="header-icon-link" href="<?= e(base_url('cart.php')) ?>" aria-label="<?= e(__('nav_cart')) ?>" data-cart-link>
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 8h12l-1 11H7L6 8Zm3-3h6a2 2 0 0 1 2 2v1H7V7a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          <span class="header-count" data-cart-count <?= $cartCount > 0 ? '' : 'hidden' ?>><?= (int) $cartCount ?></span>
        </a>

        <?php if ($currentUser): ?>
          <div class="header-user" data-user-menu>
            <button type="button" class="header-user-btn" data-user-toggle aria-expanded="false" aria-haspopup="true">
              <span class="header-avatar" aria-hidden="true"><?= e(mb_strtoupper(mb_substr((string) $currentUser['name'], 0, 1))) ?></span>
            </button>
            <div class="header-user-dropdown" data-user-dropdown hidden>
              <a href="<?= e(base_url('account/')) ?>"><?= e(__('nav_account')) ?></a>
              <a href="<?= e(base_url('account/orders.php')) ?>"><?= e(__('nav_orders')) ?></a>
              <a href="<?= e(base_url('account/favorites.php')) ?>"><?= e(__('nav_favorites')) ?></a>
              <form method="post" action="<?= e(base_url('logout.php')) ?>">
                <?= csrf_field() ?>
                <button type="submit"><?= e(__('nav_logout')) ?></button>
              </form>
            </div>
          </div>
        <?php else: ?>
          <a class="header-text-link" href="<?= e(base_url('login.php')) ?>"><?= e(__('nav_login')) ?></a>
        <?php endif; ?>

        <a class="btn btn-primary header-cta header-cta--call" href="<?= e($phoneTel) ?>">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h3l1.5 4-2 1.5a12 12 0 0 0 5 5L16 11.5 20 13v3a2 2 0 0 1-2.2 2A15 15 0 0 1 5 7.2 2 2 0 0 1 7 3Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
          <span><?= e(__('nav_call')) ?></span>
        </a>

        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="mobile-nav" aria-label="<?= e(__('open_menu')) ?>" data-menu-toggle>
          <span class="menu-toggle-bars" aria-hidden="true"></span>
        </button>
      </div>
    </div>

    <div class="mobile-nav" id="mobile-nav" data-mobile-nav hidden>
      <nav aria-label="<?= e(__('open_menu')) ?>">
        <a href="<?= e(base_url()) ?>"><?= e(__('nav_home')) ?></a>
        <a href="<?= e(base_url('menu.php')) ?>"><?= e(__('nav_menu')) ?></a>
        <a href="<?= e(base_url('gallery.php')) ?>"><?= e(__('nav_gallery')) ?></a>
        <a href="<?= e(base_url('reservation.php')) ?>"><?= e(__('nav_reservation')) ?></a>
        <a href="<?= e(base_url('contacts.php')) ?>"><?= e(__('nav_contacts')) ?></a>
        <a href="<?= e(base_url('cart.php')) ?>"><?= e(__('nav_cart')) ?> (<?= (int) $cartCount ?>)</a>
        <a href="<?= e(base_url($currentUser ? 'account/favorites.php' : 'login.php')) ?>"><?= e(__('nav_favorites')) ?> (<?= (int) $favCount ?>)</a>
        <?php if ($currentUser): ?>
          <a href="<?= e(base_url('account/')) ?>"><?= e(__('nav_account')) ?></a>
        <?php else: ?>
          <a href="<?= e(base_url('login.php')) ?>"><?= e(__('nav_login')) ?></a>
          <a href="<?= e(base_url('register.php')) ?>"><?= e(__('nav_register')) ?></a>
        <?php endif; ?>
        <a class="mobile-call" href="<?= e($phoneTel) ?>"><?= e(__('nav_call')) ?></a>
        <div class="mobile-lang">
          <?php foreach (['ru' => 'RU', 'en' => 'EN', 'de' => 'DE'] as $code => $label): ?>
            <a class="<?= $lang === $code ? 'is-active' : '' ?>" href="<?= e(lang_url($code)) ?>"><?= e($label) ?></a>
          <?php endforeach; ?>
        </div>
      </nav>
    </div>
  </header>

  <?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>" role="status" data-flash>
      <div class="container">
        <p><?= e($flash['message']) ?></p>
        <button type="button" class="flash-close" data-flash-close aria-label="<?= e(__('btn_cancel')) ?>">×</button>
      </div>
    </div>
  <?php endif; ?>

  <main id="main">
