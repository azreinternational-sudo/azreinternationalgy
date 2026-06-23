<?php
/**
 * Seed database from the parsed catalog JSON.
 *
 * Usage:
 *   php database/seed.php
 *
 * Idempotent: skips products that already exist (by sku).
 * Also creates a default admin user if no admin exists yet.
 */
define('AZRE_BOOTSTRAP', true);
require dirname(__DIR__) . '/includes/config.php';
require dirname(__DIR__) . '/includes/db.php';

$json = __DIR__ . '/catalog.json';
if (!file_exists($json)) {
    fwrite(STDERR, "Missing catalog.json — run parse_pdf.py first.\n");
    exit(1);
}

$products = json_decode(file_get_contents($json), true);
if (!is_array($products)) {
    fwrite(STDERR, "catalog.json is not valid JSON.\n");
    exit(1);
}

$pdo = db();
echo "Seeding " . count($products) . " products...\n";

// Ensure categories exist and get slug -> id map
$cats = [];
foreach ($products as $p) {
    $cat = $p['category'] ?? 'Other';
    if (!isset($cats[$cat])) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cat));
        $stmt = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
        $stmt->execute([$slug]);
        $id = $stmt->fetchColumn();
        if (!$id) {
            $ins = $pdo->prepare('INSERT INTO categories (slug, name, sort_order) VALUES (?, ?, ?)');
            $ins->execute([$slug, $cat, count($cats) * 10]);
            $id = (int)$pdo->lastInsertId();
            echo "  + category: $cat\n";
        }
        $cats[$cat] = (int)$id;
    }
}

// Insert products
$selSku = $pdo->prepare('SELECT id FROM products WHERE sku = ?');
$insProd = $pdo->prepare(
    'INSERT INTO products
        (slug, sku, name, description, brand, category_id, price, stock, image, active, featured, viscosity, form, size_label)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, ?)'
);
$count = 0; $updated = 0;
foreach ($products as $p) {
    $selSku->execute([$p['part_no']]);
    if ($selSku->fetchColumn()) {
        // Update existing
        $upd = $pdo->prepare('UPDATE products SET name=?, description=?, brand=?, category_id=?, price=?, stock=?, viscosity=?, form=?, size_label=?, active=1 WHERE sku=?');
        $upd->execute([
            $p['description'],
            $p['description'],
            $p['brand'],
            $cats[$p['category']] ?? null,
            $p['price'],
            $p['stock'] ?? 100,
            $p['viscosity'],
            $p['form'],
            $p['size'],
            $p['part_no'],
        ]);
        $updated++;
        continue;
    }
    $insProd->execute([
        $p['slug'],
        $p['part_no'],
        $p['description'],
        $p['description'],
        $p['brand'],
        $cats[$p['category']] ?? null,
        $p['price'],
        $p['stock'] ?? 100,
        $p['image'],
        !empty($p['featured']) ? 1 : 0,
        $p['viscosity'],
        $p['form'],
        $p['size'],
    ]);
    $count++;
}
echo "Inserted: $count, updated: $updated\n";

// Default admin if no admin user exists yet
$hasAdmin = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role = "admin"')->fetchColumn();
if (!$hasAdmin) {
    $email = 'admin@azreinternationalgy.com';
    $pass = 'admin1234';
    $hash = password_hash($pass, PASSWORD_BCRYPT);
    $pdo->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, "admin", NOW())')
        ->execute(['Azre Admin', $email, $hash]);
    echo "Created default admin user: $email / $pass\n";
    echo "  >>> CHANGE THIS PASSWORD IMMEDIATELY AFTER FIRST LOGIN <<<\n";
}

echo "Done.\n";
