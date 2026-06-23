<?php
if (!csrf_check()) { http_response_code(400); exit('Invalid CSRF token'); }
$pid = (int)($_POST['product_id'] ?? 0);
$qty = max(0, (int)($_POST['qty'] ?? 0));
cart_update($pid, $qty);
flash_set('success', 'Cart updated.');
redirect('/cart');
