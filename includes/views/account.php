<?php
auth_require_login();
$user = auth_user();
$page_title = 'My account';
$orders = db()->prepare('SELECT id, status, total, created_at FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 20');
$orders->execute([$user['id']]);
$orders = $orders->fetchAll();
?>
<section class="container section">
  <header class="page-head">
    <h1>Hi, <?= e($user['name']) ?></h1>
    <p class="muted">Manage your account and view orders.</p>
  </header>

  <div class="account-grid">
    <aside class="account-side">
      <div class="card">
        <h3>Profile</h3>
        <p><strong><?= e($user['name']) ?></strong></p>
        <p class="muted"><?= e($user['email']) ?></p>
        <p class="muted small">Joined <?= e(date('M j, Y', strtotime($user['created_at']))) ?></p>
      </div>
      <div class="card">
        <h3>Quick links</h3>
        <ul class="side-links">
          <li><a href="<?= e(url('/cart')) ?>">My cart</a></li>
          <li><a href="<?= e(url('/shop')) ?>">Continue shopping</a></li>
          <li><a href="<?= e(url('/account/password')) ?>">Change password</a></li>
          <li><a href="<?= e(url('/logout')) ?>">Sign out</a></li>
        </ul>
      </div>
    </aside>

    <div class="account-main">
      <div class="card">
        <h3>Recent orders</h3>
        <?php if (empty($orders)): ?>
          <p class="muted">No orders yet. Browse the <a href="<?= e(url('/shop')) ?>">catalog</a> to get started.</p>
        <?php else: ?>
          <table class="table">
            <thead><tr><th>#</th><th>Date</th><th>Status</th><th>Total</th></tr></thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
              <tr>
                <td>#<?= (int)$o['id'] ?></td>
                <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                <td><span class="status status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                <td><?= e(money((float)$o['total'])) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>
