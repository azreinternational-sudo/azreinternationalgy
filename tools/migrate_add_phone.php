<?php
/**
 * One-time migration: add phone column to users table.
 * Visit /tools/migrate_add_phone.php in your browser once, then delete
 * this file (or keep it — it's idempotent).
 */
define('AZRE_BOOTSTRAP', true);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/config.local.php';
require __DIR__ . '/../includes/db.php';

header('Content-Type: text/plain; charset=utf-8');

$pdo = db();

// Check if column already exists
$has = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
if ($has) {
    echo "OK: users.phone column already exists. Nothing to do.\n";
    echo "Description: " . ($has['Type'] ?? '') . " " . ($has['Null'] ?? '') . "\n";
    exit;
}

// Add it
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(40) NULL AFTER email");
    echo "DONE: users.phone column added.\n";
    echo "You can now delete this file: /tools/migrate_add_phone.php\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
