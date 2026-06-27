<?php
$page_title = 'Create account';
$error = null;
$next = $_GET['next'] ?? '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $error = 'Invalid form submission.'; }
    else {
        $next = $_POST['next'] ?? '';
        $r = auth_register(
            $_POST['name'] ?? '',
            $_POST['email'] ?? '',
            $_POST['password'] ?? '',
            $_POST['phone'] ?? ''
        );
        if ($r['ok']) {
            flash_set('success', 'Welcome to ' . AZRE_NAME . '!');
            // If they were on their way to checkout, take them there
            if ($next && str_starts_with($next, '/') && !str_starts_with($next, '//')) {
                redirect($next);
            }
            redirect('/account');
        } else {
            $error = $r['msg'];
            old_keep([
                'name'  => $_POST['name']  ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
            ]);
        }
    }
}
old_clear();
?>
<section class="auth-section">
  <div class="auth-card">
    <h1>Create account</h1>
    <?php if ($next === '/checkout' || $next === '/checkout/place'): ?>
      <p class="muted">Create an account to place your order — we'll need your phone to confirm delivery.</p>
    <?php else: ?>
      <p class="muted">Save addresses, track orders, and re-order your staples.</p>
    <?php endif; ?>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('/register')) ?>" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="next" value="<?= e($next) ?>">
      <label>Full name
        <input type="text" name="name" required value="<?= e(old('name')) ?>" autocomplete="name">
      </label>
      <label>Email
        <input type="email" name="email" required value="<?= e(old('email')) ?>" autocomplete="email">
      </label>
      <label>Phone number
        <input type="tel" name="phone" required value="<?= e(old('phone')) ?>" autocomplete="tel" placeholder="+592-XXX-XXXX" pattern="[\d+\-\s\(\)]{6,}">
        <small class="muted">We'll call this number to confirm your order and delivery.</small>
      </label>
      <label>Password
        <input type="password" name="password" required minlength="6" autocomplete="new-password">
        <small class="muted">At least 6 characters.</small>
      </label>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Create account</button>
    </form>
    <p class="muted small">Already have an account? <a href="<?= e(url('/login')) ?><?= $next ? '?next=' . urlencode($next) : '' ?>">Sign in</a></p>
  </div>
</section>
