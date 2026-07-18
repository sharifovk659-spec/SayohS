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

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow"><?= e(__('cart_title')) ?></p>
      <h1><?= e(__('cart_title')) ?></h1>
    </div>
  </div>
</section>

<section class="section cart-section" data-cart-page>
  <div class="container">
    <?php if ($cart['count'] <= 0): ?>
      <div class="reservation-panel" data-reveal>
        <p class="section-text"><?= e(__('cart_empty')) ?></p>
        <a class="btn btn-primary" href="<?= e(base_url('menu.php')) ?>"><?= e(__('cart_continue')) ?></a>
      </div>
    <?php else: ?>
      <div class="cart-layout" data-reveal>
        <div class="cart-items" data-cart-items>
          <?php foreach ($cart['items'] as $item): ?>
            <?php
            $dish = $item['dish'] ?? [];
            $dishId = (int) $item['dish_id'];
            $name = (string) ($dish['name'] ?? '');
            $slug = (string) ($dish['slug'] ?? '');
            $image = dish_image_url($dish['image'] ?? null);
            $detailUrl = base_url('dish.php?slug=' . rawurlencode($slug));
            ?>
            <article class="cart-item dish-card cart-item-card" data-cart-item data-dish-id="<?= $dishId ?>">
              <div class="dish-card-media cart-item-media">
                <a href="<?= e($detailUrl) ?>">
                  <img src="<?= e($image) ?>" alt="<?= e($name) ?>" loading="lazy" width="120" height="90">
                </a>
              </div>
              <div class="cart-item-body">
                <h3 class="dish-card-title">
                  <a href="<?= e($detailUrl) ?>"><?= e($name) ?></a>
                </h3>
                <p class="dish-card-price"><?= e(format_price((float) $item['unit_price'])) ?></p>

                <form class="cart-qty-form" method="post" action="<?= e(base_url('cart.php')) ?>"
                      data-cart-update data-dish-id="<?= $dishId ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="dish_id" value="<?= $dishId ?>">
                  <label class="visually-hidden" for="qty-<?= $dishId ?>"><?= e(__('qty')) ?></label>
                  <input type="number" id="qty-<?= $dishId ?>" name="quantity" class="cart-qty-input"
                         min="1" max="<?= cart_max_qty() ?>" value="<?= (int) $item['quantity'] ?>"
                         data-cart-qty>
                  <button class="btn btn-outline btn-sm" type="submit"><?= e(__('btn_update')) ?></button>
                </form>

                <form class="cart-remove-form" method="post" action="<?= e(base_url('cart.php')) ?>"
                      data-cart-remove data-dish-id="<?= $dishId ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="dish_id" value="<?= $dishId ?>">
                  <button class="btn btn-ghost btn-sm" type="submit"><?= e(__('btn_remove')) ?></button>
                </form>

                <p class="cart-line-total" data-line-total><?= e(format_price((float) $item['line_total'])) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <aside class="cart-summary reservation-panel" data-cart-summary>
          <h2 class="section-title"><?= e(__('cart_total')) ?></h2>
          <dl class="cart-totals">
            <div class="cart-total-row">
              <dt><?= e(__('cart_subtotal')) ?></dt>
              <dd data-cart-subtotal><?= e(format_price((float) $cart['subtotal'])) ?></dd>
            </div>
            <div class="cart-total-row">
              <dt><?= e(__('cart_delivery')) ?></dt>
              <dd data-cart-delivery><?= e(format_price((float) $cart['delivery_fee'])) ?></dd>
            </div>
            <div class="cart-total-row cart-total-row--grand">
              <dt><?= e(__('cart_total')) ?></dt>
              <dd data-cart-total><?= e(format_price((float) $cart['total'])) ?></dd>
            </div>
          </dl>

          <form method="post" action="<?= e(base_url('cart.php')) ?>" data-cart-clear>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="clear">
            <button class="btn btn-ghost btn-sm" type="submit"><?= e(__('btn_clear')) ?></button>
          </form>

          <a class="btn btn-primary btn-full" href="<?= e(base_url('checkout.php')) ?>"><?= e(__('cart_checkout')) ?></a>
          <a class="btn btn-outline btn-full" href="<?= e(base_url('menu.php')) ?>"><?= e(__('cart_continue')) ?></a>
        </aside>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
