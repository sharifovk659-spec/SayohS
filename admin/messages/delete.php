<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$stmt = db()->prepare('DELETE FROM contact_messages WHERE id = ?');
$stmt->execute([$id]);

flash('success', 'Сообщение удалено.');
redirect('admin/messages/index.php');
