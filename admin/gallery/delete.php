<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);

$stmt = db()->prepare('SELECT id, image FROM gallery WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    flash('error', 'Фото не найдено.');
    redirect('admin/gallery/index.php');
}

$del = db()->prepare('DELETE FROM gallery WHERE id = ?');
$del->execute([$id]);

if (!empty($item['image'])) {
    delete_upload('gallery', (string) $item['image']);
}

flash('success', 'Фото удалено.');
redirect('admin/gallery/index.php');
