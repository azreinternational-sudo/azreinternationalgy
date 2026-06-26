<?php
if (!defined('AZRE_BOOTSTRAP')) { http_response_code(403); exit('Forbidden'); }
$_page_title = $page_title ?? AZRE_NAME;
$_body_class = $body_class ?? '';
$_cart_count = $cart_count ?? 0;
$_user = $current_user ?? null;
$_flashes = flash_pop();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($_page_title) ?> — <?= e(AZRE_NAME) ?></title>
<meta name="description" content="<?= e(AZRE_TAGLINE) ?>">
<link rel="icon" type="image/svg+xml" href="<?= e(asset('/assets/images/favicon.svg')) ?>">
<link rel="stylesheet" href="<?= e(asset('/assets/css/app.css')) ?>">
</head>
<body class="<?= e($_body_class) ?>">
<a href="#main" class="skip-link">Skip to content</a>

<header class="site-header" id="siteHeader">
  <div class="container nav-row">
    <a class="brand" href="<?= e(url('/')) ?>" aria-label="<?= e(AZRE_NAME) ?> home">
      <span class="brand-mark" aria-hidden="true">
        <svg viewBox="0 0 40 40" width="36" height="36">
          <defs>
            <linearGradient id="bm" x1="0" y1="0" x2="1" y2="1">
              <stop offset="0" stop-color="#ffb347"/>
              <stop offset="1" stop-color="#ff7a18"/>
            </linearGradient>
          </defs>
          <path d="M8 30 L8 12 L18 12 L22 18 L32 18 L32 26 L26 30 Z" fill="url(#bm)" />
          <circle cx="13" cy="32" r="3" fill="#0c1830"/>
          <circle cx="27" cy="32" r="3" fill="#0c1830"/>
        </svg>
      </span>
      <span class="brand-text">
        <strong class="brand-name"><?= e(AZRE_NAME) ?></strong>
        <small class="brand-tag"><?= e(AZRE_TAGLINE) ?></small>
      </span>
    </a>
    <form class="search" action="<?= e(url('/search')) ?>" method="get" role="search">
      <input type="search" name="q" placeholder="Search oils, brands, part numbers…" value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search products">
      <button type="submit" aria-label="Search">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
      </button>
    </form>
    <nav class="nav-actions" aria-label="Primary">
      <a class="nav-link" href="<?= e(url('/shop')) ?>">Shop</a>
      <a class="nav-link" href="<?= e(url('/about')) ?>">About</a>
      <a class="nav-link" href="<?= e(url('/contact')) ?>">Contact</a>
      <a class="cart-btn" href="<?= e(url('/cart')) ?>" aria-label="Cart">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"></circle><circle cx="20" cy="21" r="1"></circle><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        <?php if ($_cart_count > 0): ?><span class="cart-badge"><?= (int)$_cart_count ?></span><?php endif; ?>
      </a>
      <?php if ($_user): ?>
        <a class="nav-link nav-user" href="<?= e(url('/account')) ?>">Hi, <?= e(explode(' ', $_user['name'])[0]) ?></a>
        <?php if ($_user['role'] === 'admin' || $_user['role'] === 'staff'): ?>
          <a class="nav-link nav-admin" href="<?= e(url('/admin')) ?>">Admin</a>
        <?php endif; ?>
        <a class="nav-link nav-quiet" href="<?= e(url('/logout')) ?>">Sign out</a>
      <?php else: ?>
        <a class="nav-link nav-quiet" href="<?= e(url('/login')) ?>">Sign in</a>
        <a class="btn btn-primary btn-sm" href="<?= e(url('/register')) ?>">Sign up</a>
      <?php endif; ?>
      <button class="nav-burger" id="navBurger" aria-label="Menu" aria-expanded="false">
        <span></span><span></span><span></span>
      </button>
    </nav>
  </div>
</header>

<main id="main" class="site-main">

<?php if (!empty($_flashes)): ?>
  <div class="container">
    <?php foreach ($_flashes as $f): ?>
      <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
