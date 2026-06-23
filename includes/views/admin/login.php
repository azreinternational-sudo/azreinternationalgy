<?php
$page_title = 'Admin sign in';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $error = 'Invalid form submission.'; }
    else if (auth_login($_POST['email'] ?? '', $_POST['password'] ?? '')) {
        $u = auth_user();
        if ($u && ($u['role'] === 'admin' || $u['role'] === 'staff')) {
            flash_set('success', 'Welcome back, admin.');
            redirect('/admin');
        }
        auth_logout();
        $error = 'This account does not have admin access.';
    } else {
        $error = 'Invalid credentials.';
    }
}
?>
<section class="auth-section">
  <div class="auth-card auth-card-narrow">
    <span class="eyebrow">Admin</span>
    <h1>Sign in</h1>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('/admin/login')) ?>" novalidate>
      <?= csrf_field() ?>
      <label>Email<input type="email" name="email" required autocomplete="username"></label>
      <label>Password<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-primary btn-block btn-lg" type="submit">Sign in</button>
    </form>
    <p class="muted small">Customer? <a href="<?= e(url('/login')) ?>">Use the regular sign-in</a>.</p>
  </div>
</section>
