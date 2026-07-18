<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$status = (string) ($_GET['status'] ?? '');
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

$allowedStatus = ['new', 'confirmed', 'completed', 'cancelled'];
if ($status !== '' && !in_array($status, $allowedStatus, true)) {
    $status = '';
}

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(customer_name LIKE ? OR phone LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '') {
    $where[] = 'status = ?';
    $params[] = $status;
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'reservation_date >= ?';
    $params[] = $dateFrom;
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'reservation_date <= ?';
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $where);
$stmt = db()->prepare(
    "SELECT id, customer_name, phone, email, reservation_date, reservation_time, guests_count, status, message, admin_comment, created_at
     FROM reservations WHERE {$whereSql}
     ORDER BY reservation_date DESC, reservation_time DESC, id DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'reservations_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit('Export failed');
}

// UTF-8 BOM for Excel
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    'ID', 'Имя', 'Телефон', 'Email', 'Дата', 'Время', 'Гости', 'Статус', 'Сообщение', 'Комментарий', 'Создано',
], ';');

foreach ($rows as $row) {
    fputcsv($out, [
        csv_safe((string) $row['id']),
        csv_safe((string) $row['customer_name']),
        csv_safe((string) $row['phone']),
        csv_safe((string) ($row['email'] ?? '')),
        csv_safe((string) $row['reservation_date']),
        csv_safe(substr((string) $row['reservation_time'], 0, 5)),
        csv_safe((string) $row['guests_count']),
        csv_safe(reservation_status_label((string) $row['status'])),
        csv_safe((string) ($row['message'] ?? '')),
        csv_safe((string) ($row['admin_comment'] ?? '')),
        csv_safe((string) $row['created_at']),
    ], ';');
}

fclose($out);
exit;
