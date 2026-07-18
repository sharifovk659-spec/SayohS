<?php

declare(strict_types=1);

/**
 * @var string $icon
 * @var string $title
 * @var string $text
 */
$icon = $icon ?? '';
$title = $title ?? '';
$text = $text ?? '';
?>
<article class="feature-card">
  <?php if ($icon !== ''): ?>
    <div class="feature-card-icon" aria-hidden="true"><?= $icon ?></div>
  <?php endif; ?>
  <h3><?= e($title) ?></h3>
  <p><?= e($text) ?></p>
</article>
