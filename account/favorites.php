<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_user();
$accountSection = 'favorites';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', __('error_csrf'));
        redirect('account/favorites.php');
    }

    $dishId = (int) ($_POST['dish_id'] ?? 0);
    if ($dishId > 0) {
        favorite_remove($dishId);
        flash('success', __('favorites_removed'));
    } else {
        flash('error', __('error_generic'));
    }
    redirect('account/favorites.php');
}

$pageTitle = __('account_favorites') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('favorites_title');
$bodyClass = 'page-account page-favorites';

$favorites = favorites_list();
$favTotal = count($favorites);

require __DIR__ . '/../includes/header.php';
?>

<section class="fav-hero" aria-labelledby="fav-hero-title">
  <div class="fav-hero__ambient" aria-hidden="true"></div>
  <div class="container fav-hero__inner">
    <div class="fav-hero__copy" data-reveal>
      <p class="eyebrow"><?= e(__('favorites_eyebrow')) ?></p>
      <h1 id="fav-hero-title"><?= e(__('account_favorites')) ?></h1>
      <p class="fav-hero__text"><?= e(__('favorites_lead')) ?></p>
      <div class="fav-hero__meta">
        <span class="fav-count-pill">
          <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" fill="currentColor"/></svg>
          <?= e(sprintf(__('favorites_count'), $favTotal)) ?>
        </span>
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('menu.php')) ?>"><?= e(__('favorites_browse')) ?></a>
      </div>
    </div>
  </div>
</section>

<div class="container account-layout fav-layout">
  <?php require __DIR__ . '/_nav.php'; ?>

  <div class="account-main">
    <?php if ($favorites === []): ?>
      <div class="fav-empty" data-reveal>
        <div class="fav-empty__icon" aria-hidden="true">
          <svg width="42" height="42" viewBox="0 0 24 24" fill="none">
            <path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
          </svg>
        </div>
        <h2><?= e(__('no_favorites')) ?></h2>
        <p><?= e(__('favorites_empty')) ?></p>
        <a class="btn btn-primary" href="<?= e(base_url('menu.php')) ?>"><?= e(__('hero_menu_btn')) ?></a>
      </div>
    <?php else: ?>
      <div class="fav-grid" data-reveal>
        <?php foreach ($favorites as $dish): ?>
          <?php
            $dishId = (int) ($dish['id'] ?? 0);
            $slug = (string) ($dish['slug'] ?? '');
            $name = (string) ($dish['name'] ?? '');
            $description = (string) ($dish['description'] ?? '');
            $categoryName = (string) ($dish['category_name'] ?? '');
            $price = (float) ($dish['price'] ?? 0);
            $oldPrice = $dish['old_price'] ?? null;
            $weight = (string) ($dish['weight'] ?? '');
            $isAvailable = (int) ($dish['is_available'] ?? 1) === 1;
            $image = dish_image_url($dish['image'] ?? null);
            $detailUrl = base_url('dish.php?slug=' . rawurlencode($slug));
          ?>
          <article class="fav-card">
            <a class="fav-card__media" href="<?= e($detailUrl) ?>">
              <img
                src="<?= e($image) ?>"
                alt="<?= e($name) ?>"
                loading="lazy"
                width="640"
                height="480"
                onerror="this.onerror=null;this.src='<?= e(asset('images/dishes/placeholder.svg')) ?>'"
              >
              <?php if ($categoryName !== ''): ?>
                <span class="fav-card__chip"><?= e($categoryName) ?></span>
              <?php endif; ?>
            </a>

            <div class="fav-card__body">
              <h2 class="fav-card__title"><a href="<?= e($detailUrl) ?>"><?= e($name) ?></a></h2>
              <?php if ($description !== ''): ?>
                <p class="fav-card__text"><?= e($description) ?></p>
              <?php endif; ?>

              <div class="fav-card__row">
                <?php if ($weight !== ''): ?>
                  <span class="fav-card__meta"><?= e($weight) ?></span>
                <?php endif; ?>
                <div class="fav-card__prices">
                  <?php if ($oldPrice !== null && (float) $oldPrice > $price): ?>
                    <span class="fav-card__old"><?= e(format_price((float) $oldPrice)) ?></span>
                  <?php endif; ?>
                  <span class="fav-card__price"><?= e(format_price($price)) ?></span>
                </div>
              </div>

              <div class="fav-card__actions">
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
                <form method="post" action="<?= e(base_url('account/favorites.php')) ?>" class="fav-card__remove-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="dish_id" value="<?= $dishId ?>">
                  <button class="fav-card__heart is-active" type="submit" aria-label="<?= e(__('btn_unfavorite')) ?>" title="<?= e(__('btn_remove')) ?>">
                    <svg width="20" height="20" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.5A4 4 0 0 1 19 10c0 5.6-7 10-7 10Z" fill="currentColor"/></svg>
                  </button>
                </form>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
