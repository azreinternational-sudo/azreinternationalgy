<?php
/**
 * Router for PHP built-in dev server (php -S).
 * Mimics the .htaccess rewrite rules.
 *
 * Usage:
 *   php -S 127.0.0.1:8000 -t public public/router.php
 */
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $uri;
if ($uri !== '/' && file_exists($file) && !is_dir($file)) {
    return false; // serve the file directly
}
// Everything else goes through index.php
require __DIR__ . '/index.php';
