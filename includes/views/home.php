<?php
/** Home page */
$page_title = 'Home';
$featured = db()->query('SELECT id, slug, name, brand, price, image FROM products WHERE active = 1 AND featured = 1 ORDER BY id DESC LIMIT 8')->fetchAll();
$latest = db()->query('SELECT id, slug, name, brand, price, image FROM products WHERE active = 1 ORDER BY id DESC LIMIT 12')->fetchAll();
$top_cats = db()->query('SELECT c.id, c.slug, c.name, COUNT(p.id) AS cnt FROM categories c LEFT JOIN products p ON p.category_id = c.id AND p.active = 1 GROUP BY c.id ORDER BY c.sort_order, c.name LIMIT 8')->fetchAll();
?>

<section class="hero">
  <div class="container hero-inner">
    <div class="hero-copy">
      <h1>Auto parts &amp; lubricants<br>that keep you <span class="grad-text">moving</span>.</h1>
      <p class="lead">From quarts to drums — engine oils, transmission fluids, gear oils, coolants, DEF, and chemicals for trucks, autos, bus, marine and industrial fleets. Sourced from a vetted global supplier. In-stock and ready to ship across Guyana.</p>
      <div class="hero-cta">
        <a class="btn btn-primary btn-lg" href="<?= e(url('/shop')) ?>">Shop the catalog</a>
        <a class="btn btn-ghost btn-lg" href="<?= e(url('/contact')) ?>">Request a quote</a>
      </div>
      <ul class="trust-row">
        <li><span class="trust-num">300+</span><span class="trust-label">SKUs in stock</span></li>
        <li><span class="trust-num">12</span><span class="trust-label">Categories</span></li>
        <li><span class="trust-num">21</span><span class="trust-label">Trusted brands</span></li>
      </ul>
    </div>
    <div class="hero-card-stack" aria-hidden="true">
      <div class="hero-card hero-card-a">
        <span class="hc-tag">5W-30</span>
        <strong>Everest Full Synthetic</strong>
        <small>12/1 Quart Case</small>
        <span class="hc-price"><?= e(money(48)) ?></span>
      </div>
      <div class="hero-card hero-card-b">
        <span class="hc-tag">DEF</span>
        <strong>Musket Premium DEF</strong>
        <small>55 Gal Drum</small>
        <span class="hc-price"><?= e(money(176)) ?></span>
      </div>
      <div class="hero-card hero-card-c">
        <span class="hc-tag">ATF</span>
        <strong>Everest Dexron VI</strong>
        <small>55 Gal Drum</small>
        <span class="hc-price"><?= e(money(843)) ?></span>
      </div>
    </div>
  </div>
</section>

<section class="container section">
  <div class="section-head">
    <h2>Shop by category</h2>
    <a class="link-more" href="<?= e(url('/shop')) ?>">Browse all →</a>
  </div>
  <div class="cat-grid">
    <?php foreach ($top_cats as $c): ?>
      <a class="cat-tile" href="<?= e(url('/category/' . $c['slug'])) ?>">
        <span class="cat-tile-img">
          <img src="<?= e(category_image($c['slug'])) ?>" alt="" loading="lazy">
        </span>
        <span class="cat-tile-meta">
          <span class="cat-tile-name"><?= e($c['name']) ?></span>
          <span class="cat-tile-cnt"><?= (int)$c['cnt'] ?> products</span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php if (!empty($featured)): ?>
<section class="container section">
  <div class="section-head">
    <h2>Featured products</h2>
    <a class="link-more" href="<?= e(url('/shop')) ?>">View all →</a>
  </div>
  <div class="product-grid">
    <?php foreach ($featured as $p): ?>
      <?php $__page('partials/product_card', ['p' => $p]); ?>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="container section">
  <div class="section-head">
    <h2>Latest arrivals</h2>
    <a class="link-more" href="<?= e(url('/shop')) ?>">View all →</a>
  </div>
  <div class="product-grid">
    <?php foreach ($latest as $p): ?>
      <?php $__page('partials/product_card', ['p' => $p]); ?>
    <?php endforeach; ?>
  </div>
</section>

<section class="container section">
  <div class="why-grid">
    <div class="why-card">
      <div class="why-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
      </div>
      <h3>Sourced right</h3>
      <p>We stock OEM-spec products from a single vetted global supplier — no grey-market guesswork.</p>
    </div>
    <div class="why-card">
      <div class="why-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      </div>
      <h3>Bulk-friendly</h3>
      <p>Quart cases, 5-gal pails, BIB, drums, and bulk — priced for shops, fleets and individual buyers.</p>
    </div>
    <div class="why-card">
      <div class="why-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <h3>Account perks</h3>
      <p>Create an account to track orders, save addresses, and re-order your shop's staples in two clicks.</p>
    </div>
    <div class="why-card">
      <div class="why-icon">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
      </div>
      <h3>Talk to a human</h3>
      <p>Need a fleet quote or a hard-to-find product? Email us — we usually reply within a few business hours.</p>
    </div>
  </div>
</section>
