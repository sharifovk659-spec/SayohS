<?php

declare(strict_types=1);

/**
 * @var array $dish
 */
$dish = $dish ?? [];
$slug = (string) ($dish['slug'] ?? '');
$name = (string) ($dish['name'] ?? '');
$description = (string) ($dish['description'] ?? '');
$categoryName = (string) ($dish['category_name'] ?? '');
$price = (float) ($dish['price'] ?? 0);
$oldPrice = $dish['old_price'] ?? null;
$weight = (string) ($dish['weight'] ?? '');
$isPopular = (int) ($dish['is_popular'] ?? 0) === 1;
$isAvailable = (int) ($dish['is_available'] ?? 1) === 1;
$dishId = (int) ($dish['id'] ?? 0);
$image = dish_image_url($dish['image'] ?? null);
$detailUrl = base_url('dish.php?slug=' . rawurlencode($slug));
$isFav = $dishId > 0 && function_exists('is_favorite') && is_favorite($dishId);
?>
<article class="dish-card" data-dish-id="<?= $dishId ?>">
  <div class="dish-card-media">
    <?php if ($isPopular): ?>
      <span class="dish-badge"><?= e(__('popular_badge')) ?></span>
    <?php endif; ?>
    <?php if (!$isAvailable): ?>
      <span class="dish-badge dish-badge--muted"><?= e(__('unavailable_badge')) ?></span>
    <?php endif; ?>
    <?php if ($dishId > 0): ?>
      <form class="dish-fav-form" method="post" action="<?= e(base_url('actions/favorite-action.php')) ?>" data-fav-form>
        <?= csrf_field() ?>
        <input type="hidden" name="dish_id" value="<?= $dishId ?>">
        <input type="hidden" name="action" value="toggle">
        <button
          type="submit"
          class="dish-fav-btn<?= $isFav ? ' is-active' : '' ?>"
          aria-label="<?= e($isFav ? __('btn_unfavorite') : __('btn_favorite')) ?>"
          aria-pressed="<?= $isFav ? 'true' : 'false' ?>"
          data-fav-btn
        >
          <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" fill="<?= $isFav ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
        </button>
      </form>
    <?php endif; ?>
    <a href="<?= e($detailUrl) ?>" tabindex="-1" aria-hidden="true">
      <img src="<?= e($image) ?>" alt="<?= e($name) ?>" loading="lazy" width="480" height="360" onerror="this.onerror=null;this.src='<?= e(asset('images/dishes/placeholder.svg')) ?>'">
    </a>
  </div>
  <div class="dish-card-body">
    <?php if ($categoryName !== ''): ?>
      <p class="dish-card-category"><?= e($categoryName) ?></p>
    <?php endif; ?>
    <h3 class="dish-card-title"><a href="<?= e($detailUrl) ?>"><?= e($name) ?></a></h3>
    <?php if ($description !== ''): ?>
      <p class="dish-card-text"><?= e($description) ?></p>
    <?php endif; ?>
    <div class="dish-card-meta-row">
      <?php if ($weight !== ''): ?>
        <span class="dish-card-meta"><?= e($weight) ?></span>
      <?php endif; ?>
      <div class="dish-card-prices">
        <?php if ($oldPrice !== null && (float) $oldPrice > $price): ?>
          <span class="dish-card-old"><?= e(format_price((float) $oldPrice)) ?></span>
        <?php endif; ?>
        <span class="dish-card-price"><?= e(format_price($price)) ?></span>
      </div>
    </div>
    <div class="dish-card-actions">
      <a class="btn btn-outline btn-sm" href="<?= e($detailUrl) ?>"><?= e(__('btn_details')) ?></a>
      <?php if ($isAvailable && $dishId > 0): ?>
        <form method="post" action="<?= e(base_url('actions/cart-action.php')) ?>" data-cart-form>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <input type="hidden" name="dish_id" value="<?= $dishId ?>">
          <input type="hidden" name="quantity" value="1">
          <button class="btn btn-primary btn-sm" type="submit" data-add-cart><?= e(__('btn_add_cart')) ?></button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</article>
