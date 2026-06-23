<?php
auth_require_login();
$user = auth_user();
$page_title = 'Change password';
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $err = 'Invalid form submission.';
    } else {
        $r = auth_change_password(
            (int)$user['id'],
            (string)($_POST['current_password'] ?? ''),
            (string)($_POST['new_password'] ?? '')
        );
        if ($r['ok']) {
            flash_set('success', 'Password updated.');
            redirect('/account');
        }
        $err = $r['msg'];
    }
}
?>
<section class="container narrow section">
  <header class="page-head">
    <nav class="crumbs"><a href="<?= e(url('/account')) ?>">My account</a> · <span>Change password</span></nav>
    <h1>Change password</h1>
  </header>
  <?php if ($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post" action="<?= e(url('/account/password')) ?>" novalidate class="admin-form">
    <?= csrf_field() ?>
    <label>Current password
      <input type="password" name="current_password" required autocomplete="current-password">
    </label>
    <label>New password
      <input type="password" name="new_password" required minlength="6" autocomplete="new-password">
      <small class="muted">At least 6 characters.</small>
    </label>
    <label>Confirm new password
      <input type="password" name="confirm_password" required minlength="6" autocomplete="new-password" oninput="this.setCustomValidity(this.value !== document.querySelector('input[name=new_password]').value ? 'Passwords do not match' : '')">
    </label>
    <div class="form-actions">
      <button class="btn btn-primary btn-lg" type="submit">Update password</button>
      <a class="btn btn-ghost" href="<?= e(url('/account')) ?>">Cancel</a>
    </div>
  </form>
</section>
