<?php
/** Order confirmation page — accessible to guests (just-redirected) and customers.
 *  Order ID in URL is treated as a token: anyone with the link can see the page.
 *  For a small store this is fine; the page does not leak other customers' orders
 *  unless someone guesses or shares their own URL. */
$order_id = (int)($params[0] ?? 0);
$user     = auth_user();

$stmt = db()->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$order_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    flash_set('error', 'Order not found.');
    redirect('/');
}

// Logged-in customers can only see their own orders
if ($user && (int)$order['user_id'] !== (int)$user['id']) {
    flash_set('error', 'Order not found.');
    redirect('/account/orders');
}

$items = db()->prepare('SELECT * FROM order_items WHERE order_id = ?');
$items->execute([$order_id]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

$page_title = 'Order confirmed';

// Greeting name: use logged-in user's name if available, otherwise first word of customer_name
$first_name = $user
    ? explode(' ', (string)$user['name'])[0]
    : explode(' ', (string)$order['customer_name'])[0];
?>
<section class="container section">
  <div class="thanks-card">
    <div class="thanks-icon" aria-hidden="true">
      <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <circle cx="12" cy="12" r="10"></circle>
        <polyline points="9 12 11.5 14.5 16 9.5"></polyline>
      </svg>
    </div>
    <h1>Your order is submitted</h1>
    <p class="muted thanks-sub">
      Thanks <?= e($first_name) ?> — we've got it.
      <br>We will call you at <strong class="thanks-phone"><?= e($order['customer_phone']) ?></strong> to confirm your order, arrange delivery &amp; payment.
    </p>

    <div class="thanks-meta">
      <div class="thanks-meta-row">
        <span class="muted">Order number</span>
        <strong class="thanks-order-no">AZ-<?= str_pad((string)$order['id'], 5, '0', STR_PAD_LEFT) ?></strong>
      </div>
      <div class="thanks-meta-row">
        <span class="muted">Status</span>
        <strong><?= e(ucfirst((string)$order['status'])) ?></strong>
      </div>
      <div class="thanks-meta-row">
        <span class="muted">Submitted</span>
        <strong><?= e(date('Y-m-d H:i', strtotime((string)$order['created_at']))) ?></strong>
      </div>
      <div class="thanks-meta-row">
        <span class="muted">Deliver to</span>
        <strong><?= e($order['ship_address']) ?></strong>
      </div>
    </div>

    <h3 class="thanks-items-head">What you ordered</h3>
    <ul class="co-list thanks-list">
      <?php foreach ($items as $it): ?>
        <li class="co-item">
          <div class="co-info">
            <span class="co-name"><?= e($it['name']) ?></span>
            <span class="muted small">SKU: <?= e($it['sku']) ?> · Qty <?= (int)$it['qty'] ?> × <?= e(money((float)$it['price'])) ?></span>
          </div>
          <strong class="co-line"><?= e(money((float)$it['qty'] * (float)$it['price'])) ?></strong>
        </li>
      <?php endforeach; ?>
    </ul>

    <div class="thanks-totals">
      <div class="sum-row"><span>Subtotal</span><strong><?= e(money((float)$order['subtotal'])) ?></strong></div>
      <div class="sum-row"><span>Shipping</span><span class="muted">Confirmed by phone</span></div>
      <div class="sum-row sum-total"><span>Total</span><strong><?= e(money((float)$order['total'])) ?></strong></div>
    </div>

    <div class="thanks-actions">
      <?php if ($user): ?>
        <a class="btn btn-primary" href="<?= e(url('/account/orders')) ?>">View your orders</a>
      <?php else: ?>
        <a class="btn btn-primary" href="<?= e(url('/register')) ?>">Create an account</a>
      <?php endif; ?>
      <a class="btn btn-ghost" href="<?= e(url('/shop')) ?>">Continue shopping</a>
    </div>

    <p class="muted small thanks-foot">
      Questions? Call us at <?= e(AZRE_PHONE ?? '+592-XXX-XXXX') ?> or email
      <a href="mailto:<?= e(AZRE_EMAIL) ?>"><?= e(AZRE_EMAIL) ?></a>.
    </p>
  </div>
</section>
