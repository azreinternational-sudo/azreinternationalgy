<?php
/** Customer account: order history. */
auth_require_login();
$page_title = 'My orders';
$user = auth_user();

$orders = db()->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 100');
$orders->execute([(int)$user['id']]);
$orders = $orders->fetchAll(PDO::FETCH_ASSOC);

// Pre-fetch item counts in one go
$counts = [];
if ($orders) {
    $ids = array_column($orders, 'id');
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT order_id, COALESCE(SUM(qty),0) AS n FROM order_items WHERE order_id IN ($place) GROUP BY order_id");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $counts[(int)$r['order_id']] = (int)$r['n'];
    }
}
?>
<section class="container section">
  <header class="page-head">
    <h1>My orders</h1>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('/account')) ?>">← Account</a>
  </header>

  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <h3>No orders yet</h3>
      <p>Browse the <a href="<?= e(url('/shop')) ?>">catalog</a> and place your first order.</p>
    </div>
  <?php else: ?>
    <div class="card flush">
      <table class="table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Placed</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td><strong>AZ-<?= str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
              <td class="muted small"><?= e(date('Y-m-d H:i', strtotime((string)$o['created_at']))) ?></td>
              <td><?= (int)($counts[(int)$o['id']] ?? 0) ?></td>
              <td><strong><?= e(money((float)$o['total'])) ?></strong></td>
              <td><span class="order-status order-status-<?= e($o['status']) ?>"><?= e(ucfirst((string)$o['status'])) ?></span></td>
              <td><a class="btn btn-ghost btn-sm" href="<?= e(url('/account/order/' . (int)$o['id'])) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
