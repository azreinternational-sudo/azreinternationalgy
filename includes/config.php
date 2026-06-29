<?php
/**
 * Azre International — Configuration
 *
 * Copy this file to includes/config.local.php on Hostinger and fill in real
 * DB credentials. The local-dev fallback uses MySQL via the DOCKER_MYSQL
 * environment if present, otherwise localhost.
 */

if (!defined('AZRE_BOOTSTRAP')) {
    http_response_code(403);
    exit('Forbidden');
}

define('AZRE_NAME', 'Azre International');
define('AZRE_TAGLINE', 'Auto parts & lubricants for trucks, autos, bus, marine & industry');
define('AZRE_DOMAIN', 'www.azreinternationalgy.com');
define('AZRE_EMAIL', 'sales@azreinternationalgy.com');
define('AZRE_PHONE', '+1-592-704-9830'); // Operator-set sales line (Guyana, dialed via +1 gateway)
define('AZRE_CURRENCY', 'GYD'); // Guyana Dollar (operator can change)
define('AZRE_CURRENCY_SYMBOL', 'G$');

// Base URL detection (Hostinger-friendly). Override by setting AZRE_BASE_URL.
if (getenv('AZRE_BASE_URL')) {
    define('AZRE_BASE_URL', rtrim(getenv('AZRE_BASE_URL'), '/'));
} else {
    $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('AZRE_BASE_URL', $proto . '://' . $host);
}

// Upload directory (product images) — writable by PHP.
define('AZRE_UPLOAD_DIR', dirname(__DIR__) . '/uploads');
define('AZRE_UPLOAD_URL', '/uploads');

// Database credentials
// Production: load from includes/config.local.php (gitignored).
$localCfg = dirname(__DIR__) . '/includes/config.local.php';
if (file_exists($localCfg)) {
    require $localCfg;
}

if (!defined('AZRE_DB_HOST')) define('AZRE_DB_HOST', getenv('AZRE_DB_HOST') ?: '127.0.0.1');
if (!defined('AZRE_DB_PORT')) define('AZRE_DB_PORT', getenv('AZRE_DB_PORT') ?: '3306');
if (!defined('AZRE_DB_NAME')) define('AZRE_DB_NAME', getenv('AZRE_DB_NAME') ?: 'azre_international');
if (!defined('AZRE_DB_USER')) define('AZRE_DB_USER', getenv('AZRE_DB_USER') ?: 'root');
if (!defined('AZRE_DB_PASS')) define('AZRE_DB_PASS', getenv('AZRE_DB_PASS') ?: 'root');
if (!defined('AZRE_DB_CHARSET')) define('AZRE_DB_CHARSET', 'utf8mb4');

// Session name
define('AZRE_SESSION_NAME', 'azre_session');

// Image limits
define('AZRE_MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5 MB
define('AZRE_ALLOWED_IMAGE_EXT', ['jpg', 'jpeg', 'png', 'webp', 'gif']);

// Timezone
date_default_timezone_set('America/Guyana');
