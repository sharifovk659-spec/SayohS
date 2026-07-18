<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$sortOrder = (int) ($_POST['sort_order'] ?? 0);

$stmt = db()->prepare('SELECT id FROM categories WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    flash('error', 'Категория не найдена.');
    redirect('admin/categories/index.php');
}

$upd = db()->prepare('UPDATE categories SET sort_order = ? WHERE id = ?');
$upd->execute([$sortOrder, $id]);

flash('success', 'Порядок обновлён.');
redirect('admin/categories/index.php');
