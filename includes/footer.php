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
            $to = substr((string) ($row['time_to'] ?? ''), 0, 5);
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
        (string) ($app['hours_weekdays'] ?? 'Пн–Чт: 10:00 — 00:00'),
        (string) ($app['hours_weekend'] ?? 'Пт–Вс: 10:00 — 00:00'),
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

$waNumber = '100944545';
$waHref = 'https://wa.me/992' . $waNumber;
$waTips = [
    'Напишите нам в WhatsApp',
    'Бронь стола за 1 минуту',
    'Доставка — спросите здесь',
    'Есть вопросы? Мы онлайн',
    'Закажите через WhatsApp',
];
$waTip = $waTips[array_rand($waTips)];

$footerLogoAsset = __DIR__ . '/../assets/images/brand/sayoh-logo.png';
$footerLogoUrl = is_file($footerLogoAsset) ? asset('images/brand/sayoh-logo.png') : '';
?>
  </main>

  <footer class="site-footer site-footer--compact">
    <div class="container footer-grid footer-grid--compact">
      <div class="footer-brand">
        <a class="brand brand-light" href="<?= e(base_url()) ?>" aria-label="<?= e($app['full_name'] ?? $app['name']) ?>">
          <?php if ($footerLogoUrl !== ''): ?>
            <img class="brand-logo brand-logo--footer" src="<?= e($footerLogoUrl) ?>" alt="<?= e((string) ($app['full_name'] ?? $app['name'])) ?>" width="48" height="48" decoding="async" loading="lazy">
          <?php endif; ?>
        </a>
        <p class="footer-text"><?= e($app['tagline'] ?? $app['description'] ?? '') ?></p>
      </div>

      <div class="footer-col">
        <h2 class="footer-title">Контакты</h2>
        <ul class="footer-contacts">
          <li><a href="tel:<?= e($app['phone_href']) ?>"><?= e($app['phone']) ?></a></li>
          <li><a href="<?= e($waHref) ?>" target="_blank" rel="noopener noreferrer">WhatsApp <?= e($waNumber) ?></a></li>
          <li><?= e($app['address']) ?></li>
        </ul>
      </div>

      <div class="footer-col">
        <h2 class="footer-title">Часы</h2>
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
            <a class="social-link" href="<?= e($waHref) ?>" target="_blank" rel="noopener noreferrer" aria-label="WhatsApp"><?= $socialIconSvg('whatsapp') ?></a>
            <a class="social-link" href="<?= e($app['instagram'] ?? '#') ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram"><?= $socialIconSvg('instagram') ?></a>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="footer-bottom">
      <div class="container footer-bottom-inner footer-bottom-links">
        <p>© <?= date('Y') ?> <?= e($app['full_name'] ?? $app['name']) ?></p>
        <div class="footer-legal">
          <a href="<?= e(base_url('privacy.php')) ?>">Политика конфиденциальности</a>
          <a href="https://komron.inovaauto.com/" target="_blank" rel="noopener noreferrer">Разработка сайта — sharifof-dev</a>
        </div>
      </div>
    </div>
  </footer>

  <a
    class="wa-float"
    href="<?= e($waHref) ?>"
    target="_blank"
    rel="noopener noreferrer"
    aria-label="WhatsApp <?= e($waNumber) ?>"
    data-wa-float
  >
    <span class="wa-float__tip" data-wa-tip><?= e($waTip) ?></span>
    <span class="wa-float__badge" data-wa-badge aria-hidden="true">1</span>
    <span class="wa-float__icon" aria-hidden="true">
      <svg viewBox="0 0 24 24" width="30" height="30" aria-hidden="true">
        <path fill="#ffffff" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.435 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/>
      </svg>
    </span>
  </a>

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
          'addressLocality' => 'Dushanbe',
          'addressCountry' => 'TJ',
      ],
      'url' => rtrim(base_url(), '/') . '/',
      'servesCuisine' => 'Чайхана, среднеазиатская',
      'priceRange' => '$$',
  ];
  if (!empty($app['map_url'])) {
      $schema['hasMap'] = (string) $app['map_url'];
  }
  ?>
  <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
  <script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
