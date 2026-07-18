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
    'email' => (string) old('email', $user['email'] ?? ''),
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
        'email' => trim((string) ($_POST['email'] ?? '')),
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

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow"><?= e(__('checkout_title')) ?></p>
      <h1><?= e(__('checkout_title')) ?></h1>
    </div>
  </div>
</section>

<section class="section checkout-section">
  <div class="container">
    <div class="checkout-layout" data-reveal>
      <form class="reservation-panel checkout-form" method="post" action="<?= e(base_url('checkout.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="idempotency_key" value="<?= e($idempotencyKey) ?>">

        <div class="form-grid">
          <div class="form-group<?= field_invalid('name', $errors) ?>">
            <label for="checkout-name"><?= e(__('checkout_name')) ?> *</label>
            <input type="text" id="checkout-name" name="name" required maxlength="120" autocomplete="name"
                   value="<?= e($defaults['name']) ?>">
            <?= field_error('name', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('phone', $errors) ?>">
            <label for="checkout-phone"><?= e(__('checkout_phone')) ?> *</label>
            <input type="tel" id="checkout-phone" name="phone" required maxlength="30" autocomplete="tel"
                   inputmode="tel" value="<?= e($defaults['phone']) ?>">
            <?= field_error('phone', $errors) ?>
          </div>

          <div class="form-group full<?= field_invalid('email', $errors) ?>">
            <label for="checkout-email"><?= e(__('checkout_email')) ?></label>
            <input type="email" id="checkout-email" name="email" maxlength="190" autocomplete="email"
                   value="<?= e($defaults['email']) ?>">
            <?= field_error('email', $errors) ?>
          </div>

          <div class="form-group full">
            <span class="form-label"><?= e(__('checkout_delivery_type')) ?> *</span>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" name="delivery_type" value="delivery"
                       <?= $defaults['delivery_type'] === 'delivery' ? 'checked' : '' ?>
                       data-delivery-toggle>
                <?= e(__('checkout_delivery')) ?>
              </label>
              <label class="radio-label">
                <input type="radio" name="delivery_type" value="pickup"
                       <?= $defaults['delivery_type'] === 'pickup' ? 'checked' : '' ?>
                       data-delivery-toggle>
                <?= e(__('checkout_pickup')) ?>
              </label>
            </div>
          </div>

          <div class="form-group full<?= field_invalid('address', $errors) ?>" data-address-field>
            <label for="checkout-address"><?= e(__('checkout_address')) ?> *</label>
            <input type="text" id="checkout-address" name="address" maxlength="255" autocomplete="street-address"
                   value="<?= e($defaults['address']) ?>">
            <?= field_error('address', $errors) ?>
          </div>

          <div class="form-group full<?= field_invalid('landmark', $errors) ?>">
            <label for="checkout-landmark"><?= e(__('checkout_landmark')) ?></label>
            <input type="text" id="checkout-landmark" name="landmark" maxlength="255"
                   value="<?= e($defaults['landmark']) ?>">
            <?= field_error('landmark', $errors) ?>
          </div>

          <div class="form-group full<?= field_invalid('comment', $errors) ?>">
            <label for="checkout-comment"><?= e(__('checkout_comment')) ?></label>
            <textarea id="checkout-comment" name="comment" maxlength="2000" rows="3"><?= e($defaults['comment']) ?></textarea>
            <?= field_error('comment', $errors) ?>
          </div>

          <div class="form-group full">
            <span class="form-label"><?= e(__('checkout_payment')) ?> *</span>
            <div class="radio-group">
              <label class="radio-label">
                <input type="radio" name="payment_method" value="cash"
                       <?= $defaults['payment_method'] === 'cash' ? 'checked' : '' ?>>
                <?= e(__('checkout_cash')) ?>
              </label>
              <label class="radio-label">
                <input type="radio" name="payment_method" value="on_receipt"
                       <?= $defaults['payment_method'] === 'on_receipt' ? 'checked' : '' ?>>
                <?= e(__('checkout_on_receipt')) ?>
              </label>
            </div>
          </div>

          <div class="form-group full form-actions">
            <button class="btn btn-primary btn-full" type="submit"><?= e(__('checkout_place_order')) ?></button>
          </div>
        </div>
      </form>

      <aside class="cart-summary reservation-panel checkout-summary">
        <h2 class="section-title"><?= e(__('cart_title')) ?></h2>
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
