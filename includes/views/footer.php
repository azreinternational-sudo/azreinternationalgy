<?php if (!defined('AZRE_BOOTSTRAP')) { http_response_code(403); exit('Forbidden'); } ?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="foot-col">
      <div class="brand">
        <span class="brand-mark" aria-hidden="true">
          <svg viewBox="0 0 40 40" width="32" height="32">
            <defs>
              <linearGradient id="bfm" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="#ffb347"/>
                <stop offset="1" stop-color="#ff7a18"/>
              </linearGradient>
            </defs>
            <path d="M8 30 L8 12 L18 12 L22 18 L32 18 L32 26 L26 30 Z" fill="url(#bfm)" />
            <circle cx="13" cy="32" r="3" fill="#0c1830"/>
            <circle cx="27" cy="32" r="3" fill="#0c1830"/>
          </svg>
        </span>
        <strong>Azre International</strong>
      </div>
      <p class="muted"><?= e(AZRE_TAGLINE) ?></p>
      <p class="muted">Georgetown, Guyana</p>
    </div>
    <div class="foot-col">
      <h4>Shop</h4>
      <ul>
        <li><a href="<?= e(url('/shop')) ?>">All products</a></li>
        <?php
        $top_cats = db()->query('SELECT slug, name FROM categories ORDER BY sort_order LIMIT 6')->fetchAll();
        foreach ($top_cats as $tc): ?>
          <li><a href="<?= e(url('/category/' . $tc['slug'])) ?>"><?= e($tc['name']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="foot-col">
      <h4>Account</h4>
      <ul>
        <?php if ($_user ?? null): ?>
          <li><a href="<?= e(url('/account')) ?>">My account</a></li>
          <li><a href="<?= e(url('/cart')) ?>">Cart</a></li>
          <li><a href="<?= e(url('/logout')) ?>">Sign out</a></li>
        <?php else: ?>
          <li><a href="<?= e(url('/login')) ?>">Sign in</a></li>
          <li><a href="<?= e(url('/register')) ?>">Create account</a></li>
        <?php endif; ?>
      </ul>
    </div>
    <div class="foot-col">
      <h4>Company</h4>
      <ul>
        <li><a href="<?= e(url('/about')) ?>">About</a></li>
        <li><a href="<?= e(url('/contact')) ?>">Contact</a></li>
      </ul>
      <p class="muted small">Sales: <?= e(AZRE_EMAIL) ?></p>
    </div>
  </div>
  <div class="container foot-bar">
    <p class="muted small">© <?= date('Y') ?> <?= e(AZRE_NAME) ?>. All rights reserved.</p>
    <p class="muted small">Built for Guyana · <?= e(AZRE_DOMAIN) ?></p>
  </div>
</footer>

<script src="<?= e(asset('/assets/js/app.js')) ?>" defer></script>
</body>
</html>
