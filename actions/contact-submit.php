<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('contacts.php');
}

$input = [
    'name' => sanitize_plain($_POST['name'] ?? ''),
    'email' => trim((string) ($_POST['email'] ?? '')),
    'phone' => trim((string) ($_POST['phone'] ?? '')),
    'subject' => sanitize_plain($_POST['subject'] ?? ''),
    'message' => sanitize_plain($_POST['message'] ?? ''),
    'privacy' => isset($_POST['privacy']) ? '1' : '',
];

$errors = [];

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    $errors['form'] = 'Сессия устарела. Обновите страницу.';
}

if (!honeypot_ok($_POST)) {
    clear_old_input();
    flash('success', 'Сообщение отправлено. Мы ответим в ближайшее время.');
    redirect('contacts.php#contact-form');
}

if (!rate_limit('contact', 5, 600)) {
    $errors['form'] = 'Слишком много сообщений. Подождите несколько минут.';
}

if ($input['name'] === '' || mb_strlen($input['name']) > 100) {
    $errors['name'] = 'Укажите имя.';
}

if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Укажите корректный email.';
}

if ($input['phone'] !== '' && !is_valid_phone($input['phone'])) {
    $errors['phone'] = 'Проверьте формат телефона.';
}

if (mb_strlen($input['subject']) > 150) {
    $errors['subject'] = 'Тема слишком длинная.';
}

if ($input['message'] === '' || mb_strlen($input['message']) > 2000) {
    $errors['message'] = 'Напишите сообщение до 2000 символов.';
}

if ($input['privacy'] !== '1') {
    $errors['privacy'] = 'Нужно согласие с политикой конфиденциальности.';
}

$submitHash = hash('sha256', implode('|', [
    $input['name'],
    $input['email'],
    $input['phone'],
    $input['subject'],
    $input['message'],
]));
if (!empty($_SESSION['last_contact_hash']) && hash_equals((string) $_SESSION['last_contact_hash'], $submitHash)) {
    $errors['form'] = 'Это сообщение уже было отправлено.';
}

if ($errors) {
    set_form_state($errors, $input);
    flash('error', $errors['form'] ?? 'Проверьте поля формы.');
    redirect('contacts.php#contact-form');
}

if (!db_available()) {
    set_form_state(['form' => 'Сервис временно недоступен.'], $input);
    flash('error', 'Сервис временно недоступен. Напишите нам на email.');
    redirect('contacts.php#contact-form');
}

try {
    $phoneStore = $input['phone'] !== '' ? normalize_phone($input['phone']) : null;
    $stmt = db()->prepare(
        'INSERT INTO contact_messages
            (customer_name, phone, email, subject, message, status)
         VALUES
            (:name, :phone, :email, :subject, :message, :status)'
    );
    $stmt->execute([
        'name' => $input['name'],
        'phone' => $phoneStore !== '' ? $phoneStore : null,
        'email' => $input['email'],
        'subject' => $input['subject'] !== '' ? $input['subject'] : null,
        'message' => $input['message'],
        'status' => 'new',
    ]);

    $_SESSION['last_contact_hash'] = $submitHash;

    try {
        notify_admin(
            'Новое сообщение с сайта',
            "Имя: {$input['name']}\nEmail: {$input['email']}\nТема: {$input['subject']}\n\n{$input['message']}\n"
        );
    } catch (Throwable) {
        // ignore mail failures
    }
} catch (Throwable $e) {
    storage_log('contact-submit: ' . $e->getMessage());
    set_form_state(['form' => 'Не удалось отправить сообщение.'], $input);
    flash('error', 'Не удалось отправить сообщение. Попробуйте позже.');
    redirect('contacts.php#contact-form');
}

clear_old_input();
flash('success', 'Сообщение отправлено. Мы ответим в ближайшее время.');
redirect('contacts.php#contact-form');
