<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Меню — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = 'Меню чайханы Сайёҳ: поиск, категории и популярные блюда.';
$bodyClass = 'page-menu';

$categories = get_menu_categories();
$filters = [
    'q' => trim((string) ($_GET['q'] ?? '')),
    'category' => (string) ($_GET['category'] ?? 'all'),
    'sort' => (string) ($_GET['sort'] ?? 'default'),
    'popular' => isset($_GET['popular']),
    'available' => isset($_GET['available']),
    'page' => max(1, (int) ($_GET['page'] ?? 1)),
    'per_page' => (int) app_config('per_page', 8),
];

$validSlugs = array_column($categories, 'slug');
if ($filters['category'] !== 'all' && !in_array($filters['category'], $validSlugs, true)) {
    $filters['category'] = 'all';
}
if (!in_array($filters['sort'], ['default', 'price_asc', 'price_desc'], true)) {
    $filters['sort'] = 'default';
}

$result = filter_menu_dishes($filters);

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--menu">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">Гастрономия</p>
      <h1>Меню</h1>
      <p>Выберите категорию или найдите блюдо по названию.</p>
    </div>
  </div>
</section>

<section class="section section--menu">
  <div class="container">
    <form class="menu-toolbar menu-toolbar--wow" method="get" action="<?= e(base_url('menu.php')) ?>" data-reveal>
      <div class="menu-search-bar">
        <span class="menu-search-bar__ico" aria-hidden="true">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="6.5" stroke="currentColor" stroke-width="1.7"/><path d="M16.2 16.2 20 20" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
        </span>
        <label class="visually-hidden" for="menu-q">Поиск</label>
        <input
          type="search"
          id="menu-q"
          name="q"
          value="<?= e($filters['q']) ?>"
          placeholder="Поиск блюда..."
          maxlength="100"
          autocomplete="off"
        >
        <?php if ($filters['category'] !== 'all'): ?>
          <input type="hidden" name="category" value="<?= e($filters['category']) ?>">
        <?php endif; ?>
        <button class="menu-search-bar__btn" type="submit"><?= e(__('btn_search')) ?></button>
      </div>

      <div class="menu-toolbar-meta">
        <div class="form-group menu-sort-group">
          <label class="visually-hidden" for="menu-sort">Сортировка</label>
          <select id="menu-sort" name="sort">
            <option value="default" <?= $filters['sort'] === 'default' ? 'selected' : '' ?>>По умолчанию</option>
            <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Цена ↑</option>
            <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Цена ↓</option>
          </select>
        </div>
        <label class="checkbox-label">
          <input type="checkbox" name="popular" value="1" <?= $filters['popular'] ? 'checked' : '' ?>>
          <span>Хиты</span>
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="available" value="1" <?= $filters['available'] ? 'checked' : '' ?>>
          <span>В наличии</span>
        </label>
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('menu.php')) ?>">Сброс</a>
      </div>
    </form>

    <div class="menu-cats" data-reveal>
      <div class="menu-cats__head">
        <h2 class="menu-cats__title">Категории</h2>
        <p class="menu-cats__hint">Листайте →</p>
      </div>
      <div class="menu-cats-slider menu-cats-slider--marquee" data-menu-cats>
        <div class="menu-cats-viewport">
          <div class="menu-cats-track menu-cats-track--marquee" data-menu-cats-track>
            <?php
            $menuCatItems = array_merge(
                [['slug' => 'all', 'name' => 'Все', 'image' => null, 'is_all' => true]],
                array_map(static function (array $c): array {
                    $c['is_all'] = false;
                    return $c;
                }, $categories)
            );
            for ($loop = 0; $loop < 2; $loop++):
                foreach ($menuCatItems as $category):
                    $catSlug = (string) ($category['slug'] ?? '');
                    $catName = (string) ($category['name'] ?? '');
                    $isAll = !empty($category['is_all']);
                    $catActive = $isAll ? ($filters['category'] === 'all') : ($filters['category'] === $catSlug);
                    $catHref = $isAll
                        ? base_url('menu.php' . menu_query(['category' => 'all', 'page' => 1]))
                        : base_url('menu.php' . menu_query(['category' => $catSlug, 'page' => 1]));
                    $catImg = $isAll ? '' : category_image_url($category['image'] ?? null);
                    ?>
            <a
              class="menu-cat-card<?= $catActive ? ' is-active' : '' ?>"
              href="<?= e($catHref) ?>"
            >
              <?php if ($isAll): ?>
                <span class="menu-cat-card__media menu-cat-card__media--all" aria-hidden="true">
                  <svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                </span>
              <?php else: ?>
                <span class="menu-cat-card__media">
                  <img src="<?= e($catImg) ?>" alt="" width="96" height="96" loading="lazy" decoding="async">
                </span>
              <?php endif; ?>
              <span class="menu-cat-card__name"><?= e($catName) ?></span>
            </a>
                    <?php
                endforeach;
            endfor;
            ?>
          </div>
        </div>
      </div>
    </div>

    <p class="menu-count">Найдено: <?= (int) $result['total'] ?></p>

    <?php if ($result['items']): ?>
      <div class="dishes-grid dishes-grid--menu">
        <?php foreach ($result['items'] as $dish): ?>
          <div data-reveal>
            <?php require __DIR__ . '/components/dish-card.php'; ?>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($result['pages'] > 1): ?>
        <nav class="pagination" aria-label="Страницы меню">
          <?php if ($result['page'] > 1): ?>
            <a class="btn btn-outline btn-sm" href="<?= e(base_url('menu.php' . menu_query(['page' => $result['page'] - 1]))) ?>">Назад</a>
          <?php endif; ?>

          <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
            <a class="page-link <?= $p === $result['page'] ? 'is-active' : '' ?>" href="<?= e(base_url('menu.php' . menu_query(['page' => $p]))) ?>"><?= $p ?></a>
          <?php endfor; ?>

          <?php if ($result['page'] < $result['pages']): ?>
            <a class="btn btn-outline btn-sm" href="<?= e(base_url('menu.php' . menu_query(['page' => $result['page'] + 1]))) ?>">Вперёд</a>
          <?php endif; ?>
        </nav>
      <?php endif; ?>
    <?php else: ?>
      <p class="section-text page-empty-note">По выбранным фильтрам ничего не найдено.</p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
