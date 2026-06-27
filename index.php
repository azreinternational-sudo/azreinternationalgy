<?php
/**
 * Azre International — Front controller & router
 */
define('AZRE_BOOTSTRAP', true);
require __DIR__ . '/includes/config.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/cart.php';

auth_start();

// URL parse
$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$uri = rtrim($uri, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Simple router
$routes = [
    // public
    '/'                       => ['page' => 'home'],
    '/shop'                   => ['page' => 'shop'],
    '/category'               => ['page' => 'category'],
    '/product'                => ['page' => 'product'],
    '/search'                 => ['page' => 'search'],
    '/cart'                   => ['page' => 'cart', 'method' => ['GET']],
    '/cart/add'               => ['page' => 'cart_add', 'method' => ['POST']],
    '/cart/update'            => ['page' => 'cart_update', 'method' => ['POST']],
    '/cart/remove'            => ['page' => 'cart_remove', 'method' => ['POST']],
    '/checkout'               => ['page' => 'checkout', 'method' => ['GET']],
    '/checkout/place'         => ['page' => 'checkout_place', 'method' => ['POST']],
    '/checkout/thanks/{id}'   => ['page' => 'checkout_thanks', 'method' => ['GET']],
    '/login'                  => ['page' => 'login', 'method' => ['GET','POST']],
    '/register'               => ['page' => 'register', 'method' => ['GET','POST']],
    '/logout'                 => ['page' => 'logout', 'method' => ['GET','POST']],
    '/account'                => ['page' => 'account', 'method' => ['GET','POST']],
    '/account/password'       => ['page' => 'account_password', 'method' => ['GET','POST']],
    '/account/orders'         => ['page' => 'account_orders', 'method' => ['GET']],
    '/account/order/{id}'     => ['page' => 'account_order_detail', 'method' => ['GET']],
    '/about'                  => ['page' => 'about'],
    '/contact'                => ['page' => 'contact', 'method' => ['GET','POST']],
    '/admin'                  => ['page' => 'admin/dashboard'],
    '/admin/login'            => ['page' => 'admin/login', 'method' => ['GET','POST']],
    '/admin/logout'           => ['page' => 'admin/logout'],
    '/admin/products'         => ['page' => 'admin/products'],
    '/admin/product/new'      => ['page' => 'admin/product_form', 'method' => ['GET','POST']],
    '/admin/product/edit'     => ['page' => 'admin/product_form', 'method' => ['GET','POST']],
    '/admin/product/delete'   => ['page' => 'admin/product_delete', 'method' => ['POST']],
    '/admin/categories'       => ['page' => 'admin/categories', 'method' => ['GET','POST']],
    '/admin/orders'           => ['page' => 'admin/orders', 'method' => ['GET']],
    '/admin/order/{id}'       => ['page' => 'admin/order_detail', 'method' => ['GET','POST']],
];

// Match route: exact match first, then parameter match
$matched = null;
$params = [];
foreach ($routes as $path => $route) {
    if ($path === $uri) {
        $matched = $route;
        break;
    }
}
// Pattern match for paths like /product/{slug}, /category/{slug}
if (!$matched) {
    foreach ($routes as $path => $route) {
        $pattern = preg_replace('#\{[^}]+\}#', '([^/]+)', $path);
        if ($pattern !== $path && preg_match('#^' . $pattern . '$#', $uri, $m)) {
            $matched = $route;
            array_shift($m);
            $params = $m;
            break;
        }
    }
}

// Also support /product/{slug}, /category/{slug} (not declared explicitly)
if (!$matched) {
    if (preg_match('#^/product/([^/]+)$#', $uri, $m)) {
        $matched = ['page' => 'product'];
        $params = [$m[1]];
    } elseif (preg_match('#^/category/([^/]+)$#', $uri, $m)) {
        $matched = ['page' => 'category'];
        $params = [$m[1]];
    } elseif (preg_match('#^/admin/product/(\d+)/edit$#', $uri, $m)) {
        $matched = ['page' => 'admin/product_form'];
        $params = [$m[1]];
    } elseif (preg_match('#^/admin/product/(\d+)/delete$#', $uri, $m)) {
        $matched = ['page' => 'admin/product_delete'];
        $params = [$m[1]];
    }
}

if (!$matched) {
    http_response_code(404);
    $page_title = 'Page not found';
    require __DIR__ . '/includes/views/header.php';
    echo '<section class="container narrow"><div class="card"><h1>404 — Not found</h1><p>The page you’re looking for doesn’t exist.</p><p><a class="btn btn-primary" href="' . e(url('/')) . '">Back to home</a></p></div></section>';
    require __DIR__ . '/includes/views/footer.php';
    exit;
}

// Method guard
if (!empty($matched['method']) && !in_array($method, $matched['method'], true)) {
    http_response_code(405);
    exit('Method not allowed');
}

// Dispatch
$page = $matched['page'];
$page_file = __DIR__ . '/includes/views/' . $page . '.php';

if (!file_exists($page_file)) {
    http_response_code(500);
    exit("Missing view: $page");
}

$page_title = $page_title ?? AZRE_NAME;
$body_class = $body_class ?? '';

// Page-level guards
if (str_starts_with($page, 'admin/') && $page !== 'admin/login') {
    auth_require_admin('/admin/login');
}

// Buffer page content
ob_start();
try {
    (function () use ($page_file, $params) {
        $__params = $params;
        extract($GLOBALS, EXTR_SKIP);
        // Common vars for views
        $__page = function ($name, $vars = []) use ($__params) {
            extract($vars, EXTR_SKIP);
            $__view_file = __DIR__ . '/includes/views/' . $name . '.php';
            if (file_exists($__view_file)) require $__view_file;
        };
        require $page_file;
    })();
} catch (Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    http_response_code(500);
    if (defined('AZRE_DEBUG') && AZRE_DEBUG) {
        echo '<pre>' . htmlspecialchars($e->getMessage() . "\n\n" . $e->getTraceAsString()) . '</pre>';
    } else {
        echo 'Server error.';
    }
    exit;
}
$content = ob_get_clean();

// Wrap with header/footer
$cart_count = cart_count();
$current_user = auth_user();
require __DIR__ . '/includes/views/header.php';
echo $content;
require __DIR__ . '/includes/views/footer.php';
