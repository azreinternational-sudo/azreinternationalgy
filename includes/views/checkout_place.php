<?php
/** Checkout submit handler — creates order, sends admin email, clears cart. */
auth_require_login('/register?next=/checkout');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/checkout');
}
if (!csrf_check()) {
    flash_set('error', 'Invalid form submission. Please try again.');
    redirect('/checkout');
}

$user = auth_user();
$items = cart_items_with_products();

if (empty($items)) {
    flash_set('info', 'Your cart is empty.');
    redirect('/cart');
}

// Validate inputs
$phone      = trim((string)($_POST['customer_phone'] ?? ''));
$address    = trim((string)($_POST['ship_address']    ?? ''));
$city       = trim((string)($_POST['ship_city']       ?? ''));
$region     = trim((string)($_POST['ship_region']     ?? ''));
$notes      = trim((string)($_POST['notes']           ?? ''));

// Fall back to phone from users record if blank on form
if ($phone === '') $phone = trim((string)($user['phone'] ?? ''));

if (strlen($phone) < 6) {
    flash_set('error', 'Please enter a valid phone number so we can confirm your order.');
    redirect('/checkout');
}
if ($address === '') {
    flash_set('error', 'Please enter a delivery address.');
    redirect('/checkout');
}

// Compose full ship address (street + city + region)
$ship_parts = array_filter([$address, $city, $region]);
$ship_full = implode(', ', $ship_parts);

// Calculate totals
$subtotal = 0.0;
foreach ($items as $it) $subtotal += $it['subtotal'];
$shipping = 0.0;
$tax      = 0.0;
$total    = $subtotal + $shipping + $tax;

$pdo = db();

