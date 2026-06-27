<?php
/**
 * One-time tool: set price = 0 for every product.
 * Visit /tools/zero_prices.php in your browser once, then delete this file.
 *
 * Optional: ?confirm=YES to actually run the update. Without it, the page
 * just shows the current state (dry-run / preview).
 */
define('AZRE_BOOTSTRAP', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/config.local.php';
require __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = db();
$total = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$nonzero = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE price > 0 OR compare_at > 0')->fetchColumn();

echo "Current state:\n";
echo "  Total products:  $total\n";
echo "  Non-zero price:  $nonzero\n\n";

if (($_GET['confirm'] ?? '') !== 'YES') {
    echo "DRY RUN — no changes made.\n";
    echo "To actually zero all prices, visit:\n";
    echo "  /tools/zero_prices.php?confirm=YES\n\n";
    echo "Then delete this file.\n";
    exit;
}

try {
    $pdo->beginTransaction();
    $pdo->exec('UPDATE products SET price = 0, compare_at = 0');
    $affected = $pdo->query('SELECT COUNT(*) FROM products WHERE price = 0')->fetchColumn();
    $pdo->commit();
    echo "DONE: prices zeroed.\n";
    echo "  Products now at \$0.00: $affected / $total\n";
    echo "Delete this file when done: /tools/zero_prices.php\n";
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
