<?php
$page_title = 'Shop';
$q = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$brand = trim($_GET['brand'] ?? '');
$form = trim($_GET['form'] ?? '');
$sort = $_GET['sort'] ?? 'new';
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 24;

$where = ['p.active = 1'];
$params = [];
if ($q !== '') {
    $where[] = '(p.name LIKE ? OR p.brand LIKE ? OR p.sku LIKE ?)';
    array_push($params, "%$q%", "%$q%", "%$q%");
}
if ($cat) { $where[] = 'p.category_id = ?'; $params[] = $cat; }
if ($brand !== '') { $where[] = 'p.brand = ?'; $params[] = $brand; }
if ($form !== '') { $where[] = 'p.form = ?'; $params[] = $form; }
$where_sql = implode(' AND ', $where);

$orderBy = match ($sort) {
    'price_asc'  => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    'name'       => 'p.name ASC',
    default      => 'p.id DESC',
};

$count = db()->prepare("SELECT COUNT(*) FROM products p WHERE $where_sql");
$count->execute($params);
$total = (int)$count->fetchColumn();

$pg = paginate($page, $per, $total);
$offset = $pg['offset'];

$sql = "SELECT p.id, p.slug, p.name, p.brand, p.price, p.image, p.form, p.size_label, p.viscosity, p.featured
        FROM products p
        WHERE $where_sql
        ORDER BY $orderBy
        LIMIT $per OFFSET $offset";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$all_cats = categories_list();
$all_brands = brands_list();
$all_forms = db()->query("SELECT DISTINCT form FROM products WHERE form IS NOT NULL AND form <> '' ORDER BY form")->fetchAll(PDO::FETCH_COLUMN);
?>
<section class="container section">
  <header class="page-head">
    <h1>Shop all products</h1>
    <p class="muted"><?= (int)$total ?> product<?= $total === 1 ? '' : 's' ?> available</p>
  </header>

  <div class="shop-layout">
    <aside class="filters">
      <form method="get" action="<?= e(url('/shop')) ?>">
        <div class="filter-block">
          <label class="filter-label">Search</label>
          <input type="search" name="q" value="<?= e($q) ?>" placeholder="Brand, SKU, name…">
        </div>
        <div class="filter-block">
          <label class="filter-label">Category</label>
          <select name="cat">
            <option value="">All categories</option>
            <?php foreach ($all_cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>" <?= $cat === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?> (<?= (int)$c['cnt'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="filter-block">
          <label class="filter-label">Brand</label>
          <select name="brand">
            <option value="">All brands</option>
            <?php foreach ($all_brands as $b): ?>
              <option value="<?= e($b['brand']) ?>" <?= $brand === $b['brand'] ? 'selected' : '' ?>><?= e($b['brand']) ?> (<?= (int)$b['cnt'] ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php if (!empty($all_forms)): ?>
        <div class="filter-block">
          <label class="filter-label">Oil type</label>
          <select name="form">
            <option value="">All types</option>
            <?php foreach ($all_forms as $f): ?>
              <option value="<?= e($f) ?>" <?= $form === $f ? 'selected' : '' ?>><?= e($f) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary btn-block">Apply</button>
        <a class="btn btn-ghost btn-block" href="<?= e(url('/shop')) ?>">Clear</a>
      </form>
    </aside>

    <div class="shop-main">
      <div class="shop-toolbar">
        <label class="sort-label">
          Sort by
          <select name="sort" onchange="window.location.href=updateQuery('sort', this.value)">
            <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>Price ↑</option>
            <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>Price ↓</option>
            <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A→Z</option>
          </select>
        </label>
      </div>

      <?php if (empty($products)): ?>
        <div class="empty-state">
          <h3>No products match your filters</h3>
          <p>Try clearing some filters or <a href="<?= e(url('/shop')) ?>">browse everything</a>.</p>
        </div>
      <?php else: ?>
        <div class="product-grid">
          <?php foreach ($products as $p): ?>
            <?php $__page('partials/product_card', ['p' => $p]); ?>
          <?php endforeach; ?>
        </div>

        <?php if ($pg['pages'] > 1): ?>
        <nav class="pager" aria-label="Pagination">
          <?php
            $baseParams = $_GET;
            $link = function(int $p) use ($baseParams) {
                $baseParams['page'] = $p;
                return url('/shop') . '?' . http_build_query($baseParams);
            };
          ?>
          <?php if ($pg['page'] > 1): ?>
            <a class="btn btn-ghost btn-sm" href="<?= e($link($pg['page'] - 1)) ?>">← Previous</a>
          <?php endif; ?>
          <span class="pager-info">Page <?= (int)$pg['page'] ?> of <?= (int)$pg['pages'] ?></span>
          <?php if ($pg['page'] < $pg['pages']): ?>
            <a class="btn btn-ghost btn-sm" href="<?= e($link($pg['page'] + 1)) ?>">Next →</a>
          <?php endif; ?>
        </nav>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
function updateQuery(key, value) {
  const url = new URL(window.location.href);
  url.searchParams.set(key, value);
  url.searchParams.delete('page');
  return url.toString();
}
</script>
