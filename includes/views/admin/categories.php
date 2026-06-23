<?php
$page_title = 'Categories';
$err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_check()) { $err = 'Invalid form submission.'; }
    else {
        $action = $_POST['action'] ?? '';
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            if ($name === '') {
                $err = 'Name required.';
            } else {
                $slug = slugify($name);
                $base = $slug; $i = 1;
                while (db()->prepare('SELECT id FROM categories WHERE slug = ?')->execute([$slug]) && db()->prepare('SELECT id FROM categories WHERE slug = ?')->fetch()) {
                    $slug = $base . '-' . (++$i);
                }
                $sort = (int)($_POST['sort_order'] ?? 0);
                db()->prepare('INSERT INTO categories (name, slug, sort_order) VALUES (?, ?, ?)')->execute([$name, $slug, $sort]);
                flash_set('success', 'Category added.');
                redirect('/admin/categories');
            }
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $sort = (int)($_POST['sort_order'] ?? 0);
            if ($id && $name !== '') {
                db()->prepare('UPDATE categories SET name=?, sort_order=? WHERE id=?')->execute([$name, $sort, $id]);
                flash_set('success', 'Category updated.');
                redirect('/admin/categories');
            }
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $cnt = (int)db()->query('SELECT COUNT(*) FROM products WHERE category_id = ' . (int)$id)->fetchColumn();
                if ($cnt > 0) {
                    flash_set('error', "Can't delete — {$cnt} product(s) are using this category.");
                } else {
                    db()->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
                    flash_set('success', 'Category deleted.');
                }
                redirect('/admin/categories');
            }
        }
    }
}

$cats = db()->query('SELECT c.*, COUNT(p.id) AS cnt FROM categories c LEFT JOIN products p ON p.category_id = c.id GROUP BY c.id ORDER BY c.sort_order, c.name')->fetchAll();
?>
<section class="container section">
  <header class="page-head"><h1>Categories</h1></header>
  <?php if ($err): ?><div class="flash flash-error"><?= e($err) ?></div><?php endif; ?>

  <div class="admin-grid">
    <div class="card">
      <h3>Add category</h3>
      <form method="post" action="<?= e(url('/admin/categories')) ?>" class="admin-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="create">
        <label>Name<input type="text" name="name" required></label>
        <label>Sort order<input type="number" name="sort_order" value="0"></label>
        <button class="btn btn-primary" type="submit">Add</button>
      </form>
    </div>
    <div class="card">
      <h3>Existing</h3>
      <table class="table">
        <thead><tr><th>Name</th><th>Slug</th><th>Order</th><th>#</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($cats as $c): ?>
          <tr>
            <form method="post" action="<?= e(url('/admin/categories')) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="update">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <td><input type="text" name="name" value="<?= e($c['name']) ?>" required></td>
              <td><code><?= e($c['slug']) ?></code></td>
              <td><input type="number" name="sort_order" value="<?= (int)$c['sort_order'] ?>" style="width:5em"></td>
              <td><?= (int)$c['cnt'] ?></td>
              <td class="row-actions">
                <button class="btn btn-ghost btn-sm" type="submit">Save</button>
            </form>
                <form method="post" action="<?= e(url('/admin/categories')) ?>" style="display:inline" onsubmit="return confirm('Delete this category?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn btn-danger btn-sm" type="submit">Delete</button>
                </form>
              </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>
