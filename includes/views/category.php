<?php
$slug = $params[0] ?? '';
$cat = db()->prepare('SELECT * FROM categories WHERE slug = ?');
$cat->execute([$slug]);
$cat = $cat->fetch();
if (!$cat) {
    http_response_code(404);
    echo '<section class="container narrow"><div class="card"><h1>Category not found</h1></div></section>';
    return;
}
$page_title = $cat['name'];
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 24;
$count = db()->prepare('SELECT COUNT(*) FROM products WHERE category_id = ? AND active = 1');
$count->execute([$cat['id']]);
$total = (int)$count->fetchColumn();
$pg = paginate($page, $per, $total);

$stmt = db()->prepare('SELECT id, slug, name, brand, price, image, form, size_label, viscosity, featured FROM products WHERE category_id = ? AND active = 1 ORDER BY id DESC LIMIT ? OFFSET ?');
$stmt->bindValue(1, $cat['id'], PDO::PARAM_INT);
$stmt->bindValue(2, $per, PDO::PARAM_INT);
$stmt->bindValue(3, $pg['offset'], PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();
?>
<section class="container section">
  <header class="page-head">
    <nav class="crumbs" aria-label="Breadcrumb"><a href="<?= e(url('/')) ?>">Home</a> · <a href="<?= e(url('/shop')) ?>">Shop</a> · <span><?= e($cat['name']) ?></span></nav>
    <h1><?= e($cat['name']) ?></h1>
    <p class="muted"><?= $total ?> product<?= $total === 1 ? '' : 's' ?></p>
  </header>

  <?php if (empty($products)): ?>
    <div class="empty-state"><p>No products in this category yet.</p></div>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $p): ?>
        <?php $__page('partials/product_card', ['p' => $p]); ?>
      <?php endforeach; ?>
    </div>
    <?php if ($pg['pages'] > 1): ?>
    <nav class="pager">
      <?php if ($pg['page'] > 1): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/category/' . $cat['slug'] . '?page=' . ($pg['page'] - 1))) ?>">← Previous</a>
      <?php endif; ?>
      <span class="pager-info">Page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></span>
      <?php if ($pg['page'] < $pg['pages']): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/category/' . $cat['slug'] . '?page=' . ($pg['page'] + 1))) ?>">Next →</a>
      <?php endif; ?>
    </nav>
    <?php endif; ?>
  <?php endif; ?>
</section>
