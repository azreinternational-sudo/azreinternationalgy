<?php
$slug = $params[0] ?? '';
$stmt = db()->prepare('SELECT p.*, c.name AS category_name, c.slug AS category_slug FROM products p LEFT JOIN categories c ON c.id = p.category_id WHERE p.slug = ? AND p.active = 1');
$stmt->execute([$slug]);
$p = $stmt->fetch();
if (!$p) {
    http_response_code(404);
    echo '<section class="container narrow"><div class="card"><h1>Product not found</h1></div></section>';
    return;
}
$page_title = $p['name'];
// Related products (same category)
$related = [];
if ($p['category_id']) {
    $rel = db()->prepare('SELECT id, slug, name, brand, price, image FROM products WHERE category_id = ? AND id <> ? AND active = 1 ORDER BY id DESC LIMIT 4');
    $rel->execute([$p['category_id'], $p['id']]);
    $related = $rel->fetchAll();
}
$csrf = csrf_token();
?>
<section class="container section">
  <nav class="crumbs" aria-label="Breadcrumb">
    <a href="<?= e(url('/')) ?>">Home</a> ·
    <a href="<?= e(url('/shop')) ?>">Shop</a> ·
    <?php if ($p['category_slug']): ?>
      <a href="<?= e(url('/category/' . $p['category_slug'])) ?>"><?= e($p['category_name']) ?></a> ·
    <?php endif; ?>
    <span><?= e($p['name']) ?></span>
  </nav>

  <div class="product-detail">
    <div class="pd-gallery">
      <div class="pd-image-wrap">
        <img src="<?= e(product_image($p['image'], $p['name'])) ?>" alt="<?= e($p['name']) ?>">
        <?php if (!empty($p['featured'])): ?><span class="prod-badge prod-badge-hot">Featured</span><?php endif; ?>
      </div>
    </div>
    <div class="pd-info">
      <span class="pd-brand"><?= e($p['brand'] ?: 'Azre') ?></span>
      <h1 class="pd-name"><?= e($p['name']) ?></h1>
      <ul class="pd-meta">
        <li><span>SKU</span><strong><?= e($p['sku']) ?></strong></li>
        <?php if ($p['viscosity']): ?><li><span>Viscosity</span><strong><?= e($p['viscosity']) ?></strong></li><?php endif; ?>
        <?php if ($p['form']): ?><li><span>Form</span><strong><?= e($p['form']) ?></strong></li><?php endif; ?>
        <?php if ($p['size_label']): ?><li><span>Size</span><strong><?= e($p['size_label']) ?></strong></li><?php endif; ?>
        <?php if ($p['category_name']): ?><li><span>Category</span><strong><?= e($p['category_name']) ?></strong></li><?php endif; ?>
      </ul>

      <div class="pd-price-row">
        <span class="pd-price"><?= e(money((float)$p['price'])) ?></span>
        <span class="pd-stock <?= (int)$p['stock'] > 0 ? 'in-stock' : 'oos' ?>">
          <?= (int)$p['stock'] > 0 ? 'In stock' : 'Out of stock' ?>
        </span>
      </div>

      <?php if ((int)$p['stock'] > 0): ?>
      <form method="post" action="<?= e(url('/cart/add')) ?>" class="pd-add">
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
        <div class="qty-row">
          <label for="qty">Quantity</label>
          <div class="qty-control">
            <button type="button" data-qty="-1" aria-label="Decrease">−</button>
            <input type="number" id="qty" name="qty" value="1" min="1" max="<?= (int)$p['stock'] ?>">
            <button type="button" data-qty="1" aria-label="Increase">+</button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg">Add to cart</button>
      </form>
      <?php endif; ?>

      <div class="pd-desc">
        <h3>About this product</h3>
        <p><?= nl2br(e($p['description'] ?: 'Stocked at Azre International. Contact us for fleet pricing or volume quotes.')) ?></p>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($related)): ?>
<section class="container section">
  <div class="section-head"><h2>You may also need</h2></div>
  <div class="product-grid">
    <?php foreach ($related as $r): ?>
      <?php $__page('partials/product_card', ['p' => $r]); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>
