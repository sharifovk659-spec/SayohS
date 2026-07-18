<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (user_logged_in()) {
    redirect('account/');
}

$pageTitle = __('forgot_password') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('forgot_password');
$bodyClass = 'page-auth page-forgot-password';
$errors = form_errors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = [
        'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
    ];

    $errors = [];

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = __('error_csrf');
    }

    if ($errors === [] && auth_rate_limited('forgot_password', 5, 900)) {
        $errors['form'] = __('error_generic');
    }

    if ($errors === [] && ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL))) {
        $errors['email'] = __('error_email');
    }

    if ($errors !== []) {
        set_form_state($errors, $input);
        flash('error', $errors['form'] ?? __('error_required'));
        redirect('forgot-password.php');
    }

    try {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ? AND status = ? LIMIT 1');
        $stmt->execute([$input['email'], 'active']);
        $userId = $stmt->fetchColumn();

        if ($userId) {
            $token = create_password_reset_token((int) $userId);
            if ($token !== null) {
                send_password_reset_mail($input['email'], $token);
            }
        }
    } catch (Throwable $e) {
        storage_log('forgot-password: ' . $e->getMessage());
    }

    clear_old_input();
    flash('success', __('success_saved'));
    redirect('forgot-password.php');
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow"><?= e(__('reset_password')) ?></p>
      <h1><?= e(__('forgot_password')) ?></h1>
      <p><?= e(__('email')) ?></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="reservation-panel auth-panel" data-reveal>
      <form class="reservation-form" method="post" action="<?= e(base_url('forgot-password.php')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-grid">
          <div class="form-group full<?= field_invalid('email', $errors) ?>">
            <label for="forgot-email"><?= e(__('email')) ?> *</label>
            <input type="email" id="forgot-email" name="email" required maxlength="190" autocomplete="email"
                   value="<?= e((string) old('email')) ?>">
            <?= field_error('email', $errors) ?>
          </div>

          <div class="form-group full form-actions">
            <button class="btn btn-primary btn-full" type="submit"><?= e(__('btn_submit')) ?></button>
          </div>

          <div class="form-group full auth-links">
            <a href="<?= e(base_url('login.php')) ?>"><?= e(__('btn_back')) ?></a>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
