<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/init.php';

$user = require_user();
$userId = (int) $user['id'];
$accountSection = 'addresses';

$editId = (int) ($_GET['edit'] ?? 0);
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        flash('error', __('error_csrf'));
        redirect('account/addresses.php');
    }

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'delete') {
        $addressId = (int) ($_POST['address_id'] ?? 0);
        if ($addressId > 0) {
            try {
                db()->prepare('DELETE FROM user_addresses WHERE id = ? AND user_id = ?')->execute([$addressId, $userId]);
                flash('success', __('success_saved'));
            } catch (Throwable $e) {
                storage_log('account/addresses delete: ' . $e->getMessage());
                flash('error', __('error_generic'));
            }
        }
        redirect('account/addresses.php');
    }

    $addressId = (int) ($_POST['address_id'] ?? 0);
    $title = sanitize_plain($_POST['title'] ?? '');
    $address = sanitize_plain($_POST['address'] ?? '');
    $landmark = sanitize_plain($_POST['landmark'] ?? '');
    $isDefault = !empty($_POST['is_default']);

    $errors = [];
    if ($address === '') {
        $errors['address'] = __('error_required');
    } elseif (mb_strlen($address) > 255) {
        $errors['address'] = __('error_required');
    }
    if (mb_strlen($title) > 80) {
        $errors['title'] = __('error_required');
    }
    if (mb_strlen($landmark) > 255) {
        $errors['landmark'] = __('error_required');
    }

    if ($errors !== []) {
        set_form_state($errors, [
            'title' => $title,
            'address' => $address,
            'landmark' => $landmark,
            'is_default' => $isDefault ? '1' : '',
        ]);
        flash('error', __('error_required'));
        redirect($addressId > 0 ? 'account/addresses.php?edit=' . $addressId : 'account/addresses.php');
    }

    try {
        if ($addressId > 0) {
            $own = db()->prepare('SELECT id FROM user_addresses WHERE id = ? AND user_id = ? LIMIT 1');
            $own->execute([$addressId, $userId]);
            if (!$own->fetch()) {
                flash('error', __('error_generic'));
                redirect('account/addresses.php');
            }

            db()->prepare(
                'UPDATE user_addresses SET title = ?, address = ?, landmark = ?, is_default = ?, updated_at = NOW()
                 WHERE id = ? AND user_id = ?'
            )->execute([
                $title !== '' ? $title : null,
                $address,
                $landmark !== '' ? $landmark : null,
                $isDefault ? 1 : 0,
                $addressId,
                $userId,
            ]);
        } else {
            db()->prepare(
                'INSERT INTO user_addresses (user_id, title, address, landmark, is_default)
                 VALUES (?, ?, ?, ?, ?)'
            )->execute([
                $userId,
                $title !== '' ? $title : null,
                $address,
                $landmark !== '' ? $landmark : null,
                $isDefault ? 1 : 0,
            ]);
            $addressId = (int) db()->lastInsertId();
        }

        if ($isDefault && $addressId > 0) {
            db()->prepare(
                'UPDATE user_addresses SET is_default = 0 WHERE user_id = ? AND id != ?'
            )->execute([$userId, $addressId]);
            db()->prepare(
                'UPDATE user_addresses SET is_default = 1 WHERE id = ? AND user_id = ?'
            )->execute([$addressId, $userId]);
        }

        clear_old_input();
        flash('success', __('success_saved'));
    } catch (Throwable $e) {
        storage_log('account/addresses save: ' . $e->getMessage());
        flash('error', __('error_generic'));
    }

    redirect('account/addresses.php');
}

$addresses = [];
try {
    $stmt = db()->prepare(
        'SELECT id, title, address, landmark, is_default, created_at
         FROM user_addresses
         WHERE user_id = ?
         ORDER BY is_default DESC, id ASC'
    );
    $stmt->execute([$userId]);
    $addresses = $stmt->fetchAll();
} catch (Throwable $e) {
    storage_log('account/addresses list: ' . $e->getMessage());
}

if ($editId > 0) {
    foreach ($addresses as $row) {
        if ((int) $row['id'] === $editId) {
            $editing = $row;
            break;
        }
    }
    if ($editing === null) {
        flash('error', __('error_generic'));
        redirect('account/addresses.php');
    }
}

