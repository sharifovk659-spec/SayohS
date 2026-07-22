<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = __('cart_title') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('cart_title');
$bodyClass = 'page-cart';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', __('error_csrf'));
        redirect('cart.php');
    }

    $action = (string) ($_POST['action'] ?? '');
    $dishId = (int) ($_POST['dish_id'] ?? 0);
    $qty = (int) ($_POST['quantity'] ?? 0);

    $ok = match ($action) {
        'update' => cart_set_qty($dishId, $qty),
        'remove' => cart_remove($dishId),
        'clear' => (static function (): bool {
            cart_clear();
            return true;
        })(),
        default => false,
    };

    flash($ok ? 'success' : 'error', $ok ? __('success_saved') : __('error_generic'));
    redirect('cart.php');
}

$cart = cart_snapshot();
$maxQty = cart_max_qty();

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--cart page-hero--compact">
  <div class="container">
    <div class="page-hero-inner">
      <h1><?= e(__('cart_title')) ?></h1>
      <?php if ($cart['count'] > 0): ?>
        <p class="cart-hero-meta"><?= (int) $cart['count'] ?> <?= e(__('qty')) ?></p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section cart-section" data-cart-page>
  <div class="container">
    <?php if ($cart['count'] <= 0 || $cart['items'] === []): ?>
      <div class="cart-empty">
        <p class="cart-empty__text"><?= e(__('cart_empty')) ?></p>
        <a class="btn btn-primary" href="<?= e(base_url('menu.php')) ?>"><?= e(__('cart_continue')) ?></a>
      </div>
    <?php else: ?>
      <div class="cart-layout">
        <div class="cart-items" data-cart-items>
          <?php foreach ($cart['items'] as $item): ?>
            <?php
            $dish = $item['dish'] ?? [];
            $dishId = (int) $item['dish_id'];
            $name = (string) ($dish['name'] ?? ('#' . $dishId));
            $slug = (string) ($dish['slug'] ?? '');
            $qty = (int) $item['quantity'];
            $image = dish_image_url($dish['image'] ?? null);
            $detailUrl = $slug !== '' ? base_url('dish.php?slug=' . rawurlencode($slug)) : base_url('menu.php');
            ?>
            <article class="cart-item-card" data-cart-item data-dish-id="<?= $dishId ?>">
              <a class="cart-item-media" href="<?= e($detailUrl) ?>">
                <img src="<?= e($image) ?>" alt="<?= e($name) ?>" loading="lazy" width="120" height="120">
              </a>

              <div class="cart-item-body">
                <div class="cart-item-head">
                  <h3 class="cart-item-title">
                    <a href="<?= e($detailUrl) ?>"><?= e($name) ?></a>
                  </h3>
                  <form method="post" action="<?= e(base_url('cart.php')) ?>" class="cart-remove-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="dish_id" value="<?= $dishId ?>">
                    <button class="cart-remove-btn" type="submit" aria-label="<?= e(__('btn_remove')) ?>">×</button>
                  </form>
                </div>

                <p class="cart-item-unit"><?= e(format_price((float) $item['unit_price'])) ?></p>

                <div class="cart-item-foot">
                  <div class="cart-stepper">
                    <form method="post" action="<?= e(base_url('cart.php')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="dish_id" value="<?= $dishId ?>">
                      <input type="hidden" name="quantity" value="<?= max(0, $qty - 1) ?>">
                      <button type="submit" class="cart-stepper__btn" aria-label="−">−</button>
                    </form>
                    <span class="cart-stepper__val"><?= $qty ?></span>
                    <form method="post" action="<?= e(base_url('cart.php')) ?>">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="update">
                      <input type="hidden" name="dish_id" value="<?= $dishId ?>">
                      <input type="hidden" name="quantity" value="<?= min($maxQty, $qty + 1) ?>">
                      <button type="submit" class="cart-stepper__btn" aria-label="+" <?= $qty >= $maxQty ? 'disabled' : '' ?>>+</button>
                    </form>
                  </div>
                  <p class="cart-line-total"><?= e(format_price((float) $item['line_total'])) ?></p>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <aside class="cart-summary" data-cart-summary>
          <h2 class="cart-summary__title"><?= e(__('cart_total')) ?></h2>
          <dl class="cart-totals">
            <div class="cart-total-row">
              <dt><?= e(__('cart_subtotal')) ?></dt>
              <dd><?= e(format_price((float) $cart['subtotal'])) ?></dd>
            </div>
            <div class="cart-total-row">
              <dt><?= e(__('cart_delivery')) ?></dt>
              <dd><?= e(format_price((float) $cart['delivery_fee'])) ?></dd>
            </div>
            <div class="cart-total-row cart-total-row--grand">
              <dt><?= e(__('cart_total')) ?></dt>
              <dd><?= e(format_price((float) $cart['total'])) ?></dd>
            </div>
          </dl>

          <div class="cart-summary__actions">
            <a class="btn btn-primary" href="<?= e(base_url('checkout.php')) ?>"><?= e(__('cart_checkout')) ?></a>
            <a class="btn btn-outline" href="<?= e(base_url('menu.php')) ?>"><?= e(__('cart_continue')) ?></a>
            <form method="post" action="<?= e(base_url('cart.php')) ?>" class="cart-clear-form">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="clear">
              <button class="btn btn-ghost btn-sm" type="submit"><?= e(__('btn_clear')) ?></button>
            </form>
          </div>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
