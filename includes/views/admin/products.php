<?php
$page_title = 'Products';
$q = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 30;

$where = '1=1';
$params = [];
if ($q !== '') {
    $where = '(name LIKE ? OR sku LIKE ? OR brand LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
$count = db()->prepare("SELECT COUNT(*) FROM products WHERE $where");
$count->execute($params);
$total = (int)$count->fetchColumn();
$pg = paginate($page, $per, $total);

$stmt = db()->prepare("SELECT p.id, p.sku, p.name, p.brand, p.price, p.stock, p.active, p.featured, c.name AS category
    FROM products p LEFT JOIN categories c ON c.id = p.category_id
    WHERE $where ORDER BY p.id DESC LIMIT $per OFFSET {$pg['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<section class="container section">
  <header class="page-head page-head-row">
    <div>
      <h1>Products</h1>
      <p class="muted"><?= $total ?> total</p>
    </div>
    <div class="head-actions">
      <form method="get" class="search">
        <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search products…">
        <button class="btn btn-ghost btn-sm" type="submit">Search</button>
      </form>
      <a class="btn btn-primary" href="<?= e(url('/admin/product/new')) ?>">+ New product</a>
    </div>
  </header>

  <div class="card flush">
    <table class="table admin-table">
      <thead>
        <tr>
          <th></th><th>SKU</th><th>Name</th><th>Brand</th><th>Category</th>
          <th>Price</th><th>Stock</th><th>Status</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($rows)): ?>
          <tr><td colspan="9" class="muted center">No products.</td></tr>
        <?php endif; ?>
        <?php foreach ($rows as $r): ?>
        <tr>
          <td>
            <img class="thumb" src="<?= e(product_image($r['image'] ?? null, $r)) ?>" alt="">
          </td>
          <td><code><?= e($r['sku']) ?></code></td>
          <td>
            <strong><?= e($r['name']) ?></strong>
            <?php if ($r['featured']): ?><span class="badge badge-gold">Featured</span><?php endif; ?>
          </td>
          <td><?= e($r['brand']) ?></td>
          <td><?= e($r['category'] ?? '—') ?></td>
          <td><?= e(money((float)$r['price'])) ?></td>
          <td class="<?= (int)$r['stock'] < 10 ? 'text-warn' : '' ?>"><?= (int)$r['stock'] ?></td>
          <td>
            <span class="status status-<?= $r['active'] ? 'active' : 'inactive' ?>">
              <?= $r['active'] ? 'Active' : 'Hidden' ?>
            </span>
          </td>
          <td class="row-actions">
            <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/product/' . $r['id'] . '/edit')) ?>">Edit</a>
            <form method="post" action="<?= e(url('/admin/product/' . $r['id'] . '/delete')) ?>" onsubmit="return confirm('Delete this product? This cannot be undone.')" style="display:inline">
              <?= csrf_field() ?>
              <button class="btn btn-danger btn-sm" type="submit">Delete</button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($pg['pages'] > 1): ?>
  <nav class="pager">
    <?php if ($pg['page'] > 1): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/products?q=' . urlencode($q) . '&page=' . ($pg['page'] - 1))) ?>">← Previous</a>
    <?php endif; ?>
    <span class="pager-info">Page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></span>
    <?php if ($pg['page'] < $pg['pages']): ?>
      <a class="btn btn-ghost btn-sm" href="<?= e(url('/admin/products?q=' . urlencode($q) . '&page=' . ($pg['page'] + 1))) ?>">Next →</a>
    <?php endif; ?>
  </nav>
  <?php endif; ?>
</section>
