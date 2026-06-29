<?php
$page_title = 'Contact';
$sales_email = setting('store_email') ?: AZRE_EMAIL;
$sent = false;
$mail_error = null;

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

    // Persist the message regardless of mail success
    try {
        db()->prepare('CREATE TABLE IF NOT EXISTS contact_messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            message TEXT NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
        db()->prepare('INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)')
            ->execute([$name, $email, $msg]);
    } catch (Throwable $e) {
        // Even if the DB write fails, try to send the email
    }

    // Build the email
    $subject = 'New contact from ' . $name . ' — Azre International website';
    $from_addr = $sales_email; // e.g. sales@azreinternationalgy.com
    $domain = parse_url(AZRE_BASE_URL, PHP_URL_HOST) ?: 'azreinternationalgy.com';
    // Use a stable Reply-To via the customer's email; From is the store mailbox.
    $reply_to = $email;
    $safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $safe_msg_html = nl2br(htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'));

    $body_html = <<<HTML
<!doctype html>
<html><body style="font-family:system-ui,-apple-system,Segoe UI,Roboto,sans-serif;color:#1a2540;max-width:600px;margin:0 auto;padding:20px">
  <div style="background:linear-gradient(135deg,#1a2540,#3a2c5a);color:#fff;padding:20px 24px;border-radius:12px 12px 0 0">
    <h2 style="margin:0;font-size:18px">New contact form submission</h2>
    <p style="margin:4px 0 0;font-size:13px;opacity:.85">via www.azreinternationalgy.com</p>
  </div>
  <div style="background:#f7f7fb;padding:20px 24px;border:1px solid #e3e3ec;border-top:0;border-radius:0 0 12px 12px">
    <table cellpadding="6" style="font-size:14px">
      <tr><td style="color:#666;width:90px">Name</td><td><strong>{$safe_name}</strong></td></tr>
      <tr><td style="color:#666">Email</td><td><a href="mailto:{$safe_email}">{$safe_email}</a></td></tr>
    </table>
    <hr style="border:0;border-top:1px solid #e3e3ec;margin:16px 0">
    <div style="font-size:15px;line-height:1.6;white-space:pre-wrap">{$safe_msg_html}</div>
  </div>
  <p style="font-size:11px;color:#888;margin-top:16px">
    Reply directly to this email to respond to the customer. The customer's email is set as the Reply-To header.
  </p>
</body></html>
HTML;

    $boundary = md5(uniqid((string)time(), true));
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: Azre International Website <' . $from_addr . '>';
    $headers[] = 'Reply-To: ' . $safe_name . ' <' . $reply_to . '>';
    $headers[] = 'X-Mailer: PHP/' . PHP_VERSION;
    $headers[] = 'X-Priority: 1';
    // Some spam filters look for these
    $headers[] = 'List-Unsubscribe: <mailto:' . $from_addr . '?subject=unsubscribe>';

    $header_str = implode("\r\n", $headers);
    $envelope_from = '-f' . $from_addr;

    $sent = @mail($from_addr, $subject, $body_html, $header_str, $envelope_from);
    if (!$sent) {
        $mail_error = error_get_last()['message'] ?? 'mail() returned false (sendmail may not be configured)';
    }

    old_clear();
}
?>
<section class="container narrow section">
  <header class="page-head"><h1>Contact</h1></header>
  <div class="prose">
    <p>Sales: <strong><?= e($sales_email) ?></strong></p>
    <p>Phone: <strong><a href="tel:<?= e(str_replace(' ', '', AZRE_PHONE)) ?>"><?= e(AZRE_PHONE) ?></a></strong> (WhatsApp &amp; calls)</p>
    <p>Need a quote, a fleet price, or a hard-to-find product? Send us a note and we'll reply within one business day.</p>
  </div>

  <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $sent): ?>
    <div class="card">
      <h3>Thanks — we got it.</h3>
      <p>Your message was sent to <strong><?= e($sales_email) ?></strong>. We’ll get back to you within one business day.</p>
    </div>
  <?php elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && !$sent): ?>
    <div class="flash flash-error">
      <strong>Message saved, but email could not be sent.</strong><br>
      We stored your message in our inbox and will follow up, but the mail server returned an error:
      <code style="display:block;margin-top:.5rem;padding:.5rem;background:rgba(0,0,0,.2);border-radius:6px"><?= e($mail_error ?? 'unknown') ?></code>
      You can also email us directly at <a href="mailto:<?= e($sales_email) ?>"><?= e($sales_email) ?></a>.
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
