<?php

declare(strict_types=1);

/**
 * Google Sign-In button (register / login).
 *
 * @var string $from 'register'|'login'
 */

$from = $from ?? 'register';
$googleUrl = base_url('auth-google.php?from=' . rawurlencode((string) $from));
?>
<div class="auth-google">
  <div class="auth-google__divider" aria-hidden="true">
    <span><?= e(__('auth_or')) ?></span>
  </div>
  <a class="btn-google" href="<?= e($googleUrl) ?>" rel="nofollow">
    <span class="btn-google__icon" aria-hidden="true">
      <svg viewBox="0 0 48 48" width="22" height="22" focusable="false">
        <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.3 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34.2 6.1 29.4 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.2-.1-2.3-.4-3.5z"/>
        <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16.1 19 13 24 13c3 0 5.8 1.1 7.9 3l5.7-5.7C34.2 6.1 29.4 4 24 4 16.3 4 9.6 8.3 6.3 14.7z"/>
        <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.3 0-9.7-3.3-11.3-7.9l-6.5 5C9.5 39.6 16.2 44 24 44z"/>
        <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.3 4.1-4.2 5.5l.1.1 6.2 5.2C39.2 36.3 44 31.5 44 24c0-1.2-.1-2.3-.4-3.5z"/>
      </svg>
    </span>
    <span class="btn-google__label"><?= e(__('auth_google')) ?></span>
  </a>
</div>
