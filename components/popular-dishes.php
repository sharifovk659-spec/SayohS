<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $popularDishes
 */
$popularDishes = $popularDishes ?? [];
?>
<section class="section popular-dishes-section" id="popular-dishes">
  <div class="container">
    <?php
    $eyebrow = __('popular_eyebrow');
    $title = __('popular_title');
    $text = __('popular_text');
    $align = 'center';
    require __DIR__ . '/section-heading.php';
    ?>

    <?php if ($popularDishes): ?>
      <div class="dishes-grid dishes-grid--popular" data-dishes-scroll>
        <?php foreach ($popularDishes as $dish): ?>
          <div class="dish-card-wrap" data-reveal>
            <?php require __DIR__ . '/dish-card.php'; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="section-text" style="text-align:center"><?= e(__('menu_empty')) ?></p>
    <?php endif; ?>

    <div class="section-actions">
      <a class="btn btn-primary" href="<?= e(base_url('menu.php')) ?>"><?= e(__('popular_all')) ?></a>
    </div>
  </div>
</section>
