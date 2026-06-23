<?php
$page_title = 'Create account';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $error = 'Invalid form submission.'; }
    else {
        $r = auth_register($_POST['name'] ?? '', $_POST['email'] ?? '', $_POST['password'] ?? '');
        if ($r['ok']) {
            flash_set('success', 'Welcome to ' . AZRE_NAME . '!');
            redirect('/account');
        } else {
            $error = $r['msg'];
            old_keep(['name' => $_POST['name'] ?? '', 'email' => $_POST['email'] ?? '']);
        }
    }
}
old_clear();
?>
<section class="auth-section">
  <div class="auth-card">
    <h1>Create account</h1>
    <p class="muted">Save addresses, track orders, and re-order your staples.</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('/register')) ?>" novalidate>
      <?= csrf_field() ?>
      <label>Full name
        <input type="text" name="name" required value="<?= e(old('name')) ?>" autocomplete="name">
      </label>
      <label>Email
        <input type="email" name="email" required value="<?= e(old('email')) ?>" autocomplete="email">
      </label>
      <label>Password
        <input type="password" name="password" required minlength="6" autocomplete="new-password">
        <small class="muted">At least 6 characters.</small>
      </label>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Create account</button>
    </form>
    <p class="muted small">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
  </div>
</section>
