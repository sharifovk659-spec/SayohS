<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Часы работы';
$adminActive = 'opening-hours';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();

    $days = $_POST['days'] ?? [];
    if (!is_array($days)) {
        flash('error', 'Некорректные данные.');
        redirect('admin/opening-hours/index.php');
    }

    try {
        $pdo = db();
        $pdo->beginTransaction();
        $upd = $pdo->prepare(
            'UPDATE opening_hours SET time_from = ?, time_to = ?, is_closed = ?, sort_order = ? WHERE id = ?'
        );

        foreach ($days as $id => $data) {
            $id = (int) $id;
            if ($id <= 0 || !is_array($data)) {
                continue;
            }
            $isClosed = !empty($data['is_closed']) ? 1 : 0;
            $from = trim((string) ($data['time_from'] ?? ''));
            $to = trim((string) ($data['time_to'] ?? ''));
            $sort = (int) ($data['sort_order'] ?? 0);

            $fromSql = $isClosed || $from === '' ? null : ($from . (strlen($from) === 5 ? ':00' : ''));
            $toSql = $isClosed || $to === '' ? null : ($to . (strlen($to) === 5 ? ':00' : ''));

            $upd->execute([$fromSql, $toSql, $isClosed, $sort, $id]);
        }

        $pdo->commit();
        flash('success', 'Часы работы сохранены.');
    } catch (Throwable $e) {
        if (db()->inTransaction()) {
            db()->rollBack();
        }
        flash('error', 'Не удалось сохранить: ' . $e->getMessage());
    }

    redirect('admin/opening-hours/index.php');
}

$rows = fetch_opening_hours();

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <?php if (!$rows): ?>
    <p class="admin-empty">Часы работы не найдены. Загрузите seed-данные.</p>
  <?php else: ?>
    <form method="post" action="">
      <?= csrf_field() ?>
      <div class="table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>День</th>
              <th>С</th>
              <th>До</th>
              <th>Выходной</th>
              <th>Порядок</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $row): ?>
              <?php
                $from = $row['time_from'] ? substr((string) $row['time_from'], 0, 5) : '';
                $to = $row['time_to'] ? substr((string) $row['time_to'], 0, 5) : '';
              ?>
              <tr>
                <td data-label="День"><?= e((string) $row['day_name']) ?></td>
                <td data-label="С">
                  <input type="time" name="days[<?= (int) $row['id'] ?>][time_from]" value="<?= e($from) ?>">
                </td>
                <td data-label="До">
                  <input type="time" name="days[<?= (int) $row['id'] ?>][time_to]" value="<?= e($to) ?>">
                </td>
                <td data-label="Выходной">
                  <label><input type="checkbox" name="days[<?= (int) $row['id'] ?>][is_closed]" value="1" <?= (int) $row['is_closed'] ? 'checked' : '' ?>></label>
                </td>
                <td data-label="Порядок">
                  <input class="sort-input" type="number" name="days[<?= (int) $row['id'] ?>][sort_order]" value="<?= (int) $row['sort_order'] ?>">
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="actions" style="margin-top:1rem">
        <button class="btn" type="submit">Сохранить</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
