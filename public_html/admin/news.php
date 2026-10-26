<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login('super_admin','sekretaris','ketua_harian','ketua_umum');

$page_title = 'Berita';
$active     = 'news';
$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);
$err    = '';

if ($action === 'delete' && $id) {
    csrf_check();
    db()->prepare('DELETE FROM news WHERE id=?')->execute([$id]);
    audit('DELETE_NEWS', 'news', (string)$id);
    redirect('/admin/news.php?msg=deleted');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title   = trim($_POST['title'] ?? '');
    $cat     = trim($_POST['category'] ?? 'umum');
    $excerpt = trim($_POST['excerpt'] ?? '');
    $body    = trim($_POST['body'] ?? '');
    $status  = in_array($_POST['status'] ?? '', ['draft','review','published'], true) ? $_POST['status'] : 'draft';
    $pubAt   = trim($_POST['published_at'] ?? '');
    $pubAt   = $pubAt ? date('Y-m-d H:i:s', strtotime($pubAt)) : null;
    if ($status === 'published' && !$pubAt) $pubAt = date('Y-m-d H:i:s');

    if ($title === '' || $body === '') {
        $err = 'Judul dan isi wajib diisi.';
    } else {
        $slug  = unique_slug($title, $id);
        $thumb = null;
        if (!empty($_FILES['thumbnail']['tmp_name'])) {
            $thumb = upload_image($_FILES['thumbnail'], 'news');
        }
        if ($id) {
            $sql = 'UPDATE news SET title=?,slug=?,category=?,excerpt=?,body=?,status=?,published_at=?';
            $params = [$title,$slug,$cat,$excerpt,$body,$status,$pubAt];
            if ($thumb) { $sql .= ',thumbnail=?'; $params[] = $thumb; }
            $sql .= ' WHERE id=?'; $params[] = $id;
            db()->prepare($sql)->execute($params);
            audit('UPDATE_NEWS','news',(string)$id);
        } else {
            db()->prepare(
                'INSERT INTO news (title,slug,category,excerpt,body,thumbnail,status,published_at,author_id)
                 VALUES (?,?,?,?,?,?,?,?,?)'
            )->execute([$title,$slug,$cat,$excerpt,$body,$thumb,$status,$pubAt,current_user()['id']]);
            audit('CREATE_NEWS','news',(string)db()->lastInsertId());
        }
        redirect('/admin/news.php?msg=saved');
    }
}

$editing = null;
if ($action === 'edit' && $id) {
    $st = db()->prepare('SELECT * FROM news WHERE id=?');
    $st->execute([$id]);
    $editing = $st->fetch();
}
$rows = db()->query('SELECT * FROM news ORDER BY created_at DESC')->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<?php if (isset($_GET['msg'])): ?>
  <div class="alert-ok">Berita <?= $_GET['msg']==='deleted' ? 'dihapus' : 'disimpan' ?>.</div>
<?php endif; ?>
<?php if ($err): ?><div class="alert-bad"><?= e($err) ?></div><?php endif; ?>

<?php if ($editing || $action === 'new'): ?>
<section class="card">
  <h2><?= $editing ? 'Edit Berita' : 'Tambah Berita' ?></h2>
  <form method="post" enctype="multipart/form-data" class="admin-form"
        action="/admin/news.php<?= $editing ? '?action=edit&id='.$editing['id'] : '?action=new' ?>">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <label>Judul <input name="title" required maxlength="255" value="<?= e($editing['title'] ?? '') ?>"></label>
    <label>Kategori <input name="category" maxlength="60" value="<?= e($editing['category'] ?? 'umum') ?>"></label>
    <label>Excerpt <textarea name="excerpt" rows="2" maxlength="500"><?= e($editing['excerpt'] ?? '') ?></textarea></label>
    <label>Isi Berita <textarea name="body" rows="12" required><?= e($editing['body'] ?? '') ?></textarea></label>
    <label>Thumbnail
      <?php if (!empty($editing['thumbnail'])): ?>
        <img src="<?= e($editing['thumbnail']) ?>" alt="" style="max-width:200px;border-radius:8px;margin:8px 0">
      <?php endif; ?>
      <input type="file" name="thumbnail" accept="image/*">
    </label>
    <label>Status
      <select name="status">
        <?php foreach (['draft','review','published'] as $s): ?>
          <option value="<?= $s ?>" <?= ($editing['status'] ?? 'draft') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Jadwal Publikasi (opsional)
      <input type="datetime-local" name="published_at"
             value="<?= !empty($editing['published_at']) ? date('Y-m-d\TH:i', strtotime($editing['published_at'])) : '' ?>">
    </label>
    <div class="form-actions">
      <a href="/admin/news.php" class="btn btn-ghost">Batal</a>
      <button class="btn btn-dark" type="submit">Simpan</button>
    </div>
  </form>
</section>
<?php endif; ?>

<section class="card">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
    <h2 style="margin:0">Daftar Berita (<?= count($rows) ?>)</h2>
    <a href="/admin/news.php?action=new" class="btn btn-accent btn-sm"><i class="fa-solid fa-plus"></i> Berita Baru</a>
  </div>
  <table class="table">
    <thead><tr><th>Judul</th><th>Kategori</th><th>Status</th><th>Tanggal</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><strong><?= e($r['title']) ?></strong><br><code class="muted">/berita/<?= e($r['slug']) ?></code></td>
        <td><?= e($r['category']) ?></td>
        <td><span class="badge <?= $r['status']==='published'?'ok':($r['status']==='review'?'warn':'') ?>"><?= e($r['status']) ?></span></td>
        <td><?= e(date('d M Y', strtotime($r['created_at']))) ?></td>
        <td class="actions">
          <a href="/admin/news.php?action=edit&id=<?= $r['id'] ?>">Edit</a>
          <form method="post" action="/admin/news.php?action=delete&id=<?= $r['id'] ?>" data-confirm="Hapus berita ini?" style="display:inline">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <button class="link-bad" type="submit">Hapus</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>