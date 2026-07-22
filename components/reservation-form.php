<?php

declare(strict_types=1);

/** @var array $app */
$errors = $formErrors ?? form_errors();
$minDate = date('Y-m-d');
$maxDate = date('Y-m-d', strtotime('+60 days'));
$formId = $formId ?? 'reservation-form';
$compactHome = ($formId === 'home-reservation');
?>
<section class="section reservation-block<?= $compactHome ? ' reservation-block--home' : '' ?>" id="booking">
  <div class="container">
    <div class="reservation-panel" data-reveal>
      <?php if (!$compactHome): ?>
      <div class="reservation-intro">
        <p class="eyebrow"><?= e(__('reservation_eyebrow')) ?></p>
        <h2 class="section-title"><?= e(__('reservation_title')) ?></h2>
        <p class="section-text"><?= e(__('reservation_text')) ?></p>
      </div>
      <?php endif; ?>

      <form
        class="reservation-form<?= $compactHome ? ' reservation-form--compact' : '' ?>"
        id="<?= e($formId) ?>"
        action="<?= e(base_url('actions/create-reservation.php')) ?>"
        method="post"
        novalidate
      >
        <?= csrf_field() ?>
        <input type="hidden" name="redirect_to" value="<?= e((string) ($redirectTo ?? 'reservation.php')) ?>">
        <div class="hp-field" aria-hidden="true" style="position:absolute;left:-9999px;height:0;overflow:hidden;">
          <label for="<?= e($formId) ?>-website">Website</label>
          <input type="text" id="<?= e($formId) ?>-website" name="website" value="" tabindex="-1" autocomplete="off">
        </div>

        <div class="form-grid">
          <div class="form-group<?= field_invalid('name', $errors) ?>">
            <label for="<?= e($formId) ?>-name"><?= e(__('res_name')) ?> *</label>
            <input type="text" id="<?= e($formId) ?>-name" name="name" required maxlength="100" autocomplete="name" value="<?= e((string) old('name')) ?>">
            <?= field_error('name', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('phone', $errors) ?>">
            <label for="<?= e($formId) ?>-phone"><?= e(__('res_phone')) ?> *</label>
            <input type="tel" id="<?= e($formId) ?>-phone" name="phone" required maxlength="30" autocomplete="tel" inputmode="tel" placeholder="+992 __ ___ ____" value="<?= e((string) old('phone')) ?>">
            <?= field_error('phone', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('guests', $errors) ?>">
            <label for="<?= e($formId) ?>-guests"><?= e(__('res_guests')) ?> *</label>
            <select id="<?= e($formId) ?>-guests" name="guests" required>
              <?php for ($i = 1; $i <= 20; $i++): ?>
                <option value="<?= $i ?>" <?= (string) old('guests', '2') === (string) $i ? 'selected' : '' ?>><?= $i ?></option>
              <?php endfor; ?>
            </select>
            <?= field_error('guests', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('reserve_date', $errors) ?>">
            <label for="<?= e($formId) ?>-date"><?= e(__('res_date')) ?> *</label>
            <input type="date" id="<?= e($formId) ?>-date" name="reserve_date" required min="<?= e($minDate) ?>" max="<?= e($maxDate) ?>" value="<?= e((string) old('reserve_date', $minDate)) ?>">
            <?= field_error('reserve_date', $errors) ?>
          </div>

          <div class="form-group<?= field_invalid('reserve_time', $errors) ?>">
            <label for="<?= e($formId) ?>-time"><?= e(__('res_time')) ?> *</label>
            <select id="<?= e($formId) ?>-time" name="reserve_time" required>
              <?php
              $times = ['12:00','12:30','13:00','13:30','14:00','15:00','16:00','17:00','18:00','18:30','19:00','19:30','20:00','20:30','21:00','21:30'];
              $selectedTime = (string) old('reserve_time', '19:00');
              foreach ($times as $time):
              ?>
                <option value="<?= e($time) ?>" <?= $selectedTime === $time ? 'selected' : '' ?>><?= e($time) ?></option>
              <?php endforeach; ?>
            </select>
            <?= field_error('reserve_time', $errors) ?>
          </div>

          <?php if (!$compactHome): ?>
          <div class="form-group full<?= field_invalid('message', $errors) ?>">
            <label for="<?= e($formId) ?>-message"><?= e(__('res_message')) ?></label>
            <textarea id="<?= e($formId) ?>-message" name="message" maxlength="1000" rows="3" placeholder="Пожелания к столу (необязательно)"><?= e((string) old('message')) ?></textarea>
            <?= field_error('message', $errors) ?>
          </div>
          <?php endif; ?>

          <div class="form-group full<?= field_invalid('privacy', $errors) ?><?= $compactHome ? ' form-privacy-compact' : '' ?>">
            <label class="checkbox-label">
              <input type="checkbox" name="privacy" value="1" <?= old('privacy') ? 'checked' : '' ?> required>
              <span><?= e(__('privacy_agree')) ?> * — <a href="<?= e(base_url('privacy.php')) ?>" target="_blank" rel="noopener"><?= e(__('footer_privacy')) ?></a></span>
            </label>
            <?= field_error('privacy', $errors) ?>
          </div>

          <div class="form-group full form-actions">
            <button class="btn btn-primary<?= $compactHome ? '' : ' btn-full' ?>" type="submit"><?= e(__('res_submit')) ?></button>
          </div>
        </div>
      </form>
    </div>
  </div>
</section>
