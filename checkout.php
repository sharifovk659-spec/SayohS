<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$cart = cart_snapshot();
if ($cart['count'] <= 0) {
    flash('error', __('cart_empty'));
    redirect('cart.php');
}

$user = current_user();
$errors = form_errors();

if (empty($_SESSION['checkout_idempotency']) || !is_string($_SESSION['checkout_idempotency'])) {
    $_SESSION['checkout_idempotency'] = bin2hex(random_bytes(16));
}
$idempotencyKey = (string) $_SESSION['checkout_idempotency'];

$defaults = [
    'name' => (string) old('name', $user['name'] ?? ''),
    'phone' => (string) old('phone', $user['phone'] ?? ''),
    'address' => (string) old('address', ''),
    'landmark' => (string) old('landmark', ''),
    'comment' => (string) old('comment', ''),
    'delivery_type' => (string) old('delivery_type', 'delivery'),
    'payment_method' => (string) old('payment_method', 'cash'),
];

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', __('error_csrf'));
        redirect('checkout.php');
    }

    $input = [
        'name' => sanitize_plain($_POST['name'] ?? ''),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => '',
        'address' => sanitize_plain($_POST['address'] ?? ''),
        'landmark' => sanitize_plain($_POST['landmark'] ?? ''),
        'comment' => sanitize_plain($_POST['comment'] ?? ''),
        'delivery_type' => (string) ($_POST['delivery_type'] ?? 'delivery'),
        'payment_method' => (string) ($_POST['payment_method'] ?? 'cash'),
        'idempotency_key' => trim((string) ($_POST['idempotency_key'] ?? $idempotencyKey)),
    ];

    $result = create_order_from_cart($input);

    if (!$result['ok']) {
        set_form_state(['form' => $result['error'] ?? __('error_generic')], $input);
        flash('error', $result['error'] ?? __('error_generic'));
        redirect('checkout.php');
    }

    unset($_SESSION['checkout_idempotency']);
    clear_old_input();
    flash('success', __('success_order'));

    $orderNumber = (string) ($result['order']['order_number'] ?? '');
    redirect('order-success.php?n=' . rawurlencode($orderNumber));
}

$pageTitle = __('checkout_title') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('checkout_title');
$bodyClass = 'page-checkout';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
  <div class="container">
    <div class="page-hero-inner">
      <h1><?= e(__('checkout_title')) ?></h1>
    </div>
  </div>
</section>

