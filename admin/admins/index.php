<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin_role('admin');

$adminPageTitle = 'Администраторы';
$adminActive = 'admins';

$currentAdminId = (int) ($_SESSION['admin']['id'] ?? 0);
$errors = form_errors();

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'toggle') {
        $targetId = (int) ($_POST['id'] ?? 0);
        if ($targetId <= 0) {
            flash('error', 'Некорректный администратор.');
            redirect('admin/admins/index.php');
        }
        if ($targetId === $currentAdminId) {
            flash('error', 'Нельзя изменить статус своей учётной записи.');
            redirect('admin/admins/index.php');
        }

        try {
            $stmt = db()->prepare('SELECT status FROM admins WHERE id = ? LIMIT 1');
            $stmt->execute([$targetId]);
            $row = $stmt->fetch();
            if (!$row) {
                flash('error', 'Администратор не найден.');
            } else {
                $newStatus = (int) $row['status'] === 1 ? 0 : 1;
                $upd = db()->prepare('UPDATE admins SET status = ?, updated_at = NOW() WHERE id = ?');
                $upd->execute([$newStatus, $targetId]);
                flash('success', $newStatus === 1 ? 'Администратор активирован.' : 'Администратор деактивирован.');
            }
        } catch (Throwable) {
            flash('error', 'Не удалось изменить статус.');
        }

        redirect('admin/admins/index.php');
    }

    $name = sanitize_plain($_POST['name'] ?? '');
    $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $role = (string) ($_POST['role'] ?? 'manager');

    $errs = [];
    if ($name === '') {
        $errs['name'] = 'Укажите имя.';
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errs['email'] = 'Укажите корректный email.';
    }
    if (strlen($password) < 8) {
        $errs['password'] = 'Пароль не менее 8 символов.';
    }
    if (!in_array($role, ['admin', 'manager'], true)) {
        $errs['role'] = 'Некорректная роль.';
    }

    if ($errs === []) {
        try {
            $dup = db()->prepare('SELECT id FROM admins WHERE email = ? LIMIT 1');
            $dup->execute([$email]);
            if ($dup->fetch()) {
                $errs['email'] = 'Email уже используется.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = db()->prepare(
                    'INSERT INTO admins (name, email, password_hash, role, status) VALUES (?, ?, ?, ?, 1)'
                );
                $ins->execute([$name, $email, $hash, $role]);
                flash('success', 'Администратор создан.');
                redirect('admin/admins/index.php');
            }
        } catch (Throwable $e) {
            $errs['form'] = 'Не удалось создать: ' . $e->getMessage();
        }
    }

    set_form_state($errs, [
        'name' => $name,
        'email' => $email,
        'role' => $role,
    ]);
    redirect('admin/admins/index.php');
}

$admins = db()->query(
    'SELECT id, name, email, role, status, last_login_at, created_at
     FROM admins ORDER BY id ASC'
)->fetchAll();

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-grid-2">
  <section class="admin-panel">
    <h2 class="admin-panel-title">Список</h2>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Имя</th>
            <th>Email</th>
            <th>Роль</th>
            <th>Статус</th>
            <th>Последний вход</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $admin): ?>
            <tr>
              <td><?= (int) $admin['id'] ?></td>
              <td><?= e((string) $admin['name']) ?><?= (int) $admin['id'] === $currentAdminId ? ' (вы)' : '' ?></td>
              <td><?= e((string) $admin['email']) ?></td>
              <td><?= e(admin_role_label((string) $admin['role'])) ?></td>
              <td>
                <?php if ((int) $admin['status'] === 1): ?>
                  <span class="badge badge-confirmed">Активен</span>
                <?php else: ?>
                  <span class="badge badge-cancelled">Неактивен</span>
                <?php endif; ?>
              </td>
              <td><?= e((string) ($admin['last_login_at'] ?: '—')) ?></td>
              <td class="actions">
                <?php if ((int) $admin['id'] !== $currentAdminId): ?>
                  <form method="post" class="inline-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= (int) $admin['id'] ?>">
                    <button class="btn btn-sm btn-light" type="submit">
                      <?= (int) $admin['status'] === 1 ? 'Деактивировать' : 'Активировать' ?>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="admin-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </section>

  <section class="admin-panel">
    <h2 class="admin-panel-title">Создать администратора</h2>
    <?php if (!empty($errors['form'])): ?>
      <div class="admin-flash admin-flash-error"><?= e($errors['form']) ?></div>
    <?php endif; ?>
    <form method="post" class="form-grid" style="grid-template-columns:1fr">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="create">
      <div class="form-group">
        <label for="name">Имя</label>
        <input class="<?= e(field_invalid('name', $errors)) ?>" type="text" id="name" name="name" required
               value="<?= e((string) old('name', '')) ?>">
        <?= field_error('name', $errors) ?>
      </div>
      <div class="form-group">
        <label for="email">Email</label>
        <input class="<?= e(field_invalid('email', $errors)) ?>" type="email" id="email" name="email" required
               value="<?= e((string) old('email', '')) ?>">
        <?= field_error('email', $errors) ?>
      </div>
      <div class="form-group">
        <label for="password">Пароль</label>
        <input class="<?= e(field_invalid('password', $errors)) ?>" type="password" id="password" name="password" required minlength="8">
        <?= field_error('password', $errors) ?>
      </div>
      <div class="form-group">
        <label for="role">Роль</label>
        <select id="role" name="role">
          <option value="manager" <?= old('role', 'manager') === 'manager' ? 'selected' : '' ?>><?= e(admin_role_label('manager')) ?></option>
          <option value="admin" <?= old('role', '') === 'admin' ? 'selected' : '' ?>><?= e(admin_role_label('admin')) ?></option>
        </select>
        <?= field_error('role', $errors) ?>
      </div>
      <div class="actions">
        <button class="btn" type="submit">Создать</button>
      </div>
    </form>
  </section>
</div>

<?php
clear_old_input();
require __DIR__ . '/../includes/admin-footer.php';
?>
