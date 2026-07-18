<?php

declare(strict_types=1);

/**
 * Локальный каталог для меню/карточек без глубокой БД-логики.
 * Позже будет заменён данными из MySQL.
 *
 * @return array{categories: list<array<string,mixed>>, dishes: list<array<string,mixed>>}
 */
function catalog_data(): array
{
    static $data = null;
    if ($data !== null) {
        return $data;
    }

    $categories = [
        ['id' => 1, 'name' => 'Пицца', 'slug' => 'pizza', 'image' => 'cat-pizza.webp'],
        ['id' => 2, 'name' => 'Бургеры', 'slug' => 'burgers', 'image' => 'cat-burgers.webp'],
        ['id' => 3, 'name' => 'Шаурма', 'slug' => 'shawarma', 'image' => 'cat-shawarma.webp'],
        ['id' => 4, 'name' => 'Гриль', 'slug' => 'grill', 'image' => 'cat-grill.webp'],
        ['id' => 5, 'name' => 'Салаты', 'slug' => 'salads', 'image' => 'cat-salads.webp'],
        ['id' => 6, 'name' => 'Напитки', 'slug' => 'drinks', 'image' => 'cat-drinks.webp'],
        ['id' => 7, 'name' => 'Десерты', 'slug' => 'desserts', 'image' => 'cat-desserts.webp'],
    ];

    $dishes = [
        [
            'id' => 1, 'slug' => 'pizza-margherita', 'category_id' => 1, 'category_name' => 'Пицца', 'category_slug' => 'pizza',
            'name' => 'Пицца Маргарита', 'description' => 'Томатный соус, моцарелла, базилик и оливковое масло.',
            'full_description' => 'Классическая итальянская пицца на тонком тесте. Готовим в печи, подаём горячей с свежим базиликом.',
            'ingredients' => 'Тонкое тесто, томатный соус, моцарелла, базилик, оливковое масло',
            'calories' => '780 ккал', 'weight' => '450 г', 'price' => 690, 'old_price' => 790,
            'image' => 'pizza-margherita.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 2, 'slug' => 'pizza-pepperoni', 'category_id' => 1, 'category_name' => 'Пицца', 'category_slug' => 'pizza',
            'name' => 'Пицца Пепперони', 'description' => 'Острая пепперони, сыр и фирменный томатный соус.',
            'full_description' => 'Пицца с пикантной колбасой пепперони и тянущимся сыром. Для любителей более насыщенного вкуса.',
            'ingredients' => 'Тесто, томатный соус, моцарелла, пепперони, орегано',
            'calories' => '920 ккал', 'weight' => '480 г', 'price' => 820, 'old_price' => null,
            'image' => 'pizza-pepperoni.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 3, 'slug' => 'classic-burger', 'category_id' => 2, 'category_name' => 'Бургеры', 'category_slug' => 'burgers',
            'name' => 'Классический бургер', 'description' => 'Говяжья котлета, сыр чеддер, соус и свежие овощи.',
            'full_description' => 'Сочная котлета из говядины, мягкая булочка и свежие овощи. Идеальный выбор на каждый день.',
            'ingredients' => 'Булочка, говядина, чеддер, салат, томат, соус',
            'calories' => '640 ккал', 'weight' => '320 г', 'price' => 640, 'old_price' => 720,
            'image' => 'burger-classic.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 4, 'slug' => 'bacon-burger', 'category_id' => 2, 'category_name' => 'Бургеры', 'category_slug' => 'burgers',
            'name' => 'Бургер с беконом', 'description' => 'Котлета, бекон, карамелизированный лук и соус BBQ.',
            'full_description' => 'Насыщенный бургер с хрустящим беконом и сладким луком. Подаём с соусом BBQ.',
            'ingredients' => 'Булочка, говядина, бекон, лук, BBQ-соус, сыр',
            'calories' => '790 ккал', 'weight' => '350 г', 'price' => 740, 'old_price' => null,
            'image' => 'burger-bacon.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 5, 'slug' => 'classic-shawarma', 'category_id' => 3, 'category_name' => 'Шаурма', 'category_slug' => 'shawarma',
            'name' => 'Шаурма классическая', 'description' => 'Курица, овощи, соус и тонкий лаваш.',
            'full_description' => 'Сочная курица, свежие овощи и фирменный соус в тонком лаваше.',
            'ingredients' => 'Лаваш, курица, капуста, томат, огурец, соус',
            'calories' => '520 ккал', 'weight' => '380 г', 'price' => 420, 'old_price' => null,
            'image' => 'shawarma.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 6, 'slug' => 'grill-steak', 'category_id' => 4, 'category_name' => 'Гриль', 'category_slug' => 'grill',
            'name' => 'Стейк на гриле', 'description' => 'Сочный стейк с овощами гриль и соусом.',
            'full_description' => 'Стейк средней прожарки с сезонными овощами и соусом на выбор.',
            'ingredients' => 'Говядина, овощи гриль, соль, перец, соус',
            'calories' => '690 ккал', 'weight' => '280 г', 'price' => 1890, 'old_price' => 2100,
            'image' => 'grill-steak.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 7, 'slug' => 'caesar-salad', 'category_id' => 5, 'category_name' => 'Салаты', 'category_slug' => 'salads',
            'name' => 'Салат Цезарь', 'description' => 'Курица, романо, пармезан и соус цезарь.',
            'full_description' => 'Лёгкий и сытный салат с хрустящими крутонами и нежным соусом.',
            'ingredients' => 'Романо, курица, пармезан, крутоны, соус цезарь',
            'calories' => '410 ккал', 'weight' => '260 г', 'price' => 560, 'old_price' => null,
            'image' => 'salad-caesar.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 8, 'slug' => 'greek-salad', 'category_id' => 5, 'category_name' => 'Салаты', 'category_slug' => 'salads',
            'name' => 'Греческий салат', 'description' => 'Томаты, огурцы, фета, оливки и орегано.',
            'full_description' => 'Свежий средиземноморский салат с фетой и оливковым маслом.',
            'ingredients' => 'Томат, огурец, фета, оливки, лук, орегано',
            'calories' => '320 ккал', 'weight' => '240 г', 'price' => 490, 'old_price' => null,
            'image' => 'salad-greek.webp', 'is_popular' => 0, 'is_available' => 1,
        ],
        [
            'id' => 9, 'slug' => 'house-lemonade', 'category_id' => 6, 'category_name' => 'Напитки', 'category_slug' => 'drinks',
            'name' => 'Авторский лимонад', 'description' => 'Цитрусы, мята и домашний сироп.',
            'full_description' => 'Освежающий лимонад собственного приготовления. Можно выбрать вкус сиропа.',
            'ingredients' => 'Лимон, лайм, мята, сироп, газированная вода',
            'calories' => '90 ккал', 'weight' => '400 мл', 'price' => 390, 'old_price' => null,
            'image' => 'lemonade.webp', 'is_popular' => 1, 'is_available' => 1,
        ],
        [
            'id' => 10, 'slug' => 'chocolate-fondant', 'category_id' => 7, 'category_name' => 'Десерты', 'category_slug' => 'desserts',
            'name' => 'Шоколадный фондан', 'description' => 'Тёплый кекс с жидкой сердцевиной.',
            'full_description' => 'Нежный шоколадный десерт с горячей начинкой. Подаём с шариком мороженого.',
            'ingredients' => 'Шоколад, масло, яйца, сахар, мука, ваниль',
            'calories' => '450 ккал', 'weight' => '140 г', 'price' => 590, 'old_price' => 650,
            'image' => 'fondant.webp', 'is_popular' => 1, 'is_available' => 0,
        ],
        [
            'id' => 11, 'slug' => 'tiramisu', 'category_id' => 7, 'category_name' => 'Десерты', 'category_slug' => 'desserts',
            'name' => 'Тирамису', 'description' => 'Классический десерт с кофе и маскарпоне.',
            'full_description' => 'Воздушные слои савоярди, крем на маскарпоне и аромат эспрессо.',
            'ingredients' => 'Савоярди, маскарпоне, эспрессо, какао, яйца',
            'calories' => '480 ккал', 'weight' => '160 г', 'price' => 550, 'old_price' => null,
            'image' => 'tiramisu.webp', 'is_popular' => 0, 'is_available' => 1,
        ],
        [
            'id' => 12, 'slug' => 'grilled-vegetables', 'category_id' => 4, 'category_name' => 'Гриль', 'category_slug' => 'grill',
            'name' => 'Овощи гриль', 'description' => 'Сезонные овощи на гриле с травами.',
            'full_description' => 'Лёгкое блюдо из сезонных овощей с ароматными травами и оливковым маслом.',
            'ingredients' => 'Цукини, перец, баклажан, масло, травы',
            'calories' => '210 ккал', 'weight' => '280 г', 'price' => 480, 'old_price' => null,
            'image' => 'grill-veg.webp', 'is_popular' => 0, 'is_available' => 1,
        ],
    ];

    $data = ['categories' => $categories, 'dishes' => $dishes];
    return $data;
}
