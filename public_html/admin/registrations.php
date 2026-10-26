<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login('super_admin','sekretaris','ketua_harian','ketua_umum');

$page_title = 'Pendaftaran';
$active     = 'reg';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    $note   = trim($_POST['note'] ?? '');
    if ($id && in_array($status, ['new','verified','rejected','converted'], true)) {
        db()->prepare('UPDATE registrations SET status=?, note=? WHERE id=?')
            ->execute([$status, $note, $id]);
        audit('UPDATE_REGISTRATION', 'registration', (string)$id, ['status'=>$status]);
    }
    redirect('/admin/registrations.php');
}

$filter = $_GET['status'] ?? 'all';
$sql = 'SELECT * FROM registrations';
$params = [];
if (in_array($filter, ['new','verified','rejected','converted'], true)) {
    $sql .= ' WHERE status=?';
    $params[] = $filter;
}
$sql .= ' ORDER BY created_at DESC LIMIT 200';
$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

require __DIR__ . '/partials/header.php';
?>
<div class="program-filter" style="margin-bottom:16px">
  <?php foreach (['all'=>'Semua','new'=>'Baru','verified'=>'Verified','rejected'=>'Ditolak','converted'=>'Converted'] as $v=>$l): ?>
    <a class="chip <?= $filter === $v ? 'active' : '' ?>" href="/admin/registrations.php?status=<?= $v ?>"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<section class="card">
  <h2>Daftar Pendaftar (<?= count($rows) ?>)</h2>
  <table class="table">
    <thead><tr><th>Nama / WA</th><th>Jenis</th><th>Program</th><th>Motivasi</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td>
          <strong><?= e($r['full_name']) ?></strong><br>
          <small class="muted"><?= e($r['phone_wa']) ?><?= $r['email'] ? ' · ' . e($r['email']) : '' ?></small>
        </td>
        <td><span class="badge"><?= e($r['type']) ?></span></td>
        <td><?= e($r['program'] ?? '—') ?></td>
        <td><small><?= e(mb_substr((string)$r['motivation'], 0, 80)) ?><?= mb_strlen((string)$r['motivation'])>80?'…':'' ?></small></td>
        <td><span class="badge <?= $r['status']==='verified'?'ok':($r['status']==='rejected'?'bad':($r['status']==='converted'?'ok':'')) ?>"><?= e($r['status']) ?></span></td>
        <td>
          <form method="post" class="admin-form" style="gap:8px">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <select name="status" style="padding:6px 10px;font-size:12px">
              <?php foreach (['new','verified','rejected','converted'] as $s): ?>
                <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= $s ?></option>
              <?php endforeach; ?>
            </select>
            <input name="note" placeholder="Catatan" style="padding:6px 10px;font-size:12px" value="<?= e($r['note'] ?? '') ?>">
            <button class="btn btn-dark btn-sm" type="submit">Simpan</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$rows): ?>
      <tr><td colspan="6" class="muted" style="text-align:center;padding:32px">Belum ada data.</td></tr>
    <?php endif; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>