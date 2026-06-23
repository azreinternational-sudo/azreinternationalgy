<?php
if (!csrf_check()) { http_response_code(400); exit('Invalid CSRF token'); }
$pid = (int)($_POST['product_id'] ?? 0);
cart_remove($pid);
flash_set('success', 'Item removed.');
redirect('/cart');
