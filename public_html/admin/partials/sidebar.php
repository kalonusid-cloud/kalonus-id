<?php
$u = current_user();
$role = $u['role'] ?? '';
$canCms   = in_array($role, ['super_admin','sekretaris','ketua_harian','ketua_umum'], true);
$canAudit = in_array($role, ['super_admin','pengawas'], true);
$canUsers = $role === 'super_admin';
$active   = $active ?? '';
$nav = [
    ['id'=>'dash', 'label'=>'Dashboard',        'url'=>'/admin/index.php',         'icon'=>'fa-gauge-high',      'show'=>true],
    ['id'=>'reg',  'label'=>'Pendaftaran',      'url'=>'/admin/registrations.php', 'icon'=>'fa-inbox',           'show'=>$canCms],
    ['id'=>'btn',  'label'=>'Kelola Tombol',    'url'=>'/admin/buttons.php',       'icon'=>'fa-link',            'show'=>$canCms],
    ['id'=>'cont', 'label'=>'Konten & Section', 'url'=>'/admin/content.php',       'icon'=>'fa-pen-to-square',   'show'=>$canCms],
    ['id'=>'news', 'label'=>'Berita',           'url'=>'/admin/news.php',          'icon'=>'fa-newspaper',       'show'=>$canCms],
    ['id'=>'faq',  'label'=>'FAQ',              'url'=>'/admin/faq.php',           'icon'=>'fa-circle-question', 'show'=>$canCms],
    ['id'=>'prog', 'label'=>'Program',          'url'=>'/admin/programs.php',      'icon'=>'fa-seedling',        'show'=>$canCms],
    ['id'=>'audit','label'=>'Audit Log',        'url'=>'/admin/audit.php',         'icon'=>'fa-shield-halved',   'show'=>$canAudit],
    ['id'=>'users','label'=>'User & Role',      'url'=>'/admin/users.php',         'icon'=>'fa-users-gear',      'show'=>$canUsers],
];
?>
<aside class="admin-sidebar">
  <a class="brand" href="/admin/index.php">KYN<span class="brand-dot"></span></a>
  <nav class="admin-nav">
    <?php foreach ($nav as $n): if (!$n['show']) continue; ?>
      <a href="<?= $n['url'] ?>" class="<?= $active === $n['id'] ? 'active' : '' ?>">
        <i class="fa-solid <?= $n['icon'] ?>"></i> <?= e($n['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>
  <div class="admin-sidebar-foot">
    <a href="/" target="_blank" rel="noopener"><i class="fa-solid fa-arrow-up-right-from-square"></i> Lihat Website</a>
    <a href="/admin/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Keluar</a>
  </div>
</aside>