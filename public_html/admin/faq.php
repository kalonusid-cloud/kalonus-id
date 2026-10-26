<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login('super_admin','sekretaris','ketua_harian','ketua_umum');

$page_title = 'FAQ';
$active     = 'faq';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (($_POST['_op'] ?? '') === 'delete') {
        db()->prepare('DELETE FROM faq WHERE id=?')->execute([(int)$_POST['id']]);
        audit('DELETE_FAQ','faq',(string)$_POST['id']);
    } else {
        $id   = (int)($_POST['id'] ?? 0);
        $q    = trim($_POST['question'] ?? '');
        $a    = trim($_POST['answer'] ?? '');
        $sort = (int)($_POST['sort_order'] ?? 0);
        $on   = isset($_POST['is_active']) ? 1 : 0;
        if ($q && $a) {
            if ($id) {
                db()->prepare('UPDATE faq SET question=?,answer=?,sort_order=?,is_active=? WHERE id=?')
                    ->execute([$q,$a,$sort,$on,$id]);
                audit('UPDATE_FAQ','faq',(string)$id);
            } else {
                db()->prepare('INSERT INTO faq (question,answer,sort_order,is_active) VALUES (?,?,?,?)')
                    ->execute([$q,$a,$sort,$on]);
                audit('CREATE_FAQ','faq',(string)db()->lastInsertId());
            }
        }
    }
    redirect('/admin/faq.php?saved=1');
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $st = db()->prepare('SELECT * FROM faq WHERE id=?');
    $st->execute([$editId]);
    $editing = $st->fetch();
}
$rows = db()->query('SELECT * FROM faq ORDER BY sort_order, id')->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<?php if (isset($_GET['saved'])): ?><div class="alert-ok">FAQ tersimpan.</div><?php endif; ?>

<section class="card">
  <h2><?= $editing ? 'Edit FAQ' : 'Tambah FAQ' ?></h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <label>Pertanyaan <input name="question" required maxlength="500" value="<?= e($editing['question'] ?? '') ?>"></label>
    <label>Jawaban <textarea name="answer" rows="4" required><?= e($editing['answer'] ?? '') ?></textarea></label>
    <label>Urutan <input type="number" name="sort_order" value="<?= (int)($editing['sort_order'] ?? 0) ?>"></label>
    <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif</label>
    <div class="form-actions">
      <?php if ($editing): ?><a href="/admin/faq.php" class="btn btn-ghost">Batal</a><?php endif; ?>
      <button class="btn btn-dark" type="submit">Simpan</button>
    </div>
  </form>
</section>

<section class="card">
  <h2>Daftar FAQ (<?= count($rows) ?>)</h2>
  <table class="table">
    <thead><tr><th>#</th><th>Pertanyaan</th><th>Urutan</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><?= e($r['question']) ?></td>
        <td><?= (int)$r['sort_order'] ?></td>
        <td><?= $r['is_active'] ? '<span class="badge ok">aktif</span>' : '<span class="badge">nonaktif</span>' ?></td>
        <td class="actions">
          <a href="/admin/faq.php?edit=<?= $r['id'] ?>">Edit</a>
          <form method="post" data-confirm="Hapus FAQ ini?" style="display:inline">
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