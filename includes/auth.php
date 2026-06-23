<?php
/**
 * Authentication / user account helpers
 */
if (!defined('AZRE_BOOTSTRAP')) {
    http_response_code(403);
    exit('Forbidden');
}

function auth_start(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_name(AZRE_SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 60 * 60 * 24 * 14,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

function auth_user(): ?array
{
    if (empty($_SESSION['uid'])) return null;
    static $user = null;
    if ($user !== null && (int)$user['id'] === (int)$_SESSION['uid']) {
        return $user;
    }
    $stmt = db()->prepare('SELECT id, email, name, role, created_at FROM users WHERE id = ? LIMIT 1');
    $stmt->execute([$_SESSION['uid']]);
    $u = $stmt->fetch();
    $user = $u ?: null;
    return $user;
}

function auth_login(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, password_hash FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([trim($email)]);
    $row = $stmt->fetch();
    if (!$row || !password_verify($password, $row['password_hash'])) {
        return false;
    }
    $_SESSION['uid'] = (int)$row['id'];
    // Merge guest cart into DB cart
    cart_merge_guest_to_user((int)$row['id']);
    return true;
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function auth_register(string $name, string $email, string $password): array
{
    $name = trim($name);
    $email = trim(strtolower($email));
    if (strlen($name) < 2) return ['ok' => false, 'msg' => 'Please enter your full name.'];
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'msg' => 'Please enter a valid email address.'];
    if (strlen($password) < 6) return ['ok' => false, 'msg' => 'Password must be at least 6 characters.'];
    $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) return ['ok' => false, 'msg' => 'An account with that email already exists.'];
    $hash = password_hash($password, PASSWORD_BCRYPT);
    $ins = db()->prepare('INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, "customer", NOW())');
    $ins->execute([$name, $email, $hash]);
    $uid = (int)db()->lastInsertId();
    $_SESSION['uid'] = $uid;
    cart_merge_guest_to_user($uid);
    return ['ok' => true];
}

function auth_is_admin(): bool
{
    $u = auth_user();
    return $u && in_array($u['role'], ['admin', 'staff'], true);
}

function auth_require_login(string $redirectTo = '/login'): void
{
    if (!auth_user()) {
        flash_set('info', 'Please sign in to continue.');
        redirect($redirectTo);
    }
}

function auth_require_admin(string $redirectTo = '/login'): void
{
    if (!auth_is_admin()) {
        flash_set('error', 'Admin sign-in required.');
        redirect($redirectTo);
    }
}

function auth_change_password(int $userId, string $current, string $new): array
{
    if (strlen($new) < 6) {
        return ['ok' => false, 'msg' => 'New password must be at least 6 characters.'];
    }
    $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['ok' => false, 'msg' => 'Account not found.'];
    }
    if (!password_verify($current, $row['password_hash'])) {
        return ['ok' => false, 'msg' => 'Current password is incorrect.'];
    }
    $hash = password_hash($new, PASSWORD_BCRYPT);
    db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $userId]);
    return ['ok' => true];
}
