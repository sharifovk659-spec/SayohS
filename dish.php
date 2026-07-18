<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' && isset($_GET['id'])) {
    foreach (get_all_dishes() as $item) {
        if ((int) $item['id'] === (int) $_GET['id']) {
            redirect('dish.php?slug=' . rawurlencode((string) $item['slug']));
        }
    }
}

$dish = $slug !== '' ? find_dish_by_slug($slug) : null;
if (!$dish) {
    http_response_code(404);
    $pageTitle = 'Блюдо не найдено — Aroma';
    $pageDescription = 'Запрашиваемое блюдо не найдено в меню.';
    $bodyClass = 'page-404';
    require __DIR__ . '/includes/header.php';
    ?>
<section class="section">
  <div class="container page-empty" data-reveal>
    <p class="eyebrow">Ошибка 404</p>
    <h1>Блюдо не найдено</h1>
    <p class="section-text">Возможно, позиция была удалена из меню.</p>
    <div class="hero-actions">
      <a class="btn btn-primary" href="<?= e(base_url('menu.php')) ?>">Вернуться в меню</a>
      <a class="btn btn-outline" href="<?= e(base_url()) ?>">На главную</a>
    </div>
  </div>
</section>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$pageTitle = $dish['name'] . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = (string) ($dish['full_description'] ?? $dish['description'] ?? $app['description']);
$bodyClass = 'page-dish';
$related = related_dishes($dish, 4);

require __DIR__ . '/includes/header.php';
?>

<section class="section">
  <div class="container">
    <article class="dish-detail" data-reveal>
      <div class="dish-detail-media">
        <img
          src="<?= e(dish_image_url($dish['image'] ?? null)) ?>"
          alt="<?= e((string) $dish['name']) ?>"
          width="900"
          height="700"
          onerror="this.onerror=null;this.src='<?= e(asset('images/dishes/placeholder.svg')) ?>'"
        >
      </div>
      <div class="dish-detail-copy">
        <p class="eyebrow"><?= e((string) $dish['category_name']) ?></p>
        <h1><?= e((string) $dish['name']) ?></h1>
        <p class="section-text"><?= e((string) ($dish['full_description'] ?? $dish['description'])) ?></p>

        <ul class="dish-facts">
          <?php if (!empty($dish['ingredients'])): ?>
            <li><span>Ингредиенты</span><strong><?= e((string) $dish['ingredients']) ?></strong></li>
          <?php endif; ?>
          <?php if (!empty($dish['weight'])): ?>
            <li><span>Вес</span><strong><?= e((string) $dish['weight']) ?></strong></li>
          <?php endif; ?>
          <?php if (!empty($dish['calories'])): ?>
            <li><span>Калорийность</span><strong><?= e((string) $dish['calories']) ?></strong></li>
          <?php endif; ?>
        </ul>

        <div class="dish-card-meta-row">
          <div class="dish-card-prices">
            <?php if (!empty($dish['old_price']) && (float) $dish['old_price'] > (float) $dish['price']): ?>
              <span class="dish-card-old"><?= e(format_price((float) $dish['old_price'])) ?></span>
            <?php endif; ?>
            <span class="dish-card-price"><?= e(format_price((float) $dish['price'])) ?></span>
          </div>
        </div>

        <div class="hero-actions dish-detail-actions">
          <?php
          $dishId = (int) ($dish['id'] ?? 0);
          $isAvailable = (int) ($dish['is_available'] ?? 1) === 1;
          $categoryLabel = trim((string) ($dish['category_name'] ?? ''));
          $categorySlug = (string) ($dish['category_slug'] ?? '');
          $categoryBtn = $categoryLabel !== ''
              ? sprintf(__('dish_to_category'), $categoryLabel)
              : __('nav_menu');
          $categoryHref = $categorySlug !== ''
              ? base_url('menu.php' . menu_query(['category' => $categorySlug, 'page' => 1]))
              : base_url('menu.php');
          ?>
          <?php if ($isAvailable && $dishId > 0): ?>
            <form method="post" action="<?= e(base_url('actions/cart-action.php')) ?>" data-cart-form>
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="add">
              <input type="hidden" name="dish_id" value="<?= $dishId ?>">
              <input type="hidden" name="quantity" value="1">
              <button class="btn btn-primary" type="submit" data-add-cart><?= e(__('btn_add_cart')) ?></button>
            </form>
          <?php endif; ?>
          <a class="btn btn-outline" href="<?= e($categoryHref) ?>"><?= e($categoryBtn) ?></a>
        </div>
      </div>
    </article>
  </div>
</section>

<?php if ($related): ?>
<section class="section section-tight">
  <div class="container">
    <?php
    $eyebrow = 'Ещё из меню';
    $title = 'Похожие блюда';
    $text = null;
    $align = 'center';
    require __DIR__ . '/components/section-heading.php';
    ?>
    <div class="dishes-grid dishes-grid--menu">
      <?php foreach ($related as $dish): ?>
        <div data-reveal>
          <?php require __DIR__ . '/components/dish-card.php'; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
