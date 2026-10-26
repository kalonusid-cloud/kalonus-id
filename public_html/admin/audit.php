<?php
require_once __DIR__ . '/../inc/auth.php';
require_login('super_admin','pengawas');

$page_title = 'Audit Log';
$active     = 'audit';

$q = trim($_GET['q'] ?? '');
$sql = 'SELECT a.*, u.name AS user_name FROM audit_log a LEFT JOIN users u ON u.id=a.user_id';
$params = [];
if ($q !== '') {
    $sql .= ' WHERE a.action LIKE ? OR a.object_type LIKE ? OR u.name LIKE ?';
    $like = "%$q%";
    $params = [$like, $like, $like];
}
$sql .= ' ORDER BY a.created_at DESC LIMIT 300';
$st = db()->prepare($sql);
$st->execute($params);
$rows = $st->fetchAll();

require __DIR__ . '/partials/header.php';
?>
<form method="get" class="admin-form" style="flex-direction:row;align-items:center;margin-bottom:16px">
  <input name="q" placeholder="Cari aksi, objek, atau user…" value="<?= e($q) ?>" style="max-width:320px">
  <button class="btn btn-dark btn-sm" type="submit">Cari</button>
</form>

<section class="card">
  <h2>Audit Log (<?= count($rows) ?>)</h2>
  <table class="table">
    <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Objek</th><th>IP</th><th>Detail</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $a): ?>
      <tr>
        <td><small><?= e(date('d M Y H:i:s', strtotime($a['created_at']))) ?></small></td>
        <td><?= e($a['user_name'] ?? '-') ?></td>
        <td><code><?= e($a['action']) ?></code></td>
        <td><?= e($a['object_type']) ?><?= $a['object_id'] ? ' #' . e($a['object_id']) : '' ?></td>
        <td><small class="muted"><?= e($a['ip_address'] ?? '-') ?></small></td>
        <td><small class="muted"><?= e(mb_substr((string)$a['details'], 0, 60)) ?></small></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>