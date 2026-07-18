<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);

$stmt = db()->prepare('SELECT id, image FROM dishes WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$dish = $stmt->fetch();

if (!$dish) {
    flash('error', 'Блюдо не найдено.');
    redirect('admin/dishes/index.php');
}

$del = db()->prepare('DELETE FROM dishes WHERE id = ?');
$del->execute([$id]);

if (!empty($dish['image'])) {
    delete_upload('dishes', (string) $dish['image']);
}

flash('success', 'Блюдо удалено.');
redirect('admin/dishes/index.php');
