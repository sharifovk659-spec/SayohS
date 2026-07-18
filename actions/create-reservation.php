<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('reservation.php');
}

$redirectTo = trim((string) ($_POST['redirect_to'] ?? 'reservation.php'));
$allowedRedirects = ['reservation.php', 'index.php', 'contacts.php'];
if (!in_array($redirectTo, $allowedRedirects, true)) {
    $redirectTo = 'reservation.php';
}

$input = [
    'name' => sanitize_plain($_POST['name'] ?? ''),
    'phone' => trim((string) ($_POST['phone'] ?? '')),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'guests' => (string) ($_POST['guests'] ?? ''),
    'reserve_date' => trim((string) ($_POST['reserve_date'] ?? '')),
    'reserve_time' => trim((string) ($_POST['reserve_time'] ?? '')),
    'message' => sanitize_plain($_POST['message'] ?? ''),
    'privacy' => isset($_POST['privacy']) ? '1' : '',
];

$errors = [];

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $errors['form'] = 'Сессия устарела. Обновите страницу и попробуйте снова.';
}

if (!honeypot_ok($_POST)) {
    clear_old_input();
    flash('success', 'Заявка отправлена. Мы свяжемся с вами для подтверждения.');
    redirect($redirectTo . '#booking');
}

if (!rate_limit('reservation', 5, 600)) {
    $errors['form'] = 'Слишком много заявок. Подождите несколько минут и попробуйте снова.';
}

if ($input['name'] === '' || mb_strlen($input['name']) > 100) {
    $errors['name'] = 'Укажите имя.';
}

$phoneDigits = normalize_phone($input['phone']);
if ($input['phone'] === '' || !is_valid_phone($input['phone'])) {
    $errors['phone'] = 'Укажите корректный телефон.';
}

if ($input['email'] !== '' && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Проверьте формат email.';
}

$guests = (int) $input['guests'];
if ($guests < 1 || $guests > 30) {
    $errors['guests'] = 'Выберите количество гостей от 1 до 30.';
}

$dateObj = DateTime::createFromFormat('Y-m-d', $input['reserve_date']);
if (!$dateObj || $dateObj->format('Y-m-d') !== $input['reserve_date']) {
    $errors['reserve_date'] = 'Укажите корректную дату.';
} else {
    $today = new DateTime('today');
    $max = (clone $today)->modify('+60 days');
    if ($dateObj < $today || $dateObj > $max) {
        $errors['reserve_date'] = 'Дата бронирования недоступна.';
    }
}

if (!preg_match('/^\d{2}:\d{2}$/', $input['reserve_time'])) {
    $errors['reserve_time'] = 'Укажите корректное время.';
} elseif (empty($errors['reserve_date']) && !is_within_opening_hours($input['reserve_date'], $input['reserve_time'])) {
    $errors['reserve_time'] = 'Выбранное время вне часов работы ресторана.';
}

if (mb_strlen($input['message']) > 1000) {
    $errors['message'] = 'Комментарий слишком длинный.';
}

if ($input['privacy'] !== '1') {
    $errors['privacy'] = 'Нужно согласие с политикой конфиденциальности.';
}

$submitHash = hash('sha256', implode('|', [
    $input['name'],
    $phoneDigits,
    $input['email'],
    $input['reserve_date'],
    $input['reserve_time'],
    (string) $guests,
    $input['message'],
]));
if (!empty($_SESSION['last_reservation_hash']) && hash_equals((string) $_SESSION['last_reservation_hash'], $submitHash)) {
    $errors['form'] = 'Эта заявка уже была отправлена.';
}

if ($errors) {
    set_form_state($errors, $input);
    flash('error', $errors['form'] ?? 'Проверьте выделенные поля формы.');
    redirect($redirectTo . '#booking');
}

if (!db_available()) {
    set_form_state(['form' => 'Сервис временно недоступен. Позвоните нам по телефону.'], $input);
    flash('error', 'Сервис временно недоступен. Позвоните нам по телефону.');
    redirect($redirectTo . '#booking');
}

try {
    $stmt = db()->prepare(
        'INSERT INTO reservations
            (customer_name, phone, email, reservation_date, reservation_time, guests_count, message, status, created_ip_hash)
         VALUES
            (:name, :phone, :email, :rdate, :rtime, :guests, :message, :status, :ip)'
    );
    $stmt->execute([
        'name' => $input['name'],
        'phone' => $phoneDigits !== '' ? $phoneDigits : $input['phone'],
        'email' => $input['email'] !== '' ? $input['email'] : null,
        'rdate' => $input['reserve_date'],
        'rtime' => $input['reserve_time'] . ':00',
        'guests' => $guests,
        'message' => $input['message'] !== '' ? $input['message'] : null,
        'status' => 'new',
        'ip' => client_ip_hash(),
    ]);

    $_SESSION['last_reservation_hash'] = $submitHash;

    try {
        notify_admin(
            'Новая бронь стола',
            "Имя: {$input['name']}\nТелефон: {$input['phone']}\nДата: {$input['reserve_date']} {$input['reserve_time']}\nГостей: {$guests}\n"
        );
    } catch (Throwable) {
        // Booking must succeed even if mail fails
    }
} catch (Throwable $e) {
    storage_log('create-reservation: ' . $e->getMessage());
    set_form_state(['form' => 'Не удалось отправить заявку. Попробуйте позже.'], $input);
    flash('error', 'Не удалось отправить заявку. Попробуйте позже.');
    redirect($redirectTo . '#booking');
}

clear_old_input();
flash('success', 'Заявка отправлена. Мы свяжемся с вами для подтверждения.');
redirect($redirectTo . '#booking');
