<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login('super_admin', 'sekretaris', 'ketua_harian', 'ketua_umum');

$page_title = 'Kelola Tombol';
$active     = 'btn';

$msg    = '';
$action = $_GET['action'] ?? 'list';
$id     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

/* ---------- DELETE ---------- */
if ($action === 'delete' && $id) {
    csrf_check();
    db()->prepare('DELETE FROM cms_buttons WHERE id=?')->execute([$id]);
    audit('DELETE_BUTTON', 'cms_button', (string)$id);
    redirect('/admin/buttons.php?msg=deleted');
}

/* ---------- SAVE (create / update) ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $data = [
        'label'      => trim($_POST['label'] ?? ''),
        'url'        => trim($_POST['url'] ?? ''),
        'target'     => in_array($_POST['target'] ?? '', ['_self','_blank'], true) ? $_POST['target'] : '_self',
        'style'      => in_array($_POST['style'] ?? '', ['dark','white','accent','ghost'], true) ? $_POST['style'] : 'dark',
        'location'   => preg_replace('/[^a-z_]/', '', $_POST['location'] ?? 'hero'),
        'icon'       => trim($_POST['icon'] ?? ''),
        'sort_order' => (int)($_POST['sort_order'] ?? 0),
        'is_active'  => isset($_POST['is_active']) ? 1 : 0,
    ];

    if ($data['label'] === '' || $data['url'] === '') {
        $msg = 'Label dan URL wajib diisi.';
    } elseif (!preg_match('#^(https?://|/|\#)#i', $data['url'])) {
        $msg = 'URL harus diawali http(s)://, / (internal), atau # (anchor).';
    } else {
        if ($id) {
            db()->prepare('UPDATE cms_buttons SET label=?,url=?,target=?,style=?,location=?,icon=?,sort_order=?,is_active=? WHERE id=?')
                ->execute([...array_values($data), $id]);
            audit('UPDATE_BUTTON', 'cms_button', (string)$id, $data);
        } else {
            db()->prepare('INSERT INTO cms_buttons (label,url,target,style,location,icon,sort_order,is_active,created_by) VALUES (?,?,?,?,?,?,?,?,?)')
                ->execute([...array_values($data), current_user()['id']]);
            audit('CREATE_BUTTON', 'cms_button', (string)db()->lastInsertId(), $data);
        }
        redirect('/admin/buttons.php?msg=saved');
    }
}

/* ---------- EDIT ---------- */
$editing = null;
if ($action === 'edit' && $id) {
    $st = db()->prepare('SELECT * FROM cms_buttons WHERE id=?');
    $st->execute([$id]);
    $editing = $st->fetch();
}

/* ---------- LIST ---------- */
$rows = db()->query('SELECT * FROM cms_buttons ORDER BY location, sort_order, id')->fetchAll();

require __DIR__ . '/partials/header.php';
?>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert-ok">Tombol <?= $_GET['msg'] === 'deleted' ? 'dihapus' : 'disimpan' ?>.</div>
<?php endif; ?>
<?php if ($msg): ?>
  <div class="alert-bad"><?= e($msg) ?></div>
<?php endif; ?>

<p class="muted" style="margin:-4px 0 4px">
  Atur tombol di hero, panel konversi, footer, atau lokasi custom. Perubahan langsung tampil di landing page.
</p>

<!-- FORM TAMBAH / EDIT -->
<section class="card">
  <h2><?= $editing ? 'Edit Tombol' : 'Tambah Tombol Baru' ?></h2>
  <form method="post" class="admin-form"
        action="/admin/buttons.php<?= $editing ? '?action=edit&id=' . $editing['id'] : '' ?>">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">

    <label>Label Tombol
      <input name="label" required maxlength="80"
             value="<?= e($editing['label'] ?? '') ?>"
             placeholder="Company Profile">
    </label>

    <label>URL / Link Tujuan
      <input name="url" required maxlength="500"
             value="<?= e($editing['url'] ?? '') ?>"
             placeholder="/company-profile.php atau https://...">
    </label>

    <label>Target
      <select name="target">
        <option value="_self"  <?= ($editing['target'] ?? '') === '_self'  ? 'selected' : '' ?>>Tab yang sama (_self)</option>
        <option value="_blank" <?= ($editing['target'] ?? '') === '_blank' ? 'selected' : '' ?>>Tab baru (_blank)</option>
      </select>
    </label>

    <label>Style
      <select name="style">
        <?php foreach (['dark','white','accent','ghost'] as $s): ?>
          <option value="<?= $s ?>" <?= ($editing['style'] ?? '') === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Lokasi Tampil
      <select name="location">
        <?php foreach (['hero','conversion','footer','custom'] as $l): ?>
          <option value="<?= $l ?>" <?= ($editing['location'] ?? '') === $l ? 'selected' : '' ?>><?= ucfirst($l) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Icon (Font Awesome class, opsional)
      <input name="icon" maxlength="60"
             value="<?= e($editing['icon'] ?? '') ?>"
             placeholder="fa-building">
    </label>

    <label>Urutan
      <input name="sort_order" type="number" value="<?= e($editing['sort_order'] ?? 0) ?>">
    </label>

    <label class="checkbox">
      <input type="checkbox" name="is_active" value="1"
             <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Aktifkan tombol
    </label>

    <div class="form-actions">
      <?php if ($editing): ?>
        <a href="/admin/buttons.php" class="btn btn-ghost">Batal</a>
      <?php endif; ?>
      <button class="btn btn-dark" type="submit"><?= $editing ? 'Simpan Perubahan' : 'Tambah Tombol' ?></button>
    </div>
  </form>
</section>

<!-- DAFTAR TOMBOL -->
<section class="card">
  <h2>Daftar Tombol (<?= count($rows) ?>)</h2>
  <table class="table">
    <thead>
      <tr>
        <th>Label</th><th>URL</th><th>Lokasi</th><th>Style</th>
        <th>Urutan</th><th>Status</th><th>Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><strong><?= e($r['label']) ?></strong></td>
          <td><code><?= e($r['url']) ?></code></td>
          <td><?= e($r['location']) ?></td>
          <td><span class="badge"><?= e($r['style']) ?></span></td>
          <td><?= (int)$r['sort_order'] ?></td>
          <td><?= $r['is_active'] ? '<span class="badge ok">aktif</span>' : '<span class="badge">nonaktif</span>' ?></td>
          <td class="actions">
            <a href="/admin/buttons.php?action=edit&id=<?= $r['id'] ?>">Edit</a>
            <form method="post"
                  action="/admin/buttons.php?action=delete&id=<?= $r['id'] ?>"
                  data-confirm="Hapus tombol ini?"
                  style="display:inline">
              <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
              <button class="link-bad" type="submit">Hapus</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?>
        <tr><td colspan="7" class="muted" style="text-align:center;padding:24px">Belum ada tombol. Tambahkan lewat form di atas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>