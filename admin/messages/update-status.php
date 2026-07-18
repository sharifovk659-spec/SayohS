<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$status = (string) ($_POST['status'] ?? '');
$allowed = ['new', 'read', 'answered'];

if (!in_array($status, $allowed, true)) {
    flash('error', 'Некорректный статус.');
    redirect('admin/messages/index.php');
}

$stmt = db()->prepare('SELECT id FROM contact_messages WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    flash('error', 'Сообщение не найдено.');
    redirect('admin/messages/index.php');
}

$upd = db()->prepare('UPDATE contact_messages SET status = ? WHERE id = ?');
$upd->execute([$status, $id]);

flash('success', 'Статус обновлён.');
redirect('admin/messages/view.php?id=' . $id);
