<?php
if (!csrf_check()) { http_response_code(400); exit('Invalid CSRF token'); }
$id = isset($params[0]) ? (int)$params[0] : 0;
if ($id) {
    $stmt = db()->prepare('SELECT image FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p && $p['image'] && file_exists(AZRE_UPLOAD_DIR . '/' . basename($p['image']))) {
        @unlink(AZRE_UPLOAD_DIR . '/' . basename($p['image']));
    }
    db()->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
    flash_set('success', 'Product deleted.');
}
redirect('/admin/products');
