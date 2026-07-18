<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);

$stmt = db()->prepare('SELECT id, is_active FROM categories WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    flash('error', 'Категория не найдена.');
    redirect('admin/categories/index.php');
}

$new = (int) $category['is_active'] === 1 ? 0 : 1;
$upd = db()->prepare('UPDATE categories SET is_active = ? WHERE id = ?');
$upd->execute([$new, $id]);

flash('success', $new ? 'Категория показана.' : 'Категория скрыта.');
redirect('admin/categories/index.php');
