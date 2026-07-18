<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Контакты — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = 'Адрес, телефон и форма связи ресторана Aroma.';
$bodyClass = 'page-contacts';
$errors = form_errors();

require __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
  <div class="container">
    <div class="page-hero-inner" data-reveal>
      <p class="eyebrow">Связь</p>
      <h1>Контакты</h1>
      <p>Напишите нам или позвоните — ответим по брони, меню и мероприятиям.</p>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="contact-grid" data-reveal>
      <div class="contact-card">
        <h2>Как нас найти</h2>
        <ul class="contact-list">
          <li><span>Адрес</span><strong><?= e($app['address']) ?></strong></li>
          <li><span>Телефон</span><a href="tel:<?= e($app['phone_href']) ?>"><?= e($app['phone']) ?></a></li>
          <li><span>Email</span><a href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a></li>
          <li><span>WhatsApp</span><a href="<?= e($app['whatsapp']) ?>" target="_blank" rel="noopener noreferrer">Написать в WhatsApp</a></li>
          <li>
            <span>Часы работы</span>
            <strong><?= e($app['hours_weekdays']) ?></strong>
            <strong><?= e($app['hours_weekend']) ?></strong>
          </li>
        </ul>

        <div class="contact-social">
          <a class="social-link social-link--light" href="<?= e($app['instagram']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">IG</a>
          <a class="social-link social-link--light" href="<?= e($app['facebook']) ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">FB</a>
          <a class="social-link social-link--light" href="<?= e($app['tiktok']) ?>" target="_blank" rel="noopener noreferrer" aria-label="TikTok">TT</a>
        </div>

        <div class="map-frame">
          <iframe
            title="Карта ресторана Aroma"
            src="<?= e($app['map_embed']) ?>"
            loading="lazy"
            referrerpolicy="no-referrer-when-downgrade"
          ></iframe>
        </div>
      </div>

      <form class="form-panel" id="contact-form" action="<?= e(base_url('actions/contact-submit.php')) ?>" method="post" novalidate>
        <?= csrf_field() ?>
        <div class="hp-field" aria-hidden="true" style="position:absolute;left:-9999px;height:0;overflow:hidden;">
          <label for="c-website">Website</label>
          <input type="text" id="c-website" name="website" value="" tabindex="-1" autocomplete="off">
        </div>
        <div class="form-grid">
          <div class="form-group<?= field_invalid('name', $errors) ?>">
            <label for="c-name">Имя *</label>
            <input type="text" id="c-name" name="name" required maxlength="100" value="<?= e((string) old('name')) ?>">
            <?= field_error('name', $errors) ?>
          </div>
          <div class="form-group<?= field_invalid('email', $errors) ?>">
            <label for="c-email">Email *</label>
            <input type="email" id="c-email" name="email" required maxlength="120" value="<?= e((string) old('email')) ?>">
            <?= field_error('email', $errors) ?>
          </div>
          <div class="form-group<?= field_invalid('phone', $errors) ?>">
            <label for="c-phone">Телефон</label>
            <input type="tel" id="c-phone" name="phone" maxlength="30" value="<?= e((string) old('phone')) ?>">
            <?= field_error('phone', $errors) ?>
          </div>
          <div class="form-group<?= field_invalid('subject', $errors) ?>">
            <label for="c-subject">Тема</label>
            <input type="text" id="c-subject" name="subject" maxlength="150" value="<?= e((string) old('subject')) ?>">
            <?= field_error('subject', $errors) ?>
          </div>
          <div class="form-group full<?= field_invalid('message', $errors) ?>">
            <label for="c-message">Сообщение *</label>
            <textarea id="c-message" name="message" required maxlength="2000"><?= e((string) old('message')) ?></textarea>
            <?= field_error('message', $errors) ?>
          </div>
          <div class="form-group full<?= field_invalid('privacy', $errors) ?>">
            <label class="checkbox-label">
              <input type="checkbox" name="privacy" value="1" <?= old('privacy') ? 'checked' : '' ?> required>
              <span>Согласен(на) с <a href="<?= e(base_url('privacy.php')) ?>">политикой конфиденциальности</a></span>
            </label>
            <?= field_error('privacy', $errors) ?>
          </div>
          <div class="form-group full">
            <button class="btn btn-primary btn-full" type="submit">Отправить сообщение</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
