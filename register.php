<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

if (user_logged_in()) {
    redirect('account/');
}

$pageTitle = __('register_title') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('register_title');
$bodyClass = 'page-auth page-register';
$errors = form_errors();
$captchaEnabled = recaptcha_enabled();
$captchaSiteKey = recaptcha_site_key();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $input = [
        'name' => sanitize_plain($_POST['name'] ?? ''),
        'phone' => trim((string) ($_POST['phone'] ?? '')),
        'email' => mb_strtolower(trim((string) ($_POST['email'] ?? ''))),
        'password' => (string) ($_POST['password'] ?? ''),
        'password_confirm' => (string) ($_POST['password_confirm'] ?? ''),
        'privacy' => isset($_POST['privacy']) ? '1' : '',
    ];

    $errors = [];

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        $errors['form'] = __('error_csrf');
    }

    if ($errors === [] && auth_rate_limited('register', 6, 900)) {
        $errors['form'] = __('error_generic');
    }

    if ($errors === [] && $captchaEnabled) {
        $captcha = recaptcha_verify($_POST['g-recaptcha-response'] ?? null, 'register');
        if (!$captcha['ok']) {
            $errors['form'] = __('error_captcha');
            $errors['captcha'] = __('error_captcha');
        }
    }

    if ($input['name'] === '' || mb_strlen($input['name']) > 120) {
        $errors['name'] = __('error_required');
    }

    if ($input['phone'] === '' || !is_valid_phone($input['phone'])) {
        $errors['phone'] = __('error_phone');
    }

    if ($input['email'] === '' || !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = __('error_email');
    }

    if (strlen($input['password']) < 8) {
        $errors['password'] = __('error_password_short');
    }

    if ($input['password'] !== $input['password_confirm']) {
        $errors['password_confirm'] = __('error_password_mismatch');
    }

    if ($input['privacy'] !== '1') {
        $errors['privacy'] = __('error_required');
    }

    if ($errors === [] && !db_available()) {
        $errors['form'] = __('error_register_unavailable');
    }

    if ($errors === []) {
        try {
            $stmt = db()->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$input['email']]);
            if ($stmt->fetchColumn()) {
                $errors['email'] = __('error_email_taken');
                $errors['form'] = __('error_email_taken');
            }
        } catch (Throwable $e) {
            storage_log('register check email: ' . $e->getMessage());
            $errors['form'] = __('error_register_unavailable');
        }
    }

    if ($errors !== []) {
        set_form_state($errors, [
            'name' => $input['name'],
            'phone' => $input['phone'],
            'email' => $input['email'],
            'privacy' => $input['privacy'],
        ]);
        flash('error', $errors['form'] ?? __('error_required'));
        redirect('register.php');
    }

    $phoneStore = normalize_phone_e164($input['phone']);
    $hash = password_hash($input['password'], PASSWORD_DEFAULT);

    try {
        $stmt = db()->prepare(
            'INSERT INTO users (name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $input['name'],
            $input['email'],
            $phoneStore !== '' ? $phoneStore : null,
            $hash,
            'active',
        ]);
        $userId = (int) db()->lastInsertId();

        $userStmt = db()->prepare(
            'SELECT id, name, email, phone, status, email_verified_at, last_login_at, created_at
             FROM users WHERE id = ? LIMIT 1'
        );
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch();

        if (!$user) {
            throw new RuntimeException('User not found after insert');
        }

        login_user($user);
        clear_old_input();
        flash('success', __('success_registered'));

        $target = trim((string) ($_SESSION['redirect_after_login'] ?? ''));
        unset($_SESSION['redirect_after_login']);
        if ($target === '' || str_contains($target, '://') || str_starts_with($target, '//')) {
            $target = 'account/';
        }
        redirect(ltrim($target, '/'));
    } catch (Throwable $e) {
        storage_log('register: ' . $e->getMessage());
        $msg = __('error_register_unavailable');
        // Duplicate email race
        if (str_contains(strtolower($e->getMessage()), 'duplicate') || str_contains(strtolower($e->getMessage()), 'unique')) {
            $msg = __('error_email_taken');
        }
        set_form_state(['form' => $msg, 'email' => $msg], [
            'name' => $input['name'],
            'phone' => $input['phone'],
            'email' => $input['email'],
            'privacy' => $input['privacy'],
        ]);
        flash('error', $msg);
        redirect('register.php');
    }
}

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero page-hero--compact">
  <div class="container">
    <div class="page-hero-inner">
      <h1><?= e(__('register_title')) ?></h1>
      <p><?= e(__('no_account')) ?> — <?= e(__('register_submit')) ?></p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="reservation-panel auth-panel">
      <form
        class="reservation-form"
        method="post"
        action="<?= e(base_url('register.php')) ?>"
        novalidate
        data-register-form
        <?php if ($captchaEnabled): ?>
          data-recaptcha-sitekey="<?= e($captchaSiteKey) ?>"
        <?php endif; ?>
      >
        <?= csrf_field() ?>
        <input type="hidden" name="g-recaptcha-response" value="" data-recaptcha-token>

        <div class="form-grid">
          <div class="form-group<?= field_invalid('name', $errors) ?>">
            <label for="reg-name"><?= e(__('name')) ?> *</label>
            <input type="text" id="reg-name" name="name" required maxlength="120" autocomplete="name"
                   value="<?= e((string) old('name')) ?>">
            <?= field_error('name', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('phone', $errors) ?>">
            <label for="reg-phone"><?= e(__('phone')) ?> *</label>
            <input type="tel" id="reg-phone" name="phone" required maxlength="30" autocomplete="tel"
                   inputmode="tel" placeholder="+992 __ ___ ____" value="<?= e((string) old('phone')) ?>">
            <?= field_error('phone', $errors) ?>
          </div>

          <div class="form-group full<?= field_invalid('email', $errors) ?>">
            <label for="reg-email"><?= e(__('email')) ?> *</label>
            <input type="email" id="reg-email" name="email" required maxlength="190" autocomplete="email"
                   value="<?= e((string) old('email')) ?>">
            <?= field_error('email', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('password', $errors) ?>">
            <label for="reg-password"><?= e(__('password')) ?> *</label>
            <input type="password" id="reg-password" name="password" required minlength="8" autocomplete="new-password">
            <?= field_error('password', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('password_confirm', $errors) ?>">
            <label for="reg-password-confirm"><?= e(__('password_confirm')) ?> *</label>
            <input type="password" id="reg-password-confirm" name="password_confirm" required minlength="8" autocomplete="new-password">
            <?= field_error('password_confirm', $errors) ?>
          </div>

          <div class="form-group full<?= field_invalid('privacy', $errors) ?>">
            <label class="checkbox-label">
              <input type="checkbox" name="privacy" value="1" <?= old('privacy') ? 'checked' : '' ?> required>
              <span><?= e(__('privacy_agree')) ?> *</span>
            </label>
            <?= field_error('privacy', $errors) ?>
          </div>

          <?php if ($captchaEnabled): ?>
            <div class="form-group full recaptcha-note<?= field_invalid('captcha', $errors) ?>">
              <p class="recaptcha-hint"><?= e(__('recaptcha_hint')) ?></p>
              <?= field_error('captcha', $errors) ?>
            </div>
          <?php endif; ?>

          <div class="form-group full form-actions">
            <button class="btn btn-primary btn-full" type="submit" data-register-submit>
              <?= e(__('register_submit')) ?>
            </button>
          </div>

          <?php
          $from = 'register';
          require __DIR__ . '/components/google-auth-button.php';
          ?>

          <div class="form-group full auth-links">
            <p><?= e(__('already_have_account')) ?> <a href="<?= e(base_url('login.php')) ?>"><?= e(__('login_submit')) ?></a></p>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<?php if ($captchaEnabled): ?>
<script src="https://www.google.com/recaptcha/api.js?render=<?= e(rawurlencode($captchaSiteKey)) ?>"></script>
<script>
(() => {
  const form = document.querySelector('[data-register-form]');
  if (!form) return;

  const siteKey = form.getAttribute('data-recaptcha-sitekey') || '';
  const tokenInput = form.querySelector('[data-recaptcha-token]');
  const submitBtn = form.querySelector('[data-register-submit]');
  const failMsg = <?= json_encode(__('error_captcha'), JSON_UNESCAPED_UNICODE) ?>;
  let submitting = false;

  const whenReady = () => new Promise((resolve, reject) => {
    let tries = 0;
    const tick = () => {
      if (window.grecaptcha && typeof window.grecaptcha.execute === 'function') {
        if (typeof window.grecaptcha.ready === 'function') {
          window.grecaptcha.ready(resolve);
        } else {
          resolve();
        }
        return;
      }
      tries += 1;
      if (tries > 80) {
        reject(new Error('recaptcha-timeout'));
        return;
      }
      setTimeout(tick, 50);
    };
    tick();
  });

  form.addEventListener('submit', (event) => {
    if (submitting || !siteKey || !tokenInput) return;
    event.preventDefault();
    if (submitBtn) submitBtn.disabled = true;

    whenReady()
      .then(() => window.grecaptcha.execute(siteKey, { action: 'register' }))
      .then((token) => {
        tokenInput.value = token || '';
        submitting = true;
        HTMLFormElement.prototype.submit.call(form);
      })
      .catch(() => {
        if (submitBtn) submitBtn.disabled = false;
        submitting = false;
        alert(failMsg);
      });
  });
})();
</script>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
