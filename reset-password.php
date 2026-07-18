<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$pageTitle = __('reset_password') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('reset_password');
$bodyClass = 'page-auth page-reset-password';
$errors = form_errors();
$tokenValid = false;
$userId = null;

$peekResetToken = static function (string $resetToken): ?int {
    if (!preg_match('/^[a-f0-9]{64}$/i', $resetToken)) {
        return null;
    }
    $hash = hash('sha256', $resetToken);
    try {
        $stmt = db()->prepare(
            'SELECT user_id FROM password_resets
             WHERE token_hash = ? AND used_at IS NULL AND expires_at >= NOW()
             LIMIT 1'
        );
        $stmt->execute([$hash]);
        $row = $stmt->fetch();
        return $row ? (int) $row['user_id'] : null;
    } catch (Throwable $e) {
        storage_log('peek_password_reset_token: ' . $e->getMessage());
        return null;
    }
};

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = [
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
        'token' => $token,
    ];

    $errors = [];

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = __('error_csrf');
    }

    if ($errors === [] && auth_rate_limited('reset_password', 8, 900)) {
        $errors['form'] = __('error_generic');
    }

    if ($errors === []) {
        $peekUserId = $peekResetToken($token);
        if ($peekUserId === null) {
            $errors['form'] = __('error_generic');
        }
    }

    if ($errors === [] && strlen($input['password']) < 8) {
        $errors['password'] = __('error_password_short');
    }

    if ($errors === [] && $input['password'] !== $input['password_confirm']) {
        $errors['password_confirm'] = __('error_password_mismatch');
    }

    if ($errors !== []) {
        set_form_state($errors, []);
        flash('error', $errors['form'] ?? __('error_required'));
        redirect('reset-password.php?token=' . rawurlencode($token));
    }

    $userId = consume_password_reset_token($token);
    if ($userId === null) {
        flash('error', __('error_generic'));
        redirect('reset-password.php?token=' . rawurlencode($token));
    }

    try {
        $hash = password_hash($input['password'], PASSWORD_DEFAULT);
        db()->prepare(
            'UPDATE users SET password_hash = ?, login_attempts = 0, locked_until = NULL, updated_at = NOW() WHERE id = ?'
        )->execute([$hash, $userId]);

        clear_old_input();
        flash('success', __('success_saved'));
        redirect('login.php');
    } catch (Throwable $e) {
        storage_log('reset-password: ' . $e->getMessage());
        flash('error', __('error_generic'));
        redirect('reset-password.php?token=' . rawurlencode($token));
    }
} elseif ($token !== '') {
    $userId = $peekResetToken($token);
    $tokenValid = $userId !== null;
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow"><?= e(__('reset_password')) ?></p>
      <h1><?= e(__('reset_password')) ?></h1>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="reservation-panel auth-panel" data-reveal>
      <?php if (!$tokenValid): ?>
        <p class="section-text"><?= e(__('error_generic')) ?></p>
        <p><a class="btn btn-outline" href="<?= e(base_url('forgot-password.php')) ?>"><?= e(__('forgot_password')) ?></a></p>
      <?php else: ?>
        <form class="reservation-form" method="post" action="<?= e(base_url('reset-password.php')) ?>" novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="token" value="<?= e($token) ?>">

          <div class="form-grid">
            <div class="form-group full<?= field_invalid('password', $errors) ?>">
              <label for="reset-password"><?= e(__('new_password')) ?> *</label>
              <input type="password" id="reset-password" name="password" required minlength="8" autocomplete="new-password">
              <?= field_error('password', $errors) ?>
            </div>

            <div class="form-group full<?= field_invalid('password_confirm', $errors) ?>">
              <label for="reset-password-confirm"><?= e(__('password_confirm')) ?> *</label>
              <input type="password" id="reset-password-confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
              <?= field_error('password_confirm', $errors) ?>
            </div>

            <div class="form-group full form-actions">
              <button class="btn btn-primary btn-full" type="submit"><?= e(__('btn_save')) ?></button>
            </div>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
