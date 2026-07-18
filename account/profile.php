<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_user();
$userId = (int) $user['id'];
$accountSection = 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', __('error_csrf'));
        redirect('account/profile.php');
    }

    $name = sanitize_plain($_POST['name'] ?? '');
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $phoneRaw = trim((string) ($_POST['phone'] ?? ''));
    $phone = $phoneRaw !== '' ? normalize_phone($phoneRaw) : '';

    $errors = [];
    if ($name === '') {
        $errors['name'] = __('error_required');
    } elseif (mb_strlen($name) > 120) {
        $errors['name'] = __('error_required');
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = __('error_email');
    } elseif (mb_strlen($email) > 190) {
        $errors['email'] = __('error_email');
    }
    if ($phoneRaw !== '' && $phone === '') {
        $errors['phone'] = __('error_phone');
    }

    if ($errors === []) {
        try {
            $dup = db()->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $dup->execute([$email, $userId]);
            if ($dup->fetch()) {
                $errors['email'] = 'Этот email уже используется';
            }
        } catch (Throwable $e) {
            storage_log('account/profile email check: ' . $e->getMessage());
            $errors['email'] = __('error_generic');
        }
    }

    if ($errors !== []) {
        set_form_state($errors, ['name' => $name, 'email' => $email, 'phone' => $phoneRaw]);
        flash('error', __('error_required'));
        redirect('account/profile.php');
    }

    try {
        $stmt = db()->prepare(
            'UPDATE users SET name = ?, email = ?, phone = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$name, $email, $phone !== '' ? $phone : null, $userId]);
        $_SESSION['user_name'] = $name;
        clear_old_input();
        flash('success', __('success_saved'));
    } catch (Throwable $e) {
        storage_log('account/profile update: ' . $e->getMessage());
        flash('error', __('error_generic'));
    }

    redirect('account/profile.php');
}

$pageTitle = __('account_profile') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('account_profile');
$bodyClass = 'page-account';
$errors = form_errors();

require __DIR__ . '/../includes/header.php';
?>

<div class="container account-layout">
  <?php require __DIR__ . '/_nav.php'; ?>

  <div class="account-main">
    <div class="account-card" data-reveal>
      <h1><?= e(__('account_profile')) ?></h1>

      <form class="form-panel account-form" method="post" action="<?= e(base_url('account/profile.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <div class="form-grid">
          <div class="form-group full<?= field_invalid('name', $errors) ?>">
            <label for="profile-name"><?= e(__('name')) ?> *</label>
            <input
              type="text"
              id="profile-name"
              name="name"
              required
              maxlength="120"
              value="<?= e((string) old('name', $user['name'] ?? '')) ?>"
            >
            <?= field_error('name', $errors) ?>
          </div>
          <div class="form-group<?= field_invalid('email', $errors) ?>">
            <label for="profile-email"><?= e(__('email')) ?> *</label>
            <input
              type="email"
              id="profile-email"
              name="email"
              required
              maxlength="190"
              value="<?= e((string) old('email', $user['email'] ?? '')) ?>"
            >
            <?= field_error('email', $errors) ?>
          </div>
          <div class="form-group<?= field_invalid('phone', $errors) ?>">
            <label for="profile-phone"><?= e(__('phone')) ?></label>
            <input
              type="tel"
              id="profile-phone"
              name="phone"
              maxlength="40"
              value="<?= e((string) old('phone', $user['phone'] ?? '')) ?>"
            >
            <?= field_error('phone', $errors) ?>
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
