<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Бронирование — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = 'Забронируйте стол в ресторане Aroma.';
$bodyClass = 'page-reservation';
$redirectTo = 'reservation.php';
$formId = 'page-reservation';

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">Резерв</p>
      <h1>Бронирование стола</h1>
      <p>Оставьте заявку — администратор подтвердит бронь.</p>
    </div>
  </div>
</section>

<?php require __DIR__ . '/components/reservation-form.php'; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
