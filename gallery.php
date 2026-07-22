<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Галерея — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = 'Атмосфера, интерьер и блюда чайханы Сайёҳ.';
$bodyClass = 'page-gallery';

$albums = [
    'all' => 'Все',
    'interior' => 'Интерьер',
    'dishes' => 'Блюда',
    'drinks' => 'Напитки',
    'team' => 'Команда',
    'events' => 'События',
];

$activeAlbum = isset($_GET['album']) ? (string) $_GET['album'] : 'all';
if (!array_key_exists($activeAlbum, $albums)) {
    $activeAlbum = 'all';
}

$items = fetch_gallery($activeAlbum === 'all' ? null : $activeAlbum);

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">Пространство</p>
      <h1>Галерея</h1>
      <p>Интерьер, блюда и атмосфера чайханы Сайёҳ.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="menu-filters gallery-filters" data-gallery-filters>
      <?php foreach ($albums as $key => $label): ?>
        <button
          type="button"
          class="filter-chip <?= $activeAlbum === $key ? 'is-active' : '' ?>"
          data-gallery-filter="<?= e($key) ?>"
        ><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>

    <?php if ($items): ?>
      <div class="gallery-page-grid" data-gallery-grid>
        <?php foreach ($items as $index => $item): ?>
          <button
            type="button"
            class="gallery-page-item"
            data-lightbox-item
            data-index="<?= (int) $index ?>"
            data-album="<?= e((string) ($item['album'] ?? 'interior')) ?>"
            data-src="<?= e(gallery_image_url($item['image'] ?? null)) ?>"
            data-title="<?= e((string) ($item['title'] ?? '')) ?>"
            aria-label="Открыть фото: <?= e((string) ($item['title'] ?? '')) ?>"
          >
            <img
              src="<?= e(gallery_image_url($item['image'] ?? null)) ?>"
              alt="<?= e((string) ($item['title'] ?? '')) ?>"
              width="700"
              height="875"
              loading="lazy"
            >
            <span class="gallery-page-caption">
              <strong><?= e((string) ($item['title'] ?? '')) ?></strong>
              <small><?= e(gallery_album_label((string) ($item['album'] ?? 'interior'))) ?></small>
            </span>
          </button>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="section-text" style="text-align:center">В этой категории пока нет фотографий.</p>
    <?php endif; ?>
  </div>
</section>

<div class="lightbox" data-lightbox hidden>
  <div class="lightbox-overlay" data-lightbox-close></div>
  <div class="lightbox-dialog" role="dialog" aria-modal="true" aria-label="Просмотр фото">
    <button type="button" class="lightbox-close" data-lightbox-close aria-label="Закрыть">×</button>
    <button type="button" class="lightbox-nav lightbox-prev" data-lightbox-prev aria-label="Предыдущее фото">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M12 4 6 10l6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
    <figure class="lightbox-figure">
      <img src="" alt="" data-lightbox-image width="1200" height="1500">
      <figcaption data-lightbox-caption></figcaption>
    </figure>
    <button type="button" class="lightbox-nav lightbox-next" data-lightbox-next aria-label="Следующее фото">
      <svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true"><path d="M8 4l6 6-6 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
    </button>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
