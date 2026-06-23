<?php
$q = trim($_GET['q'] ?? '');
$page_title = $q !== '' ? "Search: $q" : 'Search';
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 24;
$products = [];
$total = 0;
if ($q !== '') {
    $like = "%$q%";
    $count = db()->prepare('SELECT COUNT(*) FROM products WHERE active = 1 AND (name LIKE ? OR brand LIKE ? OR sku LIKE ?)');
    $count->execute([$like, $like, $like]);
    $total = (int)$count->fetchColumn();
    $pg = paginate($page, $per, $total);
    $stmt = db()->prepare('SELECT id, slug, name, brand, price, image FROM products WHERE active = 1 AND (name LIKE ? OR brand LIKE ? OR sku LIKE ?) ORDER BY id DESC LIMIT ? OFFSET ?');
    $stmt->bindValue(1, $like);
    $stmt->bindValue(2, $like);
    $stmt->bindValue(3, $like);
    $stmt->bindValue(4, $per, PDO::PARAM_INT);
    $stmt->bindValue(5, $pg['offset'], PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll();
}
?>
<section class="container section">
  <header class="page-head">
    <h1>Search</h1>
    <form method="get" action="<?= e(url('/search')) ?>" class="search search-large">
      <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search by name, brand, or SKU…" autofocus>
      <button class="btn btn-primary" type="submit">Search</button>
    </form>
  </header>
  <?php if ($q === ''): ?>
    <p class="muted">Type a brand, product name, or SKU to begin.</p>
  <?php elseif (empty($products)): ?>
    <div class="empty-state"><p>No matches for <strong><?= e($q) ?></strong>.</p><p>Try a broader term or <a href="<?= e(url('/shop')) ?>">browse the catalog</a>.</p></div>
  <?php else: ?>
    <p class="muted"><?= $total ?> result<?= $total === 1 ? '' : 's' ?> for <strong><?= e($q) ?></strong></p>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <?php $__page('partials/product_card', ['p' => $p]); ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>
