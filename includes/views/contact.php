<?php
$page_title = 'Contact';
$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        flash_set('error', 'Invalid form submission.');
        redirect('/contact');
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $msg = trim($_POST['message'] ?? '');
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $msg === '') {
        flash_set('error', 'Please fill in all fields with a valid email.');
        old_keep(['name' => $name, 'email' => $email, 'message' => $msg]);
        redirect('/contact');
    }
    // Insert into a basic contact_messages table (auto-create if missing)
    try {
        db()->prepare('CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)')->execute([$name, $email, $msg]);
    } catch (Throwable $e) { /* swallow — message still acknowledged */ }
    $sent = true;
    old_clear();
}
?>
<section class="container narrow section">
  <header class="page-head"><h1>Contact</h1></header>
  <div class="prose">
    <p>Sales: <strong><?= e(AZRE_EMAIL) ?></strong></p>
    <p>Need a quote, a fleet price, or a hard-to-find product? Send us a note.</p>
  </div>

  <?php if ($sent): ?>
    <div class="card">
      <h3>Thanks — we got it.</h3>
      <p>We’ll get back to you within one business day.</p>
    </div>
  <?php else: ?>
  <form method="post" action="<?= e(url('/contact')) ?>" class="contact-form">
    <?= csrf_field() ?>
    <label>Your name<input type="text" name="name" required value="<?= e(old('name')) ?>"></label>
    <label>Your email<input type="email" name="email" required value="<?= e(old('email')) ?>"></label>
    <label>Message<textarea name="message" rows="6" required><?= e(old('message')) ?></textarea></label>
    <button type="submit" class="btn btn-primary btn-lg">Send</button>
  </form>
  <?php endif; ?>
</section>
