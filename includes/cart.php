<?php
/**
 * Cart helpers — guest cart in session, persistent cart in DB for logged-in users.
 */
if (!defined('AZRE_BOOTSTRAP')) {
    http_response_code(403);
    exit('Forbidden');
}

function cart_get(): array
{
    $u = auth_user();
    if ($u) {
        $stmt = db()->prepare('SELECT product_id, qty FROM cart_items WHERE user_id = ?');
        $stmt->execute([$u['id']]);
        $items = [];
        foreach ($stmt->fetchAll() as $row) {
            $items[(int)$row['product_id']] = (int)$row['qty'];
        }
        return $items;
    }
    return $_SESSION['cart'] ?? [];
}

function cart_set(array $items): void
{
    $u = auth_user();
    if ($u) {
        db()->beginTransaction();
        db()->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$u['id']]);
        $ins = db()->prepare('INSERT INTO cart_items (user_id, product_id, qty, added_at) VALUES (?, ?, ?, NOW())');
        foreach ($items as $pid => $qty) {
            if ($qty > 0) {
                $ins->execute([$u['id'], (int)$pid, (int)$qty]);
            }
        }
        db()->commit();
    } else {
        $_SESSION['cart'] = $items;
    }
}

function cart_add(int $productId, int $qty = 1): void
{
    $items = cart_get();
    $items[$productId] = ($items[$productId] ?? 0) + max(1, $qty);
    cart_set($items);
}

function cart_update(int $productId, int $qty): void
{
    $items = cart_get();
    if ($qty <= 0) {
        unset($items[$productId]);
    } else {
        $items[$productId] = $qty;
    }
    cart_set($items);
}

function cart_remove(int $productId): void
{
    cart_update($productId, 0);
}

function cart_count(): int
{
    return array_sum(cart_get());
}

function cart_total(): float
{
    $items = cart_get();
    if (empty($items)) return 0.0;
    $ids = array_map('intval', array_keys($items));
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, price FROM products WHERE id IN ($place)");
    $stmt->execute($ids);
    $prices = [];
    foreach ($stmt->fetchAll() as $row) {
        $prices[(int)$row['id']] = (float)$row['price'];
    }
    $sum = 0.0;
    foreach ($items as $pid => $qty) {
        if (isset($prices[(int)$pid])) {
            $sum += $prices[(int)$pid] * (int)$qty;
        }
    }
    return $sum;
}

function cart_merge_guest_to_user(int $userId): void
{
    $guest = $_SESSION['cart'] ?? [];
    if (empty($guest)) return;
    $stmt = db()->prepare('SELECT product_id, qty FROM cart_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    $existing = [];
    foreach ($stmt->fetchAll() as $row) {
        $existing[(int)$row['product_id']] = (int)$row['qty'];
    }
    $merged = $existing;
    foreach ($guest as $pid => $qty) {
        $merged[(int)$pid] = ($merged[(int)$pid] ?? 0) + (int)$qty;
    }
    db()->beginTransaction();
    db()->prepare('DELETE FROM cart_items WHERE user_id = ?')->execute([$userId]);
    $ins = db()->prepare('INSERT INTO cart_items (user_id, product_id, qty, added_at) VALUES (?, ?, ?, NOW())');
    foreach ($merged as $pid => $qty) {
        if ($qty > 0) $ins->execute([$userId, (int)$pid, (int)$qty]);
    }
    db()->commit();
    unset($_SESSION['cart']);
}

function cart_items_with_products(): array
{
    $items = cart_get();
    if (empty($items)) return [];
    $ids = array_map('intval', array_keys($items));
    $place = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare("SELECT id, slug, sku, name, brand, price, image, stock FROM products WHERE id IN ($place) AND active = 1");
    $stmt->execute($ids);
    $rows = $stmt->fetchAll();
    $out = [];
    foreach ($rows as $row) {
        $pid = (int)$row['id'];
        $row['qty'] = $items[$pid] ?? 0;
        $row['subtotal'] = $row['qty'] * (float)$row['price'];
        $out[] = $row;
    }
    return $out;
}
