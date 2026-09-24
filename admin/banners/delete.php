<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$row = $id > 0 ? fetch_home_banner($id) : null;

if ($row === null) {
    flash('error', 'Слайд не найден.');
    redirect('admin/banners/index.php');
}

$del = db()->prepare('DELETE FROM home_banners WHERE id = ?');
$del->execute([$id]);

if (!empty($row['image'])) {
    delete_upload('banners', (string) $row['image']);
}

flash('success', 'Слайд баннера удалён.');
redirect('admin/banners/index.php');
