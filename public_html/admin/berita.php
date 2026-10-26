<?php
require_once __DIR__ . '/inc/db.php';
require_once __DIR__ . '/inc/helpers.php';

$rows = db()->query(
    "SELECT * FROM news WHERE status='published' AND (published_at IS NULL OR published_at <= NOW())
     ORDER BY published_at DESC, id DESC LIMIT 50"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="id"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Berita — <?= e(setting('site_title')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
</head><body>
<header class="site-nav">
  <a class="brand" href="/">KYN<span class="brand-dot"></span></a>
  <a href="/" class="btn btn-ghost btn-sm"><i class="fa-solid fa-arrow-left"></i> Beranda</a>
</header>

<section class="section">
  <div class="container">
    <span class="kicker">Berita & Pengumuman</span>
    <h1 class="display">Kabar dari KYN</h1>

    <div class="program-grid" style="margin-top:32px">
      <?php if (!$rows): ?>
        <p class="muted">Belum ada berita.</p>
      <?php endif; ?>
      <?php foreach ($rows as $n): ?>
        <a href="/berita/<?= e($n['slug']) ?>" class="program-card" style="text-decoration:none;color:inherit">
          <?php if ($n['thumbnail']): ?>
            <div class="program-media" style="background-image:url('<?= e($n['thumbnail']) ?>')"></div>
          <?php else: ?>
            <div class="program-media program-media-fallback"><i class="fa-solid fa-newspaper"></i></div>
          <?php endif; ?>
          <div class="program-body">
            <span class="badge"><?= e($n['category']) ?></span>
            <small class="muted"><?= e(date('d M Y', strtotime($n['published_at'] ?? $n['created_at']))) ?></small>
            <h3><?= e($n['title']) ?></h3>
            <p class="muted"><?= e(mb_substr((string)$n['excerpt'], 0, 130)) ?><?= mb_strlen((string)$n['excerpt'])>130?'…':'' ?></p>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<footer class="site-footer">
  <div class="footer-bottom"><?= e(setting('footer_copyright','© 2024 Yayasan Kalofian Yusi Nusantara')) ?></div>
</footer>
</body></html>