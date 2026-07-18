<?php

declare(strict_types=1);

/** @var array $app */

$openingHours = fetch_opening_hours();
$socialLinks = fetch_social_links();

$hoursLines = [];
if ($openingHours) {
    $weekday = null;
    $weekend = null;
    foreach ($openingHours as $row) {
        $dn = (int) ($row['day_number'] ?? 0);
        if ((int) ($row['is_closed'] ?? 0) === 1) {
            $label = ($row['day_name'] ?? '') . ': выходной';
        } else {
            $from = substr((string) ($row['time_from'] ?? ''), 0, 5);
            $toRaw = (string) ($row['time_to'] ?? '');
            $to = substr($toRaw, 0, 5);
            if ($to === '00:00') {
                $to = '00:00';
            }
            $label = ($row['day_name'] ?? '') . ': ' . $from . ' — ' . $to;
        }
        if ($dn >= 1 && $dn <= 4 && $weekday === null) {
            $from = substr((string) ($row['time_from'] ?? ''), 0, 5);
            $to = substr((string) ($row['time_to'] ?? ''), 0, 5);
            $weekday = 'Пн–Чт: ' . $from . ' — ' . $to;
        }
        if ($dn === 5) {
            $from = substr((string) ($row['time_from'] ?? ''), 0, 5);
            $to = substr((string) ($row['time_to'] ?? ''), 0, 5);
            $weekend = 'Пт–Вс: ' . $from . ' — ' . ($to === '00:00' ? '00:00' : $to);
        }
    }
    if ($weekday) {
        $hoursLines[] = $weekday;
    }
    if ($weekend) {
        $hoursLines[] = $weekend;
    }
}
if ($hoursLines === []) {
    $hoursLines = [
        (string) ($app['hours_weekdays'] ?? 'Пн–Чт: 12:00 — 23:00'),
        (string) ($app['hours_weekend'] ?? 'Пт–Вс: 12:00 — 00:00'),
    ];
}

