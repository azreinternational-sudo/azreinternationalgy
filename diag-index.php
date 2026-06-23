<?php
// Azre International — diagnostic index.php
// Replaces the real index.php temporarily to isolate the 500 error.
// Rename or delete this file once we've fixed the issue.

header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP diagnostic ===\n";
echo "PHP version:     " . PHP_VERSION . "\n";
echo "Server:          " . ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown') . "\n";
echo "Document root:   " . ($_SERVER['DOCUMENT_ROOT'] ?? 'unknown') . "\n";
echo "Script:          " . __FILE__ . "\n";
echo "Time:            " . date('Y-m-d H:i:s') . "\n";
echo "Timezone:        " . date_default_timezone_get() . "\n";

echo "\n=== Required extensions ===\n";
$required = ['pdo', 'pdo_mysql', 'mbstring', 'json', 'session', 'filter'];
foreach ($required as $ext) {
    $ok = extension_loaded($ext) ? 'OK' : 'MISSING';
    echo str_pad($ext, 14) . $ok . "\n";
}

echo "\n=== File system checks ===\n";
echo "uploads/ exists:   " . (is_dir(__DIR__ . '/uploads') ? 'YES' : 'NO') . "\n";
echo "uploads/ writable: " . (is_writable(__DIR__ . '/uploads') ? 'YES' : 'NO') . "\n";
echo "tmp writable:      " . (is_writable(sys_get_temp_dir()) ? 'YES' : 'NO') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";

echo "\n=== .user.ini (active) ===\n";
$ui = __DIR__ . '/.user.ini';
echo "exists:    " . (file_exists($ui) ? 'YES' : 'NO') . "\n";
if (file_exists($ui)) {
    echo "contents:\n";
    foreach (explode("\n", file_get_contents($ui)) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === ';') continue;
        echo "  " . $line . "\n";
    }
}

echo "\n=== .htaccess (active) ===\n";
$ht = __DIR__ . '/.htaccess';
echo "exists: " . (file_exists($ht) ? 'YES' : 'NO') . "\n";

echo "\n=== DB connection (config.php) ===\n";
try {
    // Read the constants without executing the full app
    require __DIR__ . '/includes/config.php';
    echo "Host: " . AZRE_DB_HOST . "\n";
    echo "Port: " . AZRE_DB_PORT . "\n";
    echo "Name: " . AZRE_DB_NAME . "\n";
    echo "User: " . AZRE_DB_USER . "\n";
    echo "Pass: " . (defined('AZRE_DB_PASS') ? str_repeat('*', strlen(AZRE_DB_PASS)) : 'undefined') . "\n";
    echo "Charset: " . AZRE_DB_CHARSET . "\n";
    echo "\n";
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', AZRE_DB_HOST, AZRE_DB_PORT, AZRE_DB_NAME, AZRE_DB_CHARSET);
    $pdo = new PDO($dsn, AZRE_DB_USER, AZRE_DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "DB connect: OK\n";
    $count = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    echo "products row count: $count\n";
} catch (Throwable $e) {
    echo "DB ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== session ===\n";
try {
    $ok = session_start();
    echo "session_start: " . ($ok ? 'OK' : 'FAIL') . "\n";
    $_SESSION['test'] = 'hello';
    echo "session write: " . (($_SESSION['test'] ?? '') === 'hello' ? 'OK' : 'FAIL') . "\n";
} catch (Throwable $e) {
    echo "SESSION ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== END ===\n";
