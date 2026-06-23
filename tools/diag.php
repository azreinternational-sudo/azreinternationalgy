<?php
// Azre International — diagnostic index.php (v2)
// Bypasses bootstrap guard, loads full app, surfaces real error.

define('AZRE_BOOTSTRAP', true);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP diagnostic v2 ===\n";
echo "PHP version:     " . PHP_VERSION . "\n";
echo "Server:          " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "Document root:   " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "Time:            " . date('Y-m-d H:i:s') . "\n";

echo "\n=== .user.ini (active values) ===\n";
foreach (['display_errors', 'log_errors', 'error_log', 'memory_limit', 'max_execution_time'] as $k) {
    echo "$k = " . ini_get($k) . "\n";
}

echo "\n=== Load config.php ===\n";
try {
    require __DIR__ . '/../includes/config.php';
    echo "config.php: loaded OK\n";
    echo "  DB host=" . AZRE_DB_HOST . " port=" . AZRE_DB_PORT . " name=" . AZRE_DB_NAME . " user=" . AZRE_DB_USER . "\n";
} catch (Throwable $e) {
    echo "config.php ERROR: " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}

echo "\n=== DB connection ===\n";
try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', AZRE_DB_HOST, AZRE_DB_PORT, AZRE_DB_NAME, AZRE_DB_CHARSET),
        AZRE_DB_USER, AZRE_DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $count = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    echo "DB connect: OK, products row count: $count\n";
} catch (Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
    echo "  code: " . $e->getCode() . "\n";
}

echo "\n=== Load db + helpers + auth + cart ===\n";
foreach (['db.php', 'helpers.php', 'auth.php', 'cart.php'] as $f) {
    try {
        require __DIR__ . '/../includes/' . $f;
        echo "$f: loaded OK\n";
    } catch (Throwable $e) {
        echo "$f ERROR: " . $e->getMessage() . "\n";
        echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
        exit(1);
    }
}

echo "\n=== auth_start + session ===\n";
try {
    auth_start();
    echo "session started OK, id=" . session_id() . "\n";
} catch (Throwable $e) {
    echo "SESSION ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Quick query (mimic what home.php does) ===\n";
try {
    $stmt = $pdo->query('SELECT id, name, price FROM products WHERE active = 1 ORDER BY id DESC LIMIT 3');
    $rows = $stmt->fetchAll();
    foreach ($rows as $r) {
        echo "  #" . $r['id'] . " " . $r['name'] . " — GY " . number_format((float)$r['price'], 2) . "\n";
    }
    echo "home query: OK\n";
} catch (Throwable $e) {
    echo "home query ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== settings() helper (called by helpers / views) ===\n";
try {
    $email = setting('store_email', '(not set)');
    echo "store_email: $email\n";
} catch (Throwable $e) {
    echo "settings() ERROR: " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== categories_list() helper ===\n";
try {
    $cats = categories_list();
    echo "categories count: " . count($cats) . "\n";
} catch (Throwable $e) {
    echo "categories_list ERROR: " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=== Try rendering home view ===\n";
try {
    ob_start();
    $page = 'home';
    $page_file = __DIR__ . '/../includes/views/home.php';
    $page_title = 'Home';
    require $page_file;
    $content = ob_get_clean();
    echo "home view rendered OK, " . strlen($content) . " bytes\n";
} catch (Throwable $e) {
    echo "home view ERROR: " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== END ===\n";
