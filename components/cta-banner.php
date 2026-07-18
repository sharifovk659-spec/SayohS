<?php

declare(strict_types=1);

/**
 * @var string $title
 * @var string $text
 * @var string $href
 * @var string $button
 */
$title = $title ?? 'Забронируйте стол';
$text = $text ?? 'Зарезервируйте удобное время — мы подготовим тёплый приём.';
$href = $href ?? base_url('reservation.php');
$button = $button ?? 'Забронировать';
?>
<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner">
      <div class="cta-banner-copy">
        <h2><?= e($title) ?></h2>
        <p><?= e($text) ?></p>
      </div>
      <a class="btn btn-dark" href="<?= e($href) ?>"><?= e($button) ?></a>
    </div>
  </div>
</section>
