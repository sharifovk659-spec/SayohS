<?php

declare(strict_types=1);

$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['HTTPS'] = 'off';
$_SERVER['REQUEST_URI'] = '/Restarant/';
$_SERVER['SCRIPT_NAME'] = '/Restarant/index.php';

require_once __DIR__ . '/../includes/init.php';

$ok = true;
function assert_true(bool $cond, string $msg): void
{
    global $ok;
    if ($cond) {
        echo "[OK] $msg\n";
    } else {
        echo "[FAIL] $msg\n";
        $ok = false;
    }
}

assert_true(db_available(), 'DB available');
assert_true(__('nav_home') !== 'nav_home', 'RU UI string loaded');
assert_true(lang_catalog('en')['nav_home'] !== '', 'EN catalog');
assert_true(lang_catalog('de')['nav_home'] !== '', 'DE catalog');

$dishes = fetch_popular_dishes(3);
assert_true(count($dishes) > 0, 'Popular dishes');
assert_true(isset($dishes[0]['name']) && $dishes[0]['name'] !== '', 'Dish name');

// Register test user flow (cleanup after)
$email = 'test_' . bin2hex(random_bytes(4)) . '@example.com';
$hash = password_hash('TestPass123!', PASSWORD_DEFAULT);
$stmt = db()->prepare(
    'INSERT INTO users (name, email, phone, password_hash, status) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute(['Test User', $email, '+79990001122', $hash, 'active']);
$userId = (int) db()->lastInsertId();
assert_true($userId > 0, 'User created');

$_SESSION['user_id'] = $userId;
assert_true(user_logged_in(), 'User logged in');

$dishId = (int) $dishes[0]['id'];
assert_true(favorite_add($dishId), 'Favorite add');
assert_true(is_favorite($dishId), 'Is favorite');
assert_true(cart_add($dishId, 2), 'Cart add');
$cart = cart_snapshot();
assert_true($cart['count'] === 2, 'Cart count 2');
assert_true($cart['subtotal'] > 0, 'Cart subtotal');

$order = create_order_from_cart([
    'name' => 'Test User',
    'phone' => '+79990001122',
    'email' => $email,
    'delivery_type' => 'pickup',
    'address' => '',
    'landmark' => '',
    'comment' => 'LOCAL TEST ORDER — delete',
    'payment_method' => 'cash',
    'idempotency_key' => 'test_' . bin2hex(random_bytes(8)),
]);
assert_true(!empty($order['ok']), 'Order created: ' . ($order['error'] ?? ''));
$orderNumber = $order['order']['order_number'] ?? '';
assert_true($orderNumber !== '', 'Order number ' . $orderNumber);

$cartAfter = cart_snapshot();
assert_true($cartAfter['count'] === 0, 'Cart cleared after order');

// Cleanup test data
if ($orderNumber !== '') {
    $oid = db()->prepare('SELECT id FROM orders WHERE order_number = ?');
    $oid->execute([$orderNumber]);
    $id = (int) $oid->fetchColumn();
    if ($id) {
        db()->prepare('DELETE FROM order_items WHERE order_id = ?')->execute([$id]);
        db()->prepare('DELETE FROM orders WHERE id = ?')->execute([$id]);
    }
}
db()->prepare('DELETE FROM favorites WHERE user_id = ?')->execute([$userId]);
db()->prepare('DELETE FROM cart_items WHERE cart_id IN (SELECT id FROM carts WHERE user_id = ?)')->execute([$userId]);
db()->prepare('DELETE FROM carts WHERE user_id = ?')->execute([$userId]);
db()->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);
echo "[OK] Cleanup done\n";

echo $ok ? "\nALL TESTS PASSED\n" : "\nSOME TESTS FAILED\n";
exit($ok ? 0 : 1);
