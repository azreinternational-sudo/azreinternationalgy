<?php
$id = isset($params[0]) ? (int)$params[0] : 0;
$page_title = $id ? 'Edit product' : 'New product';
$p = [
    'id' => 0, 'sku' => '', 'slug' => '', 'name' => '', 'description' => '',
    'brand' => '', 'category_id' => null, 'price' => '0.00', 'compare_at' => null,
    'stock' => 0, 'image' => null, 'active' => 1, 'featured' => 0,
    'viscosity' => '', 'form' => '', 'size_label' => '',
];
$cats = categories_list();
$err = null;

if ($id) {
    $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        flash_set('error', 'Product not found.');
        redirect('/admin/products');
    }
    $p = array_merge($p, $row);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) {
        $err = 'Invalid form submission.';
    } else {
        $p['sku'] = trim($_POST['sku'] ?? '');
        $p['name'] = trim($_POST['name'] ?? '');
        $p['description'] = trim($_POST['description'] ?? '');
        $p['brand'] = trim($_POST['brand'] ?? '');
        $p['category_id'] = (int)($_POST['category_id'] ?? 0) ?: null;
        $p['price'] = (float)($_POST['price'] ?? 0);
        $p['compare_at'] = $_POST['compare_at'] !== '' ? (float)$_POST['compare_at'] : null;
        $p['stock'] = max(0, (int)($_POST['stock'] ?? 0));
        $p['active'] = !empty($_POST['active']) ? 1 : 0;
        $p['featured'] = !empty($_POST['featured']) ? 1 : 0;
        $p['viscosity'] = trim($_POST['viscosity'] ?? '');
        $p['form'] = trim($_POST['form'] ?? '');
        $p['size_label'] = trim($_POST['size_label'] ?? '');

        if ($p['sku'] === '' || $p['name'] === '') {
            $err = 'SKU and Name are required.';
        } else {
            $p['slug'] = $id ? $p['slug'] : slugify($p['brand'] . '-' . $p['name']);
            // Handle image upload
            if (!empty($_FILES['image']['name'])) {
                $file = $_FILES['image'];
                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $err = 'Image upload failed.';
                } elseif ($file['size'] > AZRE_MAX_UPLOAD_BYTES) {
                    $err = 'Image too large (max 5 MB).';
                } else {
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    if (!in_array($ext, AZRE_ALLOWED_IMAGE_EXT, true)) {
                        $err = 'Image type not allowed.';
                    } else {
                        if (!is_dir(AZRE_UPLOAD_DIR)) @mkdir(AZRE_UPLOAD_DIR, 0755, true);
                        $safe = preg_replace('/[^A-Za-z0-9_\-]/', '', pathinfo($file['name'], PATHINFO_FILENAME));
                        $fname = ($id ? 'p' . $id . '-' : '') . $safe . '-' . substr(bin2hex(random_bytes(4)), 0, 6) . '.' . $ext;
                        $dest = AZRE_UPLOAD_DIR . '/' . $fname;
                        if (move_uploaded_file($file['tmp_name'], $dest)) {
                            // delete old image
                            if ($p['image'] && file_exists(AZRE_UPLOAD_DIR . '/' . basename($p['image']))) {
                                @unlink(AZRE_UPLOAD_DIR . '/' . basename($p['image']));
                            }
                            $p['image'] = $fname;
                        } else {
                            $err = 'Failed to save image.';
                        }
                    }
                }
            }
            if (!$err) {
                if ($id) {
                    $sql = 'UPDATE products SET sku=?, slug=?, name=?, description=?, brand=?, category_id=?, price=?, compare_at=?, stock=?, image=?, active=?, featured=?, viscosity=?, form=?, size_label=? WHERE id=?';
                    db()->prepare($sql)->execute([
                        $p['sku'], $p['slug'], $p['name'], $p['description'], $p['brand'],
                        $p['category_id'], $p['price'], $p['compare_at'], $p['stock'], $p['image'],
                        $p['active'], $p['featured'], $p['viscosity'], $p['form'], $p['size_label'], $id,
                    ]);
                    flash_set('success', 'Product updated.');
                } else {
                    if (empty($p['slug'])) $p['slug'] = slugify($p['brand'] . '-' . $p['name']);
                    // Ensure slug unique
                    $base = $p['slug']; $i = 1;
                    while (db()->prepare('SELECT id FROM products WHERE slug = ?')->execute([$p['slug']]) && db()->prepare('SELECT id FROM products WHERE slug = ?')->fetch()) {
                        $p['slug'] = $base . '-' . (++$i);
                    }
                    $sql = 'INSERT INTO products (sku, slug, name, description, brand, category_id, price, compare_at, stock, image, active, featured, viscosity, form, size_label) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
                    db()->prepare($sql)->execute([
                        $p['sku'], $p['slug'], $p['name'], $p['description'], $p['brand'],
                        $p['category_id'], $p['price'], $p['compare_at'], $p['stock'], $p['image'],
                        $p['active'], $p['featured'], $p['viscosity'], $p['form'], $p['size_label'],
                    ]);
                    flash_set('success', 'Product created.');
                }
                redirect('/admin/products');
            }
        }
    }
}
?>
<section class="container section">
  <header class="page-head">
    <nav class="crumbs"><a href="<?= e(url('/admin/products')) ?>">Products</a> · <span><?= e($page_title) ?></span></nav>
    <h1><?= e($page_title) ?></h1>
  </header>
  <?php if ($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post" action="<?= e($id ? url('/admin/product/' . $id . '/edit') : url('/admin/product/new')) ?>" enctype="multipart/form-data" class="admin-form">
    <?= csrf_field() ?>
    <div class="form-grid">
      <label>SKU<input type="text" name="sku" required value="<?= e($p['sku']) ?>"></label>
      <label>Brand<input type="text" name="brand" value="<?= e($p['brand']) ?>"></label>
      <label class="span-2">Name<input type="text" name="name" required value="<?= e($p['name']) ?>"></label>
      <label class="span-2">Description<textarea name="description" rows="4"><?= e($p['description']) ?></textarea></label>
      <label>Category
        <select name="category_id">
          <option value="">— Uncategorized —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$p['category_id'] === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Form
        <select name="form">
          <option value="">—</option>
          <?php foreach (['Full Synthetic','Synthetic Blend','Conventional'] as $opt): ?>
            <option value="<?= e($opt) ?>" <?= $p['form'] === $opt ? 'selected' : '' ?>><?= e($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <label>Viscosity<input type="text" name="viscosity" value="<?= e($p['viscosity']) ?>" placeholder="e.g. 5W-30"></label>
      <label>Size / package<input type="text" name="size_label" value="<?= e($p['size_label']) ?>" placeholder="e.g. 12/1 Quart Case"></label>
      <label>Price (GYD)<input type="number" step="0.01" name="price" required value="<?= e((string)$p['price']) ?>"></label>
      <label>Compare-at (optional)<input type="number" step="0.01" name="compare_at" value="<?= e((string)$p['compare_at']) ?>"></label>
      <label>Stock<input type="number" name="stock" min="0" value="<?= (int)$p['stock'] ?>"></label>
      <label class="span-2">Image
        <?php if ($p['image']): ?>
          <img class="thumb" src="<?= e(product_image($p['image'] ?? null, $p)) ?>" alt="">
          <small class="muted">Upload a new image to replace.</small>
        <?php endif; ?>
        <input type="file" name="image" accept="image/*">
      </label>
      <label class="checkbox"><input type="checkbox" name="active" <?= $p['active'] ? 'checked' : '' ?>> Active (visible on store)</label>
      <label class="checkbox"><input type="checkbox" name="featured" <?= $p['featured'] ? 'checked' : '' ?>> Featured on home page</label>
    </div>
    <div class="form-actions">
      <button class="btn btn-primary btn-lg" type="submit"><?= $id ? 'Save changes' : 'Create product' ?></button>
      <a class="btn btn-ghost" href="<?= e(url('/admin/products')) ?>">Cancel</a>
    </div>
  </form>
</section>
