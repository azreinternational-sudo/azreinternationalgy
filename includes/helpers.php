<?php
/**
 * Azre International — Helper functions
 */
if (!defined('AZRE_BOOTSTRAP')) {
    http_response_code(403);
    exit('Forbidden');
}

/** HTML escape */
function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Money formatter */
function money(float $amount, ?string $symbol = null): string
{
    $symbol = $symbol ?? AZRE_CURRENCY_SYMBOL;
    return $symbol . number_format($amount, 2);
}

/** Slugify */
function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

/** Build a URL relative to base */
function url(string $path = ''): string
{
    $base = AZRE_BASE_URL;
    if ($path === '') return $base . '/';
    if ($path[0] !== '/') $path = '/' . $path;
    return $base . $path;
}

/** Build an asset URL */
function asset(string $path): string
{
    if ($path[0] !== '/') $path = '/' . $path;
    return AZRE_BASE_URL . $path;
}

/** Redirect */
function redirect(string $path): void
{
    header('Location: ' . url($path));
    exit;
}

/** Flash message helper */
function flash_set(string $type, string $msg): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pop(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

/** Input old() for re-populating forms */
function old(string $key, $default = ''): string
{
    $val = $_SESSION['_old'][$key] ?? $default;
    return e((string)$val);
}

function old_keep(array $data): void
{
    $_SESSION['_old'] = $data;
}

function old_clear(): void
{
    unset($_SESSION['_old']);
}

/** CSRF token */
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): bool
{
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['_csrf'] ?? '', (string)$sent);
}

/** Image URL with placeholder fallback.
 *  If $img is a real uploaded file, return it.
 *  Otherwise, defer to /product_image.php which generates a branded
 *  SVG placeholder using the product's slug (so we can pull brand,
 *  category, SKU from the DB and color the placeholder accordingly).
 */
function product_image(?string $img, ?array $product = null): string
{
    if ($img && file_exists(AZRE_UPLOAD_DIR . '/' . basename($img))) {
        return asset(AZRE_UPLOAD_URL . '/' . basename($img));
    }
    if ($product && !empty($product['slug'])) {
        return asset('/product_image.php?slug=' . urlencode((string)$product['slug']));
    }
    return asset('/assets/images/placeholder.svg');
}

/** Category list (cached) */
function categories_list(): array
{
    static $cats = null;
    if ($cats !== null) return $cats;
    $stmt = db()->query('SELECT c.id, c.slug, c.name, COUNT(p.id) AS cnt FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.name');
    $cats = $stmt->fetchAll();
    return $cats;
}

/** Brand list (cached) */
function brands_list(): array
{
    static $brands = null;
    if ($brands !== null) return $brands;
    $stmt = db()->query('SELECT brand, COUNT(*) AS cnt FROM products GROUP BY brand ORDER BY brand');
    $brands = $stmt->fetchAll();
    return $brands;
}

/** Get a setting value */
function setting(string $key, ?string $default = null): ?string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT k, v FROM settings')->fetchAll() as $row) {
                $cache[$row['k']] = $row['v'];
            }
        } catch (Throwable $e) {
            // Table may not exist yet
        }
    }
    return $cache[$key] ?? $default;
}

/** Pagination helper */
function paginate(int $page, int $perPage, int $total): array
{
    $pages = max(1, (int)ceil($total / $perPage));
    $page = max(1, min($pages, $page));
    return [
        'page' => $page,
        'pages' => $pages,
        'per_page' => $perPage,
        'total' => $total,
        'offset' => ($page - 1) * $perPage,
    ];
}

/** Render a partial view with extracted variables */
function view(string $name, array $vars = []): void
{
    extract($vars, EXTR_SKIP);
    $path = dirname(__DIR__) . '/includes/views/' . $name . '.php';
    if (!file_exists($path)) {
        echo "<!-- missing view: $name -->";
        return;
    }
    require $path;
}
