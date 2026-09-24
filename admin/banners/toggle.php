<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();
require_post();
require_csrf();

$id = (int) ($_POST['id'] ?? 0);
$field = (string) ($_POST['field'] ?? '');

if ($id <= 0 || !in_array($field, ['is_active', 'show_text'], true)) {
    flash('error', 'Некорректный запрос.');
    redirect('admin/banners/index.php');
}

$row = fetch_home_banner($id);
if ($row === null) {
    flash('error', 'Слайд не найден.');
    redirect('admin/banners/index.php');
}

$current = (int) ($row[$field] ?? 1);
// If column missing (old DB), treat show_text as 1
if ($field === 'show_text' && !array_key_exists('show_text', $row)) {
    $current = 1;
}
$next = $current === 1 ? 0 : 1;

try {
    ensure_home_banners_show_text_column();
    $stmt = db()->prepare("UPDATE home_banners SET {$field} = ? WHERE id = ?");
    $stmt->execute([$next, $id]);
    if ($field === 'show_text') {
        flash('success', $next === 1 ? 'Текст баннера включён.' : 'Текст баннера скрыт (выкл).');
    } else {
        flash('success', $next === 1 ? 'Слайд на сайте включён.' : 'Слайд на сайте выключен.');
    }
} catch (Throwable $e) {
    flash('error', 'Не удалось переключить: ' . $e->getMessage());
}

redirect('admin/banners/index.php');
