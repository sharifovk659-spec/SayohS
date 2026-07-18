<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_user();
$userId = (int) $user['id'];
$accountSection = 'security';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', __('error_csrf'));
        redirect('account/security.php');
    }

    $current = (string) ($_POST['current_password'] ?? '');
    $new = (string) ($_POST['new_password'] ?? '');
    $confirm = (string) ($_POST['confirm_password'] ?? '');

    $errors = [];
    if ($current === '') {
        $errors['current_password'] = __('error_required');
    }
    if ($new === '') {
        $errors['new_password'] = __('error_required');
    } elseif (strlen($new) < 8) {
        $errors['new_password'] = __('error_password_short');
    }
    if ($confirm === '') {
        $errors['confirm_password'] = __('error_required');
    } elseif ($new !== $confirm) {
        $errors['confirm_password'] = __('error_password_mismatch');
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$userId]);
            $row = $stmt->fetch();
            $hash = is_array($row) ? (string) ($row['password_hash'] ?? '') : '';

            if ($hash === '' || !password_verify($current, $hash)) {
                $errors['current_password'] = 'Неверный текущий пароль';
            }
        } catch (Throwable $e) {
            storage_log('account/security verify: ' . $e->getMessage());
            $errors['current_password'] = __('error_generic');
        }
    }

    if ($errors !== []) {
        set_form_state($errors, []);
        flash('error', __('error_required'));
        redirect('account/security.php');
    }

    try {
        $newHash = password_hash($new, PASSWORD_DEFAULT);
        db()->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')
            ->execute([$newHash, $userId]);
        clear_old_input();
        flash('success', __('success_saved'));
    } catch (Throwable $e) {
        storage_log('account/security update: ' . $e->getMessage());
        flash('error', __('error_generic'));
    }

    redirect('account/security.php');
}

$pageTitle = __('account_security') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('change_password');
$bodyClass = 'page-account';
$errors = form_errors();

require __DIR__ . '/../includes/header.php';
?>

<div class="container account-layout">
  <?php require __DIR__ . '/_nav.php'; ?>

  <div class="account-main">
    <div class="account-card" data-reveal>
      <h1><?= e(__('change_password')) ?></h1>
      <p class="account-muted"><?= e(__('account_security')) ?></p>

      <form class="form-panel account-form" method="post" action="<?= e(base_url('account/security.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group full<?= field_invalid('current_password', $errors) ?>">
            <label for="sec-current"><?= e(__('current_password')) ?> *</label>
            <input type="password" id="sec-current" name="current_password" required autocomplete="current-password">
            <?= field_error('current_password', $errors) ?>
          </div>
          <div class="form-group<?= field_invalid('new_password', $errors) ?>">
            <label for="sec-new"><?= e(__('new_password')) ?> *</label>
            <input type="password" id="sec-new" name="new_password" required minlength="8" autocomplete="new-password">
            <?= field_error('new_password', $errors) ?>
          </div>
          <div class="form-group<?= field_invalid('confirm_password', $errors) ?>">
            <label for="sec-confirm"><?= e(__('password_confirm')) ?> *</label>
            <input type="password" id="sec-confirm" name="confirm_password" required minlength="8" autocomplete="new-password">
            <?= field_error('confirm_password', $errors) ?>
          </div>
          <div class="form-group full form-actions">
            <button class="btn btn-primary" type="submit"><?= e(__('btn_save')) ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
