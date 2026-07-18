<?php

declare(strict_types=1);

/** @var string $accountSection */
$accountSection = $accountSection ?? 'index';

$navItems = [
    'index' => ['href' => base_url('account/'), 'label' => __('account_title')],
    'profile' => ['href' => base_url('account/profile.php'), 'label' => __('account_profile')],
    'orders' => ['href' => base_url('account/orders.php'), 'label' => __('account_orders')],
    'favorites' => ['href' => base_url('account/favorites.php'), 'label' => __('account_favorites')],
    'addresses' => ['href' => base_url('account/addresses.php'), 'label' => __('account_addresses')],
    'security' => ['href' => base_url('account/security.php'), 'label' => __('account_security')],
];
?>
<aside class="account-nav" aria-label="<?= e(__('account_title')) ?>">
  <nav class="account-nav-inner">
    <ul class="account-nav-list">
      <?php foreach ($navItems as $key => $item): ?>
        <li>
          <a
            class="account-nav-link<?= $accountSection === $key ? ' is-active' : '' ?>"
            href="<?= e($item['href']) ?>"
            <?= $accountSection === $key ? 'aria-current="page"' : '' ?>
          ><?= e($item['label']) ?></a>
        </li>
      <?php endforeach; ?>
    </ul>
    <form
      class="account-nav-logout"
      method="post"
      action="<?= e(base_url('account/logout.php')) ?>"
      onsubmit="return confirm('<?= e(__('logout_confirm')) ?>');"
    >
      <?= csrf_field() ?>
      <button type="submit" class="account-nav-link account-nav-logout-btn"><?= e(__('nav_logout')) ?></button>
    </form>
  </nav>
</aside>
