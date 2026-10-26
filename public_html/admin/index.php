<?php
require_once __DIR__ . '/inc/helpers.php';
require_once __DIR__ . '/inc/db.php';
?>
...
<div class="hero-actions">
  <?php foreach (buttons('hero') as $b): ?>
    <a href="<?= e($b['url']) ?>"
       target="<?= e($b['target']) ?>"
       rel="<?= $b['target']==='_blank' ? 'noopener' : '' ?>"
       class="btn btn-<?= e($b['style']) ?>">
      <?php if ($b['icon']): ?><i class="fa-solid <?= e($b['icon']) ?>"></i><?php endif; ?>
      <?= e($b['label']) ?>
    </a>
  <?php endforeach; ?>
</div>

<?php
require_once __DIR__ . '/../inc/auth.php';
require_login();

$page_title = 'Dashboard';
$active     = 'dash';

$counts = [
    'news'     => (int)db()->query('SELECT COUNT(*) FROM news')->fetchColumn(),
    'programs' => (int)db()->query('SELECT COUNT(*) FROM programs WHERE is_active=1')->fetchColumn(),
    'reg_new'  => (int)db()->query("SELECT COUNT(*) FROM registrations WHERE status='new'")->fetchColumn(),
    'faq'      => (int)db()->query('SELECT COUNT(*) FROM faq WHERE is_active=1')->fetchColumn(),
    'buttons'  => (int)db()->query('SELECT COUNT(*) FROM cms_buttons WHERE is_active=1')->fetchColumn(),
];
$recentReg   = db()->query('SELECT * FROM registrations ORDER BY created_at DESC LIMIT 5')->fetchAll();
$recentAudit = db()->query(
    'SELECT a.*, u.name AS user_name FROM audit_log a
     LEFT JOIN users u ON u.id=a.user_id
     ORDER BY a.created_at DESC LIMIT 10'
)->fetchAll();

require __DIR__ . '/partials/header.php';
?>
<div class="stats-grid">
  <a href="/admin/news.php" class="stat-card">
    <div class="stat-icon"><i class="fa-solid fa-newspaper"></i></div>
    <div class="stat-value"><?= $counts['news'] ?></div>
    <div class="stat-label">Berita</div>
  </a>
  <a href="/admin/programs.php" class="stat-card">
    <div class="stat-icon"><i class="fa-solid fa-seedling"></i></div>
    <div class="stat-value"><?= $counts['programs'] ?></div>
    <div class="stat-label">Program Aktif</div>
  </a>
  <a href="/admin/registrations.php" class="stat-card">
    <div class="stat-icon"><i class="fa-solid fa-inbox"></i></div>
    <div class="stat-value"><?= $counts['reg_new'] ?></div>
    <div class="stat-label">Pendaftaran Baru</div>
  </a>
  <a href="/admin/faq.php" class="stat-card">
    <div class="stat-icon"><i class="fa-solid fa-circle-question"></i></div>
    <div class="stat-value"><?= $counts['faq'] ?></div>
    <div class="stat-label">FAQ</div>
  </a>
  <a href="/admin/buttons.php" class="stat-card">
    <div class="stat-icon"><i class="fa-solid fa-link"></i></div>
    <div class="stat-value"><?= $counts['buttons'] ?></div>
    <div class="stat-label">Tombol Aktif</div>
  </a>
</div>

<section class="card">
  <h2>Pendaftaran Terbaru</h2>
  <?php if (!$recentReg): ?><p class="muted">Belum ada pendaftaran.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>Nama</th><th>WA</th><th>Jenis</th><th>Status</th><th>Tanggal</th></tr></thead>
    <tbody>
    <?php foreach ($recentReg as $r): ?>
      <tr>
        <td><?= e($r['full_name']) ?></td>
        <td><?= e($r['phone_wa']) ?></td>
        <td><span class="badge"><?= e($r['type']) ?></span></td>
        <td><span class="badge <?= $r['status']==='verified'?'ok':($r['status']==='rejected'?'bad':'') ?>"><?= e($r['status']) ?></span></td>
        <td><?= e(date('d M Y H:i', strtotime($r['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<section class="card">
  <h2>Aktivitas Terbaru</h2>
  <?php if (!$recentAudit): ?><p class="muted">Belum ada aktivitas.</p><?php else: ?>
  <table class="table">
    <thead><tr><th>Waktu</th><th>User</th><th>Aksi</th><th>Objek</th></tr></thead>
    <tbody>
    <?php foreach ($recentAudit as $a): ?>
      <tr>
        <td><?= e(date('d M H:i', strtotime($a['created_at']))) ?></td>
        <td><?= e($a['user_name'] ?? '-') ?></td>
        <td><code><?= e($a['action']) ?></code></td>
        <td><?= e($a['object_type']) ?><?= $a['object_id'] ? ' #' . e($a['object_id']) : '' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/footer.php'; ?>