$socialIconSvg = static function (string $icon): string {
    return match (strtolower($icon)) {
        'whatsapp' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 3.5a8.5 8.5 0 0 0-7.3 12.9L4 20.5l4.2-.7A8.5 8.5 0 1 0 12 3.5Z" stroke="currentColor" stroke-width="1.5"/><path d="M9.2 9.4c.2-.4.4-.4.6-.4h.5c.2 0 .4 0 .5.3l.7 1.7c.1.2 0 .4-.1.6l-.4.5c-.1.1-.1.3 0 .4.4.7 1.1 1.4 1.9 1.8.2.1.3.1.4 0l.6-.5c.2-.1.4-.1.5 0l1.5.8c.3.1.4.3.3.6-.3 1.1-1.4 1.5-2.4 1.2-2.4-.7-4.4-2.7-5.2-5.1-.3-1 .1-2.2 1-2.5Z" fill="currentColor"/></svg>',
        'instagram' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="12" r="4" stroke="currentColor" stroke-width="1.5"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg>',
        'facebook' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 9h3V6h-3c-2.2 0-4 1.8-4 4v2H8v3h2v6h3v-6h2.5L16 12h-3v-1c0-1.1.9-2 2-2Z" fill="currentColor"/></svg>',
        'tiktok' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M14 4c.4 2.4 2 4.2 4.2 4.6V11c-1.5-.1-2.9-.7-4-1.6v6.2A5.1 5.1 0 1 1 9.4 10.5v2.4a2.8 2.8 0 1 0 2 2.7V4h2.6Z" fill="currentColor"/></svg>',
        default => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.5"/></svg>',
    };
};
?>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid footer-grid--5">
      <div class="footer-brand">
        <?php
        $footerLogoFile = setting('logo') ?: 'sayoh-logo.png';
        $footerLogoUpload = __DIR__ . '/../uploads/settings/' . basename((string) $footerLogoFile);
        $footerLogoAsset = __DIR__ . '/../assets/images/brand/sayoh-logo.png';
        if ($footerLogoFile !== '' && is_file($footerLogoUpload)) {
            $footerLogoUrl = upload_url('settings', basename((string) $footerLogoFile));
        } elseif (is_file($footerLogoAsset)) {
            $footerLogoUrl = asset('images/brand/sayoh-logo.png');
        } else {
            $footerLogoUrl = '';
        }
        ?>
        <a class="brand brand-light" href="<?= e(base_url()) ?>" aria-label="<?= e($app['full_name'] ?? $app['name']) ?>">
          <?php if ($footerLogoUrl !== ''): ?>
            <img class="brand-logo" src="<?= e($footerLogoUrl) ?>" alt="<?= e((string) ($app['full_name'] ?? $app['name'])) ?>" width="72" height="72" decoding="async" loading="lazy">
          <?php else: ?>
            <span class="brand-mark" aria-hidden="true">
              <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
                <circle cx="14" cy="14" r="13" stroke="currentColor" stroke-width="1.5"/>
                <path d="M9 17.5c1.8-3.2 3.2-6.8 5-10.2 1.7 3.4 3.1 7 5 10.2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                <circle cx="14" cy="18.5" r="1.4" fill="currentColor"/>
              </svg>
            </span>
          <?php endif; ?>
        </a>
        <p class="footer-text"><?= e($app['description']) ?></p>
      </div>

      <div class="footer-col">
        <h2 class="footer-title">Контакты</h2>
        <ul class="footer-contacts">
          <li><a href="tel:<?= e($app['phone_href']) ?>"><?= e($app['phone']) ?></a></li>
          <li><a href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a></li>
          <li><a href="<?= e($app['whatsapp']) ?>" target="_blank" rel="noopener noreferrer">WhatsApp</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h2 class="footer-title">Адрес</h2>
        <ul class="footer-contacts">
          <li><?= e($app['address']) ?></li>
          <li><a href="<?= e($app['map_url']) ?>" target="_blank" rel="noopener noreferrer">Открыть на карте</a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h2 class="footer-title">Часы работы</h2>
        <ul class="footer-contacts">
          <?php foreach ($hoursLines as $line): ?>
            <li><?= e($line) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>

      <div class="footer-col">
        <h2 class="footer-title">Соцсети</h2>
        <div class="footer-social">
          <?php if ($socialLinks): ?>
            <?php foreach ($socialLinks as $link): ?>
              <a class="social-link" href="<?= e((string) $link['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e((string) $link['platform']) ?>">
                <?= $socialIconSvg((string) ($link['icon'] ?? $link['platform'] ?? '')) ?>
              </a>
            <?php endforeach; ?>
          <?php else: ?>
            <a class="social-link" href="<?= e($app['whatsapp']) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp">
              <?= $socialIconSvg('whatsapp') ?>
            </a>
            <a class="social-link" href="<?= e($app['instagram']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
              <?= $socialIconSvg('instagram') ?>
            </a>
            <a class="social-link" href="<?= e($app['facebook']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
              <?= $socialIconSvg('facebook') ?>
            </a>
            <a class="social-link" href="<?= e($app['tiktok']) ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok">
              <?= $socialIconSvg('tiktok') ?>
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container footer-bottom-inner footer-bottom-links">
        <p>© <?= date('Y') ?> <?= e($app['full_name'] ?? $app['name']) ?>. Все права защищены.</p>
        <div class="footer-legal">
          <a href="<?= e(base_url('privacy.php')) ?>">Политика конфиденциальности</a>
          <a href="<?= e(base_url('terms.php')) ?>">Пользовательское соглашение</a>
          <a href="https://webdushanbe.tj/" target="_blank" rel="noopener noreferrer">Разработка сайта — WebDushanbe</a>
        </div>
      </div>
    </div>
  </footer>

  <?php
  $schema = [
      '@context' => 'https://schema.org',
      '@type' => 'Restaurant',
      'name' => (string) ($app['full_name'] ?? $app['name']),
      'description' => (string) ($app['description'] ?? ''),
      'telephone' => (string) ($app['phone'] ?? ''),
      'email' => (string) ($app['email'] ?? ''),
      'address' => [
          '@type' => 'PostalAddress',
          'streetAddress' => (string) ($app['address'] ?? ''),
          'addressLocality' => 'Москва',
          'addressCountry' => 'RU',
      ],
      'url' => rtrim(base_url(), '/') . '/',
      'servesCuisine' => 'Европейская, авторская',
      'priceRange' => '₽₽',
  ];
  if (!empty($app['map_url'])) {
      $schema['hasMap'] = (string) $app['map_url'];
  }
  ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
