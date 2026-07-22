<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (user_logged_in()) {
    redirect('account/');
}

const USER_MAX_ATTEMPTS = 5;
const USER_LOCK_MINUTES = 15;

$pageTitle = __('login_title') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('login_title');
$bodyClass = 'page-auth page-login';
$errors = form_errors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = [
        'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
        'password' => (string) ($_POST['password'] ?? ''),
    ];

    $errors = [];
    $genericError = __('error_generic');

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = __('error_csrf');
    }

    if ($errors === [] && auth_rate_limited('login', 12, 900)) {
        $errors['form'] = $genericError;
    }

    if ($errors === [] && ($input['email'] === '' || $input['password'] === '')) {
        $errors['form'] = __('error_required');
    }

    if ($errors === [] && !db_available()) {
        $errors['form'] = $genericError;
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare(
                'SELECT id, name, email, phone, password_hash, status, login_attempts, locked_until,
                        email_verified_at, last_login_at, created_at
                 FROM users WHERE email = ? LIMIT 1'
            );
            $stmt->execute([$input['email']]);
            $user = $stmt->fetch();

            $now = new DateTimeImmutable('now');

            if ($user && !empty($user['locked_until'])) {
                $lockedUntil = new DateTimeImmutable((string) $user['locked_until']);
                if ($lockedUntil > $now) {
                    $errors['form'] = $genericError;
                }
            }

            if ($errors === []) {
                $valid = $user
                    && ($user['status'] ?? '') === 'active'
                    && password_verify($input['password'], (string) $user['password_hash']);

                if ($valid) {
                    login_user($user);
                    clear_old_input();
                    flash('success', __('success_logged_in'));

                    $target = trim((string) ($_SESSION['redirect_after_login'] ?? ''));
                    unset($_SESSION['redirect_after_login']);
                    if ($target === '' || str_contains($target, '://') || str_starts_with($target, '//')) {
                        $target = 'account/';
                    }
                    redirect(ltrim($target, '/'));
                }

                if ($user) {
                    $attempts = (int) ($user['login_attempts'] ?? 0) + 1;
                    if ($attempts >= USER_MAX_ATTEMPTS) {
                        $lock = $now->modify('+' . USER_LOCK_MINUTES . ' minutes')->format('Y-m-d H:i:s');
                        db()->prepare('UPDATE users SET login_attempts = ?, locked_until = ? WHERE id = ?')
                            ->execute([$attempts, $lock, (int) $user['id']]);
                    } else {
                        db()->prepare('UPDATE users SET login_attempts = ? WHERE id = ?')
                            ->execute([$attempts, (int) $user['id']]);
                    }
                }

                $errors['form'] = $genericError;
            }
        } catch (Throwable $e) {
            storage_log('login: ' . $e->getMessage());
            $errors['form'] = $genericError;
        }
    }

    set_form_state($errors, $input);
    flash('error', $errors['form'] ?? $genericError);
    redirect('login.php');
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow"><?= e(__('login_title')) ?></p>
      <h1><?= e(__('login_title')) ?></h1>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="reservation-panel auth-panel" data-reveal>
      <form class="reservation-form" method="post" action="<?= e(base_url('login.php')) ?>" novalidate>
        <?= csrf_field() ?>

        <div class="form-grid">
          <div class="form-group full<?= field_invalid('email', $errors) ?>">
            <label for="login-email"><?= e(__('email')) ?> *</label>
            <input type="email" id="login-email" name="email" required maxlength="190" autocomplete="email"
                   value="<?= e((string) old('email')) ?>">
            <?= field_error('email', $errors) ?>
          </div>

          <div class="form-group full<?= field_invalid('password', $errors) ?>">
            <label for="login-password"><?= e(__('password')) ?> *</label>
            <input type="password" id="login-password" name="password" required autocomplete="current-password">
            <?= field_error('password', $errors) ?>
          </div>

          <div class="form-group full auth-links">
            <a href="<?= e(base_url('forgot-password.php')) ?>"><?= e(__('forgot_password')) ?></a>
          </div>

          <div class="form-group full form-actions">
            <button class="btn btn-primary btn-full" type="submit"><?= e(__('login_submit')) ?></button>
          </div>

          <?php
          $from = 'login';
          require __DIR__ . '/components/google-auth-button.php';
          ?>

          <div class="form-group full auth-links">
            <p><?= e(__('no_account')) ?> <a href="<?= e(base_url('register.php')) ?>"><?= e(__('register_submit')) ?></a></p>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
