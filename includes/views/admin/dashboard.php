<?php
$page_title = 'Admin';
$stats = db()->query('SELECT
    (SELECT COUNT(*) FROM products WHERE active = 1) AS active_products,
    (SELECT COUNT(*) FROM products WHERE active = 0) AS inactive_products,
    (SELECT COUNT(*) FROM categories) AS categories,
    (SELECT COUNT(*) FROM users) AS users,
    (SELECT COUNT(*) FROM users WHERE role IN ("admin","staff")) AS staff,
    (SELECT COUNT(*) FROM orders WHERE status = "pending") AS pending_orders,
    (SELECT COUNT(*) FROM orders) AS total_orders,
    (SELECT COALESCE(SUM(total),0) FROM orders WHERE status IN ("paid","shipped","delivered")) AS revenue,
    (SELECT COUNT(*) FROM contact_messages) AS messages
')->fetch();
$low_stock = db()->query('SELECT id, slug, name, stock FROM products WHERE active = 1 AND stock < 10 ORDER BY stock ASC LIMIT 10')->fetchAll();
$recent_orders = db()->query('SELECT id, status, total, created_at, customer_name FROM orders ORDER BY id DESC LIMIT 8')->fetchAll();
?>
<section class="container section">
  <header class="page-head page-head-row">
    <div>
      <h1>Dashboard</h1>
      <p class="muted">Welcome back, <?= e(auth_user()['name']) ?>.</p>
    </div>
    <div class="head-actions">
      <a class="btn btn-primary" href="<?= e(url('/admin/product/new')) ?>">+ New product</a>
      <a class="btn btn-ghost" href="<?= e(url('/admin/categories')) ?>">Categories</a>
    </div>
  </header>

  <div class="admin-cta-grid">
    <a class="admin-cta" href="<?= e(url('/admin/product/new')) ?>">
      <div class="admin-cta-icon">+</div>
      <div>
        <strong>Add a product</strong>
        <p class="muted small">Create a new SKU, set price, stock, image, category.</p>
      </div>
    </a>
    <a class="admin-cta" href="<?= e(url('/admin/categories')) ?>">
      <div class="admin-cta-icon">☰</div>
      <div>
        <strong>Manage categories</strong>
        <p class="muted small">Add, rename, or reorder product categories.</p>
      </div>
    </a>
    <a class="admin-cta" href="<?= e(url('/admin/products')) ?>">
      <div class="admin-cta-icon">≣</div>
      <div>
        <strong>Browse catalog</strong>
        <p class="muted small">Edit prices, stock, images, visibility for all products.</p>
      </div>
    </a>
  </div>

  <div class="stat-grid">
    <a class="stat-card" href="<?= e(url('/admin/products')) ?>">
      <span class="stat-num"><?= (int)$stats['active_products'] ?></span>
      <span class="stat-label">Active products</span>
      <?php if ($stats['inactive_products'] > 0): ?><span class="stat-meta"><?= (int)$stats['inactive_products'] ?> inactive</span><?php endif; ?>
    </a>
    <a class="stat-card" href="<?= e(url('/admin/categories')) ?>">
      <span class="stat-num"><?= (int)$stats['categories'] ?></span>
      <span class="stat-label">Categories</span>
    </a>
    <div class="stat-card">
      <span class="stat-num"><?= (int)$stats['total_orders'] ?></span>
      <span class="stat-label">Orders</span>
      <span class="stat-meta"><?= (int)$stats['pending_orders'] ?> pending</span>
    </div>
    <div class="stat-card">
      <span class="stat-num"><?= e(money((float)$stats['revenue'])) ?></span>
      <span class="stat-label">Revenue (paid+)</span>
    </div>
    <div class="stat-card">
      <span class="stat-num"><?= (int)$stats['users'] ?></span>
      <span class="stat-label">Users</span>
      <span class="stat-meta"><?= (int)$stats['staff'] ?> staff</span>
    </div>
    <div class="stat-card">
      <span class="stat-num"><?= (int)$stats['messages'] ?></span>
      <span class="stat-label">Contact messages</span>
    </div>
  </div>

  <div class="admin-grid">
    <div class="card">
      <h3>Low stock</h3>
      <?php if (empty($low_stock)): ?>
        <p class="muted">All products are well stocked.</p>
      <?php else: ?>
      <table class="table">
        <thead><tr><th>Product</th><th>Stock</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($low_stock as $p): ?>
          <tr>
            <td><?= e($p['name']) ?><br><span class="muted small">SKU <?= e($p['slug']) ?></span></td>
            <td><strong class="<?= (int)$p['stock'] === 0 ? 'text-warn' : '' ?>"><?= (int)$p['stock'] ?></strong></td>
            <td><a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/product/' . $p['id'] . '/edit')) ?>">Edit</a></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
    <div class="card">
      <h3>Recent orders</h3>
      <?php if (empty($recent_orders)): ?>
        <p class="muted">No orders yet.</p>
      <?php else: ?>
      <table class="table">
        <thead><tr><th>#</th><th>Date</th><th>Customer</th><th>Status</th><th>Total</th></tr></thead>
        <tbody>
          <?php foreach ($recent_orders as $o): ?>
          <tr>
            <td>#<?= (int)$o['id'] ?></td>
            <td><?= e(date('M j', strtotime($o['created_at']))) ?></td>
            <td><?= e($o['customer_name'] ?? '—') ?></td>
            <td><span class="status status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
            <td><?= e(money((float)$o['total'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </div>
  </div>
</section>
