<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$q = trim((string) ($_GET['q'] ?? ''));
$orderStatus = (string) ($_GET['order_status'] ?? '');
$paymentStatus = (string) ($_GET['payment_status'] ?? '');
$dateFrom = trim((string) ($_GET['date_from'] ?? ''));
$dateTo = trim((string) ($_GET['date_to'] ?? ''));

$allowedOrderStatus = order_statuses();
$allowedPaymentStatus = payment_statuses();

if ($orderStatus !== '' && !in_array($orderStatus, $allowedOrderStatus, true)) {
    $orderStatus = '';
}
if ($paymentStatus !== '' && !in_array($paymentStatus, $allowedPaymentStatus, true)) {
    $paymentStatus = '';
}

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = '(order_number LIKE ? OR customer_phone LIKE ? OR customer_name LIKE ?)';
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($orderStatus !== '') {
    $where[] = 'order_status = ?';
    $params[] = $orderStatus;
}

if ($paymentStatus !== '') {
    $where[] = 'payment_status = ?';
    $params[] = $paymentStatus;
}

if ($dateFrom !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
    $where[] = 'DATE(created_at) >= ?';
    $params[] = $dateFrom;
}

if ($dateTo !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
    $where[] = 'DATE(created_at) <= ?';
    $params[] = $dateTo;
}

$whereSql = implode(' AND ', $where);
$stmt = db()->prepare(
    "SELECT order_number, customer_name, customer_phone, customer_email, delivery_type, delivery_address,
            subtotal, delivery_fee, total, payment_method, payment_status, order_status, comment, admin_comment, created_at
     FROM orders WHERE {$whereSql}
     ORDER BY created_at DESC, id DESC"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$filename = 'orders_' . date('Y-m-d_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');

$out = fopen('php://output', 'w');
if ($out === false) {
    http_response_code(500);
    exit('Export failed');
}

fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, [
    '№ заказа', 'Клиент', 'Телефон', 'Email', 'Тип', 'Адрес',
    'Подытог', 'Доставка', 'Итого', 'Оплата', 'Статус оплаты', 'Статус заказа',
    'Комментарий', 'Комментарий адм.', 'Создан',
], ';');

foreach ($rows as $row) {
    fputcsv($out, [
        csv_safe((string) $row['order_number']),
        csv_safe((string) $row['customer_name']),
        csv_safe((string) $row['customer_phone']),
        csv_safe((string) ($row['customer_email'] ?? '')),
        csv_safe(delivery_type_label((string) $row['delivery_type'])),
        csv_safe((string) ($row['delivery_address'] ?? '')),
        csv_safe((string) $row['subtotal']),
        csv_safe((string) $row['delivery_fee']),
        csv_safe((string) $row['total']),
        csv_safe(payment_method_label((string) $row['payment_method'])),
        csv_safe(payment_status_label((string) $row['payment_status'])),
        csv_safe(order_status_label((string) $row['order_status'])),
        csv_safe((string) ($row['comment'] ?? '')),
        csv_safe((string) ($row['admin_comment'] ?? '')),
        csv_safe((string) $row['created_at']),
    ], ';');
}

fclose($out);
exit;
