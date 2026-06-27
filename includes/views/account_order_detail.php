<?php
/** Customer account: single order detail. */
auth_require_login();
$order_id = (int)($params[0] ?? 0);
$user = auth_user();

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order || (int)$order['user_id'] !== (int)$user['id']) {
    flash_set('error', 'Order not found.');
    redirect('/account/orders');
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
    <a class="btn btn-ghost" href="<?= e(url('/account/orders')) ?>">← All orders</a>
  </header>

  <p class="muted">
    We'll call you at <strong><?= e($order['customer_phone']) ?></strong> to confirm delivery &amp; payment.
    Questions? <a href="mailto:<?= e(AZRE_EMAIL) ?>"><?= e(AZRE_EMAIL) ?></a> or <a href="<?= e(url('/contact')) ?>">contact us</a>.
  </p>

  <div class="card flush" style="margin-top:1rem">
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
          <td colspan="4" class="right"><strong>Total</strong></td>
          <td><strong style="font-size:1.1rem"><?= e(money((float)$order['total'])) ?></strong></td>
        </tr>
      </tfoot>
    </table>
  </div>

  <?php if ($order['notes']): ?>
    <p class="muted small" style="margin-top:1rem"><strong>Your notes:</strong> <?= nl2br(e($order['notes'])) ?></p>
  <?php endif; ?>
</section>
