<?php

declare(strict_types=1);

function ensure_dir(string $path): void
{
    if (!is_dir($path)) {
        mkdir($path, 0775, true);
    }
}

function write_svg(string $path, int $w, int $h, string $c1, string $c2, string $title): void
{
    $safe = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $cx = (int) ($w * 0.35);
    $cy = (int) ($h * 0.32);
    $r = (int) ($w * 0.16);
    $ty = $h - 40;

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">'
        . '<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
        . '<stop offset="0%" stop-color="' . $c1 . '"/>'
        . '<stop offset="100%" stop-color="' . $c2 . '"/>'
        . '</linearGradient></defs>'
        . '<rect width="' . $w . '" height="' . $h . '" fill="url(#g)"/>'
        . '<circle cx="' . $w . '" cy="0" r="' . $w . '" fill="rgba(255,253,249,0.12)"/>'
        . '<circle cx="0" cy="' . $h . '" r="' . (int) ($h * 0.65) . '" fill="rgba(44,33,24,0.10)"/>'
        . '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="rgba(255,253,249,0.16)"/>'
        . '<text x="28" y="' . $ty . '" font-family="Georgia, serif" font-size="28" fill="rgba(44,33,24,0.45)">' . $safe . '</text>'
        . '</svg>';

    file_put_contents($path, $svg);
}

$root = dirname(__DIR__);
$dirs = [
    $root . '/assets/images/categories',
    $root . '/assets/images/dishes',
    $root . '/assets/images/gallery',
    $root . '/assets/images/hero',
];

foreach ($dirs as $dir) {
    ensure_dir($dir);
}

$categories = [
    'cat-pizza.svg' => ['#c45c3a', '#f0c9a0', 'Пицца'],
    'cat-burgers.svg' => ['#8b5a2b', '#e2c08a', 'Бургеры'],
    'cat-shawarma.svg' => ['#a66b3a', '#efd2a8', 'Шаурма'],
    'cat-grill.svg' => ['#6e3b24', '#c8925e', 'Гриль'],
    'cat-salads.svg' => ['#5f8a4a', '#d6e6b8', 'Салаты'],
    'cat-drinks.svg' => ['#4f7cac', '#c9dff2', 'Напитки'],
    'cat-desserts.svg' => ['#8a4f6d', '#efcfe0', 'Десерты'],
    'placeholder.svg' => ['#efe6d8', '#d4b57a', 'Категория'],
];

foreach ($categories as $file => [$c1, $c2, $label]) {
    write_svg($root . '/assets/images/categories/' . $file, 320, 320, $c1, $c2, $label);
}

$dishes = [
    'pizza-margherita.svg' => ['#d36a45', '#f4d2b0', 'Маргарита'],
    'pizza-pepperoni.svg' => ['#b84d38', '#efc4a4', 'Пепперони'],
    'burger-classic.svg' => ['#8d5a2f', '#e6c48e', 'Бургер'],
    'burger-bacon.svg' => ['#7a4726', '#d9b07a', 'Бекон'],
    'shawarma.svg' => ['#b07a3d', '#efd2a6', 'Шаурма'],
    'grill-steak.svg' => ['#5a3020', '#b47852', 'Стейк'],
    'salad-caesar.svg' => ['#6f9a52', '#dcebba', 'Цезарь'],
    'salad-greek.svg' => ['#5f8f68', '#d5ebc8', 'Греческий'],
    'lemonade.svg' => ['#aabe6e', '#e6ecb4', 'Лимонад'],
    'fondant.svg' => ['#46281e', '#965a3c', 'Фондан'],
    'placeholder.svg' => ['#efe6d8', '#d4b57a', 'Блюдо'],
];

foreach ($dishes as $file => [$c1, $c2, $label]) {
    write_svg($root . '/assets/images/dishes/' . $file, 800, 600, $c1, $c2, $label);
}

$gallery = [
    'gal-interior.svg' => ['#6a5340', '#d7c2a4', 'Интерьер'],
    'gal-pizza.svg' => ['#c45c3a', '#f0c9a0', 'Пицца'],
    'gal-hot.svg' => ['#7a4028', '#d2a078', 'Горячее'],
    'gal-drinks.svg' => ['#4f7cac', '#c9dff2', 'Напитки'],
    'gal-dessert.svg' => ['#8a4f6d', '#efcfe0', 'Десерт'],
    'gal-team.svg' => ['#5a4636', '#d8c5ae', 'Команда'],
    'gal-event.svg' => ['#3f2d22', '#b8924a', 'Событие'],
    'gal-table.svg' => ['#7d6550', '#e2d2ba', 'Сервировка'],
    'placeholder.svg' => ['#efe6d8', '#b8924a', 'Галерея'],
];

foreach ($gallery as $file => [$c1, $c2, $label]) {
    write_svg($root . '/assets/images/gallery/' . $file, 900, 1125, $c1, $c2, $label);
}

$hero = [
    'hero-main.svg' => ['#3e2a1c', '#b8924a', 'Aroma'],
    'about-preview.svg' => ['#5a4430', '#e8d6ba', 'Интерьер'],
    'about-main.svg' => ['#302018', '#a6804e', 'Кухня'],
];

foreach ($hero as $file => [$c1, $c2, $label]) {
    write_svg($root . '/assets/images/hero/' . $file, 900, 1100, $c1, $c2, $label);
}

echo "Block images generated\n";
