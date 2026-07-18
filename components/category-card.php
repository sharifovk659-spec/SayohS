<?php

declare(strict_types=1);

/**
 * @var array $category
 */
$category = $category ?? [];
$name = (string) ($category['name'] ?? '');
$slug = (string) ($category['slug'] ?? '');
$image = category_image_url($category['image'] ?? null);
$href = base_url('menu.php?category=' . rawurlencode($slug));
?>
<a class="category-card" href="<?= e($href) ?>">
  <span class="category-card-media">
    <img src="<?= e($image) ?>" alt="<?= e($name) ?>" width="160" height="160" loading="lazy">
  </span>
  <span class="category-card-name"><?= e($name) ?></span>
  <span class="category-card-arrow" aria-hidden="true">
    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
      <path d="M3.5 8h9M8.5 4l4 4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
  </span>
</a>
