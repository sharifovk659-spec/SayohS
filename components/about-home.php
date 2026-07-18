<?php

declare(strict_types=1);

/** @var array $app */
$aboutPage = apply_page_translation(fetch_page('about'));
$videoUrl = (string) (
    ($aboutPage['video_url'] ?? null)
    ?: get_setting('about_video_url', (string) app_config('about_video_url', ''))
);
$aboutTitle = (string) ($aboutPage['title'] ?? 'Добро пожаловать в Aroma');
$aboutEyebrow = (string) ($aboutPage['subtitle'] ?? __('nav_about'));
$aboutContent = (string) ($aboutPage['content'] ?? '');
$aboutImage = (string) ($aboutPage['image'] ?? 'about-interior.webp');
if (!is_file(__DIR__ . '/../assets/images/hero/about-interior.webp') && !is_file(__DIR__ . '/../assets/images/hero/about-interior.jpg')) {
    $aboutImage = 'about-preview.webp';
}
$aboutImageSrc = resolve_media($aboutImage, 'settings', 'hero', 'about-interior.webp');
if (!str_contains($aboutImageSrc, 'about-interior') && is_file(__DIR__ . '/../assets/images/hero/about-interior.jpg')) {
    $aboutImageSrc = asset('images/hero/about-interior.jpg');
}

$aboutParagraph = $aboutContent;
$aboutBenefits = ['Свежие продукты', 'Высокое качество', 'Любовь к деталям'];
if ($aboutContent !== '') {
    $parts = preg_split("/\n+/", $aboutContent) ?: [];
    $parts = array_values(array_filter(array_map('trim', $parts), static fn(string $p): bool => $p !== ''));
    if ($parts !== []) {
        $aboutParagraph = array_shift($parts);
        if (count($parts) >= 3) {
            $aboutBenefits = array_slice($parts, 0, 3);
        }
    }
}
?>
<section class="section about-home-section" id="about-preview">
  <div class="container">
    <div class="about-home" data-reveal>
      <div class="about-home-media">
        <img
          src="<?= e($aboutImageSrc) ?>"
          alt="<?= e($aboutTitle) ?>"
          width="900"
          height="1100"
          loading="lazy"
          onerror="this.onerror=null;this.src='<?= e(asset('images/hero/about-preview.svg')) ?>'"
        >
        <button
          type="button"
          class="video-play-btn"
          data-video-open
          data-video-src="<?= e((string) $videoUrl) ?>"
          aria-label="Смотреть видео о ресторане"
        >
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M9 7.5v9l8-4.5L9 7.5Z" fill="currentColor"/>
          </svg>
        </button>
      </div>

      <div class="about-home-copy">
        <p class="eyebrow"><?= e($aboutEyebrow) ?></p>
        <h2 class="section-title"><?= e($aboutTitle) ?></h2>
        <p class="section-text"><?= e($aboutParagraph) ?></p>

        <ul class="about-benefits">
          <?php foreach ($aboutBenefits as $benefit): ?>
          <li>
            <span class="about-benefit-icon" aria-hidden="true">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none"><path d="M5 12.5 9.5 17 19 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <?= e($benefit) ?>
          </li>
          <?php endforeach; ?>
        </ul>

        <a class="btn btn-primary" href="<?= e(base_url('about.php')) ?>"><?= e(__('about_more')) ?></a>
      </div>
    </div>
  </div>
</section>

<div class="modal" data-video-modal hidden>
  <div class="modal-overlay" data-video-close></div>
  <div class="modal-dialog" role="dialog" aria-modal="true" aria-label="Видео о ресторане">
    <button type="button" class="modal-close" data-video-close aria-label="Закрыть видео">×</button>
    <div class="modal-video" data-video-frame></div>
  </div>
</div>
