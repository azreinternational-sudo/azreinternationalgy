<?php
/** Checkout page — works for guests and logged-in users.
 *  Logged-in users get name/email pre-filled (read-only), phone editable.
 *  Guests fill in everything. */
$page_title = 'Checkout';

$user = auth_user();
$items = cart_items_with_products();

if (empty($items)) {
    flash_set('info', 'Your cart is empty. Add some products before checking out.');
    redirect('/cart');
}

$subtotal = 0.0;
foreach ($items as $it) $subtotal += $it['subtotal'];
$shipping = 0.0;
$total = $subtotal + $shipping;

// Pre-fill from user record if logged in
$default_name  = $user ? (string)($user['name']  ?? '') : '';
$default_email = $user ? (string)($user['email'] ?? '') : '';
$default_phone = $user ? trim((string)($user['phone'] ?? '')) : '';

$is_logged_in = (bool)$user;
?>
<section class="container section">
  <header class="page-head">
    <h1>Checkout</h1>
    <p class="muted">Review your order and submit — we'll call you to confirm delivery &amp; payment.</p>
  </header>

  <?php if (!$is_logged_in): ?>
    <div class="flash flash-info">
      Checking out as guest.
      <a href="<?= e(url('/login?next=/checkout')) ?>">Sign in</a>
      or
      <a href="<?= e(url('/register?next=/checkout')) ?>">create an account</a>
      to save your details for next time.
    </div>
  <?php endif; ?>

  <div class="checkout-layout">
    <form class="checkout-form" method="post" action="<?= e(url('/checkout/place')) ?>" novalidate>
      <?= csrf_field() ?>

      <fieldset class="form-block">
        <legend>Contact</legend>
        <div class="form-grid">
          <label class="span-2">Full name
            <input type="text" name="customer_name" required
              value="<?= e(old('customer_name') ?: $default_name) ?>"
              <?= $is_logged_in ? 'readonly' : '' ?>
              autocomplete="name">
          </label>
          <label>Email
            <input type="email" name="customer_email" required
              value="<?= e(old('customer_email') ?: $default_email) ?>"
              <?= $is_logged_in ? 'readonly' : '' ?>
              autocomplete="email">
          </label>
          <label>Phone number
            <input type="tel" name="customer_phone" required
              value="<?= e(old('customer_phone') ?: $default_phone) ?>"
              autocomplete="tel" placeholder="+592-XXX-XXXX" pattern="[\d+\-\s\(\)]{6,}">
          </label>
        </div>
        <?php if ($is_logged_in): ?>
          <small class="muted">Name &amp; email are from your account. <a href="<?= e(url('/account')) ?>">Edit profile</a></small>
        <?php else: ?>
          <small class="muted">We'll call this number to confirm your order &amp; delivery.</small>
        <?php endif; ?>
      </fieldset>

      <fieldset class="form-block">
        <legend>Delivery</legend>
        <div class="form-grid">
          <label class="span-2">Street address
            <input type="text" name="ship_address" required value="<?= e(old('ship_address')) ?>" placeholder="House / lot number, street name" autocomplete="street-address">
          </label>
          <label>City / town
            <input type="text" name="ship_city" value="<?= e(old('ship_city')) ?>" autocomplete="address-level2" placeholder="Georgetown">
          </label>
          <label>Region
            <select name="ship_region" autocomplete="address-level1">
              <option value="">Select region</option>
              <?php
              $regions = ['Demerara-Mahaica (Region 4)','Essequibo Islands-West Demerara (Region 3)','East Berbice-Corentyne (Region 6)','Mahaica-Berbice (Region 5)','Pomeroon-Supenaam (Region 2)','Upper Demerara-Berbice (Region 10)','Barima-Waini (Region 1)','Cuyuni-Mazaruni (Region 7)','Potaro-Siparuni (Region 8)','Upper Takutu-Upper Essequibo (Region 9)'];
              $sel = old('ship_region');
              foreach ($regions as $r) {
                  $s = ($sel === $r) ? ' selected' : '';
                  echo '<option value="' . e($r) . '"' . $s . '>' . e($r) . '</option>';
              }
              ?>
            </select>
          </label>
        </div>
      </fieldset>

      <fieldset class="form-block">
        <legend>Order notes (optional)</legend>
        <label>Anything we should know? Preferred delivery time, business hours, special instructions
          <textarea name="notes" rows="3" placeholder="e.g. Call before delivery. Back gate entrance."><?= e(old('notes')) ?></textarea>
        </label>
      </fieldset>

      <button type="submit" class="btn btn-primary btn-block btn-lg">Submit order</button>
      <p class="muted small center" style="margin-top:.75rem">
        No payment is taken online. After you submit, we'll call you to confirm pricing,
        arrange delivery, and take payment by bank transfer or cash on delivery.
      </p>
    </form>

    <aside class="checkout-summary">
      <h3>Order summary</h3>
      <ul class="co-list">
        <?php foreach ($items as $it): ?>
          <li class="co-item">
            <img class="co-thumb" src="<?= e(product_image($it['image'] ?? null, $it)) ?>" alt="" loading="lazy">
            <div class="co-info">
              <span class="co-name"><?= e($it['name']) ?></span>
              <span class="muted small"><?= e($it['brand']) ?> · <?= e($it['sku'] ?? '') ?></span>
              <span class="muted small">Qty: <?= (int)$it['qty'] ?> × <?= e(money((float)$it['price'])) ?></span>
            </div>
            <strong class="co-line"><?= e(money((float)$it['subtotal'])) ?></strong>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="sum-row"><span>Items</span><strong><?= (int)cart_count() ?></strong></div>
      <div class="sum-row"><span>Subtotal</span><strong><?= e(money($subtotal)) ?></strong></div>
      <div class="sum-row"><span>Shipping</span><span class="muted">Confirmed by phone</span></div>
      <div class="sum-row sum-total"><span>Estimated total</span><strong><?= e(money($total)) ?></strong></div>
      <a class="btn btn-ghost btn-block" href="<?= e(url('/cart')) ?>">← Back to cart</a>
    </aside>
  </div>
</section>
