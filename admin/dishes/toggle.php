<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);

$stmt = db()->prepare('SELECT id, is_available FROM dishes WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$dish = $stmt->fetch();

if (!$dish) {
    flash('error', 'Блюдо не найдено.');
    redirect('admin/dishes/index.php');
}

$new = (int) $dish['is_available'] === 1 ? 0 : 1;
$upd = db()->prepare('UPDATE dishes SET is_available = ? WHERE id = ?');
$upd->execute([$new, $id]);

flash('success', $new ? 'Блюдо показано в меню.' : 'Блюдо скрыто.');
redirect('admin/dishes/index.php');
