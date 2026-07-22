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
</a>
