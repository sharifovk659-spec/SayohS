<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);

$stmt = db()->prepare('SELECT id, image FROM categories WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    flash('error', 'Категория не найдена.');
    redirect('admin/categories/index.php');
}

$countStmt = db()->prepare('SELECT COUNT(*) FROM dishes WHERE category_id = ?');
$countStmt->execute([$id]);
$count = (int) $countStmt->fetchColumn();

if ($count > 0) {
    flash('error', 'Нельзя удалить категорию: в ней есть блюда (' . $count . ').');
    redirect('admin/categories/index.php');
}

$del = db()->prepare('DELETE FROM categories WHERE id = ?');
$del->execute([$id]);

if (!empty($category['image'])) {
    delete_upload('categories', (string) $category['image']);
}

flash('success', 'Категория удалена.');
redirect('admin/categories/index.php');
