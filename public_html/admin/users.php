<?php
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../inc/csrf.php';
require_login('super_admin');

$page_title = 'User & Role';
$active     = 'users';
$roles = ['super_admin','pembina','pengawas','ketua_umum','ketua_harian','sekretaris','bendahara','donatur'];
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $op = $_POST['_op'] ?? 'save';
    $id = (int)($_POST['id'] ?? 0);

    if ($op === 'delete' && $id && $id !== current_user()['id']) {
        db()->prepare('DELETE FROM users WHERE id=?')->execute([$id]);
        audit('DELETE_USER', 'user', (string)$id);
        redirect('/admin/users.php?saved=1');
    }

    if ($op === 'save') {
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = in_array($_POST['role'] ?? '', $roles, true) ? $_POST['role'] : 'sekretaris';
        $pass  = $_POST['password'] ?? '';
        $on    = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $err = 'Nama dan email valid wajib diisi.';
        } else {
            if ($id) {
                $sql = 'UPDATE users SET name=?, email=?, role=?, is_active=?';
                $params = [$name,$email,$role,$on];
                if ($pass !== '') {
                    $sql .= ', password_hash=?';
                    $params[] = password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]);
                }
                $sql .= ' WHERE id=?'; $params[] = $id;
                db()->prepare($sql)->execute($params);
                audit('UPDATE_USER', 'user', (string)$id, ['role'=>$role]);
            } else {
                if ($pass === '') { $err = 'Password wajib untuk user baru.'; }
                else {
                    db()->prepare('INSERT INTO users (name,email,password_hash,role,is_active) VALUES (?,?,?,?,?)')
                        ->execute([$name,$email,password_hash($pass, PASSWORD_BCRYPT, ['cost'=>12]),$role,$on]);
                    audit('CREATE_USER', 'user', (string)db()->lastInsertId());
                }
            }
            if (!$err) redirect('/admin/users.php?saved=1');
        }
    }
}

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $st = db()->prepare('SELECT * FROM users WHERE id=?');
    $st->execute([$editId]);
    $editing = $st->fetch();
}
$rows = db()->query('SELECT * FROM users ORDER BY id')->fetchAll();
require __DIR__ . '/partials/header.php';
?>
<?php if (isset($_GET['saved'])): ?><div class="alert-ok">User tersimpan.</div><?php endif; ?>
<?php if ($err): ?><div class="alert-bad"><?= e($err) ?></div><?php endif; ?>

<section class="card">
  <h2><?= $editing ? 'Edit User' : 'Tambah User Baru' ?></h2>
  <form method="post" class="admin-form">
    <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)($editing['id'] ?? 0) ?>">
    <label>Nama <input name="name" required maxlength="120" value="<?= e($editing['name'] ?? '') ?>"></label>
    <label>Email <input name="email" type="email" required maxlength="160" value="<?= e($editing['email'] ?? '') ?>"></label>
    <label>Role
      <select name="role">
        <?php foreach ($roles as $r): ?>
          <option value="<?= $r ?>" <?= ($editing['role'] ?? '') === $r ? 'selected' : '' ?>><?= e($r) ?></option>
        <?php endforeach; ?>
      </select>
    </label>
    <label>Password <?= $editing ? '(kosongkan jika tidak diubah)' : '' ?>
      <input name="password" type="password" minlength="8">
    </label>
    <label class="checkbox"><input type="checkbox" name="is_active" value="1" <?= ($editing['is_active'] ?? 1) ? 'checked' : '' ?>> Aktif</label>
    <div class="form-actions">
      <?php if ($editing): ?><a href="/admin/users.php" class="btn btn-ghost">Batal</a><?php endif; ?>
      <button class="btn btn-dark" type="submit">Simpan</button>
    </div>
  </form>
</section>

<section class="card">
  <h2>Daftar User (<?= count($rows) ?>)</h2>
  <table class="table">
    <thead><tr><th>#</th><th>Nama</th><th>Email</th><th>Role</th><th>Status</th><th>Aksi</th></tr></thead>
    <tbody>
    <?php foreach ($rows as $r): ?>
      <tr>
        <td><?= (int)$r['id'] ?></td>
        <td><strong><?= e($r['name']) ?></strong></td>
        <td><?= e($r['email']) ?></td>
        <td><span class="badge"><?= e($r['role']) ?></span></td>
        <td><?= $r['is_active'] ? '<span class="badge ok">aktif</span>' : '<span class="badge">nonaktif</span>' ?></td>
        <td class="actions">
          <a href="/admin/users.php?edit=<?= $r['id'] ?>">Edit</a>
          <?php if ($r['id'] != current_user()['id']): ?>
          <form method="post" data-confirm="Hapus user ini?" style="display:inline">
            <input type="hidden" name="_csrf" value="<?= csrf_token() ?>">
            <input type="hidden" name="_op" value="delete">
            <input type="hidden" name="id" value="<?= $r['id'] ?>">
            <button class="link-bad" type="submit">Hapus</button>
          </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>