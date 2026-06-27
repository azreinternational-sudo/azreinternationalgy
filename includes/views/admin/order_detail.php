<?php
/** Admin: order detail with status update. */
auth_require_admin();
$order_id = (int)($params[0] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash_set('error', 'Invalid form submission.');
        redirect('/admin/order/' . $order_id);
    }
    $new_status = $_POST['status'] ?? '';
    $valid = ['pending','paid','shipped','delivered','cancelled'];
    if (in_array($new_status, $valid, true)) {
        $stmt = db()->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$new_status, $order_id]);
        flash_set('success', 'Order status updated to ' . ucfirst($new_status) . '.');
    }
    redirect('/admin/order/' . $order_id);
}

$stmt = db()->prepare('SELECT o.*, u.name AS user_name, u.email AS user_email, u.phone AS user_phone
                       FROM orders o LEFT JOIN users u ON u.id = o.user_id
                       WHERE o.id = ? LIMIT 1');
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('/admin/orders');
}

$items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$order_id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Order AZ-' . str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT);
?>
<section class="container section">
  <header class="page-head">
    <div>
      <h1>Order AZ-<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?></h1>
      <p class="muted">Placed <?= e(date('Y-m-d H:i', strtotime((string)$order['created_at']))) ?> · <span class="order-status order-status-<?= e($order['status']) ?>"><?= e(ucfirst((string)$order['status'])) ?></span></p>
    </div>
    <a class="btn btn-ghost" href="<?= e(url('/admin/orders')) ?>">← All orders</a>
  </header>

  <div class="admin-order-grid">
    <div class="card">
      <h3>Customer</h3>
      <table class="kv">
        <tr><td class="muted">Name</td><td><strong><?= e($order['customer_name'] ?: ($order['user_name'] ?? '—')) ?></strong></td></tr>
        <tr><td class="muted">Phone</td><td><a href="tel:<?= e($order['customer_phone']) ?>"><?= e($order['customer_phone']) ?></a></td></tr>
        <tr><td class="muted">Email</td><td><a href="mailto:<?= e($order['customer_email'] ?: ($order['user_email'] ?? '')) ?>"><?= e($order['customer_email'] ?: ($order['user_email'] ?? '—')) ?></a></td></tr>
        <tr><td class="muted">Address</td><td><?= e($order['ship_address']) ?></td></tr>
        <?php if ($order['notes']): ?>
          <tr><td class="muted">Notes</td><td><?= nl2br(e($order['notes'])) ?></td></tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="card">
      <h3>Update status</h3>
      <form method="post" action="<?= e(url('/admin/order/' . (int)$order['id'])) ?>" class="admin-form">
        <?= csrf_field() ?>
        <label>Status
          <select name="status">
            <?php foreach (['pending','paid','shipped','delivered','cancelled'] as $s): ?>
              <option value="<?= e($s) ?>" <?= $order['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit" class="btn btn-primary">Save status</button>
      </form>
    </div>
  </div>

  <h3 style="margin-top:1.5rem">Items</h3>
  <div class="card flush">
    <table class="table">
      <thead>
        <tr>
          <th>Product</th>
          <th>SKU</th>
          <th>Qty</th>
          <th>Unit price</th>
          <th>Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <tr>
            <td><?= e($it['name']) ?></td>
            <td class="muted small"><?= e($it['sku']) ?></td>
            <td><?= (int)$it['qty'] ?></td>
            <td><?= e(money((float)$it['price'])) ?></td>
            <td><strong><?= e(money((float)$it['qty'] * (float)$it['price'])) ?></strong></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="4" class="right muted">Subtotal</td>
          <td><strong><?= e(money((float)$order['subtotal'])) ?></strong></td>
        </tr>
        <tr>
          <td colspan="4" class="right muted">Shipping</td>
          <td><?= e(money((float)$order['shipping'])) ?></td>
        </tr>
        <tr>
          <td colspan="4" class="right"><strong>Total</strong></td>
          <td><strong style="font-size:1.1rem"><?= e(money((float)$order['total'])) ?></strong></td>
        </tr>
      </tfoot>
    </table>
  </div>
</section>
