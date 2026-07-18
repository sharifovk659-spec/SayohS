<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$orderNumber = trim((string) ($_GET['n'] ?? ''));
if ($orderNumber === '' || !preg_match('/^AR-\d{8}-[A-Z0-9]{4}$/i', $orderNumber)) {
    redirect('index.php');
}

$pageTitle = __('checkout_success_title') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('checkout_success_text');
$bodyClass = 'page-order-success';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow"><?= e(__('checkout_success_title')) ?></p>
      <h1><?= e(__('checkout_success_title')) ?></h1>
      <p><?= e(__('checkout_success_text')) ?></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="reservation-panel order-success-panel" data-reveal>
      <p class="order-number-label"><?= e(__('order_number')) ?></p>
      <p class="order-number-value"><?= e($orderNumber) ?></p>
      <div class="form-actions">
        <a class="btn btn-primary" href="<?= e(base_url('menu.php')) ?>"><?= e(__('hero_menu_btn')) ?></a>
        <a class="btn btn-outline" href="<?= e(base_url()) ?>"><?= e(__('back_home')) ?></a>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
