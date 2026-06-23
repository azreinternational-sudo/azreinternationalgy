<?php
/** Reusable product card */
if (!defined('AZRE_BOOTSTRAP')) { http_response_code(403); exit('Forbidden'); }
/** @var array $p */
$p = $p ?? [];
?>
<a class="prod-card" href="<?= e(url('/product/' . ($p['slug'] ?? ''))) ?>" data-pid="<?= (int)($p['id'] ?? 0) ?>">
  <div class="prod-img">
    <img src="<?= e(product_image($p['image'] ?? null, $p['name'] ?? 'AZ')) ?>" alt="<?= e($p['name'] ?? '') ?>" loading="lazy">
    <?php if (!empty($p['featured'])): ?><span class="prod-badge prod-badge-hot">Featured</span><?php endif; ?>
  </div>
  <div class="prod-body">
    <?php if (!empty($p['brand'])): ?><span class="prod-brand"><?= e($p['brand']) ?></span><?php endif; ?>
    <h3 class="prod-name"><?= e($p['name'] ?? '') ?></h3>
    <div class="prod-row">
      <span class="prod-price"><?= e(money((float)($p['price'] ?? 0))) ?></span>
      <span class="prod-cta">View →</span>
    </div>
  </div>
</a>