try {
    $pdo->beginTransaction();

    // Insert order
    $ins = $pdo->prepare('INSERT INTO orders
        (user_id, status, subtotal, shipping, tax, total, customer_name, customer_email, customer_phone, ship_address, notes, created_at)
        VALUES (?, "pending", ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
    $ins->execute([
        (int)$user['id'],
        $subtotal, $shipping, $tax, $total,
        $user['name'], $user['email'], $phone,
        $ship_full, $notes
    ]);
    $order_id = (int)$pdo->lastInsertId();

    // Insert order items
    $oi = $pdo->prepare('INSERT INTO order_items (order_id, product_id, sku, name, qty, price) VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($items as $it) {
        $oi->execute([
            $order_id,
            !empty($it['id']) ? (int)$it['id'] : null,
            (string)($it['sku']  ?? ''),
            (string)($it['name'] ?? ''),
            (int)($it['qty']     ?? 1),
            (float)($it['price'] ?? 0),
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash_set('error', 'Could not save your order: ' . $e->getMessage());
    redirect('/checkout');
}

// Send admin email (best-effort — don't fail the order if mail() returns false)
try {
    $admin_to = 'azreinternational@gmail.com';
    $from_addr = 'sales@azreinternationalgy.com';
    $from_name = AZRE_NAME;
    $subject = sprintf('New order AZ-%05d from %s', $order_id, $user['name']);

    // HTML body
    $rows_html = '';
    foreach ($items as $it) {
        $rows_html .= '<tr>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #eee">' . e($it['name']) . '<br><span style="color:#888;font-size:12px">' . e($it['brand']) . ' · ' . e($it['sku'] ?? '') . '</span></td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:center">' . (int)$it['qty'] . '</td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right">' . e(money((float)$it['price'])) . '</td>'
            . '<td style="padding:6px 8px;border-bottom:1px solid #eee;text-align:right">' . e(money((float)$it['subtotal'])) . '</td>'
            . '</tr>';
    }
    $html = '
    <div style="font-family:Inter,system-ui,Arial,sans-serif;max-width:680px;margin:0 auto;color:#222">
      <div style="background:linear-gradient(135deg,#ffb347,#f76b00);padding:18px 22px;color:#1a0f00;border-radius:8px 8px 0 0">
        <h2 style="margin:0;font-size:20px">New order received</h2>
        <div style="font-size:13px;margin-top:4px">Order <strong>AZ-' . str_pad((string)$order_id, 5, '0', STR_PAD_LEFT) . '</strong> from <strong>' . e($user['name']) . '</strong></div>
      </div>
      <div style="padding:18px 22px;border:1px solid #eee;border-top:0;border-radius:0 0 8px 8px">
        <h3 style="margin:0 0 10px;font-size:15px">Customer</h3>
        <table style="font-size:13px;border-collapse:collapse">
          <tr><td style="padding:3px 8px 3px 0;color:#666">Name</td><td><strong>' . e($user['name']) . '</strong></td></tr>
          <tr><td style="padding:3px 8px 3px 0;color:#666">Phone</td><td><strong>' . e($phone) . '</strong></td></tr>
          <tr><td style="padding:3px 8px 3px 0;color:#666">Email</td><td><a href="mailto:' . e($user['email']) . '">' . e($user['email']) . '</a></td></tr>
          <tr><td style="padding:3px 8px 3px 0;color:#666">Address</td><td>' . e($ship_full) . '</td></tr>
          ' . ($notes ? '<tr><td style="padding:3px 8px 3px 0;color:#666">Notes</td><td>' . nl2br(e($notes)) . '</td></tr>' : '') . '
        </table>
        <h3 style="margin:18px 0 8px;font-size:15px">Items</h3>
        <table style="width:100%;border-collapse:collapse;font-size:13px">
          <thead>
            <tr style="background:#fafafa">
              <th style="padding:8px;text-align:left">Product</th>
              <th style="padding:8px;text-align:center">Qty</th>
              <th style="padding:8px;text-align:right">Price</th>
              <th style="padding:8px;text-align:right">Subtotal</th>
            </tr>
          </thead>
          <tbody>' . $rows_html . '</tbody>
        </table>
        <table style="width:100%;margin-top:10px;font-size:13px">
          <tr><td style="padding:3px 8px 3px 0;color:#666;text-align:right">Subtotal</td><td style="text-align:right;width:120px"><strong>' . e(money($subtotal)) . '</strong></td></tr>
          <tr><td style="padding:3px 8px 3px 0;color:#666;text-align:right">Shipping</td><td style="text-align:right">' . e(money($shipping)) . '</td></tr>
          <tr><td style="padding:3px 8px 3px 0;color:#666;text-align:right"><strong>Total</strong></td><td style="text-align:right"><strong style="font-size:16px">' . e(money($total)) . '</strong></td></tr>
        </table>
        <p style="margin:18px 0 0;font-size:12px;color:#888">
          Submitted ' . e(date('Y-m-d H:i:s')) . ' from ' . e(AZRE_DOMAIN) . '
        </p>
      </div>
    </div>';

    // Plain text fallback
    $txt_lines = [
        "New order AZ-" . str_pad((string)$order_id, 5, '0', STR_PAD_LEFT) . " from " . $user['name'],
        "",
        "Phone:   $phone",
        "Email:   {$user['email']}",
        "Address: $ship_full",
        $notes ? "Notes:   $notes" : "",
        "",
        "Items:",
    ];
    foreach ($items as $it) {
        $txt_lines[] = sprintf("  - %s (%s) x%d  %s  =  %s",
            $it['name'], $it['sku'] ?? '', $it['qty'], money((float)$it['price']), money((float)$it['subtotal']));
    }
    $txt_lines[] = "";
    $txt_lines[] = "Subtotal: " . money($subtotal);
    $txt_lines[] = "Shipping: " . money($shipping);
    $txt_lines[] = "Total:    " . money($total);
    $txt = implode("\n", array_filter($txt_lines));

    $boundary = 'azre_' . md5((string)microtime(true));
    $headers = [];
    $headers[] = 'From: ' . sprintf('%s <%s>', $from_name, $from_addr);
    $headers[] = 'Reply-To: ' . $user['name'] . ' <' . $user['email'] . '>';
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
    $body = "--$boundary\r\n"
          . "Content-Type: text/plain; charset=utf-8\r\n\r\n"
          . $txt . "\r\n\r\n"
          . "--$boundary\r\n"
          . "Content-Type: text/html; charset=utf-8\r\n\r\n"
          . $html . "\r\n\r\n"
          . "--$boundary--";

    @mail($admin_to, $subject, $body, implode("\r\n", $headers));
} catch (Throwable $e) {
    // Swallow email errors — order is already saved
}

// Clear cart
cart_set([]);

// Redirect to thanks page
redirect('/checkout/thanks/' . $order_id);
