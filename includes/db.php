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
    return $pdo;
}
