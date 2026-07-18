<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Меню — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = 'Меню ресторана Aroma: поиск, фильтры и популярные блюда.';
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

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">Гастрономия</p>
      <h1>Меню</h1>
      <p>Найдите блюдо по названию, категории или цене.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <form class="menu-toolbar" method="get" action="<?= e(base_url('menu.php')) ?>">
      <div class="menu-toolbar-row">
        <div class="form-group menu-search">
          <label class="visually-hidden" for="menu-q">Поиск</label>
          <input type="search" id="menu-q" name="q" value="<?= e($filters['q']) ?>" placeholder="Поиск блюда..." maxlength="100">
        </div>

        <div class="form-group">
          <label class="visually-hidden" for="menu-category">Категория</label>
          <select id="menu-category" name="category">
            <option value="all">Все категории</option>
            <?php foreach ($categories as $category): ?>
              <option value="<?= e($category['slug']) ?>" <?= $filters['category'] === $category['slug'] ? 'selected' : '' ?>>
                <?= e($category['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label class="visually-hidden" for="menu-sort">Сортировка</label>
          <select id="menu-sort" name="sort">
            <option value="default" <?= $filters['sort'] === 'default' ? 'selected' : '' ?>>По умолчанию</option>
            <option value="price_asc" <?= $filters['sort'] === 'price_asc' ? 'selected' : '' ?>>Цена: по возрастанию</option>
            <option value="price_desc" <?= $filters['sort'] === 'price_desc' ? 'selected' : '' ?>>Цена: по убыванию</option>
          </select>
        </div>
      </div>

      <div class="menu-toolbar-row menu-toolbar-checks">
        <label class="checkbox-label">
          <input type="checkbox" name="popular" value="1" <?= $filters['popular'] ? 'checked' : '' ?>>
          <span>Популярные</span>
        </label>
        <label class="checkbox-label">
          <input type="checkbox" name="available" value="1" <?= $filters['available'] ? 'checked' : '' ?>>
          <span>В наличии</span>
        </label>
        <button class="btn btn-primary btn-sm" type="submit">Применить</button>
        <a class="btn btn-outline btn-sm" href="<?= e(base_url('menu.php')) ?>">Сбросить</a>
      </div>
    </form>

    <div class="menu-cat-chips" aria-label="Категории">
      <a class="filter-chip <?= $filters['category'] === 'all' ? 'is-active' : '' ?>" href="<?= e(base_url('menu.php' . menu_query(['category' => 'all', 'page' => 1]))) ?>">Все</a>
      <?php foreach ($categories as $category): ?>
        <a class="filter-chip <?= $filters['category'] === $category['slug'] ? 'is-active' : '' ?>" href="<?= e(base_url('menu.php' . menu_query(['category' => $category['slug'], 'page' => 1]))) ?>">
          <?= e($category['name']) ?>
        </a>
      <?php endforeach; ?>
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