$pageTitle = __('account_addresses') . ' — ' . ($app['full_name'] ?? $app['name']);
$pageDescription = __('account_addresses');
$bodyClass = 'page-account';
$errors = form_errors();

$formTitle = (string) old('title', $editing['title'] ?? '');
$formAddress = (string) old('address', $editing['address'] ?? '');
$formLandmark = (string) old('landmark', $editing['landmark'] ?? '');
$formDefault = old('is_default', !empty($editing['is_default']) ? '1' : '') ? true : false;

require __DIR__ . '/../includes/header.php';
?>

<div class="container account-layout">
  <?php require __DIR__ . '/_nav.php'; ?>

  <div class="account-main">
    <div class="account-card" data-reveal>
      <h1><?= e(__('account_addresses')) ?></h1>

      <?php if ($addresses === []): ?>
        <p class="account-muted"><?= e(__('checkout_address')) ?></p>
      <?php else: ?>
        <div class="account-addresses-list">
          <?php foreach ($addresses as $addr): ?>
            <article class="account-address-item<?= (int) ($addr['is_default'] ?? 0) === 1 ? ' is-default' : '' ?>">
              <div class="account-address-body">
                <?php if (!empty($addr['title'])): ?>
                  <h3><?= e((string) $addr['title']) ?></h3>
                <?php endif; ?>
                <p><?= e((string) $addr['address']) ?></p>
                <?php if (!empty($addr['landmark'])): ?>
                  <p class="account-muted"><?= e((string) $addr['landmark']) ?></p>
                <?php endif; ?>
                <?php if ((int) ($addr['is_default'] ?? 0) === 1): ?>
                  <span class="account-badge"><?= e(__('yes')) ?></span>
                <?php endif; ?>
              </div>
              <div class="account-address-actions">
                <a class="btn btn-outline btn-sm" href="<?= e(base_url('account/addresses.php?edit=' . (int) $addr['id'])) ?>"><?= e(__('btn_update')) ?></a>
                <form method="post" action="<?= e(base_url('account/addresses.php')) ?>" onsubmit="return confirm('<?= e(__('btn_remove')) ?>?');">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="address_id" value="<?= (int) $addr['id'] ?>">
                  <button class="btn btn-outline btn-sm" type="submit"><?= e(__('btn_remove')) ?></button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <div class="account-card" data-reveal>
      <h2><?= e($editing ? __('btn_update') : __('btn_save')) ?></h2>

      <form class="form-panel account-form" method="post" action="<?= e(base_url('account/addresses.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <?php if ($editing): ?>
          <input type="hidden" name="address_id" value="<?= (int) $editing['id'] ?>">
        <?php endif; ?>
        <div class="form-grid">
          <div class="form-group<?= field_invalid('title', $errors) ?>">
            <label for="addr-title"><?= e(__('optional')) ?></label>
            <input type="text" id="addr-title" name="title" maxlength="80" value="<?= e($formTitle) ?>" placeholder="<?= e(__('checkout_address')) ?>">
            <?= field_error('title', $errors) ?>
          </div>
          <div class="form-group full<?= field_invalid('address', $errors) ?>">
            <label for="addr-address"><?= e(__('checkout_address')) ?> *</label>
            <input type="text" id="addr-address" name="address" required maxlength="255" value="<?= e($formAddress) ?>">
            <?= field_error('address', $errors) ?>
          </div>
          <div class="form-group full<?= field_invalid('landmark', $errors) ?>">
            <label for="addr-landmark"><?= e(__('checkout_landmark')) ?></label>
            <input type="text" id="addr-landmark" name="landmark" maxlength="255" value="<?= e($formLandmark) ?>">
            <?= field_error('landmark', $errors) ?>
          </div>
          <div class="form-group full">
            <label class="checkbox-label">
              <input type="checkbox" name="is_default" value="1" <?= $formDefault ? 'checked' : '' ?>>
              <span><?= e(__('checkout_address')) ?> — <?= e(__('yes')) ?></span>
            </label>
          </div>
          <div class="form-group full form-actions">
            <button class="btn btn-primary" type="submit"><?= e(__('btn_save')) ?></button>
            <?php if ($editing): ?>
              <a class="btn btn-outline" href="<?= e(base_url('account/addresses.php')) ?>"><?= e(__('btn_cancel')) ?></a>
            <?php endif; ?>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
