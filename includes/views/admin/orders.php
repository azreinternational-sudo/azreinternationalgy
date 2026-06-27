<?php
/** Admin: orders list with status filter. */
auth_require_admin();
$page_title = 'Orders';

$status_filter = $_GET['status'] ?? '';
$valid_statuses = ['pending','paid','shipped','delivered','cancelled'];

$sql = 'SELECT o.*, u.name AS user_name, u.email AS user_email
        FROM orders o
        LEFT JOIN users u ON u.id = o.user_id';
$params = [];
if (in_array($status_filter, $valid_statuses, true)) {
    $sql .= ' WHERE o.status = ?';
    $params[] = $status_filter;
}
$sql .= ' ORDER BY o.created_at DESC LIMIT 200';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Counts per status for the tab nav
$counts = db()->query('SELECT status, COUNT(*) AS n FROM orders GROUP BY status')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<section class="container section">
  <header class="page-head">
    <div>
      <h1>Orders</h1>
      <p class="muted">All customer orders. Click an order to view details &amp; update status.</p>
    </div>
  </header>

  <nav class="order-tabs">
    <a class="order-tab <?= $status_filter === '' ? 'is-active' : '' ?>" href="<?= e(url('/admin/orders')) ?>">All <span class="badge"><?= (int)array_sum($counts) ?></span></a>
    <?php foreach ($valid_statuses as $s): ?>
      <a class="order-tab <?= $status_filter === $s ? 'is-active' : '' ?>" href="<?= e(url('/admin/orders?status=' . $s)) ?>">
        <?= e(ucfirst($s)) ?> <span class="badge"><?= (int)($counts[$s] ?? 0) ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <?php if (empty($orders)): ?>
    <div class="empty-state">
      <h3>No orders yet</h3>
      <p>When customers place orders, they'll appear here.</p>
    </div>
  <?php else: ?>
    <div class="card flush">
      <table class="table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Customer</th>
            <th>Phone</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Placed</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <?php
              $item_count = (int)db()->query('SELECT COALESCE(SUM(qty),0) FROM order_items WHERE order_id = ' . (int)$o['id'])->fetchColumn();
            ?>
            <tr>
              <td><strong>AZ-<?= str_pad((string)$o['id'], 5, '0', STR_PAD_LEFT) ?></strong></td>
              <td>
                <div><?= e($o['customer_name'] ?: ($o['user_name'] ?? '—')) ?></div>
                <div class="muted small"><?= e($o['customer_email'] ?: ($o['user_email'] ?? '')) ?></div>
              </td>
              <td><a href="tel:<?= e($o['customer_phone']) ?>"><?= e($o['customer_phone']) ?></a></td>
              <td><?= $item_count ?></td>
              <td><strong><?= e(money((float)$o['total'])) ?></strong></td>
              <td><span class="order-status order-status-<?= e($o['status']) ?>"><?= e(ucfirst((string)$o['status'])) ?></span></td>
              <td class="muted small"><?= e(date('Y-m-d H:i', strtotime((string)$o['created_at']))) ?></td>
              <td><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/order/' . (int)$o['id'])) ?>">View</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>
