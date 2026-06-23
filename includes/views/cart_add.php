<?php
if (!csrf_check()) { http_response_code(400); exit('Invalid CSRF token'); }
$pid = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['qty'] ?? 1));
$stmt = db()->prepare('SELECT id, stock FROM products WHERE id = ? AND active = 1');
$stmt->execute([$pid]);
$p = $stmt->fetch();
if (!$p) {
    flash_set('error', 'Product not found.');
    redirect($_SERVER['HTTP_REFERER'] ?? '/shop');
}
$qty = min($qty, max(1, (int)$p['stock']));
cart_add($pid, $qty);
flash_set('success', 'Added to cart.');
$back = $_POST['back'] ?? ($_SERVER['HTTP_REFERER'] ?? url('/cart'));
if (str_starts_with($back, '/')) {
    header('Location: ' . $back);
} else {
    redirect('/cart');
}
exit;
