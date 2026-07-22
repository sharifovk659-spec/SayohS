<?php

declare(strict_types=1);

/**
 * Optional local overrides for Google reCAPTCHA v3.
 * Copy to recaptcha.php and fill keys (recaptcha.php is gitignored if you prefer).
 *
 * Create keys: https://www.google.com/recaptcha/admin
 * Type: reCAPTCHA v3
 * Domains: sayoh-s-jekq.vercel.app (and your custom domain)
 */
return [
    'site_key' => '',
    'secret_key' => '',
    'min_score' => 0.4,
];
