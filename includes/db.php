<?php
/**
 * Database connection (PDO).
 */
if (!defined('AZRE_BOOTSTRAP')) {
    http_response_code(403);
    exit('Forbidden');
}

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        AZRE_DB_HOST,
        AZRE_DB_PORT,
        AZRE_DB_NAME,
        AZRE_DB_CHARSET
    );
    try {
        $pdo = new PDO($dsn, AZRE_DB_USER, AZRE_DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        if (defined('AZRE_DEBUG') && AZRE_DEBUG) {
            exit('DB connect failed: ' . htmlspecialchars($e->getMessage()));
        }
        exit('Database temporarily unavailable. Please try again shortly.');
    }

    // Idempotent schema migrations applied on first DB use per process.
    // Safe to run repeatedly; each statement is a no-op once applied.
    azre_apply_migrations($pdo);

    return $pdo;
}

/**
 * Idempotent migrations — adds columns / indexes that older databases
 * may be missing. Safe to run on every request; each step is a no-op
 * once the schema is up to date.
 */
function azre_apply_migrations(PDO $pdo): void
{
    static $done = false;
    if ($done) return;
    $done = true;

    // users.phone — needed by checkout/guest flows (added 2026-06-27)
    try {
        $has = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
        if (!$has) {
            $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(40) NULL AFTER email");
        }
    } catch (Throwable $e) {
        // Don't break the request — log silently in debug mode
        if (defined('AZRE_DEBUG') && AZRE_DEBUG) {
            error_log('azre migration users.phone failed: ' . $e->getMessage());
        }
    }
}
