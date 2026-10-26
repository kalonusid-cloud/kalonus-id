<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login('super_admin','sekretaris','ketua_harian','ketua_umum');

$page_title = 'Program';
$active     = 'prog';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['_op'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM programs WHERE id=?')->execute([(int)$_POST['id']]);
        audit('DELETE_PROGRAM','program',(string)$_POST['id']);
    } else {
        $id     = (int)($_POST['id'] ?? 0);
        $name   = trim($_POST['name'] ?? '');
        $cat    = in_array($_POST['category'] ?? '', ['pendidikan','budaya','ekonomi','digital','sosial'], true) ? $_POST['category'] : 'sosial';
        $desc   = trim($_POST['description'] ?? '');
        $feat   = isset($_POST['is_featured']) ? 1 : 0;
        $sort   = (int)($_POST['sort_order'] ?? 0);
        $on     = isset($_POST['is_active']) ? 1 : 0;
        $img    = null;
        if (!empty($_FILES['image']['tmp_name'])) {
            $img = upload_image($_FILES['image'], 'programs');
        }
        if ($name !== '') {
            if ($id) {
                $sql = 'UPDATE programs SET name=?,category=?,description=?,is_featured=?,sort_order=?,is_active=?';
                $params = [$name,$cat,$desc,$feat,$sort,$on];
                if ($img) { $sql .= ',image_path=?'; $params[] = $img; }
                $sql .= ' WHERE id=?'; $params[] = $id;
                db()->prepare($sql)->execute($params);
                audit('UPDATE_PROGRAM','program',(string)$id);
            } else {
                db()->prepare('INSERT INTO programs (name,category,description,image_path,is_featured,sort_order,is_active) VALUES (?,?,?,?,?,?,?)')
                    ->execute([$name,$cat,$desc,$img,$feat,$sort,$on]);
                audit('CREATE_PROGRAM','program',(string)db()->lastInsertId());
            }
        }
    }
    redirect('/admin/programs.php?saved=1');
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $st = db()->prepare('SELECT * FROM programs WHERE id=?');
    $st->execute([$editId]);
    $editing = $st->fetch();
}
$rows = db()->query('SELECT * FROM programs ORDER BY sort_order, id')->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<?php if (isset($_GET['saved'])): ?><div class="alert-ok">Program tersimpan.</div><?php endif; ?>

<section class="card">
  <h2><?= $editing ? 'Edit Program' : 'Tambah Program' ?></h2>
  <form method="post" enctype="multipart/form-data" class="admin-form">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <label>Nama Program <input name="name" required maxlength="160" value="<?= e($editing['name'] ?? '') ?>"></label>
    <label>Kategori
      <select name="category">
        <?php foreach (['pendidikan','budaya','ekonomi','digital','sosial'] as $c): ?>
          <option value="<?= $c ?>" <?= ($editing['category'] ?? '') === $c ? 'selected' : '' ?>><?= ucfirst($c) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Deskripsi <textarea name="description" rows="4"><?= e($editing['description'] ?? '') ?></textarea></label>
    <label>Gambar
      <?php if (!empty($editing['image_path'])): ?>
        <img src="<?= e($editing['image_path']) ?>" alt="" style="max-width:240px;border-radius:8px;margin:8px 0">
      <?php endif; ?>
      <input type="file" name="image" accept="image/*">
    </label>
    <label>Urutan <input type="number" name="sort_order" value="<?= (int)($editing['sort_order'] ?? 0) ?>"></label>
    <label class="checkbox"><input type="checkbox" name="is_featured" value="1" <?= ($editing['is_featured'] ?? 0) ? 'checked' : '' ?>> Tandai unggulan</label>
    <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif</label>
    <div class="form-actions">
      <?php if ($editing): ?><a href="/admin/programs.php" class="btn btn-ghost">Batal</a><?php endif; ?>
      <button class="btn btn-dark" type="submit">Simpan</button>
    </div>
  </form>
</section>

<section class="card">
  <h2>Daftar Program (<?= count($rows) ?>)</h2>
  <table class="table">
    <thead><tr><th>Nama</th><th>Kategori</th><th>Unggulan</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['name']) ?></strong></td>
        <td><span class="badge"><?= e($r['category']) ?></span></td>
        <td><?= $r['is_featured'] ? '<span class="badge ok">ya</span>' : '—' ?></td>
        <td><?= $r['is_active'] ? '<span class="badge ok">aktif</span>' : '<span class="badge">nonaktif</span>' ?></td>
        <td class="actions">
          <a href="/admin/programs.php?edit=<?= $r['id'] ?>">Edit</a>
          <form method="post" data-confirm="Hapus program ini?" style="display:inline">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="_op" value="delete">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="link-bad" type="submit">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>