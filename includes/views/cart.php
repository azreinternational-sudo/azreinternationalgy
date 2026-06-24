<?php
$page_title = 'Cart';
$items = cart_items_with_products();
$total = 0.0;
foreach ($items as $it) $total += $it['subtotal'];
$csrf = csrf_token();
?>
<section class="container section">
  <header class="page-head">
    <h1>Your cart</h1>
  </header>

  <?php if (empty($items)): ?>
    <div class="empty-state">
      <h3>Your cart is empty</h3>
      <p>Start by browsing the <a href="<?= e(url('/shop')) ?>">catalog</a>.</p>
    </div>
  <?php else: ?>
  <div class="cart-layout">
    <div class="cart-list">
      <?php foreach ($items as $it): ?>
      <div class="cart-row" data-pid="<?= (int)$it['id'] ?>">
        <a class="cart-img" href="<?= e(url('/product/' . $it['slug'])) ?>">
          <img src="<?= e(product_image($it['image'] ?? null, $it)) ?>" alt="<?= e($it['name']) ?>" loading="lazy">
        </a>
        <div class="cart-meta">
          <a class="cart-name" href="<?= e(url('/product/' . $it['slug'])) ?>"><?= e($it['name']) ?></a>
          <span class="cart-brand"><?= e($it['brand']) ?></span>
          <span class="muted small">SKU: <?= e($it['sku'] ?? '') ?></span>
        </div>
        <div class="cart-qty">
          <form method="post" action="<?= e(url('/cart/update')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
            <div class="qty-control">
              <button type="button" data-qty="-1">−</button>
              <input type="number" name="qty" value="<?= (int)$it['qty'] ?>" min="0" max="<?= (int)$it['stock'] ?>">
              <button type="button" data-qty="1">+</button>
            </div>
            <button class="btn btn-ghost btn-sm" type="submit">Update</button>
          </form>
        </div>
        <div class="cart-line-price">
          <strong><?= e(money((float)$it['subtotal'])) ?></strong>
          <span class="muted small"><?= e(money((float)$it['price'])) ?> each</span>
        </div>
        <form method="post" action="<?= e(url('/cart/remove')) ?>" class="cart-remove">
          <?= csrf_field() ?>
          <input type="hidden" name="product_id" value="<?= (int)$it['id'] ?>">
          <button class="link-danger" type="submit" aria-label="Remove">Remove</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <aside class="cart-summary">
      <h3>Summary</h3>
      <div class="sum-row"><span>Items</span><strong><?= (int)cart_count() ?></strong></div>
      <div class="sum-row"><span>Subtotal</span><strong><?= e(money($total)) ?></strong></div>
      <div class="sum-row"><span>Shipping</span><span class="muted">Calculated at checkout</span></div>
      <div class="sum-row sum-total"><span>Estimated total</span><strong><?= e(money($total)) ?></strong></div>
      <p class="muted small">Checkout is coming soon. <a href="<?= e(url('/contact')) ?>">Contact us</a> for a quote or to place an order.</p>
      <a class="btn btn-ghost btn-block" href="<?= e(url('/shop')) ?>">Continue shopping</a>
    </aside>
  </div>
  <?php endif; ?>
</section>