<section class="section checkout-section">
  <div class="container">
    <div class="checkout-layout checkout-layout--compact">
      <form class="checkout-form checkout-form--wow" method="post" action="<?= e(base_url('checkout.php')) ?>" novalidate data-checkout-form>
        <?= csrf_field() ?>
        <input type="hidden" name="idempotency_key" value="<?= e($idempotencyKey) ?>">

        <div class="checkout-card">
          <h2 class="checkout-card__title"><?= e(__('checkout_name')) ?> / <?= e(__('checkout_phone')) ?></h2>
          <div class="form-grid form-grid--2">
            <div class="form-group<?= field_invalid('name', $errors) ?>">
              <label for="checkout-name"><?= e(__('checkout_name')) ?> *</label>
              <input type="text" id="checkout-name" name="name" required maxlength="120" autocomplete="name"
                     value="<?= e($defaults['name']) ?>">
              <?= field_error('name', $errors) ?>
            </div>
            <div class="form-group<?= field_invalid('phone', $errors) ?>">
              <label for="checkout-phone"><?= e(__('checkout_phone')) ?> *</label>
              <input type="tel" id="checkout-phone" name="phone" required maxlength="30" autocomplete="tel"
                     inputmode="tel" placeholder="+992 __ ___ ____" value="<?= e($defaults['phone']) ?>">
              <?= field_error('phone', $errors) ?>
            </div>
          </div>
        </div>

        <div class="checkout-card">
          <h2 class="checkout-card__title"><?= e(__('checkout_delivery_type')) ?></h2>
          <div class="checkout-seg" role="group" aria-label="<?= e(__('checkout_delivery_type')) ?>">
            <label class="checkout-seg__item">
              <input type="radio" name="delivery_type" value="delivery"
                     <?= $defaults['delivery_type'] === 'delivery' ? 'checked' : '' ?>
                     data-delivery-toggle>
              <span><?= e(__('checkout_delivery')) ?></span>
            </label>
            <label class="checkout-seg__item">
              <input type="radio" name="delivery_type" value="pickup"
                     <?= $defaults['delivery_type'] === 'pickup' ? 'checked' : '' ?>
                     data-delivery-toggle>
              <span><?= e(__('checkout_pickup')) ?></span>
            </label>
          </div>

          <div class="checkout-address<?= $defaults['delivery_type'] === 'pickup' ? ' is-hidden' : '' ?>" data-address-block>
            <div class="form-group<?= field_invalid('address', $errors) ?>" data-address-field>
              <label for="checkout-address"><?= e(__('checkout_address')) ?> *</label>
              <div class="checkout-address-row">
                <input type="text" id="checkout-address" name="address" maxlength="255" autocomplete="street-address"
                       placeholder="<?= e(__('checkout_address')) ?>"
                       value="<?= e($defaults['address']) ?>" data-address-input>
                <button
                  type="button"
                  class="btn btn-outline checkout-geo-btn"
                  data-geo-btn
                  aria-label="<?= e(__('checkout_geo')) ?>"
                  title="<?= e(__('checkout_geo')) ?>"
                  data-geo-loading="<?= e(__('checkout_geo_loading')) ?>"
                  data-geo-ok="<?= e(__('checkout_geo_ok')) ?>"
                  data-geo-fail="<?= e(__('checkout_geo_fail')) ?>"
                >
                  <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M12 2.5c-3.6 0-6.5 2.9-6.5 6.5 0 4.6 6.5 12.5 6.5 12.5s6.5-7.9 6.5-12.5c0-3.6-2.9-6.5-6.5-6.5Z" stroke="currentColor" stroke-width="1.7"/>
                    <circle cx="12" cy="9" r="2.4" stroke="currentColor" stroke-width="1.7"/>
                  </svg>
                </button>
              </div>
              <p class="checkout-geo-status" data-geo-status hidden></p>
              <?= field_error('address', $errors) ?>
            </div>
            <div class="form-group<?= field_invalid('landmark', $errors) ?>">
              <label for="checkout-landmark"><?= e(__('checkout_landmark')) ?></label>
              <input type="text" id="checkout-landmark" name="landmark" maxlength="255"
                     value="<?= e($defaults['landmark']) ?>">
              <?= field_error('landmark', $errors) ?>
            </div>
          </div>
        </div>

        <div class="checkout-card">
          <h2 class="checkout-card__title"><?= e(__('checkout_payment')) ?></h2>
          <div class="checkout-seg">
            <label class="checkout-seg__item">
              <input type="radio" name="payment_method" value="cash"
                     <?= $defaults['payment_method'] === 'cash' ? 'checked' : '' ?>>
              <span><?= e(__('checkout_cash')) ?></span>
            </label>
            <label class="checkout-seg__item">
              <input type="radio" name="payment_method" value="on_receipt"
                     <?= $defaults['payment_method'] === 'on_receipt' ? 'checked' : '' ?>>
              <span><?= e(__('checkout_on_receipt')) ?></span>
            </label>
          </div>
          <div class="form-group<?= field_invalid('comment', $errors) ?>">
            <label for="checkout-comment"><?= e(__('checkout_comment')) ?></label>
            <textarea id="checkout-comment" name="comment" maxlength="2000" rows="2"><?= e($defaults['comment']) ?></textarea>
            <?= field_error('comment', $errors) ?>
          </div>
        </div>

        <button class="btn btn-primary btn-full checkout-submit" type="submit"><?= e(__('checkout_place_order')) ?></button>
      </form>

      <aside class="checkout-summary checkout-summary--wow">
        <h2 class="checkout-summary__title"><?= e(__('cart_title')) ?></h2>
        <ul class="checkout-items">
          <?php foreach ($cart['items'] as $item): ?>
            <li>
              <span><?= e((string) (($item['dish']['name'] ?? '') . ' × ' . $item['quantity'])) ?></span>
              <span><?= e(format_price((float) $item['line_total'])) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
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
      </aside>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
