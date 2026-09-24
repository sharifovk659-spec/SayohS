<?php

declare(strict_types=1);

require __DIR__ . '/../includes/bootstrap.php';

ensure_home_banners_show_text_column();

$slides = [
    // show_text=0 → photo only (admin can turn text on)
    [1, 'Вкусные блюда', 'Восточный плов', 'Ароматный рис, мясо и специи каждый день', 'baneri-1.png', 0],
    [2, 'Настоящий вкус', 'Шашлык как искусство', 'Сочное мясо. Ароматный дым. Незабываемый вкус.', 'baneri-2.png', 0],
    [3, 'Душевные встречи', 'Чай, который собирает друзей', 'Ароматный чай и любимые блюда в уютной атмосфере', 'baneri-3.png', 0],
    [4, 'Полезно и вкусно', 'Салаты и лёгкие блюда', 'Забота о вашем здоровье', 'baneri-4.png', 0],
    [5, 'Любимые десерты', 'Сладкие моменты', 'Десерты, которые делают день ярче', 'baneri-5.png', 0],
];

db()->exec('UPDATE home_banners SET is_active = 0');

$upd = db()->prepare(
    'UPDATE home_banners SET sort_order=?, is_active=1, show_text=?, label=?, title=?, subtitle=?, image=? WHERE id=?'
);
$byImage = db()->prepare('SELECT id FROM home_banners WHERE image = ? LIMIT 1');
$ins = db()->prepare(
    'INSERT INTO home_banners (sort_order, is_active, show_text, label, title, subtitle, image)
     VALUES (?, 1, ?, ?, ?, ?, ?)'
);

foreach ($slides as $s) {
    [$sort, $label, $title, $sub, $image, $showText] = $s;
    $byImage->execute([$image]);
    $id = $byImage->fetchColumn();
    if ($id) {
        $upd->execute([$sort, $showText, $label, $title, $sub, $image, $id]);
        echo "upd id={$id} {$image}\n";
    } else {
        $ins->execute([$sort, $showText, $label, $title, $sub, $image]);
        echo "ins {$image}\n";
    }
}

foreach (db()->query('SELECT id,sort_order,title,image,show_text,is_active FROM home_banners WHERE is_active=1 ORDER BY sort_order')->fetchAll() as $r) {
    echo implode(' | ', $r) . PHP_EOL;
}
echo "OK\n";
