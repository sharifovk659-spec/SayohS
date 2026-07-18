<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $galleryPreview
 */
$galleryPreview = $galleryPreview ?? [];
?>
<section class="section gallery-preview-section" id="gallery-preview">
  <div class="container">
    <?php
    $eyebrow = __('gallery_eyebrow');
    $title = __('gallery_title');
    $text = null;
    $align = 'center';
    require __DIR__ . '/section-heading.php';
    ?>

    <?php if ($galleryPreview): ?>
      <div class="gallery-preview-grid">
        <?php foreach ($galleryPreview as $item): ?>
          <figure class="gallery-preview-item" data-reveal>
            <img
              src="<?= e(gallery_image_url($item['image'] ?? null)) ?>"
              alt="<?= e((string) ($item['title'] ?? '')) ?>"
              width="640"
              height="800"
              loading="lazy"
            >
            <figcaption><?= e((string) ($item['title'] ?? '')) ?></figcaption>
          </figure>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="section-actions">
      <a class="btn btn-outline" href="<?= e(base_url('gallery.php')) ?>"><?= e(__('gallery_all')) ?></a>
    </div>
  </div>
</section>
