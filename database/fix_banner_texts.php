<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

$db = db();

// Exact texts from design mockup, matched to actual slide photos:
// 1 plov, 2 shashlik, 3 tea, 4 salad, 5 dessert
$byImage = [
    'baneri-1.png' => [
        'sort_order' => 1,
        'label' => 'Вкусные блюда',
        'title' => 'Восточный плов',
        'subtitle' => 'Ароматный рис, мясо и специи каждый день',
    ],
    'baneri-2.png' => [
        'sort_order' => 2,
        'label' => 'Настоящий вкус',
        'title' => 'Шашлык как искусство',
        'subtitle' => 'Сочное мясо. Ароматный дым. Незабываемый вкус.',
    ],
    'baneri-3.png' => [
        'sort_order' => 3,
        'label' => 'Душевные встречи',
        'title' => 'Чай, который собирает друзей',
        'subtitle' => 'Ароматный чай и любимые блюда в уютной атмосфере',
    ],
    'baneri-4.png' => [
        'sort_order' => 4,
        'label' => 'Полезно и вкусно',
        'title' => 'Салаты и лёгкие блюда',
        'subtitle' => 'Забота о вашем здоровье',
    ],
    'baneri-5.png' => [
        'sort_order' => 5,
        'label' => 'Любимые десерты',
        'title' => 'Сладкие моменты',
        'subtitle' => 'Десерты, которые делают день ярче',
    ],
];

$db->exec('UPDATE home_banners SET is_active = 0');

$upd = $db->prepare(
    'UPDATE home_banners
     SET sort_order = :sort_order, is_active = 1, label = :label, title = :title, subtitle = :subtitle
     WHERE image = :image'
);

$fallbackUpd = $db->prepare(
    'UPDATE home_banners
     SET sort_order = :sort_order, is_active = 1, label = :label, title = :title, subtitle = :subtitle, image = :image
     WHERE id = :id'
);

foreach ($byImage as $image => $meta) {
    $upd->execute([
        'sort_order' => $meta['sort_order'],
        'label' => $meta['label'],
        'title' => $meta['title'],
        'subtitle' => $meta['subtitle'],
        'image' => $image,
    ]);
    if ($upd->rowCount() === 0) {
        // Active rows may use different ids — match by sort or insert
        $id = (int) $meta['sort_order'];
        $exists = $db->prepare('SELECT id FROM home_banners WHERE id = ?');
        $exists->execute([$id]);
        if ($exists->fetchColumn()) {
            $fallbackUpd->execute([
                'sort_order' => $meta['sort_order'],
                'label' => $meta['label'],
                'title' => $meta['title'],
                'subtitle' => $meta['subtitle'],
                'image' => $image,
                'id' => $id,
            ]);
        } else {
            $ins = $db->prepare(
                'INSERT INTO home_banners (sort_order, is_active, label, title, subtitle, image)
                 VALUES (:sort_order, 1, :label, :title, :subtitle, :image)'
            );
            $ins->execute([
                'sort_order' => $meta['sort_order'],
                'label' => $meta['label'],
                'title' => $meta['title'],
                'subtitle' => $meta['subtitle'],
                'image' => $image,
            ]);
        }
    }
    echo "OK {$image} → {$meta['title']}\n";
}

$rows = $db->query(
    'SELECT id, sort_order, label, title, image FROM home_banners WHERE is_active = 1 ORDER BY sort_order, id'
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $r) {
    echo implode(' | ', $r) . PHP_EOL;
}
