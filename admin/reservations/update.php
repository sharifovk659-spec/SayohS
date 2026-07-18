<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$comment = sanitize_plain($_POST['admin_comment'] ?? '');

$allowed = ['new', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $allowed, true)) {
    flash('error', 'Некорректный статус.');
    redirect('admin/reservations/index.php');
}

$stmt = db()->prepare('SELECT id FROM reservations WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    flash('error', 'Бронирование не найдено.');
    redirect('admin/reservations/index.php');
}

$upd = db()->prepare('UPDATE reservations SET status = ?, admin_comment = ? WHERE id = ?');
$upd->execute([$status, $comment !== '' ? $comment : null, $id]);

flash('success', 'Бронирование обновлено.');
redirect('admin/reservations/view.php?id=' . $id);
