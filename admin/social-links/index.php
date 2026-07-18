<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-bootstrap.php';
require_admin();

$adminPageTitle = 'Соцсети';
$adminActive = 'social-links';

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    require_csrf();
    $action = (string) ($_POST['action'] ?? '');

    try {
        if ($action === 'create') {
            $platform = sanitize_plain($_POST['platform'] ?? '');
            $url = sanitize_plain($_POST['url'] ?? '');
            $icon = sanitize_plain($_POST['icon'] ?? '');
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($platform === '' || $url === '') {
                throw new RuntimeException('Укажите платформу и URL.');
            }
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Некорректный URL.');
            }

            $stmt = db()->prepare(
                'INSERT INTO social_links (platform, url, icon, is_active, sort_order) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$platform, $url, $icon !== '' ? $icon : null, $isActive, $sortOrder]);
            flash('success', 'Ссылка добавлена.');
        } elseif ($action === 'update') {
            $id = (int) ($_POST['id'] ?? 0);
            $platform = sanitize_plain($_POST['platform'] ?? '');
            $url = sanitize_plain($_POST['url'] ?? '');
            $icon = sanitize_plain($_POST['icon'] ?? '');
            $sortOrder = (int) ($_POST['sort_order'] ?? 0);
            $isActive = isset($_POST['is_active']) ? 1 : 0;

            if ($id <= 0 || $platform === '' || $url === '') {
                throw new RuntimeException('Заполните обязательные поля.');
            }
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                throw new RuntimeException('Некорректный URL.');
            }

            $stmt = db()->prepare(
                'UPDATE social_links SET platform = ?, url = ?, icon = ?, is_active = ?, sort_order = ? WHERE id = ?'
            );
            $stmt->execute([$platform, $url, $icon !== '' ? $icon : null, $isActive, $sortOrder, $id]);
            flash('success', 'Ссылка обновлена.');
        } elseif ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = db()->prepare('DELETE FROM social_links WHERE id = ?');
            $stmt->execute([$id]);
            flash('success', 'Ссылка удалена.');
        } else {
            throw new RuntimeException('Неизвестное действие.');
        }
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }

    redirect('admin/social-links/index.php');
}

$rows = fetch_social_links();

require __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-panel">
  <h2 class="admin-panel-title">Добавить ссылку</h2>
  <form method="post" class="form-grid" style="margin-bottom:1.25rem">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="create">
    <div class="form-group">
      <label for="platform">Платформа</label>
      <input type="text" id="platform" name="platform" required>
    </div>
    <div class="form-group">
      <label for="url">URL</label>
      <input type="url" id="url" name="url" required>
    </div>
    <div class="form-group">
      <label for="icon">Иконка</label>
      <input type="text" id="icon" name="icon" placeholder="instagram">
    </div>
    <div class="form-group">
      <label for="sort_order">Порядок</label>
      <input type="number" id="sort_order" name="sort_order" value="0">
    </div>
    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" checked> Активна</label>
    </div>
    <div class="form-group">
      <button class="btn" type="submit">Добавить</button>
    </div>
  </form>

  <?php if (!$rows): ?>
    <p class="admin-empty">Ссылок пока нет.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Платформа</th>
            <th>URL</th>
            <th>Иконка</th>
            <th>Порядок</th>
            <th>Статус</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows as $row): ?>
            <tr>
              <td colspan="6" style="padding:0.85rem 0">
                <form method="post" class="form-grid">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="id" value="<?= (int) $row['id'] ?>">
                  <div class="form-group">
                    <label>Платформа</label>
                    <input type="text" name="platform" required value="<?= e((string) $row['platform']) ?>">
                  </div>
                  <div class="form-group">
                    <label>URL</label>
                    <input type="url" name="url" required value="<?= e((string) $row['url']) ?>">
                  </div>
                  <div class="form-group">
                    <label>Иконка</label>
                    <input type="text" name="icon" value="<?= e((string) ($row['icon'] ?? '')) ?>">
                  </div>
                  <div class="form-group">
                    <label>Порядок</label>
                    <input type="number" name="sort_order" value="<?= (int) $row['sort_order'] ?>">
                  </div>
                  <div class="form-group">
                    <label><input type="checkbox" name="is_active" value="1" <?= (int) $row['is_active'] ? 'checked' : '' ?>> Активна</label>
                  </div>
                  <div class="form-group actions">
                    <button class="btn btn-sm" type="submit">Сохранить</button>
                    <button class="btn btn-sm btn-danger" type="submit" name="action" value="delete" onclick="return confirm('Удалить ссылку?');">Удалить</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/admin-footer.php'; ?>
