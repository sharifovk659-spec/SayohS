<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $categories
 */
$categories = $categories ?? [];
?>
<section class="section categories-section" id="categories">
  <div class="container">
    <?php
    $eyebrow = __('categories_eyebrow');
    $title = __('categories_title');
    $text = null;
    $align = 'center';
    require __DIR__ . '/section-heading.php';
    ?>

    <div class="categories-slider categories-slider--marquee" data-categories-slider>
      <div class="categories-viewport">
        <div class="categories-track categories-track--marquee" data-categories-track>
          <?php
          // Render twice for seamless infinite loop
          for ($loop = 0; $loop < 2; $loop++):
              foreach ($categories as $category):
                  require __DIR__ . '/category-card.php';
              endforeach;
          endfor;
          ?>
        </div>
      </div>

      <div class="categories-nav">
        <button type="button" class="slider-btn" data-cat-prev aria-label="<?= e(__('btn_back')) ?>">
          <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d="M11 4 6 9l5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
        <button type="button" class="slider-btn" data-cat-next aria-label="<?= e(__('btn_continue')) ?>">
          <svg width="18" height="18" viewBox="0 0 18 18" fill="none" aria-hidden="true">
            <path d="M7 4l5 5-5 5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </button>
      </div>
    </div>
  </div>
</section>
