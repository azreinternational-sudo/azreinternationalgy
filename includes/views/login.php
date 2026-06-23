<?php
$page_title = 'Sign in';
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $error = 'Invalid form submission.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass = (string)($_POST['password'] ?? '');
        if (auth_login($email, $pass)) {
            $u = auth_user();
            if ($u && ($u['role'] === 'admin' || $u['role'] === 'staff')) {
                redirect('/admin');
            }
            flash_set('success', 'Welcome back!');
            $next = $_GET['next'] ?? '/account';
            if (!preg_match('#^/[^/]#', $next)) $next = '/account';
            redirect($next);
        } else {
            $error = 'Email or password is incorrect.';
            old_keep(['email' => $email]);
        }
    }
}
old_clear();
?>
<section class="auth-section">
  <div class="auth-card">
    <h1>Sign in</h1>
    <p class="muted">Welcome back to <?= e(AZRE_NAME) ?>.</p>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('/login')) ?>" novalidate>
      <?= csrf_field() ?>
      <label>Email
        <input type="email" name="email" required value="<?= e(old('email')) ?>" autocomplete="email">
      </label>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button type="submit" class="btn btn-primary btn-block btn-lg">Sign in</button>
    </form>
    <p class="muted small">New here? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
  </div>
</section>
