<?php

declare(strict_types=1);

/**
 * @var string $eyebrow
 * @var string $title
 * @var string|null $text
 * @var string $align
 */
$eyebrow = $eyebrow ?? '';
$title = $title ?? '';
$text = $text ?? null;
$align = $align ?? 'left';
?>
<div class="section-heading section-heading--<?= e($align) ?>">
  <?php if ($eyebrow !== ''): ?>
    <p class="eyebrow"><?= e($eyebrow) ?></p>
  <?php endif; ?>
  <h2 class="section-title"><?= e($title) ?></h2>
  <?php if ($text): ?>
    <p class="section-text"><?= e($text) ?></p>
  <?php endif; ?>
</div>
